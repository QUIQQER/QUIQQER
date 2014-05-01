<?php

/**
 * this file contains \QUI\Security
 */

namespace QUI;

/**
 * Main Security class
 * For quiqqer system specific de / encryption
 *
 * @author www.pcsg.de (Henning Leutz)
 */

class Security
{
    /**
     * Verschlüsselung auf Basis des QUIQQER Salt
     *
     * @param String $str
     * @return String
     */
    static function encrypt($str)
    {
        if ( empty( $str ) ) {
            return '';
        }

        $Cipher = new \Crypt_Blowfish();
        $Cipher->setKey( \QUI::conf( 'globals', 'salt' ) );

        return $Cipher->encrypt( $str );
    }

    /**
     * Entschlüsselung auf Basis des CMS Salt
     *
     * @param String $str
     * @return String
     */
    static function decrypt($str)
    {
        if ( empty( $str ) ) {
            return '';
        }

        $Cipher = new \Crypt_Blowfish();
        $Cipher->setKey( \QUI::conf( 'globals', 'salt' ) );

        return $Cipher->decrypt( $str );
    }

    /**
     * Encrypted a password base64
     *
     * @param String $pass		- String to encrpyted
     * @param Integer $switch	- where to split
     *
     * @return String
     */
    static function b64encrypt($pass, $switch=3)
    {
        // Passwort drehn
        $newpass = substr($pass, 3) . substr($pass, 0, 3);

        return base64_encode($newpass);
    }

    /**
     * Decrypt a base64 password
     *
     * @param String $pass	  - String to decrypt
     * @param Integer $switch - where to split
     *
     * @return String
     */
    static function b64decrypt($pass, $switch=3)
    {
        $newpass = base64_decode($pass);

        return substr($newpass, -3) . substr($newpass, 0, -3);
    }
}