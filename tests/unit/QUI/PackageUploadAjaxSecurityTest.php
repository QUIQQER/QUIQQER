<?php

namespace QUI;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class PackageUploadAjaxSecurityTest extends TestCase
{
    public function testCrossSitePackageUploadWithoutTokenIsRejected(): void
    {
        \QUI::$Ajax = new Ajax();

        $Users = \QUI::getUsers();
        $SessionProperty = new ReflectionProperty($Users, 'Session');
        $SessionProperty->setValue($Users, $Users->getSystemUser());

        require dirname(__DIR__, 3) . '/admin/ajax/system/packages/upload/package.php';

        $result = \QUI::getAjax()->callRequestFunction(
            'ajax_system_packages_upload_package'
        );

        self::assertFalse(\QUI::isBackend());
        self::assertSame(403, $result['Exception']['code']);
        self::assertSame(Exception::class, $result['Exception']['type']);
    }
}
