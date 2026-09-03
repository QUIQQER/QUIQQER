<?php

declare(strict_types=1);

namespace QUI\MCP;

use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\Mail\Mailer;
use QUI\MCP\Users\CreateUser;
use QUI\MCP\Users\CreateUserAddress;
use QUI\MCP\Users\DeleteUserAddress;
use QUI\MCP\Users\DeleteUserWebAuthnCredential;
use QUI\MCP\Users\DisableUserAuthenticator;
use QUI\MCP\Users\GetUserAddress;
use QUI\MCP\Users\InviteUser;
use QUI\MCP\Users\ListUserAddresses;
use QUI\MCP\Users\ListUserAuthenticators;
use QUI\MCP\Users\SetDefaultUserAddress;
use QUI\MCP\Users\SetUserPassword;
use QUI\MCP\Users\UpdateUserAddress;
use QUI\Permissions\Permission;
use QUI\Projects\ProjectIntegrationTestCase;
use QUI\Users\Auth\WebAuthn\CredentialRepository;
use QUI\Users\User;
use ReflectionProperty;
use Throwable;

class UserAdvancedToolIntegrationTest extends ProjectIntegrationTestCase
{
    public function testUserInvitationWithoutExposingInitialPassword(): void
    {
        self::skipIfDatabaseOrSuperUserIsUnavailable();
        $userUuid = null;
        $mailSendingDisabled = Mailer::$DISABLE_MAIL_SENDING;

        self::runAsRootUser(function () use (&$userUuid, $mailSendingDisabled): void {
            try {
                Mailer::$DISABLE_MAIL_SENDING = true;
                $invited = self::invokeTool(new InviteUser(), [
                    'mcp-invitation-' . uniqid() . '@example.invalid',
                    []
                ]);
                $userUuid = (string)$invited['user']['uuid'];

                self::assertTrue($invited['invited']);
                self::assertTrue($invited['user']['active']);
                self::assertArrayNotHasKey('password', $invited);
                self::assertArrayNotHasKey('password', $invited['user']);
                self::assertTrue((bool)self::getManagedTestUser($userUuid)->getAttribute('quiqqer.set.new.password'));
            } finally {
                Mailer::$DISABLE_MAIL_SENDING = $mailSendingDisabled;
                self::cleanupUser($userUuid, null);
            }
        });
    }

    public function testAddressPasswordAuthenticatorAndWebAuthnLifecycle(): void
    {
        self::skipIfDatabaseOrSuperUserIsUnavailable();
        $userUuid = null;
        $credentialId = null;

        self::runAsRootUser(function () use (&$userUuid, &$credentialId): void {
            try {
                $username = 'mcp-advanced-user-' . uniqid();
                $created = self::invokeTool(new CreateUser(), [
                    $username,
                    ['email' => $username . '@example.invalid']
                ]);
                $userUuid = (string)$created['user']['uuid'];
                $initialAddresses = self::invokeTool(new ListUserAddresses(), [$userUuid]);
                $initialAddressCount = (int)$initialAddresses['count'];

                $first = self::invokeTool(new CreateUserAddress(), [
                    $userUuid,
                    [
                        'firstname' => 'Ada',
                        'lastname' => 'Lovelace',
                        'street_no' => 'First Street 1',
                        'zip' => '12345',
                        'city' => 'London',
                        'country' => 'GB'
                    ],
                    ['ada@example.invalid'],
                    [['type' => 'mobile', 'no' => '+44 123']],
                    true
                ]);
                $firstAddressUuid = (string)$first['address']['uuid'];
                self::assertTrue($first['created']);
                self::assertTrue($first['address']['default']);

                $second = self::invokeTool(new CreateUserAddress(), [
                    $userUuid,
                    [
                        'firstname' => 'Ada',
                        'lastname' => 'Lovelace',
                        'street_no' => 'Second Street 2',
                        'zip' => '54321',
                        'city' => 'Oxford',
                        'country' => 'GB'
                    ],
                    ['office@example.invalid'],
                    null
                ]);
                $secondAddressUuid = (string)$second['address']['uuid'];

                $addresses = self::invokeTool(new ListUserAddresses(), [$userUuid]);
                self::assertSame($initialAddressCount + 2, $addresses['count']);

                $updated = self::invokeTool(new UpdateUserAddress(), [
                    $userUuid,
                    $secondAddressUuid,
                    ['city' => 'Cambridge', 'suffix' => 'Office'],
                    ['new-office@example.invalid'],
                    [['type' => 'tel', 'no' => '+44 456']]
                ]);
                self::assertTrue($updated['updated']);
                self::assertSame('Cambridge', $updated['address']['attributes']['city']);
                self::assertSame('Office', $updated['address']['attributes']['suffix']);
                self::assertSame(['new-office@example.invalid'], $updated['address']['mails']);
                self::assertSame([['type' => 'tel', 'no' => '+44 456']], $updated['address']['phones']);

                $default = self::invokeTool(new SetDefaultUserAddress(), [$userUuid, $secondAddressUuid]);
                self::assertTrue($default['updated']);
                self::assertTrue($default['address']['default']);

                $address = self::invokeTool(new GetUserAddress(), [$userUuid, $secondAddressUuid]);
                self::assertTrue($address['default']);

                $deleted = self::invokeTool(new DeleteUserAddress(), [$userUuid, $firstAddressUuid]);
                self::assertTrue($deleted['deleted']);
                self::assertSame($firstAddressUuid, $deleted['addressUuid']);

                $password = 'MCP-test-password-' . uniqid();
                $passwordResult = self::invokeTool(new SetUserPassword(), [
                    $userUuid,
                    $password,
                    true
                ]);
                self::assertTrue($passwordResult['updated']);
                self::assertTrue($passwordResult['forceChange']);
                $User = self::getManagedTestUser($userUuid);
                self::assertTrue($User->checkPassword($password));
                self::assertTrue((bool)$User->getAttribute('quiqqer.set.new.password'));

                $Repository = new CredentialRepository();
                $credentialRawId = random_bytes(32);
                $Repository->create(
                    $userUuid,
                    $Repository->createUserHandle(),
                    $credentialRawId,
                    'test-public-key',
                    0,
                    null,
                    ['usb'],
                    'MCP test security key',
                    true,
                    false
                );
                $credential = $Repository->findByCredentialId($credentialRawId);
                self::assertIsArray($credential);
                $credentialId = (int)$credential['id'];

                $authenticators = self::invokeTool(new ListUserAuthenticators(), [$userUuid]);
                self::assertIsArray($authenticators['authenticators']);
                self::assertCount(1, $authenticators['webauthnCredentials']);
                self::assertSame($credentialId, $authenticators['webauthnCredentials'][0]['id']);
                self::assertArrayNotHasKey('credentialId', $authenticators['webauthnCredentials'][0]);
                self::assertArrayNotHasKey('publicKey', $authenticators['webauthnCredentials'][0]);

                self::testSecondaryAuthenticatorDisable($User, $authenticators['authenticators']);

                $credentialDeleted = self::invokeTool(new DeleteUserWebAuthnCredential(), [
                    $userUuid,
                    $credentialId
                ]);
                self::assertTrue($credentialDeleted['deleted']);
                self::assertSame([], $credentialDeleted['remaining']);
                self::assertNull($Repository->findById($credentialId));
                $credentialId = null;
            } finally {
                self::cleanupUser($userUuid, $credentialId);
            }
        });
    }

    /**
     * @param array<int, array<string, mixed>> $authenticators
     */
    private static function testSecondaryAuthenticatorDisable(User $User, array $authenticators): void
    {
        $RootUser = Server::getRequestUser();

        foreach ($authenticators as $authenticator) {
            if (empty($authenticator['secondary']) || !is_string($authenticator['class'] ?? null)) {
                continue;
            }

            $class = $authenticator['class'];

            try {
                $User->enableAuthenticator($class, $RootUser);
            } catch (Throwable) {
                continue;
            }

            $disabled = self::invokeTool(new DisableUserAuthenticator(), [$User->getUUID(), $class]);
            self::assertTrue($disabled['disabled']);
            self::assertFalse($User->hasAuthenticator($class));

            return;
        }
    }

    private static function getManagedTestUser(string $uuid): User
    {
        $User = QUI::getUsers()->get($uuid);
        self::assertInstanceOf(User::class, $User);

        return $User;
    }

    /**
     * @param array<int, mixed> $arguments
     * @return array<string, mixed>
     */
    private static function invokeTool(ToolInterface $Tool, array $arguments): array
    {
        $Builder = new Builder();
        $Tool->register($Builder);
        $tools = (new ReflectionProperty(Builder::class, 'tools'))->getValue($Builder);
        $Handler = $tools[0]['handler'] ?? $tools[0]['callback'] ?? null;

        self::assertIsCallable($Handler);
        $result = $Handler(...$arguments);
        self::assertIsArray($result);

        return $result;
    }

    private static function cleanupUser(?string $userUuid, ?int $credentialId): void
    {
        if ($userUuid === null) {
            return;
        }

        if ($credentialId !== null) {
            try {
                (new CredentialRepository())->deleteForUser($credentialId, $userUuid);
            } catch (Throwable) {
            }
        }

        try {
            $User = QUI::getUsers()->get($userUuid);

            if ($User instanceof User) {
                $User->delete(Server::getRequestUser());
            }
        } catch (Throwable) {
        }
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
            return $Callback();
        } finally {
            $SessionProperty->setValue($Users, $PreviousSessionUser);
            $PermissionProperty->setValue(null, $PreviousPermissionUser);
            $RequestUserProperty->setValue(null, $PreviousRequestUser);
        }
    }
}
