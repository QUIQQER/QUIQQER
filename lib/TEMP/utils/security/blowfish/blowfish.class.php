<?php

/**
 * This file contains the Utils_Security_Encryption
 */

/**
 * include blowfish box
 */
include "blowfish.box.php";

/**
 * CBC"
 * @var Integer
 * @package com.pcsg.qui.utils.security.blowfish
 */
define("CBC", 1);

/**
 * PHP Implementation of Blowfish (www.php-einfach.de)
 *
 * Blowfish was designed in 1993 by Bruce Schneier as a fast,
 * free alternative to existing encryption algorithms.
 *
 * It is a 64-bit Feistel cipher, consisting of 16 rounds.
 * Blowfish has a key length of anywhere from 32 bits up to 448 bits.
 *
 * Blowfish uses a large key-dependent S-boxes, a complex key shedule and a 18-entry P-Box
 *
 * Blowfish is unpatented and license-free, and is available free for all uses.
 *
 * ***********************
 * Diese Implementierung darf frei verwendet werden, der Author uebernimmt keine
 * Haftung fuer die Richtigkeit, Fehlerfreiheit oder die Funktionsfaehigkeit dieses Scripts.
 * Benutzung auf eigene Gefahr.
 *
 * Ueber einen Link auf www.php-einfach.de wuerden wir uns freuen.
 *
 * ************************
 * Usage:
 * <?php
 * include("blowfish.class.php");
 *
 * $blowfish = new Blowfish("secret Key");
 * $cipher = $blowfish->Encrypt("Hello World"); //Encrypts 'Hello World'
 * $plain = $blowfish->Decrypt($cipher); //Decrypts the cipher text
 *
 * echo $plain;
 * ?>
 *
 * #######################################
 *
 * @author www.php-einfach.de
 * @author www.pcsg.de (Henning Leutz) - rewrite the class for php5
 *
 * @todo auslagern als package
 * @todo look for better class
 *
 * @package com.pcsg.qui.utils.security
 */

class Blowfish
{
    /**
     * pbox
     * @var unknown_type
     */
	protected $_pbox;

	/**
	 * sbox0
	 * @var unknown_type
	 */
	protected $_sbox0;

	/**
	 * sbox1
	 * @var unknown_type
	 */
	protected $_sbox1;

	/**
	 * sbox2
	 * @var unknown_type
	 */
	protected $_sbox2;

	/**
	 * sbox3
	 * @var unknown_type
	 */
	protected $_sbox3;

	/**
	 * constructor
	 * @param String $key - encrypt key
	 */
	public function __construct($key)
	{
		$this->KeySetup($key);
	}

	/**
	 * Encrypt a string
	 *
	 * @param String $text - string to be encrypted
	 */
	public function Encrypt($text)
	{
		$n = strlen($text);

		if ($n%8 != 0)
		{
			$lng = ($n+(8-($n%8)));
		} else
		{
			$lng = 0;
		}

		$text = str_pad($text, $lng, ' ');
		$text = $this->_str2long($text);

		// Initialization vector: IV
		if (CBC == 1)
		{
			$cipher[0][0] = time();
			$cipher[0][1] = (double)microtime()*1000000;
		}

		$a = 1;

		for ($i = 0; $i < count($text); $i+=2)
		{
			if (CBC == 1)
			{
	            //$text mit letztem Geheimtext XOR Verknuepfen
	            //$text is XORed with the previous ciphertext
	            $text[$i]   ^= $cipher[$a-1][0];
	            $text[$i+1] ^= $cipher[$a-1][1];
         	}

			$cipher[] = $this->block_encrypt($text[$i],$text[$i+1]);
			$a++;
		}

		$output = "";

		for ($i = 0; $i < count($cipher); $i++)
		{
			$output .= $this->_long2str($cipher[$i][0]);
			$output .= $this->_long2str($cipher[$i][1]);
		}

		return base64_encode($output);
	}

	/**
	 * Decrypt a string
	 *
	 * @param String $text - string to be decrypted
	 */
	public function Decrypt($text)
	{
		$output = '';
		$plain  = array();
		$cipher = $this->_str2long(base64_decode($text));

		if (CBC == 1)
		{
			$i = 2; //Message start at second block
		} else
		{
			$i = 0; //Message start at first block
		}

		for ($i; $i < count($cipher); $i+=2)
		{
			if (!isset($cipher[$i+1])) {
				$cipher[$i+1] = '';
			}

			$return = $this->block_decrypt($cipher[$i], $cipher[$i+1]);

         	// Xor Verknuepfung von $return und Geheimtext aus von den letzten beiden Bloecken
			// XORed $return with the previous ciphertext

			if (CBC == 1)
			{
            	$plain[] = array($return[0]^$cipher[$i-2],$return[1]^$cipher[$i-1]);
			} else
			{          //EBC Mode
            	$plain[] = $return;
			}
      	}

		for ($i = 0; $i < count($plain); $i++)
		{
			$output .= $this->_long2str($plain[$i][0]);
			$output .= $this->_long2str($plain[$i][1]);
		}

		return $output;
	}

    /**
     * Prepares the Key to ver / decrypt before
     *
     * @param String $key - blowfish key
     */
	public function KeySetup($key)
	{
		//global $pbox,$sbox0,$sbox1,$sbox2,$sbox3;

		$this->pbox  = $this->_pbox;
		$this->sbox0 = $this->_sbox0;
		$this->sbox1 = $this->_sbox1;
		$this->sbox2 = $this->_sbox2;
		$this->sbox3 = $this->_sbox3;

		if (!isset($key) || strlen($key) == 0)
		{
			$key = array(0);
		} else
		{
			if (strlen($key) % 4 == 0)
			{ //is the key a multiple of 4 bytes?
				$key = $this->_str2long($key);
			} else
			{
				$key = $this->_str2long(str_pad($key, strlen($key)+(4-strlen($key)%4), $key));
			}
		}

		# XOR Pbox1 with the first 32 bits of the key, XOR P2 with the second 32-bits of the key,
		for ($i=0; $i < count($this->pbox); $i++) {
			$this->pbox[$i] ^= $key[$i%count($key)];
		}

		$v[0] = 0x00000000;
		$v[1] = 0x00000000;

		//P-Box durch verschluesselte Nullbit Bloecke ersetzen. In der niechsten Runde das Resultat erneut verschluesseln
		//Encrypt Nullbit Blocks and replace the Pbox with the Chiffre. Next round, encrypt the result
		for ($i=0; $i<count($this->pbox); $i+=2)
		{
			$v = $this->block_encrypt($v[0],$v[1]);

			$this->pbox[$i]   = $v[0];
			$this->pbox[$i+1] = $v[1];
		}

      	// S-Box [0 bis 3] durch verschloesselte Bloecke ersetzen
		// Replace S-Box [0 to 3] entries with encrypted blocks
		for ($i=0; $i<count($this->sbox0); $i+=2)
		{
			$v = $this->block_encrypt($v[0],$v[1]);

			$this->sbox0[$i]   = $v[0];
			$this->sbox0[$i+1] = $v[1];
		}

		// S-Box1
		for ($i=0; $i<count($this->sbox1); $i+=2)
		{
			$v = $this->block_encrypt($v[0],$v[1]);

			$this->sbox1[$i]   = $v[0];
			$this->sbox1[$i+1] = $v[1];
		}

		// S-Box2
		for ($i=0; $i<count($this->sbox2); $i+=2)
		{
			$v = $this->block_encrypt($v[0],$v[1]);

			$this->sbox2[$i]   = $v[0];
			$this->sbox2[$i+1] = $v[1];
		}

		// S-Box3
		for ($i=0; $i<count($this->sbox3); $i+=2)
		{
			$v = $this->block_encrypt($v[0],$v[1]);

			$this->sbox3[$i]   = $v[0];
			$this->sbox3[$i+1] = $v[1];
		}
	}

	/**
	 * Block encryption
	 *
	 * @param Integer $v0
	 * @param Integer $v1
	 * @return array
	 */
	public function block_encrypt($v0, $v1)
	{
		if ($v0 < 0) {
			$v0 += 4294967296;
		}

		if ($v1 < 0) {
			$v1 += 4294967296;
		}

		for ($i = 0; $i < 16; $i++)
		{
			$temp = $v0 ^ $this->pbox[$i];

			if ($temp < 0) {
            	$temp += 4294967296;
			}

         	$v0 = ((($this->sbox0[($temp >> 24) & 0xFF]
				+ $this->sbox1[($temp >> 16) & 0xFF]
               ) ^ $this->sbox2[($temp >> 8) & 0xFF]
               ) + $this->sbox3[$temp & 0xFF]
               ) ^ $v1;

			$v1 = $temp;
		}

		$v1 = $this->_xor($v0, $this->pbox[16]);
		$v0 = $this->_xor($temp, $this->pbox[17]);

		return array($v0, $v1);
	}

	/**
	 * Block decryption
	 *
	 * @param Integer $v0
	 * @param Integer $v1
	 * @return array
	 */
	public function block_decrypt($v0, $v1)
	{
		if ($v0 < 0) {
            $v0 += 4294967296;
		}

		if ($v1 < 0) {
            $v1 += 4294967296;
		}

        for ($i = 17; $i > 1; $i--)
        {
			$temp = $v0 ^ $this->pbox[$i];

			if ($temp < 0) {
				$temp += 4294967296;
			}

            $v0 = ((($this->sbox0[($temp >> 24) & 0xFF]
                     + $this->sbox1[($temp >> 16) & 0xFF]
                    ) ^ $this->sbox2[($temp >> 8) & 0xFF]
                   ) + $this->sbox3[$temp & 0xFF]
                  ) ^ $v1;

            $v1 = $temp;
        }

        $v1 = $this->_xor($v0, $this->pbox[1]);
        $v0 = $this->_xor($temp, $this->pbox[0]);

        return array($v0, $v1);
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $l
     * @param unknown_type $r
     * @return float
     */
    protected function _xor($l, $r)
    {
        $x = (($l < 0) ? (float)($l + 4294967296) : (float)$l)
             ^ (($r < 0) ? (float)($r + 4294967296) : (float)$r);

        return (float)(($x < 0) ? $x + 4294967296 : $x);
    }

   /**
    * Convert a string into longinteger
    *
    * @param unknown_type $data
    * @return Integer
    */
	protected function _str2long($data)
	{
		$tmp = unpack('N*', $data);
		$j   = 0;

		$data_long = array();

		foreach ($tmp as $value) {
			$data_long[$j++] = $value;
		}

		return $data_long;
	}

   /**
    * Convert a longinteger into a string
    *
    * @param unknown_type $l
    * @return String
    */
	protected function _long2str($l)
	{
       return pack('N', $l);
	}
}

?>