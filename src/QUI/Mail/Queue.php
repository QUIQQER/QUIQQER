<?php

/**
 * This file contains the \QUI\Mail\Queue
 */

namespace QUI\Mail;

use Exception;
use QUI;
use QUI\Utils\System\File;

use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_dir;
use function json_decode;
use function json_encode;
use function preg_replace;
use function str_replace;
use function time;

/**
 * Mail queue
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Queue
{
    const STATUS_ADDED = 0;

    const STATUS_SENT = 1;

    const STATUS_SENDING = 2;

    const STATUS_ERROR = 3;

    /**
     * Execute the db mail queue setup
     *
     * @throws QUI\Database\Exception
     * @throws QUI\Exception
     */
    public static function setup(): void
    {
        $Table = QUI::getDataBase()->table();

        $Table->addColumn(self::table(), [
            'id' => 'int(11) NOT NULL',
            'subject' => 'varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL',
            'body' => 'LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL',
            'text' => 'LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL',
            'from' => 'TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL',
            'fromName' => 'TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL',
            'ishtml' => 'int(1)',
            'mailto' => 'TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL',
            'replyto' => 'TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL',
            'cc' => 'TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL',
            'bcc' => 'TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL',
            'attachements' => 'TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL',
            'status' => 'int(1) NOT NULL DEFAULT 0',
            'lastsend' => 'int(11)',
            'retry' => 'int(3) NOT NULL DEFAULT 0'
        ]);

        $Table->setPrimaryKey(self::table(), 'id');
        $Table->setAutoIncrement(self::table(), 'id');
    }

    public static function table(): string
    {
        return QUI::getDBTableName('mailqueue');
    }

    /**
     * Add a mail to the mail queue
     *
     * @param Mailer|QUI\Mail $Mail
     *
     * @return integer - Mailqueue-ID
     *
     * @throws QUI\Database\Exception
     * @throws QUI\Exception
     */
    public static function addToQueue(QUI\Mail|Mailer $Mail): int
    {
        $params = $Mail->toArray();

        $params['mailto'] = json_encode($params['mailto']);
        $params['replyto'] = json_encode($params['replyto']);
        $params['cc'] = json_encode($params['cc']);
        $params['bcc'] = json_encode($params['bcc']);
        $params['status'] = self::STATUS_ADDED;

        $attachments = [];

        if (isset($params['attachements'])) {
            $attachments = $params['attachements'];
            unset($params['attachements']);
        }

        QUI::getDataBase()->insert(self::table(), $params);

        $newMailId = (int)QUI::getDataBase()->getPDO()->lastInsertId('id');

        // attachments
        $attachmentFiles = [];

        if (is_array($attachments) && !empty($attachments)) {
            $mailQueueDir = self::getAttachmentDir($newMailId);

            File::mkdir($mailQueueDir);

            foreach ($attachments as $attachment) {
                if (!file_exists($attachment)) {
                    continue;
                }

                $infos = File::getInfo($attachment);

                File::copy($attachment, $mailQueueDir . $infos['basename']);

                $attachmentFiles[] = $infos['basename'];
            }

            if (!empty($attachmentFiles)) {
                QUI::getDataBase()->update(
                    self::table(),
                    ['attachements' => json_encode($attachmentFiles)],
                    ['id' => $newMailId]
                );
            }
        }

        return $newMailId;
    }

    /**
     * Return the path of the attachment directory
     *
     * @param integer|string $mailId - ID of the Mail Queue Entry
     *
     * @return string
     */
    public static function getAttachmentDir(int|string $mailId): string
    {
        return VAR_DIR . 'mailQueue/' . (int)$mailId . '/';
    }

    /**
     * Send the next mail from the queue
     *
     * @throws QUI\Database\Exception
     */
    public function send(): bool
    {
        if (Mailer::$DISABLE_MAIL_SENDING) {
            return true;
        }

        $params = QUI::getDataBase()->fetch([
            'from' => self::table(),
            'where' => [
                'status' => [
                    'type' => 'NOT',
                    'value' => self::STATUS_SENDING
                ]
            ],
            'limit' => 1
        ]);

        if (!isset($params[0])) {
            return true;
        }

        $entry = $params[0];

        QUI::getDataBase()->update(
            self::table(),
            [
                'status' => self::STATUS_SENDING,
                'lastsend' => time(),
                'retry' => (int)$entry['retry']++
            ],
            ['id' => $entry['id']]
        );


        try {
            $send = $this->sendMail($entry);

            // successful send
            if ($send) {
                QUI::getDataBase()->delete(self::table(), [
                    'id' => $entry['id']
                ]);

                return true;
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError(
                $Exception->getMessage(),
                ['trace' => $Exception->getTraceAsString()],
                'mail_queue'
            );
        }

        return false;
    }

    /**
     * Send the mail
     *
     * @param array $params - mail data
     * @return boolean
     *
     * @throws QUI\Exception
     */
    protected function sendMail(array $params): bool
    {
        if (Mailer::$DISABLE_MAIL_SENDING) {
            return true;
        }

        try {
            QUI::getEvents()->fireEvent('mailerSendInit', [
                $this
            ]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        $queueLimitPerHour = (int)QUI::conf('mail', 'queueLimitPerHour');

        if ($queueLimitPerHour > 0 && $this->getMailsSentInLastHour() >= $queueLimitPerHour) {
            return false;
        }

        try {
            $PhpMailer = QUI::getMailManager()->getPHPMailer();

            $mailto = json_decode($params['mailto'], true);
            $replyTo = json_decode($params['replyto'], true);
            $cc = json_decode($params['cc'], true);
            $bcc = json_decode($params['bcc'], true);

            // mailto
            foreach ($mailto as $address) {
                if (is_array($address)) {
                    $PhpMailer->addAddress($address[0], $address[1]);
                    continue;
                }

                $PhpMailer->addAddress($address);
            }

            // reply
            foreach ($replyTo as $entry) {
                if (is_array($entry)) {
                    $PhpMailer->addAddress($entry[0], $entry[1]);
                    continue;
                }

                $PhpMailer->addReplyTo($entry);
            }

            // cc
            foreach ($cc as $entry) {
                if (is_array($entry)) {
                    $PhpMailer->addAddress($entry[0], $entry[1]);
                    continue;
                }

                $PhpMailer->addCC($entry);
            }

            // bcc
            foreach ($bcc as $entry) {
                if (is_array($entry)) {
                    $PhpMailer->addAddress($entry[0], $entry[1]);
                    continue;
                }

                $PhpMailer->addBCC($entry);
            }

            // exist attachments?
            $mailQueueDir = false;

            if (!empty($params['attachements'])) {
                $attachmentFiles = json_decode($params['attachements'], true);
                $mailQueueDir = self::getAttachmentDir($params['id']);

                if (is_dir($mailQueueDir)) {
                    foreach ($attachmentFiles as $fileName) {
                        $file = $mailQueueDir . $fileName;

                        if (!file_exists($file)) {
                            continue;
                        }

                        $infos = File::getInfo($file);

                        if (!isset($infos['mime_type'])) {
                            $infos['mime_type'] = 'application/octet-stream';
                        }

                        $PhpMailer->addAttachment(
                            $file,
                            $infos['basename'],
                            'base64',
                            $infos['mime_type']
                        );
                    }
                }
            }


            // html mail ?
            if ($params['ishtml']) {
                $PhpMailer->isHTML();
                $PhpMailer->AltBody = $params['text'];
            }

            // remove picture elements
            $html = $params['body'];

            $Output = new QUI\Output();
            $Output->setSetting('use-absolute-urls', true);
            $Output->setSetting('parse-to-picture-elements', false);
            $html = $Output->parse($html);

            $html = preg_replace('#<picture([^>]*)>#i', '', $html);
            $html = preg_replace('#<source([^>]*)>#i', '', $html);
            $html = str_replace('</picture>', '', $html);

            $PhpMailer->From = $params['from'];
            $PhpMailer->FromName = $params['fromName'];
            $PhpMailer->Subject = $params['subject'];
            $PhpMailer->Body = $html;

            try {
                QUI::getEvents()->fireEvent('mailerSendBegin', [$this, $PhpMailer]);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }

            try {
                QUI::getEvents()->fireEvent('mailerSend', [$this, $PhpMailer]);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }

            Log::logSend($PhpMailer);

            $PhpMailer->send();

            if ($mailQueueDir && is_dir($mailQueueDir)) {
                File::deleteDir($mailQueueDir);
            }

            if ($queueLimitPerHour > 0) {
                $this->increaseMailsSent();
            }

            return true;
        } catch (Exception $Exception) {
            $message = $Exception->getMessage();

            if (str_contains($message, 'Recipient address rejected:')) {
                QUI\System\Log::addError($Exception->getMessage());
                return true;
            }

            QUI\System\Log::writeException($Exception);
            Log::logException($Exception);

            QUI::getDataBase()->update(
                self::table(),
                ['status' => self::STATUS_ERROR],
                ['id' => $params['id']]
            );

            throw new QUI\Exception(
                'Mail Error: ' . $Exception->getMessage(),
                500
            );
        }
    }

    /**
     * @throws QUI\Exception
     */
    protected function getMailsSentInLastHour(): int
    {
        $cacheFile = QUI::getPackage('quiqqer/core')->getVarDir() . 'mailqueue';
        $time = time();

        if (!file_exists($cacheFile)) {
            file_put_contents($cacheFile, "$time-0");

            return 0;
        }

        $mailsSent = explode('-', file_get_contents($cacheFile));
        $createTime = (int)$mailsSent[0];

        if ((time() - $createTime) > 3600) {
            file_put_contents($cacheFile, "$time-0");

            return 0;
        }

        return (int)$mailsSent[1];
    }

    /**
     * Increase number of mails sent by 1 and save this information in the cache
     *
     * @throws QUI\Exception
     */
    protected function increaseMailsSent(): void
    {
        $cacheFile = QUI::getPackage('quiqqer/core')->getVarDir() . 'mailqueue';
        $mailsSent = $this->getMailsSentInLastHour();

        $mailsSentCache = explode('-', file_get_contents($cacheFile));
        file_put_contents($cacheFile, $mailsSentCache[0] . '-' . ($mailsSent + 1));
    }

    /**
     * Send all mails from the queue
     *
     * @throws QUI\Database\Exception
     */
    public function sendAll(): void
    {
        if (Mailer::$DISABLE_MAIL_SENDING) {
            return;
        }

        $result = QUI::getDataBase()->fetch([
            'select' => 'id',
            'from' => self::table(),
            'where' => [
                'status' => [
                    'type' => 'NOT',
                    'value' => self::STATUS_SENDING
                ]
            ]
        ]);

        foreach ($result as $row) {
            try {
                $this->sendById($row['id']);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addError(
                    $Exception->getMessage(),
                    ['trace' => $Exception->getTraceAsString()],
                    'mail_queue'
                );
            }
        }
    }

    /**
     * Send an mail by its mail queue id
     *
     * @throws QUI\Exception
     */
    public function sendById(int $id): bool
    {
        if (Mailer::$DISABLE_MAIL_SENDING) {
            return true;
        }

        $params = QUI::getDataBase()->fetch([
            'from' => self::table(),
            'where' => [
                'id' => $id
            ],
            'limit' => 1
        ]);

        if (!isset($params[0])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'system',
                    'exception.mailqueue.mail.not.found'
                ),
                404
            );
        }

        if ((int)$params[0]['status'] === self::STATUS_SENDING) {
            return true;
        }

        $entry = $params[0];

        QUI::getDataBase()->update(
            self::table(),
            [
                'status' => self::STATUS_SENDING,
                'lastsend' => time(),
                'retry' => (int)$entry['retry']++
            ],
            ['id' => $id]
        );

        try {
            $send = $this->sendMail($entry);

            // successful send
            if ($send) {
                QUI::getDataBase()->delete(self::table(), [
                    'id' => $entry['id']
                ]);

                return true;
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError(
                $Exception->getMessage(),
                ['trace' => $Exception->getTraceAsString()],
                'mail_queue'
            );
        }

        return false;
    }

    /**
     * @throws QUI\Database\Exception
     */
    public function count(): int
    {
        $result = QUI::getDataBase()->fetch([
            'from' => self::table(),
            'count' => [
                'select' => 'id',
                'as' => 'count'
            ]
        ]);

        return $result[0]['count'];
    }

    /**
     * @throws QUI\Database\Exception
     */
    public function getList(): array
    {
        return QUI::getDataBase()->fetch([
            'from' => self::table()
        ]);
    }
}
