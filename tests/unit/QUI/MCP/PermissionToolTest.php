<?php

declare(strict_types=1);

namespace QUI\MCP;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\MCP\Permissions\AbstractPermissionTool;
use QUI\MCP\Permissions\GetEffectivePermission;
use QUI\Permissions\Manager;
use QUI\Users\User;
use ReflectionMethod;

class PermissionToolTest extends TestCase
{
    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function validPermissionValueProvider(): iterable
    {
        yield 'boolean' => ['bool', true];
        yield 'integer' => ['int', 12];
        yield 'array' => ['array', ['one', 'two']];
        yield 'single user UUID' => ['user', '3386e988-6b66-4d60-9d01-5acc931600a3'];
        yield 'legacy user ID' => ['user', '42'];
        yield 'multiple users' => ['users', '42,3386e988-6b66-4d60-9d01-5acc931600a3'];
        yield 'users and groups' => [
            'users_and_groups',
            'u3386e988-6b66-4d60-9d01-5acc931600a3,g42'
        ];
        yield 'empty ACL' => ['users_and_groups', ''];
        yield 'string' => ['string', 'value'];
    }

    #[DataProvider('validPermissionValueProvider')]
    public function testPermissionValueValidationAcceptsValidValues(string $type, mixed $value): void
    {
        self::invokeValidator($type, $value);
        self::addToAssertionCount(1);
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function invalidPermissionValueProvider(): iterable
    {
        yield 'integer is not boolean' => ['bool', 1];
        yield 'numeric string is not integer' => ['int', '12'];
        yield 'scalar is not array' => ['array', 'one'];
        yield 'multiple values for singular user' => ['user', '1,2'];
        yield 'untyped ACL value' => ['users_and_groups', '42'];
        yield 'invalid ACL UUID' => ['users_and_groups', 'u-not-a-uuid'];
        yield 'boolean is not string' => ['string', false];
    }

    #[DataProvider('invalidPermissionValueProvider')]
    public function testPermissionValueValidationRejectsInvalidValues(string $type, mixed $value): void
    {
        $this->expectException(QUI\Exception::class);
        self::invokeValidator($type, $value);
    }

    public function testEffectiveGlobalPermissionGrantsSuperUsers(): void
    {
        $Manager = $this->createMock(Manager::class);
        $Manager->expects(self::never())->method('getUserPermissionData');
        $User = $this->createMock(User::class);
        $User->expects(self::once())->method('isSU')->willReturn(true);
        $Method = new ReflectionMethod(GetEffectivePermission::class, 'getEffectiveUserPermission');

        $value = $Method->invoke(
            null,
            $Manager,
            $User,
            'test.permission',
            'bool',
            [['uuid' => 'test-group', 'name' => 'Test group', 'value' => false]]
        );

        self::assertTrue($value);
    }

    private static function invokeValidator(string $type, mixed $value): void
    {
        $Method = new ReflectionMethod(AbstractPermissionTool::class, 'validatePermissionValue');
        $Method->invoke(null, 'test.permission', $type, $value);
    }
}
