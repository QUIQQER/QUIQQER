<?php

/**
 * This file contains \QUI\Mail
 */

namespace QUI;

use Html2Text\Html2Text;
use PHPMailer;
use QUI;
use QUI\Mail\Mailer;

use function file_exists;
use function is_array;
use function is_string;

/**
 * E-Mail
 *
 * @author  www.pcsg.de (Moritz Scholz)
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 *
 * @example $Mail = new \QUI\Mail(array(
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
 * @example $Mail->send(array(
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
     * Mail template
     *
     * @var \QUI\Mail\Template
     */
    public Mail\Template $Template;
    /**
     * internal mail config
     *
     * @var array
     */
    private array $config;
    /**
     * internal PHPMailer object
     *
     * @var PHPMailer
     */
    private PHPMailer $Mail;

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
        $mailConf = QUI::conf('mail');

        $this->config = [
            'IsSMTP' => $mailConf['SMTP'],
            'SMTPServer' => $mailConf['SMTPServer'],
            'SMTPAuth' => $mailConf['SMTPAuth'],
            'SMTPUser' => $mailConf['SMTPUser'],
            'SMTPPass' => $mailConf['SMTPPass'],
            'MAILFrom' => $mailConf['MAILFrom'],
            'MAILFromText' => $mailConf['MAILFromText'],
            'MAILReplyTo' => $mailConf['MAILReplyTo'],
            'CharSet' => 'UTF-8'
        ];

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
        $this->Mail = new PHPMailer();

        if ($this->config['IsSMTP'] == true) {
            //$this->mail->IsSMTP();
            $this->Mail->Mailer = 'smtp';
            $this->Mail->Host = $this->config['SMTPServer'];
            $this->Mail->SMTPAuth = $this->config['SMTPAuth'];
            $this->Mail->Username = $this->config['SMTPUser'];
            $this->Mail->Password = $this->config['SMTPPass'];
        }

        $this->Mail->From = $this->config['MAILFrom'];
        $this->Mail->FromName = $this->config['MAILFromText'];
        $this->Mail->CharSet = $this->config['CharSet'];

        //$this->mail->SetLanguage( 'de', LIB_DIR .'extern/phpmail/language/' );

        QUI::getErrorHandler()->setAttribute('ERROR_8192', true);
    }

    /**
     * send the mail
     *
     * @param array $mailconf
     *
     * @return true
     * @throws QUI\Exception
     * @example send(array(
     *        'MailTo'    => 'cms@pcsg.de',
     *        'Subject'    => 'CMS Newsletter',
     *        'IsHTML'    => true,
     *        'files'    => array('datei1', 'datei2', 'datei3')
     * ));
     *
     */
    public function send(array $mailconf): bool
    {
        if (Mailer::$DISABLE_MAIL_SENDING) {
            return true;
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
            $this->Mail->addCC(QUI::conf('mail', 'admin_mail'));
        }

        if (QUI::conf('mail', 'bccToAdmin')) {
            $this->Mail->addCC(QUI::conf('mail', 'admin_mail'));
        }

        QUI::getErrorHandler()->setAttribute('ERROR_8192', false);

        if ($IsHTML) {
            $this->Mail->isHTML(true);
        }

        if (is_array($MailTo)) {
            foreach ($MailTo as $mail) {
                $this->Mail->addAddress($mail);
            }
        } else {
            $this->Mail->addAddress($MailTo);
        }

        // Mail ReplyTo überschreiben
        if (isset($MAILReplyTo) && is_array($MAILReplyTo)) {
            foreach ($MAILReplyTo as $mail) {
                $this->Mail->addReplyTo($mail);
            }
        } elseif (isset($MAILReplyTo) && is_string($MAILReplyTo)) {
            $this->Mail->addReplyTo($MAILReplyTo);
        }

        // Mail From überschreiben
        if (isset($mailconf['MAILFrom'])) {
            $this->Mail->From = $mailconf['MAILFrom'];
        }

        if (isset($mailconf['MAILFromText'])) {
            $this->Mail->FromName = $mailconf['MAILFromText'];
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

                $this->Mail->addAttachment(
                    $file,
                    '',
                    'base64',
                    $infos['mime_type']
                );
            }
        }

        $this->Mail->Subject = $Subject;
        $this->Mail->Body = $Body;

        if ($IsHTML) {
            $Html2Text = new Html2Text($Body);

            $this->Mail->AltBody = $Html2Text->getText();
        }

        // with mail queue?
        if (QUI::conf('mail', 'queue')) {
            $Queue = new Mail\Queue();
            $id = $Queue->addToQueue($this);

            $Queue->sendById($id);

            return true;
        }


        QUI::getErrorHandler()->setAttribute('ERROR_8192', true);

        if ($this->Mail->send()) {
            return true;
        }

        throw new QUI\Exception(
            'Mail Error: ' . $this->Mail->ErrorInfo,
            500
        );
    }

    /**
     * Return the internal PHPMailer object
     *
     * @return PHPMailer
     */
    public function getPHPMailer()
    {
        return $this->Mail;
    }

    /**
     * Mail params to array
     *
     * @return array
     */
    public function toArray(): array
    {
        $IsHTML = true;

        if ($this->Mail->ContentType === 'text/plain') {
            $IsHTML = false;
        }

        return [
            'subject' => $this->Mail->Subject,
            'body' => $this->Mail->Body,
            'text' => $this->Mail->AltBody,
            'from' => $this->Mail->From,
            'fromName' => $this->Mail->FromName,
            'ishtml' => $IsHTML ? 1 : 0,
            'mailto' => $this->Mail->getAllRecipientAddresses(),
            'replyto' => $this->Mail->getReplyToAddresses(),
            'cc' => $this->Mail->getCcAddresses(),
            'bcc' => $this->Mail->getBccAddresses(),
            'attachements' => $this->Mail->getAttachments()
        ];
    }
}
