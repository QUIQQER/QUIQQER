<?php

namespace QUI\Tests\Security;

use QUI;

/**
 * \QUI\Security\Encryption Test
 */
class EncryptionTest extends \PHPUnit_Framework_TestCase
{

    public function testEncrypt()
    {
        $data   = 'my-test';
        $result = QUI\Security\Encryption::encrypt($data);

        $this->assertNotEquals($data, $result);
    }

    public function testDecrypt()
    {
        $data   = 'my-test';
        $result = QUI\Security\Encryption::encrypt($data);
        $result = QUI\Security\Encryption::decrypt($result);

        $this->assertEquals($data, $result);
    }
}
