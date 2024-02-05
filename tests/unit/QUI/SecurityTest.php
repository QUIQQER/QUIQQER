<?php

namespace QUI;

use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase
{
    public function testB64encryptAndDecrypt()
    {
        $sut = new Security();
        $stringToEncrypt = 'test_string';

        $encryptedString = $sut::b64encrypt($stringToEncrypt);
        $decryptedString = $sut::b64decrypt($encryptedString);

        $this->assertNotEquals($stringToEncrypt, $encryptedString);
        $this->assertEquals($stringToEncrypt, $decryptedString);
    }
}
