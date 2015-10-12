<?php

namespace QUI\Tests\Groups;

use QUI;

/**
 * Class GroupTest
 */
class GroupTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Create child test
     * @throws \QUI\Exception
     */
    public function testCreateChild()
    {
        $Root = QUI::getGroups()->firstChild();

        // exists phpunit group?
        $children = $Root->getChildren();

        foreach ($children as $groupData) {
            if ($groupData['name'] == 'phpunit') {
                QUI::getGroups()->get($groupData['id'])->delete();
            }
        }

        $PHPUnitGroup = $Root->createChild('phpunit');

        $this->assertGreaterThan(10, $PHPUnitGroup->getId());

        // test phunit group in db
        $result = QUI::getDataBase()->fetch(array(
            'from'  => QUI::getGroups()->Table(),
            'where' => array(
                'name' => 'phpunit'
            )
        ));

        $this->assertArrayHasKey(0, $result);
        $this->assertEquals($result[0]['name'], 'phpunit');





        // delete phpunit group
        $PHPUnitGroup->delete();
    }
}
