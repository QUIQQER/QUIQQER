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
 * @licence  For copyright and license information, please view the /README.md
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
    private $config;

    /**
     * internal PHPMailer object
     *
     * @var \PHPMailer
     */
    private $mail;

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
     * @param array|boolean $config - (optional) array(
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
        QUI::getErrorHandler()->setAttribute('ERROR_8192', false);

        //require_once LIB_DIR .'extern/phpmail/class.phpmailer.php';

        // Standard Config setzen
        $mailconf = QUI::conf('mail');

        $this->config = array(
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
                $this->config['IsSMTP'] = $config['IsSMTP'];
            }

            if (isset($config['SMTPServer'])) {
                $this->config['SMTPServer'] = $config['SMTPServer'];
            }

            if (isset($config['SMTPAuth'])) {
                $this->config['SMTPAuth'] = $config['SMTPAuth'];
            }

            if (isset($config['SMTPUser'])) {
                $this->config['SMTPUser'] = $config['SMTPUser'];
            }

            if (isset($config['SMTPPass'])) {
                $this->config['SMTPPass'] = $config['SMTPPass'];
            }

            if (isset($config['MAILFrom'])) {
                $this->config['MAILFrom'] = $config['MAILFrom'];
            }

            if (isset($config['MAILFromText'])) {
                $this->config['MAILFromText'] = $config['MAILFromText'];
            }

            if (isset($config['MAILReplyTo'])) {
                $this->config['MAILReplyTo'] = $config['MAILReplyTo'];
            }

            if (isset($config['CharSet'])) {
                $this->config['CharSet'] = $config['CharSet'];
            }
        }

        // Mail Klasse laden und einstellungen übergeben
        $this->mail = new \PHPMailer();

        if ($this->config['IsSMTP'] == true) {
            //$this->mail->IsSMTP();
            $this->mail->Mailer   = 'smtp';
            $this->mail->Host     = $this->config['SMTPServer'];
            $this->mail->SMTPAuth = $this->config['SMTPAuth'];
            $this->mail->Username = $this->config['SMTPUser'];
            $this->mail->Password = $this->config['SMTPPass'];
        }

        $this->mail->From     = $this->config['MAILFrom'];
        $this->mail->FromName = $this->config['MAILFromText'];
        $this->mail->CharSet  = $this->config['CharSet'];

        //$this->mail->SetLanguage( 'de', LIB_DIR .'extern/phpmail/language/' );

        QUI::getErrorHandler()->setAttribute('ERROR_8192', true);
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
     * @param array $mailconf
     *
     * @return true
     * @throws QUI\Exception
     */
    public function send($mailconf)
    {
        if (!is_array($mailconf)) {
            throw new QUI\Exception(
                'Mail Error: send() Fehlender Paramater',
                400
            );
        }

        if (!isset($mailconf['MailTo'])) {
            throw new QUI\Exception(
                'Mail Error: send() Fehlender Paramater MailTo',
                400
            );
        }

        if (!isset($mailconf['Subject'])) {
            throw new QUI\Exception(
                'Mail Error: send() Fehlender Paramater Subject',
                400
            );
        }

        if (!isset($mailconf['Body'])) {
            throw new QUI\Exception(
                'Mail Error: send() Fehlender Paramater Body',
                400
            );
        }

        $Body    = $mailconf['Body'];
        $MailTo  = $mailconf['MailTo'];
        $Subject = $mailconf['Subject'];

        if (isset($mailconf['MAILReplyTo'])) {
            $MAILReplyTo = $mailconf['MAILReplyTo'];
        }

        $IsHTML = false;
        $files  = false;

        if (isset($mailconf['IsHTML'])) {
            $IsHTML = $mailconf['IsHTML'];
        }

        if (isset($mailconf['files']) && is_array($mailconf['files'])) {
            $files = $mailconf['files'];
        }

        if (DEBUG_MODE) {
            $this->mail->AddCC(QUI::conf('mail', 'admin_mail'));
        }

        if (QUI::conf('mail', 'bccToAdmin')) {
            $this->mail->AddBCC(QUI::conf('mail', 'admin_mail'));
        }

        QUI::getErrorHandler()->setAttribute('ERROR_8192', false);

        if ($IsHTML) {
            $this->mail->IsHTML(true);
        }

        if (is_array($MailTo)) {
            foreach ($MailTo as $mail) {
                $this->mail->AddAddress($mail);
            }
        } else {
            $this->mail->AddAddress($MailTo);
        }

        // Mail ReplyTo überschreiben
        if (isset($MAILReplyTo) && is_array($MAILReplyTo)) {
            foreach ($MAILReplyTo as $mail) {
                $this->mail->AddReplyTo($mail);
            }
        } elseif (isset($MAILReplyTo) && is_string($MAILReplyTo)) {
            $this->mail->AddReplyTo($MAILReplyTo);
        }

        // Mail From überschreiben
        if (isset($mailconf['MAILFrom'])) {
            $this->mail->From = $mailconf['MAILFrom'];
        }

        if (isset($mailconf['MAILFromText'])) {
            $this->mail->FromName = $mailconf['MAILFromText'];
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

                $this->mail->AddAttachment(
                    $file,
                    '',
                    'base64',
                    $infos['mime_type']
                );
            }
        }

        $this->mail->Subject = $Subject;
        $this->mail->Body    = $Body;

        if ($IsHTML) {
            $Html2Text = new Html2Text($Body);

            $this->mail->AltBody = $Html2Text->get_text();
        }

        // with mail queue?
        if (QUI::conf('mail', 'queue')) {
            $Queue = new Mail\Queue();
            $id    = $Queue->addToQueue($this);

            $Queue->sendById($id);

            return true;
        }


        if ($this->mail->Send()) {
            QUI::getErrorHandler()->setAttribute('ERROR_8192', true);

            return true;
        }

        QUI::getErrorHandler()->setAttribute('ERROR_8192', true);

        throw new QUI\Exception(
            'Mail Error: ' . $this->mail->ErrorInfo,
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
        return $this->mail;
    }

    /**
     * Mail params to array
     *
     * @return array
     */
    public function toArray()
    {
        $IsHTML = true;

        if ($this->mail->ContentType === 'text/plain') {
            $IsHTML = false;
        }

        return array(
            'subject'      => $this->mail->Subject,
            'body'         => $this->mail->Body,
            'text'         => $this->mail->AltBody,
            'from'         => $this->mail->From,
            'fromName'     => $this->mail->FromName,
            'ishtml'       => $IsHTML ? 1 : 0,
            'mailto'       => $this->mail->getAllRecipientAddresses(),
            'replyto'      => $this->mail->getReplyToAddresses(),
            'cc'           => $this->mail->getCcAddresses(),
            'bcc'          => $this->mail->getBccAddresses(),
            'attachements' => $this->mail->getAttachments()
        );
    }
}
