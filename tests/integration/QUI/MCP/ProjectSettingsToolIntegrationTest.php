<?php

declare(strict_types=1);

namespace QUI\MCP;

use QUI;
use QUI\Projects\ProjectIntegrationTestCase;
use QUI\Projects\ProjectTestHelper;

require_once __DIR__ . '/ProjectTestSettingsTool.php';

class ProjectSettingsToolIntegrationTest extends ProjectIntegrationTestCase
{
    public function testCoreProjectSettingCanBeReadAndUpdated(): void
    {
        $Project = self::getTestProject();
        $setting = ProjectTestSettingsTool::get($Project, 'adminSitemapMax');
        $originalValue = $setting['value'];
        $newValue = $originalValue === 37 ? 38 : 37;

        self::assertSame('integer', $setting['type']);
        self::assertSame('quiqqer/core', $setting['source']);

        try {
            $result = ProjectTestHelper::runAsSystemUser(
                static fn(): array => ProjectTestSettingsTool::update(
                    $Project,
                    ['adminSitemapMax' => $newValue]
                )
            );

            self::assertSame($newValue, $result['settings'][0]['value']);
            self::assertSame($originalValue, $result['settings'][0]['previousValue']);
            self::assertTrue($result['settings'][0]['changed']);
        } finally {
            ProjectTestHelper::runAsSystemUser(
                static fn(): array => ProjectTestSettingsTool::update(
                    self::getTestProject(),
                    ['adminSitemapMax' => $originalValue]
                )
            );
        }
    }

    public function testInvalidBatchDoesNotChangeAValidSetting(): void
    {
        $Project = self::getTestProject();
        $originalValue = ProjectTestSettingsTool::get($Project, 'adminSitemapMax')['value'];
        $newValue = $originalValue === 41 ? 42 : 41;

        try {
            ProjectTestHelper::runAsSystemUser(
                static fn(): array => ProjectTestSettingsTool::update(
                    $Project,
                    [
                        'adminSitemapMax' => $newValue,
                        'unknown.setting' => true
                    ]
                )
            );

            self::fail('An unknown setting must reject the complete batch.');
        } catch (QUI\Exception $Exception) {
            self::assertStringContainsString('Unknown project setting', $Exception->getMessage());
        }

        self::assertSame(
            $originalValue,
            ProjectTestSettingsTool::get(self::getTestProject(), 'adminSitemapMax')['value']
        );
    }
}
