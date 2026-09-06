<?php

declare(strict_types=1);

namespace QUI\Package;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Cache\LongTermCache;
use QUI\Config;
use QUI\Projects\Manager as Projects;
use QUI\Projects\Project;
use QUI\Setup;
use ReflectionProperty;

final class InstalledPackagesCacheTest extends TestCase
{
    public function testSetupReloadsInstalledPackagesBeforeSettingUpProjects(): void
    {
        $directory = sys_get_temp_dir() . '/quiqqer-package-cache-' . bin2hex(random_bytes(8)) . '/';
        mkdir($directory . 'composer', 0700, true);
        $previousCache = null;
        $PreviousManager = QUI::$PackageManager;
        $PreviousConfig = QUI::$Configs['etc/projects.ini'] ?? null;
        $previousProjects = Projects::$projects;
        $PreviousStandard = Projects::$Standard;

        try {
            try {
                $previousCache = LongTermCache::get(Manager::CACHE_NAME_TYPES);
            } catch (\QUI\Cache\Exception) {
            }

            $old = [['name' => 'phpunit/removed-package', 'version' => '1.0.0']];
            $new = [['name' => 'phpunit/installed-package', 'version' => '2.0.0']];
            file_put_contents($directory . 'composer/installed.json', json_encode(['packages' => $new]));
            LongTermCache::set(Manager::CACHE_NAME_TYPES, $old);
            $Manager = new Manager();
            (new ReflectionProperty(Manager::class, 'dir'))->setValue($Manager, $directory);

            self::assertSame($old, $Manager->getInstalled());

            $Project = $this->createMock(Project::class);
            $Project->expects(self::once())->method('setup')->with(['executePackagesSetup' => false])
                ->willReturnCallback(static function () use ($Manager, $new): void {
                    self::assertSame($new, $Manager->getInstalled(), 'Package list must be fresh before project setup');
                });
            $Config = $this->createMock(Config::class);
            $Config->method('toArray')->willReturn([
                'packageCacheTest' => ['default_lang' => 'en', 'template' => '']
            ]);
            QUI::$Configs['etc/projects.ini'] = $Config;
            QUI::$PackageManager = $Manager;
            Projects::$projects = ['packageCacheTest' => ['en' => $Project]];

            Setup::executeEachProjectSetup();
            self::assertSame($new, $Manager->getInstalled());

            $FreshManager = new Manager();
            (new ReflectionProperty(Manager::class, 'dir'))->setValue($FreshManager, $directory);
            self::assertSame($new, $FreshManager->getInstalled());
        } finally {
            QUI::$PackageManager = $PreviousManager;
            Projects::$projects = $previousProjects;
            Projects::$Standard = $PreviousStandard;

            if ($PreviousConfig === null) {
                unset(QUI::$Configs['etc/projects.ini']);
            } else {
                QUI::$Configs['etc/projects.ini'] = $PreviousConfig;
            }

            LongTermCache::clear(Manager::CACHE_NAME_TYPES);

            if ($previousCache !== null) {
                LongTermCache::set(Manager::CACHE_NAME_TYPES, $previousCache);
            }

            unlink($directory . 'composer/installed.json');
            rmdir($directory . 'composer');
            rmdir($directory);
        }
    }
}
