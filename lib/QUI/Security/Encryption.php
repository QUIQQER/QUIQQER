<?php

/**
 * This file contains QUI\Security\Encryption
 */

namespace QUI\Security;

use QUI;

/**
 * Class Encryption
 * @package QUI
 */
class Encryption
{
    /**
     * Encrypts data (Verschlüsselt Daten)
     *
     * @param string $data
     * @return string
     * @throws QUI\Exception
     */
    public static function encrypt($data)
    {
        $Config = QUI::getConfig('etc/conf.ini.php');

        $salt = $Config->getValue('globals', 'salt');
        $sl   = $Config->getValue('globals', 'saltlength');
        $iv   = $Config->getValue('openssl', 'iv');

        if ($iv === false) {
            $iv = \openssl_random_pseudo_bytes(\openssl_cipher_iv_length('aes-256-cbc'));

            QUI::getConfig('etc/conf.ini.php')->setValue(
                'openssl',
                'iv',
                \bin2hex($iv)
            );

            QUI::getConfig('etc/conf.ini.php')->setValue(
                'openssl',
                'length',
                \openssl_cipher_iv_length('aes-256-cbc')
            );

            QUI::getConfig('etc/conf.ini.php')->save();
        } else {
            $iv = \hex2bin($iv);
        }

        $data = \substr($data, $sl).\substr($data, 0, $sl);

        return \openssl_encrypt($data, 'aes-256-cbc', $salt, 0, $iv);
    }

    /**
     * Decrypts data (Entschlüsselt Daten)
     *
     * @param string $data
     * @return string
     */
    public static function decrypt($data)
    {
        $Config = QUI::getConfig('etc/conf.ini.php');
        $salt   = $Config->getValue('globals', 'salt');
        $sl     = $Config->getValue('globals', 'saltlength');

        if (!$Config->getValue('openssl', 'iv')) {
            self::encrypt('');
        }

        $iv = $Config->getValue('openssl', 'iv');
        $iv = \hex2bin($iv);

        $data = \openssl_decrypt($data, 'aes-256-cbc', $salt, 0, $iv);
        $data = \substr($data, -$sl).\substr($data, 0, -$sl);

        return $data;
    }
}
