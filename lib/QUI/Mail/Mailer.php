<?php

/**
 * This file contains \QUI\Mail\Mailer
 */

namespace QUI\Mail;

use QUI;
use Html2Text\Html2Text;

/**
 * Mailer class sends a mail
 * Its the main mail wrapper for the php mailer
 *
 * if you want send a mail, look at \QUI::getMailManager()->send() first
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Mailer extends QUI\QDOM
{
    /**
     * Mail template
     *
     * @var \QUI\Mail\Template
     */
    public $Template = null;

    /**
     * list of recipients
     *
     * @var array
     */
    protected $_recipients = array();

    /**
     * list of reply
     *
     * @var array
     */
    protected $_reply = array();

    /**
     * list of cc
     *
     * @var array
     */
    protected $_cc = array();

    /**
     * list of bcc
     *
     * @var array
     */
    protected $_bcc = array();

    /**
     * list of attachments
     *
     * @var array
     */
    protected $_attachments = array();

    /**
     * constructor
     *
     * @param array $attributes
     */
    public function __construct($attributes = array())
    {
        $config = \QUI::conf('mail');

        // default
        $this->setAttributes(array(
            'html'    => true,
            'Project' => \QUI::getProjectManager()->get()
        ));

        if (isset($config['MAILFrom'])) {
            $this->setFrom($config['MAILFrom']);
        }

        if (isset($config['MAILFromText'])) {
            $this->setFromName($config['MAILFromText']);
        }


        // construct array
        $this->setAttributes($attributes);

        // html mail template
        $this->Template = new Template(array(
            'Project' => $this->getAttribute('Project')
        ));
    }

    /**
     * Send the mail
     *
     * @throws \QUI\Exception
     */
    public function send()
    {
        $PHPMailer = QUI::getMailManager()->getPHPMailer();

        $PHPMailer->Subject = $this->getAttribute('subject');
        $PHPMailer->Body = $this->Template->getHTML();

        // html ?
        if ($this->getAttribute('html')) {
            $Html2Text = new Html2Text($PHPMailer->Body);
            $PHPMailer->AltBody = $Html2Text->get_text();
        }

        // addresses
        foreach ($this->_recipients as $email) {
            $PHPMailer->addAddress($email);
        }

        foreach ($this->_reply as $email) {
            $PHPMailer->addReplyTo($email);
        }

        foreach ($this->_cc as $email) {
            $PHPMailer->addCC($email);
        }

        foreach ($this->_bcc as $email) {
            $PHPMailer->addBCC($email);
        }

        // attachments
        foreach ($this->_attachments as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $infos = QUI\Utils\System\File::getInfo($file);

            if (!isset($infos['mime_type'])) {
                $infos['mime_type'] = 'application/octet-stream';
            }

            $PHPMailer->addAttachment($file, $infos['basename'], 'base64',
                $infos['mime_type']);
        }


        // with mail queue?
        if (QUI::conf('mail', 'queue')) {
            $Queue = new Queue();
            $id = $Queue->addToQueue($this);

            $Queue->sendById($id);

            return true;
        }


        // no mail queue
        if ($PHPMailer->Send()) {
            return true;
        }

        throw new QUI\Exception(
            'Mail Error: '.$PHPMailer->ErrorInfo
        );
    }

    /**
     * Mail params to array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'subject'      => $this->getAttribute('subject'),
            'body'         => $this->Template->getHTML(),
            'text'         => $this->Template->getText(),
            'from'         => $this->getAttribute('from'),
            'fromName'     => $this->getAttribute('fromName'),
            'ishtml'       => (bool)$this->getAttribute('html') ? 1 : 0,
            'mailto'       => $this->_recipients,
            'replyto'      => $this->_reply,
            'cc'           => $this->_cc,
            'bcc'          => $this->_bcc,
            'attachements' => $this->_attachments
        );
    }

    /**
     * setter
     */

    /**
     * Set the from mail
     *
     * @param string $from - mail@domain.net
     */
    public function setFrom($from)
    {
        $this->setAttribute('from', $from);
    }

    /**
     * Set the from name for the mail
     *
     * @param string $fromName - Firstname Lastname
     */
    public function setFromName($fromName)
    {
        $this->setAttribute('fromName', $fromName);
    }

    /**
     * Set the mail subject
     *
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->setAttribute('subject', $subject);
    }

    /**
     * set the html flag, is html mail or not
     *
     * @param boolean $html - is the mail a html mail or not?
     */
    public function setHTML($html)
    {
        $this->setAttribute('html', (bool)$html);
    }

    /**
     * Set the body
     *
     * @param string $html
     */
    public function setBody($html)
    {
        $this->Template->setBody($html);
    }

    /**
     * Set the project object, for the mailer and the mailer template
     *
     * @param \QUI\Projects\Project $Project
     */
    public function setProject(QUI\Projects\Project $Project)
    {
        $this->setAttribute('Project', $Project);
        $this->Template->setAttribute('Project', $Project);
    }

    /**
     * add methods
     */

    /**
     * Add an recipient
     *
     * @param string $email - E-Mail
     */
    public function addRecipient($email)
    {
        $email = trim($email);
        $email = explode(',', $email);

        foreach ($email as $mail) {
            $this->_recipients[] = $mail;
        }
    }

    /**
     * Add reply to address
     *
     * @param string $email - E-Mail
     */
    public function addReplyTo($email)
    {
        $email = trim($email);
        $email = explode(',', $email);

        foreach ($email as $mail) {
            $this->_reply[] = $mail;
        }
    }

    /**
     * Add cc address
     *
     * @param string $email - E-Mail
     */
    public function addCC($email)
    {
        $email = trim($email);
        $email = explode(',', $email);

        foreach ($email as $mail) {
            $this->_cc[] = $mail;
        }
    }

    /**
     * Add bcc address
     *
     * @param string $email - E-Mail
     */
    public function addBCC($email)
    {
        $email = trim($email);
        $email = explode(',', $email);

        foreach ($email as $mail) {
            $this->_bcc[] = $mail;
        }
    }

    /**
     * Add a file to the mail
     *
     * @param string $file - path to the file
     *
     * @return boolean
     */
    public function addAttachment($file)
    {
        if (!file_exists($file)) {
            return false;
        }

        $this->_attachments[] = $file;

        return true;
    }

    /**
     * Add a files to the mail
     *
     * @param array|string $files   - array with file paths eq:
     *                              addAttachments( array('path/file1.end', 'path/file2.end') )
     *                              addAttachments( 'path/file1.end' )
     */
    public function addAttachments($files)
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
