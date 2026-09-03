<?php

declare(strict_types=1);

namespace QUI\MCP;

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
use ReflectionProperty;
use Throwable;

class UserGroupToolIntegrationTest extends TestCase
{
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

    /**
     * @param array<int, mixed> $arguments
     * @return array<string, mixed>
     */
    private static function invokeTool(ToolInterface $Tool, array $arguments): array
    {
        $Builder = new Builder();
        $Tool->register($Builder);
        $ToolsProperty = new ReflectionProperty(Builder::class, 'tools');
        $tools = $ToolsProperty->getValue($Builder);
        $Handler = $tools[0]['handler'] ?? $tools[0]['callback'] ?? null;

        self::assertIsCallable($Handler);
        $result = $Handler(...$arguments);

        self::assertIsArray($result);

        return $result;
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
        $SessionProperty = new ReflectionProperty($Users, 'Session');
        $PermissionProperty = new ReflectionProperty(Permission::class, 'User');
        $RequestUserProperty = new ReflectionProperty(Server::class, 'RequestUser');
        $PreviousSessionUser = $SessionProperty->getValue($Users);
        $PreviousPermissionUser = $PermissionProperty->getValue();
        $PreviousRequestUser = $RequestUserProperty->getValue();

        $SessionProperty->setValue($Users, $RootUser);
        $PermissionProperty->setValue(null, $RootUser);
        $RequestUserProperty->setValue(null, $RootUser);

        try {
            return $Callback($RootUser);
        } finally {
            $SessionProperty->setValue($Users, $PreviousSessionUser);
            $PermissionProperty->setValue(null, $PreviousPermissionUser);
            $RequestUserProperty->setValue(null, $PreviousRequestUser);
        }
    }
}
