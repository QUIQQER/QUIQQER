<?php

namespace QUI\Users;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Groups\Manager as GroupManager;
use ReflectionClass;
use ReflectionProperty;

class UserTest extends TestCase
{
    private ?GroupManager $originalGroupManager;

    protected function setUp(): void
    {
        $this->originalGroupManager = QUI::$Groups;
    }

    protected function tearDown(): void
    {
        QUI::$Groups = $this->originalGroupManager;
    }

    public function testGetGroupsDoesNotResolveEmptyGroupIds(): void
    {
        $GroupManager = $this->createMock(GroupManager::class);
        $GroupManager->expects(self::never())->method('get');
        QUI::$Groups = $GroupManager;

        $Reflection = new ReflectionClass(User::class);
        $User = $Reflection->newInstanceWithoutConstructor();
        $GroupsProperty = new ReflectionProperty(User::class, 'groups');
        $GroupsProperty->setValue($User, ', ,');

        self::assertSame([], $User->getGroups());
    }
}
