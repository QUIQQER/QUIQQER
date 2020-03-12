<?php

/**
 * This file contains QUI\Security\Encryption
 */

namespace QUI\Security;

use QUI;

/**
 * Class Encryption
 *
 * @package QUI
 * @todo verschiedenen Verschlüsselungsmethoden mit Fallback
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
            if (\strpos($iv, ',') !== false) {
                $iv = \explode(',', trim($iv))[0];
            }

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
     *
     * @throws \Exception
     */
    public static function decrypt($data)
    {
        if (empty($data)) {
            return $data;
        }

        $Config    = QUI::getConfig('etc/conf.ini.php');
        $salt      = $Config->getValue('globals', 'salt');
        $sl        = $Config->getValue('globals', 'saltlength');
        $givenData = $data;

        if (!$Config->getValue('openssl', 'iv')) {
            self::encrypt('');
        }

        $iv = $Config->getValue('openssl', 'iv');

        /**
         * multi key support
         */
        if (\strpos($iv, ',') !== false) {
            $ivs = \explode(',', trim($iv));
        } else {
            $ivs[] = trim($iv);
        }

        foreach ($ivs as $iv) {
            try {
                $iv   = @\hex2bin($iv);
                $data = \openssl_decrypt($givenData, 'aes-256-cbc', $salt, 0, $iv);

                if ($data !== false) {
                    return \substr($data, -$sl).\substr($data, 0, -$sl);
                }
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return $givenData;
    }
}
