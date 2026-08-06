<?php

namespace QUI\Permissions;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Groups\Group;
use QUI\Projects\Media\File;
use QUI\Projects\Project;
use QUI\Projects\Site;
use ReflectionClass;
use stdClass;

require_once __DIR__ . '/AccessibleManager.php';

class ManagerTest extends TestCase
{
    #[DataProvider('permissionTypeProvider')]
    public function testParseType(string $type, string $expected): void
    {
        $this->assertSame($expected, Manager::parseType($type));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function permissionTypeProvider(): array
    {
        return [
            'bool' => ['bool', 'bool'],
            'string' => ['string', 'string'],
            'int' => ['int', 'int'],
            'array' => ['array', 'array'],
            'group' => ['group', 'group'],
            'groups' => ['groups', 'groups'],
            'user' => ['user', 'user'],
            'users' => ['users', 'users'],
            'users and groups' => ['users_and_groups', 'users_and_groups'],
            'unknown falls back to bool' => ['unknown', 'bool']
        ];
    }

    #[DataProvider('permissionAreaProvider')]
    public function testParseArea(string $area, string $expected): void
    {
        $this->assertSame($expected, Manager::parseArea($area));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function permissionAreaProvider(): array
    {
        return [
            'global' => ['global', 'global'],
            'user' => ['user', 'user'],
            'groups' => ['groups', 'groups'],
            'site' => ['site', 'site'],
            'project' => ['project', 'project'],
            'media' => ['media', 'media'],
            'unknown falls back to empty area' => ['unknown', '']
        ];
    }

    #[DataProvider('classAreaProvider')]
    public function testClassToArea(string $className, string $expected): void
    {
        $this->assertSame($expected, Manager::classToArea($className));
    }

    /**
     * @return array<string, array{class-string, string}>
     */
    public static function classAreaProvider(): array
    {
        return [
            'user' => [QUI\Users\User::class, 'user'],
            'system user' => [QUI\Users\SystemUser::class, 'user'],
            'nobody' => [QUI\Users\Nobody::class, 'user'],
            'group' => [Group::class, 'groups'],
            'guest group' => [QUI\Groups\Guest::class, 'groups'],
            'everyone group' => [QUI\Groups\Everyone::class, 'groups'],
            'project' => [Project::class, 'project'],
            'site' => [Site::class, 'site'],
            'editable site' => [QUI\Projects\Site\Edit::class, 'site'],
            'database-only site' => [QUI\Projects\Site\OnlyDB::class, 'site'],
            'media manager' => [QUI\Projects\Media::class, 'media'],
            'media file' => [File::class, 'media'],
            'media folder' => [QUI\Projects\Media\Folder::class, 'media'],
            'media image' => [QUI\Projects\Media\Image::class, 'media'],
            'unknown' => [stdClass::class, '__null__']
        ];
    }

    public function testObjectToAreaUsesInterfacesAndKnownClasses(): void
    {
        $Manager = new AccessibleManager();
        $User = $this->createMock(QUI\Interfaces\Users\User::class);
        $Group = $this->createMock(Group::class);

        $this->assertSame('user', $Manager->getTestObjectArea($User));
        $this->assertSame('groups', $Manager->getTestObjectArea($Group));
        $this->assertSame('__null__', $Manager->getTestObjectArea(new stdClass()));
    }

    public function testGetPermissionListFiltersByArea(): void
    {
        $Manager = new AccessibleManager();
        $Manager->setTestCache([
            'global.permission' => ['area' => 'global'],
            'empty.permission' => ['area' => ''],
            'group.permission' => ['area' => 'groups'],
            'user.permission' => ['area' => 'user'],
            'project.permission' => ['area' => 'project']
        ]);

        $this->assertCount(5, $Manager->getPermissionList());
        $this->assertSame(
            ['global.permission', 'empty.permission', 'group.permission'],
            array_keys($Manager->getPermissionList('groups'))
        );
        $this->assertSame(
            ['global.permission', 'empty.permission', 'user.permission'],
            array_keys($Manager->getPermissionList('user'))
        );
        $this->assertSame(
            ['project.permission'],
            array_keys($Manager->getPermissionList('project'))
        );
    }

    public function testGetPermissionDataUsesCache(): void
    {
        $Manager = new AccessibleManager();
        $Manager->setTestCache([
            'test.permission' => [
                'area' => 'global',
                'type' => 'bool'
            ]
        ]);

        $this->assertSame(
            ['area' => 'global', 'type' => 'bool'],
            $Manager->getPermissionData('test.permission')
        );
    }

    public function testGetPermissionDataRejectsUnknownPermission(): void
    {
        $this->expectException(QUI\Exception::class);

        (new AccessibleManager())->getPermissionData('unknown.permission');
    }

    #[DataProvider('cleanValueProvider')]
    public function testCleanValue(string $type, int|array|string $value, mixed $expected): void
    {
        $this->assertSame(
            $expected,
            (new AccessibleManager())->cleanTestValue($type, $value)
        );
    }

    /**
     * @return array<string, array{string, int|array|string, mixed}>
     */
    public static function cleanValueProvider(): array
    {
        return [
            'integer' => ['int', '12', 12],
            'string' => ['string', 'value', 'value'],
            'boolean true' => ['bool', 1, true],
            'boolean false' => ['bool', 0, false],
            'unknown type is boolean' => ['unknown', 1, true],
            'array' => ['array', [1, true, null, new stdClass()], [1, true, null]],
            'single group' => ['group', 'group-123', '123'],
            'multiple groups' => ['groups', 'group-12,group-34', '12,34'],
            'multiple users' => ['users', 'user-12,user-34', '12,34'],
            'users and groups' => ['users_and_groups', 'g12,u34,invalid', 'g12,u34']
        ];
    }

    #[DataProvider('dispatchObjectProvider')]
    public function testSetPermissionsDispatchesKnownObjects(object $Object, string $expectedArea): void
    {
        $Manager = new AccessibleManager();

        $Manager->setPermissions($Object, ['test.permission' => true]);

        $this->assertSame($expectedArea, $Manager->dispatchedArea);
    }

    /**
     * @return array<string, array{object, string}>
     */
    public static function dispatchObjectProvider(): array
    {
        return [
            'project' => [self::newObjectWithoutConstructor(Project::class), 'project'],
            'site' => [self::newObjectWithoutConstructor(Site::class), 'site'],
            'media' => [self::newObjectWithoutConstructor(File::class), 'media']
        ];
    }

    public function testSetPermissionsRejectsEmptyPermissionList(): void
    {
        $this->expectException(QUI\Exception::class);

        (new AccessibleManager())->setPermissions(new stdClass(), []);
    }

    public function testSetPermissionsRejectsUnknownObject(): void
    {
        $this->expectException(QUI\Exception::class);

        (new AccessibleManager())->setPermissions(
            new stdClass(),
            ['test.permission' => true]
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T
     */
    private static function newObjectWithoutConstructor(string $className): object
    {
        return (new ReflectionClass($className))->newInstanceWithoutConstructor();
    }
}
