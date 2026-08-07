<?php

declare(strict_types=1);

namespace QUI\MCP;

use Mcp\Server\Builder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\MCP\Project\AddLanguage;
use QUI\MCP\Project\GetSetting;
use QUI\MCP\Project\ListSettings;
use QUI\MCP\Project\SetSetting;
use QUI\MCP\Project\UpdateSettings;
use QUI\MCP\System\GetSystemInfo;
use QUI\MCP\VHost\CreateVHost;
use QUI\MCP\VHost\DeleteVHost;
use QUI\MCP\VHost\GetVHost;
use QUI\MCP\VHost\ListVHosts;
use QUI\MCP\VHost\UpdateVHost;
use ReflectionProperty;

class ToolRegistrationTest extends TestCase
{
    /**
     * @return iterable<string, array{ToolInterface, string, array<int, string>}>
     */
    public static function toolProvider(): iterable
    {
        yield 'add project language' => [
            new AddLanguage(),
            'quiqqer_projects_add_language',
            ['project', 'lang']
        ];
        yield 'list project settings' => [
            new ListSettings(),
            'quiqqer_project_settings_list',
            ['project']
        ];
        yield 'get project setting' => [
            new GetSetting(),
            'quiqqer_project_setting_get',
            ['project', 'key']
        ];
        yield 'set project setting' => [
            new SetSetting(),
            'quiqqer_project_setting_set',
            ['project', 'key', 'value']
        ];
        yield 'update project settings' => [
            new UpdateSettings(),
            'quiqqer_project_settings_update',
            ['project', 'settings']
        ];
        yield 'get system information' => [
            new GetSystemInfo(),
            'quiqqer_system_info_get',
            []
        ];
        yield 'list VHosts' => [
            new ListVHosts(),
            'quiqqer_vhosts_list',
            []
        ];
        yield 'get VHost' => [
            new GetVHost(),
            'quiqqer_vhosts_get',
            ['host']
        ];
        yield 'create VHost' => [
            new CreateVHost(),
            'quiqqer_vhosts_create',
            ['host', 'project', 'rootLanguage']
        ];
        yield 'update VHost' => [
            new UpdateVHost(),
            'quiqqer_vhosts_update',
            ['host']
        ];
        yield 'delete VHost' => [
            new DeleteVHost(),
            'quiqqer_vhosts_delete',
            ['host']
        ];
    }

    /**
     * @param array<int, string> $required
     */
    #[DataProvider('toolProvider')]
    public function testToolRegistration(
        ToolInterface $Tool,
        string $expectedName,
        array $required
    ): void {
        $Builder = new Builder();
        $Tool->register($Builder);

        $tools = (new ReflectionProperty(Builder::class, 'tools'))->getValue($Builder);

        self::assertCount(1, $tools);
        self::assertSame($expectedName, $tools[0]['name']);
        self::assertSame($required, $tools[0]['inputSchema']['required'] ?? []);
        self::assertFalse($tools[0]['inputSchema']['additionalProperties'] ?? false);
    }

    public function testProviderContainsRegisteredTools(): void
    {
        $Provider = new Provider();
        $tools = (new ReflectionProperty(Provider::class, 'tools'))->getValue($Provider);
        $classes = array_map(
            static fn(ToolInterface $Tool): string => $Tool::class,
            $tools
        );

        foreach (self::toolProvider() as [$Tool]) {
            self::assertContains($Tool::class, $classes);
        }
    }
}
