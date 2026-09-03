<?php

declare(strict_types=1);

namespace QUI\MCP;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\AI\MCP\Server;
use QUI\Interfaces\Users\User as UserInterface;
use QUI\MCP\Groups\ActivateGroup;
use QUI\MCP\Groups\AddGroupUsers;
use QUI\MCP\Groups\CreateGroup;
use QUI\MCP\Groups\DeactivateGroup;
use QUI\MCP\Groups\DeleteGroup;
use QUI\MCP\Groups\ListGroupUsers;
use QUI\MCP\Groups\ListUserGroups;
use QUI\MCP\Groups\RemoveGroupUsers;
use QUI\MCP\Groups\UpdateGroup;
use QUI\MCP\Users\ActivateUser;
use QUI\MCP\Users\CreateUser;
use QUI\MCP\Users\DeactivateUser;
use QUI\MCP\Users\DeleteUser;
use QUI\MCP\Users\GetUser;
use QUI\MCP\Users\SearchUsers;
use QUI\MCP\Users\UpdateUser;
use QUI\Permissions\Permission;
use QUI\Users\User;
use ReflectionProperty;
use Throwable;

class UserGroupToolIntegrationTest extends TestCase
{
    private const AUTHORIZATION_TEST_PREFIX = 'mcp-group-auth-';

    public function testUserGroupAndMembershipLifecycle(): void
    {
        self::skipIfDatabaseOrSuperUserIsUnavailable();

        $username = 'codex-mcp-user-' . uniqid();
        $groupName = 'codex-mcp-group-' . uniqid();
        $userUuid = null;
        $groupUuid = null;

        self::runAsRootUser(function (UserInterface $RootUser) use (
            $username,
            $groupName,
            &$userUuid,
            &$groupUuid
        ): void {
            try {
                $createdUser = self::invokeTool(new CreateUser(), [
                    $username,
                    [
                        'email' => $username . '@example.invalid',
                        'firstname' => 'MCP',
                        'password' => 'must-not-be-accepted'
                    ]
                ]);
                $userUuid = (string)$createdUser['user']['uuid'];

                self::assertTrue($createdUser['created']);
                self::assertSame(['password'], $createdUser['ignoredAttributes']);

                $User = QUI::getUsers()->get($userUuid);
                $User->setPassword('codex-mcp-test-password', $RootUser);

                $updatedUser = self::invokeTool(new UpdateUser(), [
                    $userUuid,
                    ['lastname' => 'Lifecycle', 'authenticator' => ['unsafe']]
                ]);
                self::assertSame('Lifecycle', $updatedUser['user']['lastName']);
                self::assertSame(['authenticator'], $updatedUser['ignoredAttributes']);

                $activatedUser = self::invokeTool(new ActivateUser(), [$userUuid]);
                self::assertTrue($activatedUser['activated']);

                $deactivatedUser = self::invokeTool(new DeactivateUser(), [$userUuid]);
                self::assertTrue($deactivatedUser['deactivated']);

                $loadedUser = self::invokeTool(new GetUser(), [$userUuid]);
                self::assertSame($username, $loadedUser['username']);
                self::assertArrayNotHasKey('password', $loadedUser);

                $searchResult = self::invokeTool(new SearchUsers(), [$username, 10, 0]);
                self::assertContains($userUuid, array_column($searchResult['users'], 'uuid'));

                $createdGroup = self::invokeTool(new CreateGroup(), [
                    $groupName,
                    QUI::conf('globals', 'root')
                ]);
                $groupUuid = (string)$createdGroup['group']['uuid'];
                self::assertTrue($createdGroup['created']);

                $updatedGroup = self::invokeTool(new UpdateGroup(), [
                    $groupUuid,
                    ['name' => $groupName . '-updated', 'rights' => ['unsafe']]
                ]);
                self::assertSame($groupName . '-updated', $updatedGroup['group']['name']);
                self::assertSame(['rights'], $updatedGroup['ignoredAttributes']);

                $activatedGroup = self::invokeTool(new ActivateGroup(), [$groupUuid]);
                self::assertTrue($activatedGroup['activated']);

                $added = self::invokeTool(new AddGroupUsers(), [$groupUuid, [$userUuid]]);
                self::assertSame(1, $added['added']);

                $userGroups = self::invokeTool(new ListUserGroups(), [$userUuid]);
                self::assertContains($groupUuid, array_column($userGroups['groups'], 'uuid'));

                $groupUsers = self::invokeTool(new ListGroupUsers(), [$groupUuid, 10, 0]);
                self::assertContains($userUuid, array_column($groupUsers['users'], 'uuid'));

                $removed = self::invokeTool(new RemoveGroupUsers(), [$groupUuid, [$userUuid]]);
                self::assertSame(1, $removed['removed']);

                $deactivatedGroup = self::invokeTool(new DeactivateGroup(), [$groupUuid]);
                self::assertTrue($deactivatedGroup['deactivated']);

                $deletedGroup = self::invokeTool(new DeleteGroup(), [$groupUuid]);
                self::assertTrue($deletedGroup['deleted']);
                $groupUuid = null;

                $deletedUser = self::invokeTool(new DeleteUser(), [$userUuid]);
                self::assertTrue($deletedUser['deleted']);
                $userUuid = null;
            } finally {
                if ($groupUuid !== null) {
                    try {
                        QUI::getGroups()->get($groupUuid)->delete();
                    } catch (Throwable) {
                    }
                }

                if ($userUuid !== null) {
                    try {
                        QUI::getUsers()->get($userUuid)->delete($RootUser);
                    } catch (Throwable) {
                    }
                }
            }
        });
    }

    public function testDelegatedEditorCannotAssignUsersToStrongerOrRootGroups(): void
    {
        self::skipIfDatabaseOrSuperUserIsUnavailable();

        $actorUuid = null;
        $allowedTargetUuid = null;
        $protectedTargetUuid = null;
        $allowedGroupUuid = null;
        $protectedGroupUuid = null;

        try {
            self::runAsRootUser(function (UserInterface $RootUser) use (
                &$actorUuid,
                &$allowedTargetUuid,
                &$protectedTargetUuid,
                &$allowedGroupUuid,
                &$protectedGroupUuid
            ): void {
                $Actor = self::createAuthorizationTestUser('actor', $RootUser);
                $AllowedTarget = self::createAuthorizationTestUser('allowed-target', $RootUser);
                $ProtectedTarget = self::createAuthorizationTestUser('protected-target', $RootUser);
                $RootGroup = QUI::getGroups()->get(QUI::conf('globals', 'root'));
                $AllowedGroup = $RootGroup->createChild(
                    self::AUTHORIZATION_TEST_PREFIX . 'allowed-' . bin2hex(random_bytes(5)),
                    $RootUser
                );
                $ProtectedGroup = $RootGroup->createChild(
                    self::AUTHORIZATION_TEST_PREFIX . 'protected-' . bin2hex(random_bytes(5)),
                    $RootUser
                );

                QUI::getPermissionManager()->setPermissions($Actor, [
                    'quiqqer.admin' => true,
                    'quiqqer.admin.groups.edit' => true,
                    'quiqqer.admin.users.edit' => true,
                    'quiqqer.core.mcp.canUse' => true,
                    'quiqqer.core.mcp.groups.canUse' => true,
                    'quiqqer.core.mcp.users.canUse' => true,
                    'quiqqer.projects.create' => true
                ], $RootUser);
                QUI::getPermissionManager()->setPermissions(
                    $AllowedGroup,
                    ['quiqqer.projects.create' => true],
                    $RootUser
                );
                QUI::getPermissionManager()->setPermissions(
                    $ProtectedGroup,
                    ['quiqqer.system.update' => true],
                    $RootUser
                );
                $AllowedGroup->addUser($Actor);
                $Actor->save($RootUser);

                $actorUuid = (string)$Actor->getUUID();
                $allowedTargetUuid = (string)$AllowedTarget->getUUID();
                $protectedTargetUuid = (string)$ProtectedTarget->getUUID();
                $allowedGroupUuid = (string)$AllowedGroup->getUUID();
                $protectedGroupUuid = (string)$ProtectedGroup->getUUID();
            });

            $Actor = QUI::getUsers()->get((string)$actorUuid);
            self::assertInstanceOf(User::class, $Actor);

            self::runAsUser($Actor, function () use (
                $allowedTargetUuid,
                $protectedTargetUuid,
                $allowedGroupUuid,
                $protectedGroupUuid
            ): void {
                $allowed = self::invokeTool(new AddGroupUsers(), [
                    $allowedGroupUuid,
                    [$allowedTargetUuid]
                ]);
                self::assertSame(1, $allowed['added']);

                $protected = self::invokeToolRaw(new AddGroupUsers(), [
                    $protectedGroupUuid,
                    [$protectedTargetUuid]
                ]);
                self::assertInstanceOf(CallToolResult::class, $protected);

                $rootGroup = QUI::getGroups()->get(QUI::conf('globals', 'root'));
                $root = self::invokeToolRaw(new AddGroupUsers(), [
                    $rootGroup->getUUID(),
                    [$protectedTargetUuid]
                ]);
                self::assertInstanceOf(CallToolResult::class, $root);

                $ProtectedTarget = QUI::getUsers()->get((string)$protectedTargetUuid);
                self::assertInstanceOf(User::class, $ProtectedTarget);
                $ProtectedTarget->refresh();
                self::assertNotContains($protectedGroupUuid, $ProtectedTarget->getGroups(false));
                self::assertNotContains($rootGroup->getUUID(), $ProtectedTarget->getGroups(false));
            });
        } finally {
            self::runAsRootUser(function (UserInterface $RootUser) use (
                $actorUuid,
                $allowedTargetUuid,
                $protectedTargetUuid,
                $allowedGroupUuid,
                $protectedGroupUuid
            ): void {
                foreach ([$allowedGroupUuid, $protectedGroupUuid] as $groupUuid) {
                    if ($groupUuid === null) {
                        continue;
                    }

                    try {
                        QUI::getGroups()->get($groupUuid)->delete();
                    } catch (Throwable) {
                    }
                }

                foreach ([$actorUuid, $allowedTargetUuid, $protectedTargetUuid] as $userUuid) {
                    if ($userUuid === null) {
                        continue;
                    }

                    try {
                        $User = QUI::getUsers()->get($userUuid);

                        if ($User instanceof User) {
                            $User->delete($RootUser);
                        }
                    } catch (Throwable) {
                    }
                }
            });
        }
    }

    /**
     * @param array<int, mixed> $arguments
     * @return array<string, mixed>
     */
    private static function invokeTool(ToolInterface $Tool, array $arguments): array
    {
        $result = self::invokeToolRaw($Tool, $arguments);

        self::assertIsArray($result);

        return $result;
    }

    /**
     * @param array<int, mixed> $arguments
     */
    private static function invokeToolRaw(ToolInterface $Tool, array $arguments): mixed
    {
        $Builder = new Builder();
        $Tool->register($Builder);
        $ToolsProperty = new ReflectionProperty(Builder::class, 'tools');
        $tools = $ToolsProperty->getValue($Builder);
        $Handler = $tools[0]['handler'] ?? $tools[0]['callback'] ?? null;

        self::assertIsCallable($Handler);
        $result = $Handler(...$arguments);

        return $result;
    }

    private static function createAuthorizationTestUser(string $suffix, UserInterface $RootUser): User
    {
        $username = self::AUTHORIZATION_TEST_PREFIX . $suffix . '-' . bin2hex(random_bytes(5));
        $User = QUI::getUsers()->createChildWithAttributes([
            'username' => $username,
            'email' => $username . '@example.invalid'
        ], $RootUser);
        self::assertInstanceOf(User::class, $User);
        $User->setPassword(self::AUTHORIZATION_TEST_PREFIX . bin2hex(random_bytes(8)), $RootUser);
        $User->activate('', $RootUser);

        return $User;
    }

    private static function skipIfDatabaseOrSuperUserIsUnavailable(): void
    {
        try {
            QUI::getDataBaseConnection()->executeQuery('SELECT 1')->free();
            $RootUser = QUI::getUsers()->get(QUI::conf('globals', 'rootuser'));
        } catch (Throwable $Exception) {
            self::markTestSkipped('QUIQQER user database is unavailable: ' . $Exception->getMessage());
        }

        if (!$RootUser->isSU()) {
            self::markTestSkipped('QUIQQER database has no usable super-user fixture.');
        }
    }

    private static function runAsRootUser(callable $Callback): mixed
    {
        $Users = QUI::getUsers();
        $RootUser = $Users->get(QUI::conf('globals', 'rootuser'));

        return self::runAsUser($RootUser, $Callback);
    }

    private static function runAsUser(UserInterface $User, callable $Callback): mixed
    {
        $Users = QUI::getUsers();
        $SessionProperty = new ReflectionProperty($Users, 'Session');
        $PermissionProperty = new ReflectionProperty(Permission::class, 'User');
        $RequestUserProperty = new ReflectionProperty(Server::class, 'RequestUser');
        $PreviousSessionUser = $SessionProperty->getValue($Users);
        $PreviousPermissionUser = $PermissionProperty->getValue();
        $PreviousRequestUser = $RequestUserProperty->getValue();

        $SessionProperty->setValue($Users, $User);
        $PermissionProperty->setValue(null, $User);
        $RequestUserProperty->setValue(null, $User);

        try {
            return $Callback($User);
        } finally {
            $SessionProperty->setValue($Users, $PreviousSessionUser);
            $PermissionProperty->setValue(null, $PreviousPermissionUser);
            $RequestUserProperty->setValue(null, $PreviousRequestUser);
        }
    }
}
