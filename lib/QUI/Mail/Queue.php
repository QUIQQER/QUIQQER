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
    static function Table()
    {
        return QUI_DB_PRFX.'mailqueue';
    }

    /**
     * Execute the db mail queue setup
     */
    static function setup()
    {
        $Table = QUI::getDataBase()->Table();

        $Table->appendFields(self::Table(), array(
            'id'           => 'int(11) NOT NULL',
            'subject'      => 'varchar(1000)',
            'body'         => 'text',
            'text'         => 'text',
            'from'         => 'text',
            'fromName'     => 'text',
            'ishtml'       => 'int(1)',
            'mailto'       => 'text',
            'replyto'      => 'text',
            'cc'           => 'text',
            'bcc'          => 'text',
            'attachements' => 'text'
        ));

        $Table->setPrimaryKey(self::Table(), 'id');
        $Table->setAutoIncrement(self::Table(), 'id');
    }

    /**
     * Return the path of the attachment directory
     *
     * @param String|Integer $mailId - ID of the Mail Queue Entry
     *
     * @return string
     */
    static function getAttachmentDir($mailId)
    {
        return VAR_DIR.'mailQueue/'.(int)$mailId.'/';
    }

    /**
     * Add a mail to the mail queue
     *
     * @param Mailer|QUI\Mail $Mail
     *
     * @return Integer - Mailqueue-ID
     */
    static function addToQueue($Mail)
    {
        $params = $Mail->toArray();

        $params['mailto'] = json_encode($params['mailto']);
        $params['replyto'] = json_encode($params['replyto']);
        $params['cc'] = json_encode($params['cc']);
        $params['bcc'] = json_encode($params['bcc']);

        $attachements = array();

        if (isset($params['attachements'])) {
            $attachements = $params['attachements'];
            unset($params['attachements']);
        }


        QUI::getDataBase()->insert(self::Table(), $params);

        $newMailId = QUI::getDataBase()->getPDO()->lastInsertId('id');

        // attachements
        if (is_array($attachements)) {
            $mailQueueDir = self::getAttachmentDir($newMailId);

            File::mkdir($mailQueueDir);

            foreach ($attachements as $attachement) {
                if (!file_exists($attachement)) {
                    continue;
                }

                $infos = File::getInfo($attachement);

                File::copy($attachement, $mailQueueDir.$infos['basename']);
            }
        }

        return $newMailId;
    }

    /**
     * Send a mail from the queue
     *
     * @return Bool
     */
    public function send()
    {
        $params = QUI::getDataBase()->fetch(array(
            'from'  => self::Table(),
            'limit' => 1
        ));

        if (!isset($params[0])) {
            return true;
        }

        try {
            $send = $this->_sendMail($params[0]);

            // successful send
            if ($send) {
                QUI::getDataBase()->delete(self::Table(), array(
                    'id' => $params[0]['id']
                ));

                return true;
            }

        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage(), 'mail_queue');
        }

        return false;
    }

    /**
     * Send an mail by its mailqueue id
     *
     * @param Integer $id
     *
     * @return Bool
     * @throws QUI\Exception
     */
    public function sendById($id)
    {
        $params = QUI::getDataBase()->fetch(array(
            'from'  => self::Table(),
            'where' => array(
                'id' => (int)$id
            ),
            'limit' => 1
        ));

        if (!isset($params[0])) {
            throw new QUI\Exception(
                QUI::getLocale(
                    'system',
                    'exception.mailqueue.mail.not.found'
                ),
                404
            );
        }


        try {
            $send = $this->_sendMail($params[0]);

            // successful send
            if ($send) {
                QUI::getDataBase()->delete(self::Table(), array(
                    'id' => $params[0]['id']
                ));

                return true;
            }

        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage(), 'mail_queue');
        }

        return false;
    }

    /**
     * Send the mail
     *
     * @throws \QUI\Exception
     *
     * @param Array $params - mail data
     *
     * @return Boolean
     * @todo attachments
     */
    protected function _sendMail($params)
    {
        $PhpMailer = QUI::getMailManager()->getPHPMailer();

        $mailto = json_decode($params['mailto'], true);
        $replyto = json_decode($params['replyto'], true);
        $cc = json_decode($params['cc'], true);
        $bcc = json_decode($params['bcc'], true);

        // mailto
        foreach ($mailto as $address) {
            $PhpMailer->addAddress($address);
        }

        // reply
        foreach ($replyto as $entry) {
            $PhpMailer->addReplyTo($entry);
        }

        // cc
        foreach ($cc as $entry) {
            $PhpMailer->addCC($entry);
        }

        // bcc
        foreach ($bcc as $entry) {
            $PhpMailer->addBCC($entry);
        }

        // exist attachements?
        $mailQueueDir = self::getAttachmentDir($params['id']);

        if (is_dir($mailQueueDir)) {
            $files = File::readDir($mailQueueDir);

            foreach ($files as $file) {
                $file = $mailQueueDir.$file;

                if (!file_exists($file)) {
                    continue;
                }

                $infos = File::getInfo($file);

                if (!isset($infos['mime_type'])) {
                    $infos['mime_type'] = 'application/octet-stream';
                }

                $PhpMailer->addAttachment($file, $infos['basename'], 'base64',
                    $infos['mime_type']);
            }
        }


        // html mail ?
        if ($params['ishtml']) {
            $PhpMailer->IsHTML(true);
            $PhpMailer->AltBody = $params['text'];
        }

        $PhpMailer->From = $params['from'];
        $PhpMailer->FromName = $params['fromName'];
        $PhpMailer->Subject = $params['subject'];
        $PhpMailer->Body = $params['body'];


        if ($PhpMailer->send()) {
            if (is_dir($mailQueueDir)) {
                File::deleteDir($mailQueueDir);
            }

            return true;
        }

        throw new QUI\Exception(
            'Mail Error: '.$PhpMailer->ErrorInfo,
            500
        );
    }

    /**
     * Return the number of the queue
     *
     * @return Integer
     */
    public function count()
    {
        $result = QUI::getDataBase()->fetch(array(
            'from'  => self::Table(),
            'count' => array(
                'select' => 'id',
                'as'     => 'count'
            )
        ));

        return $result[0]['count'];
    }

    /**
     * Return the queue list
     *
     * @return Array
     */
    public function getList()
    {
        return QUI::getDataBase()->fetch(array(
            'from' => self::Table()
        ));
    }
}
