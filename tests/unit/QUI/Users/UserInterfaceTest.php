<?php

declare(strict_types=1);

namespace QUI\Users;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Interfaces\Users\User as UserInterface;
use QUI\Permissions\Exception as PermissionException;
use QUI\Permissions\Manager as PermissionManager;
use QUI\Permissions\Permission;
use ReflectionClass;
use ReflectionProperty;

final class UserInterfaceTest extends TestCase
{
    private ?PermissionManager $previousPermissionManager;
    private ?UserInterface $previousPermissionUser;

    protected function setUp(): void
    {
        $this->previousPermissionManager = QUI::$Rights;
        $this->previousPermissionUser = (new ReflectionProperty(Permission::class, 'User'))->getValue();
    }

    protected function tearDown(): void
    {
        QUI::$Rights = $this->previousPermissionManager;
        (new ReflectionProperty(Permission::class, 'User'))->setValue(null, $this->previousPermissionUser);
    }

    public static function userClasses(): array
    {
        return [
            'user' => [User::class],
            'nobody' => [Nobody::class],
            'system user' => [SystemUser::class]
        ];
    }

    #[DataProvider('userClasses')]
    public function testDirectPermissionValuesAreAvailableThroughInterface(string $class): void
    {
        $User = $this->createUser($class);
        $Manager = $this->createMock(PermissionManager::class);
        $Manager->expects(self::exactly(3))->method('getUserPermissionData')->with($User)->willReturn([
            'allowed' => true,
            'value' => 'assigned-value'
        ]);
        QUI::$Rights = $Manager;

        self::assertTrue($User->hasPermission('allowed'));
        self::assertSame('assigned-value', $User->hasPermission('value'));
        self::assertFalse($User->hasPermission('missing'));
    }

    #[DataProvider('userClasses')]
    public function testBackendAccessUsesCanUseBackend(string $class): void
    {
        $User = $this->createUser($class);
        $Manager = $this->createMock(PermissionManager::class);
        $Manager->method('getPermissions')->willReturn([]);
        QUI::$Rights = $Manager;

        self::assertFalse($User->canUseBackend());
        self::assertFalse($User->isAdmin());
        self::assertFalse(method_exists(UserInterface::class, 'isAdmin'));
    }

    public function testNobodyAndSystemUserHaveDisplayNamesAndNoCurrentAddress(): void
    {
        foreach ([Nobody::class, SystemUser::class] as $class) {
            $User = $this->createUser($class);
            self::assertSame($User->getName(), $User->getDisplayName());
            self::assertNotSame('', $User->getDisplayName());
            self::assertNull($User->getCurrentAddress());
        }
    }

    public function testUserCurrentAddressFallsBackToStandardAddress(): void
    {
        $Address = $this->createMock(Address::class);
        $User = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStandardAddress'])
            ->getMock();
        $User->expects(self::once())->method('getStandardAddress')->willReturn($Address);

        self::assertSame($Address, $User->getCurrentAddress());

        $CurrentAddress = $this->createMock(Address::class);
        $User->setAttribute('CurrentAddress', $CurrentAddress);
        self::assertSame($CurrentAddress, $User->getCurrentAddress());
    }

    public function testUserDisplayNameUsesAvailableNameAttributes(): void
    {
        $User = $this->createUser(User::class);
        $User->setAttribute('firstname', 'Ada');
        self::assertSame('Ada', $User->getDisplayName());

        $User->setAttribute('lastname', 'Lovelace');
        self::assertSame('Ada Lovelace', $User->getDisplayName());
    }

    public function testNobodyPermissionCheckRejectsAccessDespitePrivilegedDefaultUser(): void
    {
        Permission::setUser(new SystemUser());
        $Manager = $this->createMock(PermissionManager::class);
        $Manager->method('getPermissions')->willReturn([]);
        QUI::$Rights = $Manager;

        $this->expectException(PermissionException::class);
        $this->expectExceptionCode(403);

        $this->createUser(Nobody::class)->checkPermission('test.permission');
    }

    public function testNobodyPermissionCheckIncludesGuestGroupPermissions(): void
    {
        $Manager = $this->createMock(PermissionManager::class);
        $Manager->method('getPermissions')->willReturnCallback(
            static fn (object $Object): array => $Object instanceof QUI\Groups\Guest
                ? ['test.permission' => true]
                : []
        );
        QUI::$Rights = $Manager;

        $this->createUser(Nobody::class)->checkPermission('test.permission');
        $this->addToAssertionCount(1);
    }

    public function testSystemUserPermissionCheckUsesSystemIdentity(): void
    {
        Permission::setUser(new Nobody());
        $Manager = $this->createMock(PermissionManager::class);
        $Manager->expects(self::never())->method('getPermissions');
        QUI::$Rights = $Manager;

        $this->createUser(SystemUser::class)->checkPermission('test.permission');
    }

    private function createUser(string $class): UserInterface
    {
        if ($class !== User::class) {
            return new $class();
        }

        $User = (new ReflectionClass(User::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty(User::class, 'groups'))->setValue($User, '');

        return $User;
    }
}
