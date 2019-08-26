<?php

namespace QUI\Tests\Users;

use QUI;

/**
 * \QUI\Users\Manager Test
 */
class ManagerTest extends \PHPUnit_Framework_TestCase
{

    public function testCreateChild()
    {
        $User = QUI::getUsers()->createChild('phpunit');

        $this->assertGreaterThan(10, $User->getId());

        $User->delete();
    }

}