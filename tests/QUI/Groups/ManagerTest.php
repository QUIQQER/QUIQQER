<?php

/**
 * Class GroupTest
 */
class ManagerTest extends PHPUnit_Framework_TestCase
{

    public function testFirstChild()
    {
        $Root = QUI::getGroups()->firstChild();
        $this->assertGreaterThan(10, $Root->getId());
    }

}