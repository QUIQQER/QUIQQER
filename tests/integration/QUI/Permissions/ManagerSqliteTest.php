<?php

namespace QUI\Permissions;

use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\MockObject\MockObject;
use QUI;
use QUI\Groups\Group;
use QUI\Interfaces\Users\User as UserInterface;
use QUI\Projects\Media;
use QUI\Projects\Media\File;
use QUI\Projects\Project;
use QUI\Projects\Site;
use QUI\Users\User;
use QUI\Package\Package;

require_once __DIR__ . '/SqlitePermissionTestCase.php';
require_once __DIR__ . '/SqliteAccessibleManager.php';

class ManagerSqliteTest extends SqlitePermissionTestCase
{
    public function testSetupCreatesPermissionSchema(): void
    {
        $SchemaManager = $this->Connection->createSchemaManager();
        $OtherTable = new Table('other_names');
        $OtherTable->addColumn('name', 'string');
        $OtherTable->addIndex(['name'], 'name');
        $SchemaManager->createTable($OtherTable);

        $this->createPermissionSchema();

        $table = Manager::table();

        $this->assertTrue($SchemaManager->tablesExist([
            $table,
            $table . '2users',
            $table . '2groups',
            $table . '2sites',
            $table . '2projects',
            $table . '2media'
        ]));

        $PermissionTable = $SchemaManager->introspectTable($table);
        $permissionNameIndexes = array_filter(
            $PermissionTable->getIndexes(),
            static fn ($Index): bool => $Index->getColumns() === ['name']
        );

        $this->assertCount(1, $permissionNameIndexes);
        $this->assertFalse($PermissionTable->hasIndex('name'));
    }

    public function testSetupCanRunMoreThanOnce(): void
    {
        $this->createPermissionSchema();
        $this->createPermissionSchema();

        $this->assertTrue(
            $this->Connection->createSchemaManager()->tablesExist([Manager::table()])
        );
    }

    public function testConstructorLoadsExistingPermissionRows(): void
    {
        $this->createPermissionSchema();
        $this->insertPermission([
            'name' => 'test.permission',
            'type' => 'bool',
            'area' => 'global',
            'defaultvalue' => '1'
        ]);

        $Manager = new Manager();

        $this->assertSame(
            'bool',
            $Manager->getPermissionData('test.permission')['type']
        );
    }

    public function testAddPermissionInsertsAndUpdatesRow(): void
    {
        $this->createPermissionSchema();
        $Manager = new Manager();

        $Manager->addPermission([
            'name' => 'test.permission',
            'title' => ' First title ',
            'desc' => ' First description ',
            'type' => 'string',
            'area' => 'global',
            'src' => 'unit-test',
            'defaultvalue' => 'first'
        ]);
        $Manager->addPermission([
            'name' => 'test.permission',
            'title' => ' Updated title ',
            'desc' => ' Updated description ',
            'type' => 'int',
            'area' => 'user',
            'src' => 'unit-test',
            'defaultvalue' => '12'
        ]);

        $row = $this->Connection->fetchAssociative(
            'SELECT * FROM ' . Manager::table() . ' WHERE name = ?',
            ['test.permission']
        );

        $this->assertIsArray($row);
        $this->assertSame('Updated title', $row['title']);
        $this->assertSame('Updated description', $row['desc']);
        $this->assertSame('int', $row['type']);
        $this->assertSame('user', $row['area']);
        $this->assertSame('12', $row['defaultvalue']);
    }

    public function testGetPermissionsCombinesGroupDefaultsAndStoredValues(): void
    {
        $this->createPermissionSchema();
        $this->insertPermission([
            'name' => 'test.enabled',
            'type' => 'bool',
            'area' => 'global',
            'defaultvalue' => '0'
        ]);
        $this->insertPermission([
            'name' => 'test.limit',
            'type' => 'int',
            'area' => 'groups',
            'defaultvalue' => '5'
        ]);
        $this->Connection->insert(Manager::table() . '2groups', [
            'group_id' => 'group-uuid',
            'permissions' => json_encode([
                'test.enabled' => true,
                'test.limit' => 12
            ])
        ]);

        $Group = $this->createMock(Group::class);
        $Group->method('getUUID')->willReturn('group-uuid');

        $this->assertSame(
            [
                'test.enabled' => true,
                'test.limit' => 12
            ],
            (new Manager())->getPermissions($Group)
        );
    }

    public function testProjectPermissionsCanBeInsertedUpdatedAndRead(): void
    {
        $this->createPermissionSchema();
        $this->insertPermission([
            'name' => 'project.permission',
            'type' => 'string',
            'area' => 'project',
            'defaultvalue' => 'default'
        ]);

        $Project = $this->createProject();
        $Manager = new SqliteAccessibleManager();
        $EditUser = $this->createSuperUser();

        $Manager->setProjectPermissions(
            $Project,
            ['project.permission' => 'first'],
            $EditUser
        );
        $this->assertSame(
            ['project.permission' => 'first'],
            $Manager->getProjectPermissions($Project)
        );

        $Manager->setProjectPermissions(
            $Project,
            ['project.permission' => 'updated'],
            $EditUser
        );
        $this->assertSame(
            'updated',
            $this->Connection->fetchOne(
                'SELECT value FROM ' . Manager::table() . '2projects WHERE permission = ?',
                ['project.permission']
            )
        );
    }

    public function testSitePermissionsCanBeInsertedUpdatedReadAndRemoved(): void
    {
        $this->createPermissionSchema();
        $this->insertPermission([
            'name' => 'site.permission',
            'type' => 'users_and_groups',
            'area' => 'site'
        ]);

        $Project = $this->createProject();
        $Site = $this->createSite($Project);
        $Manager = new SqliteAccessibleManager();

        $Manager->setSitePermissions($Site, ['site.permission' => 'u12,g34']);
        $this->assertSame(
            ['site.permission' => 'u12,g34'],
            $Manager->getSitePermissions($Site)
        );

        $Manager->setSitePermissions($Site, ['site.permission' => 'u56']);
        $this->assertSame(
            'u56',
            $this->Connection->fetchOne(
                'SELECT value FROM ' . Manager::table() . '2sites WHERE permission = ?',
                ['site.permission']
            )
        );

        $Manager->removeSitePermissions($Site);
        $this->assertSame(
            0,
            (int)$this->Connection->fetchOne(
                'SELECT COUNT(*) FROM ' . Manager::table() . '2sites'
            )
        );
    }

    public function testMediaPermissionsCanBeInsertedUpdatedReadAndRemoved(): void
    {
        $this->createPermissionSchema();
        $this->insertPermission([
            'name' => 'media.permission',
            'type' => 'users',
            'area' => 'media'
        ]);

        $Project = $this->createProject();
        $Media = $this->createMock(Media::class);
        $Media->method('getProject')->willReturn($Project);
        $MediaItem = $this->createMock(File::class);
        $MediaItem->method('getId')->willReturn(42);
        $MediaItem->method('getMedia')->willReturn($Media);
        $MediaItem->method('getProject')->willReturn($Project);
        $Manager = new SqliteAccessibleManager();

        $Manager->setMediaPermissions($MediaItem, ['media.permission' => '12,34']);
        $this->assertSame(
            ['media.permission' => '12,34'],
            $Manager->getMediaPermissions($MediaItem)
        );

        $Manager->setMediaPermissions($MediaItem, ['media.permission' => '56']);
        $this->assertSame(
            '56',
            $this->Connection->fetchOne(
                'SELECT value FROM ' . Manager::table() . '2media WHERE permission = ?',
                ['media.permission']
            )
        );

        $Manager->removeMediaPermissions($MediaItem);
        $this->assertSame(
            0,
            (int)$this->Connection->fetchOne(
                'SELECT COUNT(*) FROM ' . Manager::table() . '2media'
            )
        );
    }

    public function testDataCacheIdsAreStableForDatabaseObjects(): void
    {
        $this->createPermissionSchema();
        $Project = $this->createProject();
        $Site = $this->createSite($Project);
        $MediaItem = $this->createMock(File::class);
        $MediaItem->method('getId')->willReturn(42);
        $MediaItem->method('getProject')->willReturn($Project);
        $Manager = new SqliteAccessibleManager();

        $this->assertSame(
            'permission2groups_test_en',
            $Manager->getTestDataCacheId($Project)
        );
        $this->assertSame(
            'permission2site_test_en_17',
            $Manager->getTestDataCacheId($Site)
        );
        $this->assertSame(
            'permission2media_test_en_42',
            $Manager->getTestDataCacheId($MediaItem)
        );
    }

    public function testUserPermissionsCanBeInsertedUpdatedAndRead(): void
    {
        $this->createPermissionSchema();
        $this->insertPermission([
            'name' => 'user.permission',
            'type' => 'int',
            'area' => 'user',
            'defaultvalue' => '5'
        ]);

        $User = $this->createMock(User::class);
        $User->method('getUUID')->willReturn('user-uuid');
        $Manager = new SqliteAccessibleManager();
        $EditUser = $this->createSuperUser();

        $Manager->setPermissions(
            $User,
            ['user.permission' => '12'],
            $EditUser
        );
        $this->assertSame(
            ['user.permission' => 12],
            $Manager->getPermissions($User)
        );

        $Manager->setPermissions(
            $User,
            ['user.permission' => '27'],
            $EditUser
        );
        $this->assertSame(
            ['user.permission' => 27],
            $Manager->getUserPermissionData($User)
        );
    }

    public function testGroupPermissionsCanBeInsertedUpdatedAndRead(): void
    {
        $this->createPermissionSchema();
        $this->insertPermission([
            'name' => 'group.permission',
            'type' => 'string',
            'area' => 'groups',
            'defaultvalue' => 'default'
        ]);

        $Group = $this->createMock(Group::class);
        $Group->method('getUUID')->willReturn('group-uuid');
        $Manager = new SqliteAccessibleManager();
        $EditUser = $this->createSuperUser();

        $Manager->setPermissions(
            $Group,
            ['group.permission' => 'first'],
            $EditUser
        );
        $this->assertSame(
            ['group.permission' => 'first'],
            $Manager->getPermissions($Group)
        );

        $Manager->setPermissions(
            $Group,
            ['group.permission' => 'updated'],
            $EditUser
        );
        $this->assertSame(
            ['group.permission' => 'updated'],
            $Manager->getCompletePermissionList($Group)
        );
    }

    public function testUserPermissionReturnsDirectStoredValue(): void
    {
        $this->createPermissionSchema();
        $this->insertPermission([
            'name' => 'user.permission',
            'type' => 'string',
            'area' => 'user'
        ]);
        $this->Connection->insert(Manager::table() . '2users', [
            'user_id' => 'user-uuid',
            'permissions' => json_encode(['user.permission' => 'direct'])
        ]);

        $User = $this->createMock(User::class);
        $User->method('getUUID')->willReturn('user-uuid');
        $User->method('getGroups')->willReturn([]);

        $this->assertSame(
            'direct',
            (new SqliteAccessibleManager())->getUserPermission(
                $User,
                'user.permission'
            )
        );
    }

    public function testDeletePermissionOnlyDeletesUserPermissions(): void
    {
        $this->createPermissionSchema();
        $this->insertPermission([
            'name' => 'user.created',
            'type' => 'bool',
            'area' => 'global',
            'src' => 'user'
        ]);

        $Manager = new SqliteAccessibleManager();
        $Manager->deletePermission('user.created');

        $this->assertFalse(
            $this->Connection->fetchOne(
                'SELECT name FROM ' . Manager::table() . ' WHERE name = ?',
                ['user.created']
            )
        );
    }

    public function testDeletePermissionRejectsPackagePermission(): void
    {
        $this->createPermissionSchema();
        $this->insertPermission([
            'name' => 'package.permission',
            'type' => 'bool',
            'area' => 'global',
            'src' => 'quiqqer/core'
        ]);

        $this->expectException(Exception::class);

        (new SqliteAccessibleManager())->deletePermission('package.permission');
    }

    public function testDeletePermissionRejectsUnknownPermission(): void
    {
        $this->createPermissionSchema();
        $this->expectException(Exception::class);

        (new SqliteAccessibleManager())->deletePermission('unknown.permission');
    }

    public function testDeletePermissionsFromPackageRemovesOnlyMatchingRows(): void
    {
        $this->createPermissionSchema();
        $this->insertPermission([
            'name' => 'package.permission',
            'type' => 'bool',
            'area' => 'global',
            'src' => 'test/package'
        ]);
        $this->insertPermission([
            'name' => 'other.permission',
            'type' => 'bool',
            'area' => 'global',
            'src' => 'other/package'
        ]);

        $Package = $this->createMock(Package::class);
        $Package->method('getName')->willReturn('test/package');
        $Manager = new SqliteAccessibleManager();
        $Manager->deletePermissionsFromPackage($Package);

        $this->assertSame(
            ['other.permission'],
            $this->Connection->fetchFirstColumn(
                'SELECT name FROM ' . Manager::table() . ' ORDER BY name'
            )
        );
    }

    public function testRightParamsFromGroupNormalizesStoredTypes(): void
    {
        $this->createPermissionSchema();
        foreach (
            [
                ['right.int', 'int'],
                ['right.groups', 'groups'],
                ['right.array', 'array'],
                ['right.string', 'string'],
                ['right.bool', 'bool']
            ] as [$name, $type]
        ) {
            $this->insertPermission([
                'name' => $name,
                'type' => $type,
                'area' => 'groups'
            ]);
        }

        $values = [
            'right.int' => '12',
            'right.groups' => ['g12', 'g34'],
            'right.array' => ['value', new \stdClass()],
            'right.string' => ' text ',
            'right.bool' => 1
        ];
        $Group = $this->createMock(Group::class);
        $Group->method('existsRight')->willReturn(true);
        $Group->method('hasPermission')->willReturnCallback(
            static fn (string $permission): mixed => $values[$permission]
        );

        $this->assertSame(
            [
                'right.int' => 12,
                'right.groups' => '12,34',
                'right.array' => ['value'],
                'right.string' => ' text ',
                'right.bool' => true
            ],
            (new SqliteAccessibleManager())->getRightParamsFromGroup($Group)
        );
    }

    /**
     * @param array<string, mixed> $permission
     */
    private function insertPermission(array $permission): void
    {
        $this->Connection->insert(Manager::table(), [
            'name' => $permission['name'],
            'type' => $permission['type'],
            'area' => $permission['area'],
            'title' => $permission['title'] ?? '',
            'desc' => $permission['desc'] ?? '',
            'src' => $permission['src'] ?? 'unit-test',
            'defaultvalue' => $permission['defaultvalue'] ?? ''
        ]);
    }

    private function createProject(): Project&MockObject
    {
        $Project = $this->createMock(Project::class);
        $Project->method('getName')->willReturn('test');
        $Project->method('getLang')->willReturn('en');

        return $Project;
    }

    private function createSite(Project $Project): Site&MockObject
    {
        $Site = $this->createMock(Site::class);
        $Site->method('getProject')->willReturn($Project);
        $Site->method('getId')->willReturn(17);

        return $Site;
    }

    private function createSuperUser(): UserInterface&MockObject
    {
        $User = $this->createMock(User::class);
        $User->method('isSU')->willReturn(true);

        return $User;
    }
}
