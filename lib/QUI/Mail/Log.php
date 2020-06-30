<?php

namespace QUI\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use QUI;

/**
 * Class Log
 *
 * @package QUI\Mail
 */
class Log
{
    /**
     * @param $message
     */
    public static function write($message)
    {
        if ((int)QUI::conf('mail', 'logging') !== 1) {
            return;
        }

        $message = \date('Y-m-d H:i:s').' :: '.\trim($message).PHP_EOL;
        $file    = VAR_DIR.'log/mail-'.\date('Y-m-d').'.log';

        \error_log($message, 3, $file);
    }

    /**
     * @param PHPMailer $PhpMailer
     */
    public static function logSend($PhpMailer)
    {
        $addresses = self::parseAddresses($PhpMailer->getToAddresses());
        $bcc       = self::parseAddresses($PhpMailer->getBccAddresses());
        $cc        = self::parseAddresses($PhpMailer->getCcAddresses());

        if (!empty($addresses)) {
            QUI\Mail\Log::write('Send Mail to '.\implode(',', $addresses).' - '.$PhpMailer->Subject);
        }

        if (!empty($bcc)) {
            QUI\Mail\Log::write('Send Mail (BCC) to '.\implode(',', $bcc).' - '.$PhpMailer->Subject);
        }

        if (!empty($cc)) {
            QUI\Mail\Log::write('Send Mail (CC) to '.\implode(',', $cc).' - '.$PhpMailer->Subject);
        }
    }

    /**
     * @param $PhpMailer
     */
    public static function logDone($PhpMailer)
    {
        $addresses = self::parseAddresses($PhpMailer->getToAddresses());
        $bcc       = self::parseAddresses($PhpMailer->getBccAddresses());
        $cc        = self::parseAddresses($PhpMailer->getCcAddresses());

        if (!empty($addresses)) {
            QUI\Mail\Log::write('OK: '.\implode(',', $addresses).' - '.$PhpMailer->Subject);
        }

        if (!empty($bcc)) {
            QUI\Mail\Log::write('OK: (BCC) '.\implode(',', $bcc).' - '.$PhpMailer->Subject);
        }

        if (!empty($cc)) {
            QUI\Mail\Log::write('OK: (CC) '.\implode(',', $cc).' - '.$PhpMailer->Subject);
        }
    }

    /**
     * @param \Exception $Exception
     */
    public static function logException($Exception)
    {
        QUI\Mail\Log::write('ERROR Mail '.$Exception->getMessage());
    }

    /**
     * @param $addresses
     * @return array
     */
    protected static function parseAddresses($addresses)
    {
        return \array_map(function ($entry) {
            return $entry[0];
        }, $addresses);
    }
}
