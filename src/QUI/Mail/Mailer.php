<?php

/**
 * This file contains \QUI\Mail\Mailer
 */

namespace QUI\Mail;

use Exception;
use Html2Text\Html2Text;
use QUI;
use QUI\Projects\Project;

use function explode;
use function file_exists;
use function is_array;
use function preg_replace;
use function str_replace;
use function trim;

/**
 * Mailer class sends a mail
 * It's the main mail wrapper for the php mailer
 *
 * if you want to send a mail, look at \QUI::getMailManager()->send() first
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Mailer extends QUI\QDOM
{
    /**
     * Static flag in the Mailer class to control the runtime behavior of mail sending.
     *
     * @var bool $DISABLE_MAIL_SENDING Indicates whether mail sending is enabled (false) or disabled (true) during runtime.
     */
    public static bool $DISABLE_MAIL_SENDING = false;

    /**
     * Mail template
     *
     * @var Template|null
     */
    public ?Template $Template = null;

    /**
     * list of recipients
     *
     * @var array
     */
    protected array $recipients = [];

    /**
     * list of reply
     *
     * @var array
     */
    protected array $reply = [];

    /**
     * list of cc
     *
     * @var array
     */
    protected array $cc = [];

    /**
     * list of bcc
     *
     * @var array
     */
    protected array $bcc = [];

    /**
     * list of attachments
     *
     * @var array
     */
    protected array $attachments = [];

    /**
     * constructor
     *
     * @param array $attributes
     * @throws QUI\Exception
     */
    public function __construct(array $attributes = [])
    {
        $config = QUI::conf('mail');

        // default
        $this->setAttributes([
            'html' => true,
            'Project' => QUI::getProjectManager()->get()
        ]);

        if (isset($config['MAILFrom'])) {
            $this->setFrom($config['MAILFrom']);
        }

        if (isset($config['MAILFromText'])) {
            $this->setFromName($config['MAILFromText']);
        }


        // construct array
        $this->setAttributes($attributes);

        // html mail template
        $this->Template = new Template([
            'Project' => $this->getAttribute('Project')
        ]);

        try {
            QUI::getEvents()->fireEvent('mailer', [$this]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * Set the from value for the mail
     *
     * @param string $from - mail@domain.net
     */
    public function setFrom(string $from): void
    {
        $this->setAttribute('from', $from);
    }

    /**
     * Set the from name for the mail
     *
     * @param string $fromName - Firstname Lastname
     */
    public function setFromName(string $fromName): void
    {
        $this->setAttribute('fromName', $fromName);
    }

    /**
     * setter
     */

    /**
     * Send the mail
     *
     * @throws QUI\Exception|\PHPMailer\PHPMailer\Exception
     */
    public function send(): bool
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

        $PHPMailer = QUI::getMailManager()->getPHPMailer();
        $html = $this->Template->getHTML();

        // remove picture elements
        $Output = new QUI\Output();
        $Output->setSetting('use-absolute-urls', true);
        $Output->setSetting('parse-to-picture-elements', false);
        $html = $Output->parse($html);

        $html = preg_replace('#<picture([^>]*)>#i', '', $html);
        $html = preg_replace('#<source([^>]*)>#i', '', $html);
        $html = str_replace('</picture>', '', $html);

        $PHPMailer->Subject = $this->getAttribute('subject');
        $PHPMailer->Body = $html;

        try {
            QUI::getEvents()->fireEvent('mailerSendBegin', [$this, $PHPMailer]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        // html ?
        if ($this->getAttribute('html')) {
            $Html2Text = new Html2Text($PHPMailer->Body);
            $PHPMailer->AltBody = $Html2Text->getText();
        }

        // addresses
        foreach ($this->recipients as $email) {
            if (empty($email)) {
                continue;
            }

            if (is_array($email)) {
                $PHPMailer->addAddress($email[0], $email[1]);
                continue;
            }

            $PHPMailer->addAddress($email);
        }

        foreach ($this->reply as $email) {
            if (empty($email)) {
                continue;
            }

            if (is_array($email)) {
                $PHPMailer->addReplyTo($email[0], $email[1]);
                continue;
            }

            $PHPMailer->addReplyTo($email);
        }

        foreach ($this->cc as $email) {
            if (empty($email)) {
                continue;
            }

            if (is_array($email)) {
                $PHPMailer->addCC($email[0], $email[1]);
                continue;
            }

            $PHPMailer->addCC($email);
        }

        foreach ($this->bcc as $email) {
            if (empty($email)) {
                continue;
            }

            if (is_array($email)) {
                $PHPMailer->addBCC($email[0], $email[1]);
                continue;
            }

            $PHPMailer->addBCC($email);
        }

        if ((int)QUI::conf('mail', 'admin_bcc')) {
            $adminBccMails = trim(QUI::conf('mail', 'admin_mail'));
            $adminBccMails = explode(',', $adminBccMails);

            foreach ($adminBccMails as $mail) {
                if (!empty($mail)) {
                    $PHPMailer->addBCC($mail);
                }
            }

            $this->addBCC(QUI::conf('mail', 'admin_mail'));
        }

        // attachments
        foreach ($this->attachments as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $info = QUI\Utils\System\File::getInfo($file);

            if (!isset($info['mime_type'])) {
                $info['mime_type'] = 'application/octet-stream';
            }

            try {
                $PHPMailer->addAttachment(
                    $file,
                    $info['basename'],
                    'base64',
                    $info['mime_type']
                );
            } catch (\PHPMailer\PHPMailer\Exception $Exception) {
                throw new QUI\Exception(
                    $Exception->getMessage(),
                    $Exception->getCode()
                );
            }
        }

        try {
            QUI::getEvents()->fireEvent('mailerSend', [$this, $PHPMailer]);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        // with mail queue?
        if (QUI::conf('mail', 'queue')) {
            $Queue = new Queue();
            $id = $Queue->addToQueue($this);

            $Queue->sendById($id);

            return true;
        }

        // no mail queue
        try {
            Log::logSend($PHPMailer);

            $PHPMailer->send();

            Log::logDone($PHPMailer);

            return true;
        } catch (Exception $Exception) {
            Log::logException($Exception);

            throw new QUI\Exception(
                'Mail Error: ' . $Exception->getMessage()
            );
        }
    }

    /**
     * Add reply to address
     *
     * @param string $email - E-Mail
     * @param boolean|string $name - E-Mail Name
     */
    public function addReplyTo(string $email, bool|string $name = false): void
    {
        $email = trim($email);
        $email = explode(',', $email);

        foreach ($email as $mail) {
            if ($name) {
                $this->reply[] = [$mail, $name];
                continue;
            }
            $this->reply[] = $mail;
        }
    }

    /**
     * Add cc address
     *
     * @param string $email - E-Mail
     * @param boolean|string $name - E-Mail Name
     */
    public function addCC(string $email, bool|string $name = false): void
    {
        $email = trim($email);
        $email = explode(',', $email);

        foreach ($email as $mail) {
            if ($name) {
                $this->cc[] = [$mail, $name];
                continue;
            }
            $this->cc[] = $mail;
        }
    }

    /**
     * Add bcc address
     *
     * @param string $email - E-Mail
     * @param boolean|string $name - E-Mail Name
     */
    public function addBCC(string $email, bool|string $name = false): void
    {
        $email = trim($email);
        $email = explode(',', $email);

        foreach ($email as $mail) {
            if ($name) {
                $this->bcc[] = [$mail, $name];
                continue;
            }
            $this->bcc[] = $mail;
        }
    }

    /**
     * Add a file to the mail
     *
     * @param string $file - path to the file
     *
     * @return boolean
     */
    public function addAttachment(string $file): bool
    {
        if (!file_exists($file)) {
            return false;
        }

        $this->attachments[] = $file;

        return true;
    }

    /**
     * Mail params to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'subject' => $this->getAttribute('subject'),
            'body' => $this->Template->getHTML(),
            'text' => $this->Template->getText(),
            'from' => $this->getAttribute('from'),
            'fromName' => $this->getAttribute('fromName'),
            'ishtml' => $this->getAttribute('html') ? 1 : 0,
            'mailto' => $this->recipients,
            'replyto' => $this->reply,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'attachements' => $this->attachments
        ];
    }

    /**
     * add methods
     */

    /**
     * Set the mail subject
     *
     * @param string $subject
     */
    public function setSubject(string $subject): void
    {
        $this->setAttribute('subject', $subject);
    }

    /**
     * set the html flag, is html mail or not
     *
     * @param boolean $html - is the mail a html mail or not?
     */
    public function setHTML(bool $html): void
    {
        $this->setAttribute('html', $html);
    }

    /**
     * Set the body
     *
     * @param string $html
     */
    public function setBody(string $html): void
    {
        $this->Template->setBody($html);
    }

    /**
     * Set the project object, for the mailer and the mailer template
     *
     * @param Project $Project
     */
    public function setProject(Project $Project): void
    {
        $this->setAttribute('Project', $Project);
        $this->Template->setAttribute('Project', $Project);
    }

    /**
     * Add a recipient
     *
     * @param string $email - E-Mail
     * @param boolean|string $name - E-Mail Name
     */
    public function addRecipient(string $email, bool|string $name = false): void
    {
        $email = trim($email);
        $email = explode(',', $email);

        foreach ($email as $mail) {
            if ($name) {
                $this->recipients[] = [$mail, $name];
                continue;
            }
            $this->recipients[] = $mail;
        }
    }

    /**
     * Add a files to the mail
     *
     * @param array|string $files - array with file paths eq:
     *                              addAttachments( array('path/file1.end', 'path/file2.end') )
     *                              addAttachments( 'path/file1.end' )
     */
    public function addAttachments(array|string $files): void
    {
        if (!is_array($files)) {
            $this->addAttachment($files);

            return;
        }

        foreach ($files as $file) {
            $this->addAttachment($file);
        }
    }
}
