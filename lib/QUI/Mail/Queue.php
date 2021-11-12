<?php

/**
 * This file contains the \QUI\Mail\Queue
 */

namespace QUI\Mail;

use QUI;
use QUI\Utils\System\File;

/**
 * Mail queue
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Queue
{
    /**
     * Return the table string
     *
     * @return string
     */
    public static function table()
    {
        return QUI::getDBTableName('mailqueue');
    }

    /**
     * Execute the db mail queue setup
     */
    public static function setup()
    {
        $Table = QUI::getDataBase()->table();

        $Table->addColumn(self::table(), [
            'id'           => 'int(11) NOT NULL',
            'subject'      => 'varchar(1000)',
            'body'         => 'longtext',
            'text'         => 'longtext',
            'from'         => 'text',
            'fromName'     => 'text',
            'ishtml'       => 'int(1)',
            'mailto'       => 'text',
            'replyto'      => 'text',
            'cc'           => 'text',
            'bcc'          => 'text',
            'attachements' => 'text'
        ]);

        $Table->setPrimaryKey(self::table(), 'id');
        $Table->setAutoIncrement(self::table(), 'id');
    }

    /**
     * Return the path of the attachment directory
     *
     * @param string|integer $mailId - ID of the Mail Queue Entry
     *
     * @return string
     */
    public static function getAttachmentDir($mailId)
    {
        return VAR_DIR.'mailQueue/'.(int)$mailId.'/';
    }

    /**
     * Add a mail to the mail queue
     *
     * @param Mailer|QUI\Mail $Mail
     *
     * @return integer - Mailqueue-ID
     */
    public static function addToQueue($Mail)
    {
        $params = $Mail->toArray();

        $params['mailto']  = \json_encode($params['mailto']);
        $params['replyto'] = \json_encode($params['replyto']);
        $params['cc']      = \json_encode($params['cc']);
        $params['bcc']     = \json_encode($params['bcc']);

        $attachements = [];

        if (isset($params['attachements'])) {
            $attachements = $params['attachements'];
            unset($params['attachements']);
        }

        QUI::getDataBase()->insert(self::table(), $params);

        $newMailId = QUI::getDataBase()->getPDO()->lastInsertId('id');

        // attachements
        $attachmentFiles = [];

        if (\is_array($attachements)) {
            $mailQueueDir = self::getAttachmentDir($newMailId);

            File::mkdir($mailQueueDir);

            foreach ($attachements as $attachement) {
                if (!\file_exists($attachement)) {
                    continue;
                }

                $infos = File::getInfo($attachement);

                File::copy($attachement, $mailQueueDir.$infos['basename']);

                $attachmentFiles[] = $infos['basename'];
            }

            if (!empty($attachmentFiles)) {
                QUI::getDataBase()->update(
                    self::table(),
                    [
                        'attachements' => \json_encode($attachmentFiles)
                    ],
                    [
                        'id' => $newMailId
                    ]
                );
            }
        }

        return $newMailId;
    }

    /**
     * Send a mail from the queue
     *
     * @return boolean
     */
    public function send()
    {
        $params = QUI::getDataBase()->fetch([
            'from'  => self::table(),
            'limit' => 1
        ]);

        if (!isset($params[0])) {
            return true;
        }

        try {
            $send = $this->sendMail($params[0]);

            // successful send
            if ($send) {
                QUI::getDataBase()->delete(self::table(), [
                    'id' => $params[0]['id']
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
     * Send all mails from the queue
     *
     * @return void
     */
    public function sendAll()
    {
        $result = QUI::getDataBase()->fetch([
            'select' => 'id',
            'from'   => self::table()
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
     * Send an mail by its mailqueue id
     *
     * @param integer $id
     *
     * @return boolean
     * @throws QUI\Exception
     */
    public function sendById($id)
    {
        $params = QUI::getDataBase()->fetch([
            'from'  => self::table(),
            'where' => [
                'id' => (int)$id
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


        try {
            $send = $this->sendMail($params[0]);

            // successful send
            if ($send) {
                QUI::getDataBase()->delete(self::table(), [
                    'id' => $params[0]['id']
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
     * @throws \QUI\Exception
     */
    protected function sendMail($params)
    {
        $queueLimitPerHour = (int)QUI::conf('mail', 'queueLimitPerHour');

        if ($queueLimitPerHour > 0 && $this->getMailsSentInLastHour() >= $queueLimitPerHour) {
            return false;
        }

        try {
            $PhpMailer = QUI::getMailManager()->getPHPMailer();

            $mailto  = \json_decode($params['mailto'], true);
            $replyto = \json_decode($params['replyto'], true);
            $cc      = \json_decode($params['cc'], true);
            $bcc     = \json_decode($params['bcc'], true);

            // mailto
            foreach ($mailto as $address) {
                if (\is_array($address)) {
                    $PhpMailer->addAddress($address[0], $address[1]);
                    continue;
                }

                $PhpMailer->addAddress($address);
            }

            // reply
            foreach ($replyto as $entry) {
                if (\is_array($entry)) {
                    $PhpMailer->addAddress($entry[0], $entry[1]);
                    continue;
                }

                $PhpMailer->addReplyTo($entry);
            }

            // cc
            foreach ($cc as $entry) {
                if (\is_array($entry)) {
                    $PhpMailer->addAddress($entry[0], $entry[1]);
                    continue;
                }

                $PhpMailer->addCC($entry);
            }

            // bcc
            foreach ($bcc as $entry) {
                if (\is_array($entry)) {
                    $PhpMailer->addAddress($entry[0], $entry[1]);
                    continue;
                }

                $PhpMailer->addBCC($entry);
            }

            // exist attachements?
            $mailQueueDir = false;

            if (!empty($params['attachements'])) {
                $attachmentFiles = \json_decode($params['attachements'], true);
                $mailQueueDir    = self::getAttachmentDir($params['id']);

                if (\is_dir($mailQueueDir)) {
                    foreach ($attachmentFiles as $fileName) {
                        $file = $mailQueueDir.$fileName;

                        if (!\file_exists($file)) {
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
                $PhpMailer->isHTML(true);
                $PhpMailer->AltBody = $params['text'];
            }

            // remove picture elements
            $html = $params['body'];

            $Output = new QUI\Output();
            $Output->setSetting('use-absolute-urls', true);
            $Output->setSetting('parse-to-picture-elements', false);
            $html = $Output->parse($html);

            $html = \preg_replace('#<picture([^>]*)>#i', '', $html);
            $html = \preg_replace('#<source([^>]*)>#i', '', $html);
            $html = \str_replace('</picture>', '', $html);

            $PhpMailer->From     = $params['from'];
            $PhpMailer->FromName = $params['fromName'];
            $PhpMailer->Subject  = $params['subject'];
            $PhpMailer->Body     = $html;

            Log::logSend($PhpMailer);

            $PhpMailer->send();

            if ($mailQueueDir && \is_dir($mailQueueDir)) {
                File::deleteDir($mailQueueDir);
            }

            if ($queueLimitPerHour > 0) {
                $this->increaseMailsSent();
            }

            return true;
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            Log::logException($Exception);

            throw new QUI\Exception(
                'Mail Error: '.$Exception->getMessage(),
                500
            );
        }
    }

    /**
     * Return the number of the queue
     *
     * @return integer
     */
    public function count()
    {
        $result = QUI::getDataBase()->fetch([
            'from'  => self::table(),
            'count' => [
                'select' => 'id',
                'as'     => 'count'
            ]
        ]);

        return $result[0]['count'];
    }

    /**
     * Return the queue list
     *
     * @return array
     */
    public function getList()
    {
        return QUI::getDataBase()->fetch([
            'from' => self::table()
        ]);
    }

    /**
     * Get number of mails that have been sent via queue in the last hour
     *
     * @return int
     */
    protected function getMailsSentInLastHour()
    {
        $cacheFile = QUI::getPackage('quiqqer/quiqqer')->getVarDir().'mailqueue';
        $time      = \time();

        if (!\file_exists($cacheFile)) {
            \file_put_contents($cacheFile, "$time-0");

            return 0;
        }

        $mailsSent  = \explode('-', \file_get_contents($cacheFile));
        $createTime = (int)$mailsSent[0];

        if ((\time() - $createTime) > 3600) {
            \file_put_contents($cacheFile, "$time-0");

            return 0;
        }

        return (int)$mailsSent[1];
    }

    /**
     * Increase number of mails sent by 1 and save this information in the cache
     *
     * @return void
     */
    protected function increaseMailsSent()
    {
        $cacheFile = QUI::getPackage('quiqqer/quiqqer')->getVarDir().'mailqueue';
        $mailsSent = $this->getMailsSentInLastHour();

        $mailsSentCache = \explode('-', \file_get_contents($cacheFile));
        \file_put_contents($cacheFile, $mailsSentCache[0].'-'.($mailsSent + 1));
    }
}
