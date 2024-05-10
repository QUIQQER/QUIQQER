<?php

/**
 * This file contains \QUI\Mail\Manager
 */

namespace QUI\Mail;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use QUI;

use function explode;
use function rtrim;
use function trim;

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
     */
    protected ?Queue $Queue = null;

    /**
     * Send a mail
     *
     * @throws QUI\Exception
     * @throws Exception
     */
    public function send(string $to, string $subject, string $body): void
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
     */
    public function getQueue(): Queue
    {
        if ($this->Queue === null) {
            $this->Queue = new Queue();
        }

        return $this->Queue;
    }

    /**
     * Return a Mailer object
     * Easier send, uses the mailer queue
     */
    public function getMailer(): Mailer
    {
        return new Mailer();
    }

    public function getPHPMailer(): PHPMailer
    {
        $config = QUI::conf('mail');
        $Mail = new PHPMailer(true);

        if (isset($config['SMTP']) && $config['SMTP']) {
            $Mail->Mailer = 'smtp';
            $Mail->Host = $config['SMTPServer'];
            $Mail->SMTPAuth = $config['SMTPAuth'];
            $Mail->Username = $config['SMTPUser'];
            $Mail->Password = $config['SMTPPass'];

            if (!empty($config['SMTPPort'])) {
                $Mail->Port = (int)$config['SMTPPort'];
            }

            if (!empty($config['SMTPDebug'])) {
                $Mail->SMTPDebug = (int)$config['SMTPDebug'];

                $Mail->Debugoutput = static function ($str, $level) {
                    Log::write(rtrim($str));
                };
            }

            if (isset($config['SMTPSecure'])) {
                switch ($config['SMTPSecure']) {
                    case "tls":
                    case "ssl":
                        $Mail->SMTPSecure = $config['SMTPSecure'];
                        break;
                }
            }

            /**
             * These options are set regardless of the "SMTPSecure" setting
             * because PHPMailer may try to establish a secure connection if the mail
             * server supports it regardless of the "SMTPSecure" setting.
             */
            $Mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => (int)$config['SMTPSecureSSL_verify_peer'],
                    'verify_peer_name' => (int)$config['SMTPSecureSSL_verify_peer_name'],
                    'allow_self_signed' => (int)$config['SMTPSecureSSL_allow_self_signed']
                ]
            ];
        }

        $Mail->From = $config['MAILFrom'];
        $Mail->FromName = $config['MAILFromText'];
        $Mail->CharSet = 'UTF-8';

        return $Mail;
    }
}
