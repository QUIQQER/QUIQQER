<?php

/**
 *
 * @author hen
 *
 */
namespace QUI\System\Console\Tools;

use QUI;

/**
 * MailQueue Console Manager
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class MailQueue extends QUI\System\Console\Tool
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->setName('quiqqer:mailqueue')
             ->setDescription('Functions for the mail queue');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        $this->writeLn('What would you execute?');
        $this->writeLn('- count : Number of mails in the queue');
        $this->writeLn('- send : sends the mails in the queue, step by step');
        $this->writeLn('- list : list the queue');

        $this->writeLn('Command: ');
        $comand = $this->readInput();

        $MailQueue = new QUI\Mail\Queue();

        switch ($comand) {
            case 'count':
                $this->writeLn(
                    $MailQueue->count().' mail(s) in the queue',
                    'red'
                );

                $this->resetColor();
                $this->writeLn('');
                break;

            case 'send':
                $this->writeLn('Send mail ...');
                $MailQueue->send();
                break;

            case 'list':
                $list = $MailQueue->getList();

                $this->writeLn('====== Mail Queue ======');

                foreach ($list as $entry) {
                    $to = '';
                    $mailTo = json_decode($entry['mailto'], true);

                    if (is_array($mailTo)) {
                        $to = key($mailTo);
                    }

                    $this->writeLn("#{$entry['id']} - {$to} - {$entry['subject']}");
                }

                $this->writeLn('');
                break;
        }
    }
}
