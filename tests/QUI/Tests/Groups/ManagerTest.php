<?php

namespace QUI\Tests\Groups;

use QUI;

/**
 * \QUI\Groups\Manager Test
 */
class ManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * \QUI\Groups\Manager->firstChild()
     */
    public function testFirstChild()
    {
        $Root = QUI::getGroups()->firstChild();
        $this->assertGreaterThan(10, $Root->getId());
    }

}