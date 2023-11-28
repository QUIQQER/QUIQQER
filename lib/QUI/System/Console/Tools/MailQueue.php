<?php

/**
 *
 * @author hen
 *
 */

namespace QUI\System\Console\Tools;

use League\CLImate\CLImate;
use QUI;

use function implode;
use function is_numeric;
use function json_decode;

use const PHP_EOL;

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
            ->setDescription(
                'The tool provides a detailed view of the emails in the queue, ' .
                'including status information, recipient and subject.'
            )
            ->addArgument('count', 'Number of mails in the queue', false, true)
            ->addArgument('send', 'Sends the mails in the queue', false, true)
            ->addArgument('list', 'List mails in queue', false, true)
            ->addArgument('delete', 'Deletes a mail in the queue [--id=]', false, true)
            ->addArgument('clear', 'Deletes the complete queue', false, true);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        $MailQueue = new QUI\Mail\Queue();

        if ($this->getArgument('count')) {
            $this->writeLn($MailQueue->count() . ' mail(s) in the queue');
            $this->writeLn('');
            return;
        }

        if ($this->getArgument('send')) {
            $this->writeLn('Send mail ...');
            $MailQueue->send();
            return;
        }

        if ($this->getArgument('clear')) {
            $this->writeLn(
                'Attention: You are about to delete the entire mail queue. ' .
                'Please note that this process is irreversible and cannot be undone.' .
                PHP_EOL .
                'Are you sure you want to delete the entire queue?' .
                PHP_EOL .
                'If so, please confirm with "YES": ',
                'red'
            );

            $this->resetColor();
            $input = $this->readInput();

            if ($input === 'YES') {
                QUI::getDataBase()->fetchSQL(
                    'TRUNCATE ' . QUI\Mail\Queue::table() . ';'
                );

                $this->writeLn('The queue has been successfully cleared');
                $this->writeLn();
            } else {
                $this->writeLn('The queue has not been cleared', 'yellow');
                $this->writeLn();
            }

            return;
        }

        if ($this->getArgument('delete')) {
            $mailId = $this->getArgument('id');

            if (empty($mailId)) {
                $this->writeLn('please enter an email id: ');
                $mailId = $this->readInput();
            }

            if (empty($mailId) && !is_numeric($mailId)) {
                $this->writeLn('No mail ID specified');
                return;
            }

            try {
                QUI::getDataBase()->delete(
                    QUI\Mail\Queue::table(),
                    ['id' => $mailId]
                );

                $this->writeLn('Mail was successfully deleted');
                $this->writeLn();
            } catch (\Exception $exception) {
                $this->writeLn($exception->getMessage(), 'red');
                $this->resetColor();
                $this->writeLn();
            }

            return;
        }

        if ($this->getArgument('list')) {
            $list = $MailQueue->getList();

            $this->writeLn('Mail Queue:');
            $this->writeLn('');
            $this->writeLn('');

            $Climate = new CLImate();
            $data = [
                [
                    'ID',
                    'To',
                    'Subject',
                    'Status',
                    'Last send',
                    'Retries'
                ]
            ];

            foreach ($list as $entry) {
                $mailto = json_decode($entry['mailto'], true);
                $mailto = implode(',', $mailto);

                switch ((int)$entry['status']) {
                    case QUI\Mail\Queue::STATUS_ADDED:
                        $status = 'added';
                        break;
                    case QUI\Mail\Queue::STATUS_SENT:
                        $status = 'sent';
                        break;
                    case QUI\Mail\Queue::STATUS_SENDING:
                        $status = 'sending';
                        break;
                    case QUI\Mail\Queue::STATUS_ERROR:
                        $status = 'error';
                        break;

                    default:
                        $status = 'unknown';
                }

                $data[] = [
                    $entry['id'],
                    $mailto,
                    $entry['subject'],
                    $status,
                    QUI::getLocale()->formatDate($entry['lastsend']),
                    $entry['retry'],
                ];
            }

            $Climate->table($data);
            $this->writeLn();
            return;
        }

        $this->outputHelp();
    }
}
