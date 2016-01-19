<?php

/**
 * This file contains \QUI\Mail\Manager
 */

namespace QUI\Mail;

use QUI;

/**
 * Mail Manager
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Manager
{
    /**
     * mail queue manager
     *
     * @var \QUI\Mail\Queue
     */
    protected $Queue = null;

    /**
     * Send a mail
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     */
    public function send($to, $subject, $body)
    {
        $Mailer = new Mailer();

        $to = trim($to);
        $to = explode(',', $to);

        foreach ($to as $mail) {
            $Mailer->addRecipient(trim($mail));
        }

        $Mailer->setSubject($subject);
        $Mailer->setBody($body);
        $Mailer->send();
    }

    /**
     * Return the mail queue manager
     *
     * @return \QUI\Mail\Queue
     */
    public function getQueue()
    {
        if ($this->Queue === null) {
            $this->Queue = new Queue();
        }

        return $this->Queue;
    }

    /**
     * Return a Mailer object
     * Easier send, uses the mailer queue
     *
     * @return Mailer
     */
    public function getMailer()
    {
        return new Mailer();
    }

    /**
     * Return a PHPMailer object
     *
     * @return \PHPMailer
     */
    public function getPHPMailer()
    {
        $config = QUI::conf('mail');
        $Mail   = new \PHPMailer(true);

        if ($config['SMTP'] == true) {
            $Mail->Mailer   = 'smtp';
            $Mail->Host     = $config['SMTPServer'];
            $Mail->SMTPAuth = $config['SMTPAuth'];
            $Mail->Username = $config['SMTPUser'];
            $Mail->Password = $config['SMTPPass'];

            if (isset($config['SMTPPort'])
                && !empty($config['SMTPPort'])
            ) {
                $Mail->Port = (int)$config['SMTPPort'];
            }

            if (isset($config['SMTPDebug'])
                && !empty($config['SMTPDebug'])
            ) {
                $Mail->SMTPDebug = 2;
            }

            if (isset($config['SMTPSecure'])) {
                switch ($config['SMTPSecure']) {
                    case "ssl":
                        $Mail->SMTPSecure = $config['SMTPSecure'];
QUI\System\Log::writeRecursive($config);
                        $Mail->SMTPOptions = array(
                            'ssl' => array(
                                'verify_peer' => (int)$config['SMTPSecureSSL_verify_peer'],
                                'verify_peer_name' => (int)$config['SMTPSecureSSL_verify_peer_name'],
                                'allow_self_signed' => (int)$config['SMTPSecureSSL_allow_self_signed']
                            )
                        );
                        break;
                    case "tls":
                        $Mail->SMTPSecure = $config['SMTPSecure'];
                        break;
                }
            }
        }

        $Mail->From     = $config['MAILFrom'];
        $Mail->FromName = $config['MAILFromText'];
        $Mail->CharSet  = 'UTF-8';

        return $Mail;
    }
}
