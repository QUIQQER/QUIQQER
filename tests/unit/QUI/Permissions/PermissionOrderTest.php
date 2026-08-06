<?php

namespace QUI\Permissions;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use QUI\Groups\Group;
use QUI\Interfaces\Users\User as UserInterface;

class PermissionOrderTest extends TestCase
{
    private const PERMISSION = 'test.permission';

    public function testPermissionReturnsFalseForEmptyList(): void
    {
        $this->assertFalse(PermissionOrder::permission(self::PERMISSION, []));
    }

    public function testPermissionReturnsTrueImmediately(): void
    {
        $GrantedGroup = $this->createGroupWithPermission(true);
        $UncheckedGroup = $this->createMock(Group::class);
        $UncheckedGroup->expects($this->never())->method('hasPermission');

        $this->assertTrue(
            PermissionOrder::permission(
                self::PERMISSION,
                [$GrantedGroup, $UncheckedGroup]
            )
        );
    }

    public function testPermissionReturnsHighestNumericValue(): void
    {
        $this->assertSame(
            12,
            PermissionOrder::permission(
                self::PERMISSION,
                [
                    $this->createGroupWithPermission(4),
                    $this->createGroupWithPermission('12'),
                    $this->createGroupWithPermission(7)
                ]
            )
        );
    }

    public function testPermissionReturnsStringValue(): void
    {
        $this->assertSame(
            'allowed-file-types',
            PermissionOrder::permission(
                self::PERMISSION,
                [
                    $this->createGroupWithPermission(false),
                    $this->createGroupWithPermission('allowed-file-types')
                ]
            )
        );
    }

    public function testPermissionIgnoresArrayValue(): void
    {
        $this->assertSame(
            'fallback-string',
            PermissionOrder::permission(
                self::PERMISSION,
                [
                    $this->createGroupWithPermission(['unsupported']),
                    $this->createGroupWithPermission('fallback-string')
                ]
            )
        );
    }

    public function testPermissionIgnoresUserWithoutHasPermissionMethod(): void
    {
        $User = $this->createMock(UserInterface::class);

        $this->assertFalse(
            PermissionOrder::permission(self::PERMISSION, [$User])
        );
    }

    public function testMaxIntegerReturnsHighestPermissionValue(): void
    {
        $this->assertSame(
            12,
            PermissionOrder::maxInteger(
                self::PERMISSION,
                [
                    $this->createGroupWithPermission(4),
                    $this->createGroupWithPermission('12'),
                    $this->createGroupWithPermission(false)
                ]
            )
        );
    }

    public function testMinIntegerReturnsLowestPermissionValue(): void
    {
        $this->assertSame(
            4,
            PermissionOrder::minInteger(
                self::PERMISSION,
                [
                    $this->createGroupWithPermission(9),
                    $this->createGroupWithPermission('4'),
                    $this->createGroupWithPermission(false)
                ]
            )
        );
    }

    private function createGroupWithPermission(bool|int|array|string $value): Group&MockObject
    {
        $Group = $this->createMock(Group::class);
        $Group->method('hasPermission')->with(self::PERMISSION)->willReturn($value);

        return $Group;
    }
}
