<?php

namespace QUI\Mail;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use QUI;

use function array_map;
use function date;
use function error_log;
use function implode;
use function trim;

class Log
{
    public static function logSend(PHPMailer $PhpMailer): void
    {
        $addresses = self::parseAddresses($PhpMailer->getToAddresses());
        $bcc = self::parseAddresses($PhpMailer->getBccAddresses());
        $cc = self::parseAddresses($PhpMailer->getCcAddresses());

        if (!empty($addresses)) {
            QUI\Mail\Log::write('Send Mail to ' . implode(',', $addresses) . ' - ' . $PhpMailer->Subject);
        }

        if (!empty($bcc)) {
            QUI\Mail\Log::write('Send Mail (BCC) to ' . implode(',', $bcc) . ' - ' . $PhpMailer->Subject);
        }

        if (!empty($cc)) {
            QUI\Mail\Log::write('Send Mail (CC) to ' . implode(',', $cc) . ' - ' . $PhpMailer->Subject);
        }
    }

    protected static function parseAddresses($addresses): array
    {
        return array_map(static function ($entry) {
            return $entry[0];
        }, $addresses);
    }

    public static function write($message): void
    {
        if ((int)QUI::conf('mail', 'logging') !== 1) {
            return;
        }

        $message = date('Y-m-d H:i:s') . ' :: ' . trim($message) . PHP_EOL;
        $file = VAR_DIR . 'log/mail-' . date('Y-m-d') . '.log';

        error_log($message, 3, $file);
    }

    public static function logDone($PhpMailer): void
    {
        $addresses = self::parseAddresses($PhpMailer->getToAddresses());
        $bcc = self::parseAddresses($PhpMailer->getBccAddresses());
        $cc = self::parseAddresses($PhpMailer->getCcAddresses());

        if (!empty($addresses)) {
            QUI\Mail\Log::write('OK: ' . implode(',', $addresses) . ' - ' . $PhpMailer->Subject);
        }

        if (!empty($bcc)) {
            QUI\Mail\Log::write('OK: (BCC) ' . implode(',', $bcc) . ' - ' . $PhpMailer->Subject);
        }

        if (!empty($cc)) {
            QUI\Mail\Log::write('OK: (CC) ' . implode(',', $cc) . ' - ' . $PhpMailer->Subject);
        }
    }

    public static function logException(Exception $Exception): void
    {
        QUI\Mail\Log::write('ERROR Mail ' . $Exception->getMessage());
    }
}
