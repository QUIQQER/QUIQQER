<?php

declare(strict_types=1);

namespace QUI\Package;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ComposerStabilityTest extends TestCase
{
    private string $directory;
    private Manager $Manager;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/quiqqer-composer-stability-' . bin2hex(random_bytes(8)) . '/';
        mkdir($this->directory, 0700);

        $this->Manager = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getServerList', 'getList'])
            ->getMock();
        $this->Manager->method('getServerList')->willReturn([]);
        $this->Manager->method('getList')->willReturn([]);

        (new ReflectionProperty(Manager::class, 'varDir'))->setValue($this->Manager, $this->directory);
        (new ReflectionProperty(Manager::class, 'composer_json'))->setValue(
            $this->Manager,
            $this->directory . 'composer.json'
        );
    }

    protected function tearDown(): void
    {
        if (is_file($this->directory . 'composer.json')) {
            unlink($this->directory . 'composer.json');
        }

        rmdir($this->directory);
    }

    public static function stabilitySettings(): array
    {
        return [
            'defaults' => [[], 'stable', true],
            'no stable preference keeps stable default' => [['prefer-stable' => false], 'stable', false],
            'explicit stable without preference' => [
                ['minimum-stability' => 'stable', 'prefer-stable' => false], 'stable', false
            ],
            'explicit beta without preference' => [
                ['minimum-stability' => 'beta', 'prefer-stable' => false], 'beta', false
            ],
            'explicit RC without preference' => [
                ['minimum-stability' => 'RC', 'prefer-stable' => false], 'RC', false
            ],
            'explicit dev with stable preference' => [
                ['minimum-stability' => 'dev', 'prefer-stable' => true], 'dev', true
            ],
            'explicit dev without preference' => [
                ['minimum-stability' => 'dev', 'prefer-stable' => false], 'dev', false
            ],
            'explicit alpha with default preference' => [['minimum-stability' => 'alpha'], 'alpha', true]
        ];
    }

    #[DataProvider('stabilitySettings')]
    public function testRegeneratingComposerJsonPreservesStabilityIndependentlyOfPreference(
        array $settings,
        string $expectedStability,
        bool $expectedPreference
    ): void {
        file_put_contents($this->directory . 'composer.json', json_encode($settings + [
            'require' => ['php' => '^8.2', 'phpunit/explicit-development-package' => 'dev-main']
        ], JSON_THROW_ON_ERROR));

        // A second refresh must not silently broaden the allowed stability either.
        for ($refresh = 0; $refresh < 2; $refresh++) {
            $this->Manager->refreshServerList();
            $config = $this->readComposerJson();

            self::assertSame($expectedStability, $config['minimum-stability']);
            self::assertSame($expectedPreference, $config['prefer-stable']);
            self::assertSame('dev-main', $config['require']['phpunit/explicit-development-package']);
        }
    }

    public function testNewComposerJsonDefaultsToStable(): void
    {
        $this->Manager->refreshServerList();
        $config = $this->readComposerJson();

        self::assertSame('stable', $config['minimum-stability']);
        self::assertTrue($config['prefer-stable']);
    }

    public function testRegeneratingComposerJsonPrefersArchives(): void
    {
        file_put_contents($this->directory . 'composer.json', json_encode([
            'config' => ['preferred-install' => 'source'],
            'require' => ['php' => '^8.2']
        ], JSON_THROW_ON_ERROR));

        for ($refresh = 0; $refresh < 2; $refresh++) {
            $this->Manager->refreshServerList();
            $config = $this->readComposerJson();

            self::assertSame('dist', $config['config']['preferred-install']);
            self::assertSame(DEVELOPMENT ? 'dev' : 'stable', $config['minimum-stability']);
            self::assertSame(!DEVELOPMENT, $config['prefer-stable']);
        }
    }

    public function testDevelopmentModeAlsoPrefersArchives(): void
    {
        // DEVELOPMENT is immutable, so exercise the real generator in a separate runtime.
        $Process = proc_open([
            PHP_BINARY,
            dirname(__DIR__, 4) . '/tools/phpunit',
            '--no-configuration',
            '--bootstrap',
            __DIR__ . '/Fixtures/development-bootstrap.php',
            '--filter',
            'testRegeneratingComposerJsonPrefersArchives',
            __FILE__
        ], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        self::assertIsResource($Process);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        self::assertSame(0, proc_close($Process), $errors . $output);
    }

    private function readComposerJson(): array
    {
        return json_decode(file_get_contents($this->directory . 'composer.json'), true, flags: JSON_THROW_ON_ERROR);
    }
}
