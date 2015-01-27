<?php

/**
 * This file contains \QUI\Mail\Manager
 */

namespace QUI\Mail;
use QUI\System\Log;

/**
 * Mail Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 */

class Manager
{
    /**
     * mail queue manager
     * @var \QUI\Mail\Queue
     */
    protected $_Queue = null;

    /**
     * Send a mail
     *
     * @param String $to
     * @param String $subject
     * @param String $body
     */
    public function send($to, $subject, $body)
    {
        $Mailer = new Mailer();

        $Mailer->addRecipient( $to );
        $Mailer->setSubject( $subject );
        $Mailer->setBody( $body );

        $Mailer->send();
    }

    /**
     * Return the mail queue manager
     *
     * @return \QUI\Mail\Queue
     */
    public function getQueue()
    {
        if ( is_null( $this->_Queue ) ) {
            $this->_Queue = new Queue();
        }

        return $this->_Queue;
    }

    /**
     * Return the PHPMailer object
     *
     * @return \PHPMailer
     */
    public function getPHPMailer()
    {
        $config = \QUI::conf( 'mail' );
        $Mail   = new \PHPMailer();

        if ( $config['SMTP'] == true )
        {
            //$this->_mail->IsSMTP();
            $Mail->Mailer   = 'smtp';
            $Mail->Host     = $config['SMTPServer'];
            $Mail->SMTPAuth = $config['SMTPAuth'];
            $Mail->Username = $config['SMTPUser'];
            $Mail->Password = $config['SMTPPass'];
        }

        $Mail->From     = $config['MAILFrom'];
        $Mail->FromName = $config['MAILFromText'];
        $Mail->CharSet  = 'UTF-8';

        return $Mail;
    }
}
