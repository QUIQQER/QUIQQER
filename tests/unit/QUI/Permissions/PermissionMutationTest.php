<?php

namespace QUI\Permissions;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Groups\Group;
use QUI\Interfaces\Users\User as UserInterface;
use QUI\Projects\Media\File;
use QUI\Projects\Project;
use QUI\Projects\Site;
use QUI\Users\User;
use ReflectionProperty;

class PermissionMutationTest extends TestCase
{
    private const PERMISSION = 'test.permission';

    private ?UserInterface $previousPermissionUser;
    private ?Manager $previousPermissionManager;

    protected function setUp(): void
    {
        $UserProperty = new ReflectionProperty(Permission::class, 'User');

        $this->previousPermissionUser = $UserProperty->getValue();
        $this->previousPermissionManager = QUI::$Rights;
    }

    protected function tearDown(): void
    {
        $UserProperty = new ReflectionProperty(Permission::class, 'User');
        $UserProperty->setValue(null, $this->previousPermissionUser);

        QUI::$Rights = $this->previousPermissionManager;
    }

    public function testAddUserToSitePermission(): void
    {
        $User = $this->createUser('user-uuid');
        $Site = $this->createMock(Site::class);
        $Manager = $this->createManager();
        $Manager->method('getSitePermissions')->willReturn([
            self::PERMISSION => 'uexisting'
        ]);
        $Manager->expects($this->once())
            ->method('setSitePermissions')
            ->with(
                $Site,
                [self::PERMISSION => 'uexisting,uuser-uuid'],
                null
            );
        QUI::$Rights = $Manager;

        $this->assertTrue(
            Permission::addUserToSitePermission(
                $User,
                $Site,
                self::PERMISSION
            )
        );
    }

    public function testAddGroupToSitePermission(): void
    {
        $Group = $this->createGroup('group-uuid');
        $Site = $this->createMock(Site::class);
        $Manager = $this->createManager();
        $Manager->method('getSitePermissions')->willReturn([
            self::PERMISSION => ''
        ]);
        $Manager->expects($this->once())
            ->method('setSitePermissions')
            ->with(
                $Site,
                [self::PERMISSION => 'ggroup-uuid'],
                null
            );
        QUI::$Rights = $Manager;

        $this->assertTrue(
            Permission::addGroupToSitePermission(
                $Group,
                $Site,
                self::PERMISSION
            )
        );
    }

    public function testRemoveUserFromSitePermission(): void
    {
        $User = $this->createUser('user-uuid');
        $Site = $this->createMock(Site::class);
        $Manager = $this->createManager();
        $Manager->method('getSitePermissions')->willReturn([
            self::PERMISSION => 'uuser-uuid,gother'
        ]);
        $Manager->expects($this->once())
            ->method('setSitePermissions')
            ->with(
                $Site,
                [self::PERMISSION => 'gother'],
                null
            );
        QUI::$Rights = $Manager;

        $this->assertTrue(
            Permission::removeUserFromSitePermission(
                $User,
                $Site,
                self::PERMISSION
            )
        );
    }

    public function testRemoveGroupFromSitePermission(): void
    {
        $Group = $this->createGroup('group-uuid');
        $Site = $this->createMock(Site::class);
        $Manager = $this->createManager();
        $Manager->method('getSitePermissions')->willReturn([
            self::PERMISSION => 'ggroup-uuid,uother'
        ]);
        $Manager->expects($this->once())
            ->method('setSitePermissions')
            ->with(
                $Site,
                [self::PERMISSION => 'uother'],
                null
            );
        QUI::$Rights = $Manager;

        $this->assertTrue(
            Permission::removeGroupFromSitePermission(
                $Group,
                $Site,
                self::PERMISSION
            )
        );
    }

    public function testAddUserToProjectPermission(): void
    {
        $User = $this->createUser('user-uuid');
        $EditUser = $this->createUser('editor-uuid', true);
        $Project = $this->createMock(Project::class);
        $Manager = $this->createManager();
        $Manager->method('getProjectPermissions')->willReturn([
            self::PERMISSION => 'uexisting'
        ]);
        $Manager->expects($this->once())
            ->method('setProjectPermissions')
            ->with(
                $Project,
                [self::PERMISSION => 'uexisting,uuser-uuid'],
                $EditUser
            );
        QUI::$Rights = $Manager;

        $this->assertTrue(
            Permission::addUserToProjectPermission(
                $User,
                $Project,
                self::PERMISSION,
                $EditUser
            )
        );
    }

    public function testAddGroupToProjectPermission(): void
    {
        $Group = $this->createGroup('group-uuid');
        $EditUser = $this->createUser('editor-uuid', true);
        $Project = $this->createMock(Project::class);
        $Manager = $this->createManager();
        $Manager->method('getProjectPermissions')->willReturn([
            self::PERMISSION => ''
        ]);
        $Manager->expects($this->once())
            ->method('setProjectPermissions')
            ->with(
                $Project,
                [self::PERMISSION => 'ggroup-uuid'],
                $EditUser
            );
        QUI::$Rights = $Manager;

        $this->assertTrue(
            Permission::addGroupToProjectPermission(
                $Group,
                $Project,
                self::PERMISSION,
                $EditUser
            )
        );
    }

    public function testRemoveUserFromProjectPermission(): void
    {
        $User = $this->createUser('user-uuid');
        $Project = $this->createMock(Project::class);
        $Manager = $this->createManager();
        $Manager->method('getProjectPermissions')->willReturn([
            self::PERMISSION => 'uuser-uuid,gother'
        ]);
        $Manager->expects($this->once())
            ->method('setProjectPermissions')
            ->with(
                $Project,
                [self::PERMISSION => 'gother']
            );
        Permission::setUser($this->createUser('editor-uuid', true));
        QUI::$Rights = $Manager;

        Permission::removeUserFromProjectPermission(
            $User,
            $Project,
            self::PERMISSION
        );

        $this->addToAssertionCount(1);
    }

    public function testRemoveGroupFromProjectPermission(): void
    {
        $Group = $this->createGroup('group-uuid');
        $Project = $this->createMock(Project::class);
        $Manager = $this->createManager();
        $Manager->method('getProjectPermissions')->willReturn([
            self::PERMISSION => 'ggroup-uuid,uother'
        ]);
        $Manager->expects($this->once())
            ->method('setProjectPermissions')
            ->with(
                $Project,
                [self::PERMISSION => 'uother']
            );
        Permission::setUser($this->createUser('editor-uuid', true));
        QUI::$Rights = $Manager;

        $this->assertTrue(
            Permission::removeGroupFromProjectPermission(
                $Group,
                $Project,
                self::PERMISSION
            )
        );
    }

    public function testAddUserToMediaPermission(): void
    {
        $User = $this->createUser('user-uuid');
        $MediaItem = $this->createMock(File::class);
        $Manager = $this->createManager();
        $Manager->method('getMediaPermissions')->willReturn([
            self::PERMISSION => 'uexisting'
        ]);
        $Manager->expects($this->once())
            ->method('setMediaPermissions')
            ->with(
                $MediaItem,
                [self::PERMISSION => 'uexisting,uuser-uuid'],
                null
            );
        QUI::$Rights = $Manager;

        $this->assertTrue(
            Permission::addUserToMediaPermission(
                $User,
                $MediaItem,
                self::PERMISSION
            )
        );
    }

    public function testAddGroupToMediaPermission(): void
    {
        $Group = $this->createGroup('group-uuid');
        $MediaItem = $this->createMock(File::class);
        $Manager = $this->createManager();
        $Manager->method('getMediaPermissions')->willReturn([
            self::PERMISSION => ''
        ]);
        $Manager->expects($this->once())
            ->method('setMediaPermissions')
            ->with(
                $MediaItem,
                [self::PERMISSION => 'ggroup-uuid'],
                null
            );
        QUI::$Rights = $Manager;

        $this->assertTrue(
            Permission::addGroupToMediaPermission(
                $Group,
                $MediaItem,
                self::PERMISSION
            )
        );
    }

    public function testRemoveUserFromMediaPermission(): void
    {
        $User = $this->createUser('user-uuid');
        $MediaItem = $this->createMock(File::class);
        $Manager = $this->createManager();
        $Manager->method('getMediaPermissions')->willReturn([
            self::PERMISSION => 'uuser-uuid,gother'
        ]);
        $Manager->expects($this->once())
            ->method('setMediaPermissions')
            ->with(
                $MediaItem,
                [self::PERMISSION => 'gother'],
                null
            );
        QUI::$Rights = $Manager;

        $this->assertTrue(
            Permission::removeUserFromMediaPermission(
                $User,
                $MediaItem,
                self::PERMISSION
            )
        );
    }

    public function testRemoveGroupFromMediaPermission(): void
    {
        $Group = $this->createGroup('group-uuid');
        $MediaItem = $this->createMock(File::class);
        $Manager = $this->createManager();
        $Manager->method('getMediaPermissions')->willReturn([
            self::PERMISSION => 'ggroup-uuid,uother'
        ]);
        $Manager->expects($this->once())
            ->method('setMediaPermissions')
            ->with(
                $MediaItem,
                [self::PERMISSION => 'uother'],
                null
            );
        QUI::$Rights = $Manager;

        $this->assertTrue(
            Permission::removeGroupFromMediaPermission(
                $Group,
                $MediaItem,
                self::PERMISSION
            )
        );
    }

    private function createUser(string $uuid, bool $isSuperUser = false): User&MockObject
    {
        $User = $this->createMock(User::class);
        $User->method('getUUID')->willReturn($uuid);
        $User->method('isSU')->willReturn($isSuperUser);

        return $User;
    }

    private function createGroup(string $uuid): Group&MockObject
    {
        $Group = $this->createMock(Group::class);
        $Group->method('getUUID')->willReturn($uuid);

        return $Group;
    }

    private function createManager(): Manager&MockObject
    {
        return $this->createMock(Manager::class);
    }
}
