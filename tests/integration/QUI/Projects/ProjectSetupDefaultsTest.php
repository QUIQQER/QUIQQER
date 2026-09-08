<?php

declare(strict_types=1);

namespace QUI\Projects;

use QUI\Cache\Manager as Cache;

final class ProjectSetupDefaultsTest extends ProjectIntegrationTestCase
{
    public function testSetupImportsNewDefaultsWithWarmCachesAndRefreshesTheProject(): void
    {
        $Project = self::getTestProject();
        $name = $Project->getName();
        $Config = Manager::getConfig();
        $originalConfig = $Config->toArray()[$name];
        $settingsFile = USR_DIR . $name . '/settings.xml';
        $originalXml = is_file($settingsFile) ? file_get_contents($settingsFile) : null;

        try {
            Cache::clear($Project->getCachePath());
            Manager::getProjectConfigList($Project);

            file_put_contents($settingsFile, <<<'XML'
<?xml version="1.0"?>
<quiqqer>
    <project>
        <settings name="setupDefaults">
            <config>
                <section name="test">
                    <conf name="new"><type>string</type><defaultvalue>new-default</defaultvalue></conf>
                    <conf name="custom"><type>string</type><defaultvalue>default</defaultvalue></conf>
                    <conf name="empty"><type>string</type><defaultvalue>default</defaultvalue></conf>
                    <conf name="zero"><type>int</type><defaultvalue>1</defaultvalue></conf>
                </section>
            </config>
        </settings>
    </project>
</quiqqer>
XML);
            $Config->setValue($name, 'setupDefaults.test.custom', 'custom-value');
            $Config->setValue($name, 'setupDefaults.test.empty', '');
            $Config->setValue($name, 'setupDefaults.test.zero', '0');
            $Config->save();

            ProjectTestHelper::runAsSystemUser(static function () use ($Project): void {
                $Project->setup(['executePackagesSetup' => false]);
            });

            $expected = [
                'setupDefaults.test.new' => 'new-default',
                'setupDefaults.test.custom' => 'custom-value',
                'setupDefaults.test.empty' => '',
                'setupDefaults.test.zero' => '0'
            ];
            $stored = $Config->toArray()[$name];

            foreach ($expected as $key => $value) {
                self::assertSame($value, $stored[$key] ?? null, $key . ' must be persisted');
                self::assertSame($value, (string)$Project->getConfig($key), $key . ' must be available immediately');
            }

            ProjectTestHelper::runAsSystemUser(static function () use ($Project): void {
                $Project->setup(['executePackagesSetup' => false]);
            });

            self::assertSame($stored, $Config->toArray()[$name], 'Repeated setup must preserve configuration');
        } finally {
            if ($originalXml === null) {
                unlink($settingsFile);
            } else {
                file_put_contents($settingsFile, $originalXml);
            }

            $Config->set($name, $originalConfig);
            $Config->save();
            Cache::clear($Project->getCachePath());
            $Project->refresh();
        }
    }
}
