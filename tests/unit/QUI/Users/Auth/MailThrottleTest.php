<?php

namespace QUI\Users\Auth;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Locale;
use QUI\Mail\Manager as MailManager;
use QUI\Package\Manager as PackageManager;
use QUI\Package\Package;
use QUI\Security\Throttle;
use QUI\System\Console\Session;
use QUI\Users\Attribute\Verifiable\MailAttribute;
use QUI\Users\Manager as UserManager;
use QUI\Users\User;
use QUI\Verification\Interface\VerificationFactoryInterface;
use ReflectionProperty;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class MailThrottleTest extends TestCase
{
    private Connection $Connection;

    protected function setUp(): void
    {
        // Resolve the project while the complete fixture database is still installed.
        QUI::getRewrite()->getProject();
        $this->Connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true
        ]);

        $ConnectionProperty = new ReflectionProperty(QUI::class, 'QueryBuilder');
        $ConnectionProperty->setValue(null, $this->Connection);

        $table = Throttle::table();
        $quotedTable = $this->Connection->getDatabasePlatform()->quoteIdentifier($table);

        $this->Connection->executeStatement(
            'CREATE TABLE ' . $quotedTable . ' ('
            . 'throttleKey VARCHAR(64) PRIMARY KEY, '
            . 'package VARCHAR(255) NOT NULL, '
            . 'subjectKey VARCHAR(64) NOT NULL, '
            . 'reservationId VARCHAR(32) NOT NULL, '
            . 'expiresAt BIGINT NOT NULL'
            . ')'
        );

        $_SERVER['HTTP_HOST'] = 'example.test';
    }

    public function testPasswordResetMailUsesPersistentUserThrottle(): void
    {
        $User = $this->createConcreteUser('password-user');
        Throttle::acquireForUser($User, 'quiqqer/core', 'users.password-reset-mail', 60);

        $VerificationFactory = $this->createMock(VerificationFactoryInterface::class);
        $VerificationFactory->expects(self::never())->method('createLinkVerification');

        $this->configureVerificationPackage();

        (new Handler($VerificationFactory))->sendPasswordResetVerificationMail($User);
    }

    public function testPasswordResetReleasesThrottleAfterFailure(): void
    {
        $User = $this->createConcreteUser('password-user');
        $VerificationFactory = $this->createMock(VerificationFactoryInterface::class);
        $VerificationFactory->expects(self::exactly(2))
            ->method('createLinkVerification')
            ->willThrowException(new QUI\Exception('Verification creation failed.'));

        $this->configureVerificationPackage();
        $Handler = new Handler($VerificationFactory);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            try {
                $Handler->sendPasswordResetVerificationMail($User);
            } catch (QUI\Exception) {
            }
        }
    }

    private function configureVerificationPackage(): void
    {
        $Package = $this->createMock(Package::class);
        $Package->method('getVersion')->willReturn('3.0.0');

        $PackageManager = $this->createMock(PackageManager::class);
        $PackageManager->method('isInstalled')
            ->with('quiqqer/verification')
            ->willReturn(true);
        $PackageManager->method('getInstalledPackage')
            ->with('quiqqer/verification')
            ->willReturn($Package);
        QUI::$PackageManager = $PackageManager;
    }

    public function testAuthMailCannotBeRequestedAgainWithANewSession(): void
    {
        $User = $this->createConcreteUser('auth-user');
        $this->configureVerifiedMailDependencies($User, 1);

        $FirstSession = $this->createSession('auth-user');
        QUI::$Session = $FirstSession;
        VerifiedMail2FA::sendAuthMailToSessionUser();

        $SecondSession = $this->createSession('auth-user');
        QUI::$Session = $SecondSession;
        VerifiedMail2FA::sendAuthMailToSessionUser();

        self::assertNotEmpty($FirstSession->get(VerifiedMail2FA::USER_CODE_ATTRIBUTE));
        self::assertFalse($SecondSession->get(VerifiedMail2FA::USER_CODE_ATTRIBUTE));
    }

    public function testEnableMailCannotBeRequestedAgainWithANewSession(): void
    {
        $User = $this->createConcreteUser('enable-user');
        $this->configureVerifiedMailDependencies($User, 1);

        $FirstSession = $this->createSession('enable-user');
        QUI::$Session = $FirstSession;
        VerifiedMail2FA::sendEnableMailToSessionUser();

        $SecondSession = $this->createSession('enable-user');
        QUI::$Session = $SecondSession;
        VerifiedMail2FA::sendEnableMailToSessionUser();

        self::assertNotEmpty($FirstSession->get(VerifiedMail2FA::USER_CODE_VERIFYING_ATTRIBUTE));
        self::assertFalse($SecondSession->get(VerifiedMail2FA::USER_CODE_VERIFYING_ATTRIBUTE));
    }

    public function testAuthAndEnableMailUseSeparateThrottleBuckets(): void
    {
        $User = $this->createConcreteUser('shared-user');
        $this->configureVerifiedMailDependencies($User, 2);
        QUI::$Session = $this->createSession('shared-user');

        VerifiedMail2FA::sendAuthMailToSessionUser();
        VerifiedMail2FA::sendEnableMailToSessionUser();

        self::assertNotEmpty(QUI::getSession()->get(VerifiedMail2FA::USER_CODE_ATTRIBUTE));
        self::assertNotEmpty(QUI::getSession()->get(VerifiedMail2FA::USER_CODE_VERIFYING_ATTRIBUTE));
    }

    private function createConcreteUser(string $uuid): User
    {
        $User = $this->createMock(User::class);
        $User->method('getUUID')->willReturn($uuid);
        $User->method('getAttribute')->with('email')->willReturn('user@example.test');
        $User->method('isAttributeVerified')
            ->with('user@example.test', MailAttribute::class)
            ->willReturn(true);

        return $User;
    }

    private function createSession(string $uuid): Session
    {
        $Session = new Session();
        $Session->set('uid', $uuid);

        return $Session;
    }

    private function configureVerifiedMailDependencies(User $User, int $expectedMails): void
    {
        $Users = $this->createMock(UserManager::class);
        $Users->method('get')->with($User->getUUID())->willReturn($User);
        QUI::$Users = $Users;

        $Mailer = $this->createMock(MailManager::class);
        $Mailer->expects(self::exactly($expectedMails))->method('send');
        QUI::$MailManager = $Mailer;

        $Locale = $this->createMock(Locale::class);
        $Locale->method('get')->willReturn('translated mail text');
        QUI::$Locale = $Locale;
    }
}
