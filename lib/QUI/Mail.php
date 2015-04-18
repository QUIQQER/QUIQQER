<?php

/**
 * This file contains \QUI\Mail
 */

namespace QUI;

use QUI;
use Html2Text\Html2Text;

/**
 * E-Mail
 *
 * @author   www.pcsg.de (Moritz Scholz)
 * @author   www.pcsg.de (Henning Leutz)
 *
 * @requires phpmailer/phpmailer
 *
 * @example  $Mail = new \QUI\Mail(array(
 * 'MAILFrom'     => $MAILFrom,
 * 'MAILFromText' => $MAILFromText,
 * 'MAILReplyTo'  => $MAILReplyTo
 * ));
 *
 * $Mail->send(array(
 * 'MailTo'  => $MailTo,
 * 'Subject' => $Subject,
 * 'Body'    => $Body,
 * 'IsHTML'  => true
 * ));
 * @example  $Mail->send(array(
 * 'MailTo'  => $MailTo,
 * 'Subject' => $Subject,
 * 'Body'    => $Body,
 * 'IsHTML'  => true
 * ));
 *
 * @deprecated
 */
class Mail
{
    /**
     * internal mail config
     *
     * @var array
     */
    private $_config;

    /**
     * internal PHPMailer object
     *
     * @var \PHPMailer
     */
    private $_mail;

    /**
     * Mail template
     *
     * @var \QUI\Mail\Template
     */
    public $Template;

    /**
     * constructor
     * The E-Mail class uses the internal QUIQQER config settings
     *
     * @param Array|bool $config - (optional) array(
     *                           'IsSMTP',
     *                           'SMTPServer',
     *                           'SMTPAuth',
     *                           'SMTPUser',
     *                           'SMTPPass',
     *                           'MAILFrom',
     *                           'MAILFromText',
     *                           'MAILReplyTo'
     *                           )
     */
    public function __construct($config = false)
    {
        \QUI::getErrorHandler()->setAttribute('ERROR_8192', false);

        //require_once LIB_DIR .'extern/phpmail/class.phpmailer.php';

        // Standard Config setzen
        $mailconf = \QUI::conf('mail');

        $this->_config = array(
            'IsSMTP'       => $mailconf['SMTP'],
            'SMTPServer'   => $mailconf['SMTPServer'],
            'SMTPAuth'     => $mailconf['SMTPAuth'],
            'SMTPUser'     => $mailconf['SMTPUser'],
            'SMTPPass'     => $mailconf['SMTPPass'],
            'MAILFrom'     => $mailconf['MAILFrom'],
            'MAILFromText' => $mailconf['MAILFromText'],
            'MAILReplyTo'  => $mailconf['MAILReplyTo'],
            'CharSet'      => 'UTF-8'
        );

        // Übergebene Config übernehmen
        if ($config != false) {
            if (isset($config['IsSMTP'])) {
                $this->_config['IsSMTP'] = $config['IsSMTP'];
            }

            if (isset($config['SMTPServer'])) {
                $this->_config['SMTPServer'] = $config['SMTPServer'];
            }

            if (isset($config['SMTPAuth'])) {
                $this->_config['SMTPAuth'] = $config['SMTPAuth'];
            }

            if (isset($config['SMTPUser'])) {
                $this->_config['SMTPUser'] = $config['SMTPUser'];
            }

            if (isset($config['SMTPPass'])) {
                $this->_config['SMTPPass'] = $config['SMTPPass'];
            }

            if (isset($config['MAILFrom'])) {
                $this->_config['MAILFrom'] = $config['MAILFrom'];
            }

            if (isset($config['MAILFromText'])) {
                $this->_config['MAILFromText'] = $config['MAILFromText'];
            }

            if (isset($config['MAILReplyTo'])) {
                $this->_config['MAILReplyTo'] = $config['MAILReplyTo'];
            }

            if (isset($config['CharSet'])) {
                $this->_config['CharSet'] = $config['CharSet'];
            }
        }

        // Mail Klasse laden und einstellungen übergeben
        $this->_mail = new \PHPMailer();

        if ($this->_config['IsSMTP'] == true) {
            //$this->_mail->IsSMTP();
            $this->_mail->Mailer = 'smtp';
            $this->_mail->Host = $this->_config['SMTPServer'];
            $this->_mail->SMTPAuth = $this->_config['SMTPAuth'];
            $this->_mail->Username = $this->_config['SMTPUser'];
            $this->_mail->Password = $this->_config['SMTPPass'];
        }

        $this->_mail->From = $this->_config['MAILFrom'];
        $this->_mail->FromName = $this->_config['MAILFromText'];
        $this->_mail->CharSet = $this->_config['CharSet'];

        //$this->_mail->SetLanguage( 'de', LIB_DIR .'extern/phpmail/language/' );

        \QUI::getErrorHandler()->setAttribute('ERROR_8192', true);
    }

    /**
     * send the mail
     *
     * @example send(array(
     *        'MailTo'    => 'cms@pcsg.de',
     *        'Subject'    => 'CMS Newsletter',
     *        'IsHTML'    => true,
     *        'files'    => array('datei1', 'datei2', 'datei3')
     * ));
     *
     * @param Array $mailconf
     *
     * @return true
     * @throws QUI\Exception
     */
    public function send($mailconf)
    {
        if (!is_array($mailconf)) {
            throw new QUI\Exception('Mail Error: send() Fehlender Paramater',
                400);
        }

        if (!isset($mailconf['MailTo'])) {
            throw new QUI\Exception('Mail Error: send() Fehlender Paramater MailTo',
                400);
        }

        if (!isset($mailconf['Subject'])) {
            throw new QUI\Exception('Mail Error: send() Fehlender Paramater Subject',
                400);
        }

        if (!isset($mailconf['Body'])) {
            throw new QUI\Exception('Mail Error: send() Fehlender Paramater Body',
                400);
        }

        $Body = $mailconf['Body'];
        $MailTo = $mailconf['MailTo'];
        $Subject = $mailconf['Subject'];

        if (isset($mailconf['MAILReplyTo'])) {
            $MAILReplyTo = $mailconf['MAILReplyTo'];
        }

        $IsHTML = false;
        $files = false;

        if (isset($mailconf['IsHTML'])) {
            $IsHTML = $mailconf['IsHTML'];
        }

        if (isset($mailconf['files']) && is_array($mailconf['files'])) {
            $files = $mailconf['files'];
        }

        if (DEBUG_MODE) {
            $this->_mail->AddCC(\QUI::conf('mail', 'admin_mail'));
        }

        if (\QUI::conf('mail', 'bccToAdmin')) {
            $this->_mail->AddBCC(\QUI::conf('mail', 'admin_mail'));
        }

        \QUI::getErrorHandler()->setAttribute('ERROR_8192', false);

        if ($IsHTML) {
            $this->_mail->IsHTML(true);
        }

        if (is_array($MailTo)) {
            foreach ($MailTo as $mail) {
                $this->_mail->AddAddress($mail);
            }
        } else {
            $this->_mail->AddAddress($MailTo);
        }

        // Mail ReplyTo überschreiben
        if (isset($MAILReplyTo) && is_array($MAILReplyTo)) {
            foreach ($MAILReplyTo as $mail) {
                $this->_mail->AddReplyTo($mail);
            }
        } elseif (isset($MAILReplyTo) && is_string($MAILReplyTo)) {
            $this->_mail->AddReplyTo($MAILReplyTo);
        }

        // Mail From überschreiben
        if (isset($mailconf['MAILFrom'])) {
            $this->_mail->From = $mailconf['MAILFrom'];
        }

        if (isset($mailconf['MAILFromText'])) {
            $this->_mail->FromName = $mailconf['MAILFromText'];
        }


        if (is_array($files)) {
            foreach ($files as $file) {
                if (!file_exists($file)) {
                    continue;
                }

                $infos = QUI\Utils\System\File::getInfo($file);

                if (!isset($infos['mime_type'])) {
                    $infos['mime_type'] = 'application/octet-stream';
                }

                $this->_mail->AddAttachment($file, '', 'base64',
                    $infos['mime_type']);
            }
        }

        $this->_mail->Subject = $Subject;
        $this->_mail->Body = $Body;

        if ($IsHTML) {
            $Html2Text = new Html2Text($Body);

            $this->_mail->AltBody = $Html2Text->get_text();
        }

        // with mail queue?
        if (QUI::conf('mail', 'queue')) {
            $Queue = new Mail\Queue();
            $id = $Queue->addToQueue($this);

            $Queue->sendById($id);

            return true;
        }


        if ($this->_mail->Send()) {
            QUI::getErrorHandler()->setAttribute('ERROR_8192', true);

            return true;
        }

        QUI::getErrorHandler()->setAttribute('ERROR_8192', true);

        throw new QUI\Exception(
            'Mail Error: '.$this->_mail->ErrorInfo,
            500
        );
    }

    /**
     * Return the internal PHPMailer object
     *
     * @return \PHPMailer
     */
    public function getPHPMailer()
    {
        return $this->_mail;
    }

    /**
     * Mail params to array
     *
     * @return Array
     */
    public function toArray()
    {
        $IsHTML = true;

        if ($this->_mail->ContentType === 'text/plain') {
            $IsHTML = false;
        }

        return array(
            'subject'      => $this->_mail->Subject,
            'body'         => $this->_mail->Body,
            'text'         => $this->_mail->AltBody,
            'from'         => $this->_mail->From,
            'fromName'     => $this->_mail->FromName,
            'ishtml'       => $IsHTML ? 1 : 0,
            'mailto'       => $this->_mail->getAllRecipientAddresses(),
            'replyto'      => $this->_mail->getReplyToAddresses(),
            'cc'           => $this->_mail->getCcAddresses(),
            'bcc'          => $this->_mail->getBccAddresses(),
            'attachements' => $this->_mail->getAttachments()
        );
    }
}
