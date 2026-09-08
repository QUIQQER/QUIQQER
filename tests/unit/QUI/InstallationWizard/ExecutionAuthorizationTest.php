<?php

declare(strict_types=1);

namespace QUI\InstallationWizard;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Permissions\Exception as PermissionException;
use QUI\Users\User;
use ReflectionProperty;
use UnexpectedValueException;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class ExecutionAuthorizationTest extends TestCase
{
    public function testRunnerRejectsNonSuperUserBeforeClaimingExecution(): void
    {
        $User = $this->createMock(User::class);
        $User->method('isSU')->willReturn(false);
        $this->setSessionUser($User);
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->expectException(PermissionException::class);
        $this->expectExceptionCode(403);

        require dirname(__DIR__, 4) . '/src/QUI/InstallationWizard/bin/execute.php';
    }

    public function testRunnerRejectsGetRequestsFromSuperUser(): void
    {
        $User = $this->createMock(User::class);
        $User->method('isSU')->willReturn(true);
        $this->setSessionUser($User);
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectException(QUI\Exception::class);
        $this->expectExceptionCode(405);

        require dirname(__DIR__, 4) . '/src/QUI/InstallationWizard/bin/execute.php';
    }

    public function testRunnerRejectsPostWithoutCsrfToken(): void
    {
        $User = $this->createMock(User::class);
        $User->method('isSU')->willReturn(true);
        $this->setSessionUser($User);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        unset($_POST['_csrf']);

        $this->expectException(QUI\Exception::class);
        $this->expectExceptionCode(403);

        require dirname(__DIR__, 4) . '/src/QUI/InstallationWizard/bin/execute.php';
    }

    public function testPreparedExecutionCanOnlyBeClaimedOnce(): void
    {
        $configFile = tempnam(sys_get_temp_dir(), 'quiqqer-installation-wizard-');
        self::assertIsString($configFile);
        file_put_contents($configFile, ";<?php exit; ?>\n");

        $configProperty = new ReflectionProperty(ProviderHandler::class, 'Config');
        $previousConfig = $configProperty->getValue();
        $configProperty->setValue(null, new QUI\Config($configFile));

        try {
            self::assertTrue(ProviderHandler::prepareExecution(
                QuiqqerProvider::class,
                '{"mail.admin_mail":"admin@example.test"}'
            ));

            self::assertSame(
                [
                    'provider' => QuiqqerProvider::class,
                    'data' => ['mail.admin_mail' => 'admin@example.test']
                ],
                ProviderHandler::claimExecution()
            );

            $this->expectException(UnexpectedValueException::class);
            ProviderHandler::claimExecution();
        } finally {
            $configProperty->setValue(null, $previousConfig);
            unlink($configFile);
        }
    }

    private function setSessionUser(User $User): void
    {
        $Users = QUI::getUsers();
        $SessionProperty = new ReflectionProperty($Users, 'Session');
        $SessionProperty->setValue($Users, $User);
    }
}
