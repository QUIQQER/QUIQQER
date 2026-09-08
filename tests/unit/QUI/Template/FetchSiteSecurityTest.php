<?php

declare(strict_types=1);

namespace QUITests\Template;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Interfaces\Projects\Site;
use QUI\Interfaces\Template\EngineInterface;
use QUI\Package\Manager as PackageManager;
use QUI\Package\Package;
use QUI\Projects\Project;
use QUI\Template;

require_once __DIR__ . '/FetchSiteTestTemplate.php';

final class FetchSiteSecurityTest extends TestCase
{
    private string $root;
    private EngineInterface&MockObject $Engine;
    private PackageManager&MockObject $PackageManager;
    private array $packages = [];
    private array $installedTemplates = [];
    private array $siteTypes = [];
    private array $originalGlobals = [];

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/quiqqer-fetch-site-' . bin2hex(random_bytes(6)) . '/';
        mkdir($this->root . 'packages', 0777, true);
        mkdir($this->root . 'usr', 0777, true);
        $GLOBALS['quiqqerTemplateMarkers'] = [];

        foreach (['PackageManager', 'Rewrite', 'Users', 'Locale', 'Events'] as $property) {
            $this->originalGlobals[$property] = QUI::${$property};
        }

        $this->Engine = $this->createMock(EngineInterface::class);
        $this->Engine->method('fetch')->willReturn('<body></body>');

        $this->PackageManager = $this->createMock(PackageManager::class);
        $this->PackageManager->method('searchInstalledPackages')->willReturnCallback(
            fn(): array => array_map(static fn(string $name): array => ['name' => $name], $this->installedTemplates)
        );
        $this->PackageManager->method('getAvailableSiteTypes')->willReturnCallback(
            fn(): array => ['test' => array_map(static fn(string $type): array => ['type' => $type], $this->siteTypes)]
        );
        $this->PackageManager->method('getInstalledPackage')->willReturnCallback(
            function (string $name): Package {
                if (!isset($this->packages[$name])) {
                    throw new QUI\Exception('Package not installed');
                }
                return $this->packages[$name];
            }
        );
        $this->PackageManager->method('getLastUpdateDate')->willReturn(0);

        QUI::$PackageManager = $this->PackageManager;
        QUI::$Rewrite = $this->createMock(QUI\Rewrite::class);
        QUI::$Rewrite->method('getVHosts')->willReturn([]);
        QUI::$Users = $this->createMock(QUI\Users\Manager::class);
        QUI::$Users->method('getUserBySession')->willReturn($this->createMock(QUI\Users\User::class));
        QUI::$Locale = $this->createMock(QUI\Locale::class);
        QUI::$Events = $this->createMock(QUI\Events\Manager::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->originalGlobals as $property => $value) {
            QUI::${$property} = $value;
        }
        unset($GLOBALS['quiqqerTemplateMarkers']);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            is_dir($file->getPathname()) && !is_link($file->getPathname())
                ? rmdir($file->getPathname())
                : unlink($file->getPathname());
        }
        rmdir($this->root);
    }

    public function testRegisteredTemplateIndexAndStandardTypeAreIncluded(): void
    {
        $this->registerTemplate('vendor/template');
        $this->marker('packages/template/index.php', 'template-index');
        $this->marker('packages/template/standard.php', 'standard');

        $this->render('vendor/template', 'standard');

        self::assertSame(['template-index', 'standard'], $GLOBALS['quiqqerTemplateMarkers']);
    }

    public function testTemplateParentAndVHostFallbacksAreIncluded(): void
    {
        $Parent = $this->registerTemplate('vendor/parent');
        $this->marker('packages/parent/index.php', 'parent-index');
        $this->registerTemplate('vendor/child', $Parent);

        $this->render('vendor/child', 'standard');
        self::assertSame(['parent-index'], $GLOBALS['quiqqerTemplateMarkers']);

        $GLOBALS['quiqqerTemplateMarkers'] = [];
        QUI::$Rewrite = $this->createMock(QUI\Rewrite::class);
        QUI::$Rewrite->method('getVHosts')->willReturn([
            ['project' => 'project', 'template' => 'vendor/parent']
        ]);

        $this->render('', 'standard');
        self::assertSame(['parent-index'], $GLOBALS['quiqqerTemplateMarkers']);
    }

    #[DataProvider('missingTemplateParentProvider')]
    public function testMissingTemplateParentStillRendersChildTemplate(bool $viaVHost): void
    {
        $Package = $this->registerPackage('vendor/child');
        $Package->method('hasTemplateParent')->willReturn(true);
        $Package->method('getTemplateParent')->willReturn(null);
        $this->installedTemplates[] = 'vendor/child';
        $this->marker('packages/child/index.php', 'child-index');
        $this->Engine->expects(self::once())->method('fetch');

        if ($viaVHost) {
            QUI::$Rewrite = $this->createMock(QUI\Rewrite::class);
            QUI::$Rewrite->method('getVHosts')->willReturn([
                ['project' => 'project', 'template' => 'vendor/child']
            ]);
        }

        $this->render($viaVHost ? '' : 'vendor/child', 'standard');

        self::assertSame(['child-index'], $GLOBALS['quiqqerTemplateMarkers']);
    }

    public static function missingTemplateParentProvider(): array
    {
        return ['project template' => [false], 'virtual host template' => [true]];
    }

    public function testRegisteredSiteTypeUsesTemplateAndProjectOverrideOrder(): void
    {
        $this->registerTemplate('vendor/template');
        $this->registerPackage('vendor/site');
        $this->siteTypes[] = 'vendor/site:blog';
        $this->marker('packages/site/blog.php', 'package-site');
        $this->marker('packages/template/vendor/site/blog.php', 'template-site');
        $this->marker('usr/lib/vendor/template/blog.php', 'project-template');
        $this->marker('usr/project/lib/vendor/site/blog.php', 'project-site');

        $this->render('vendor/template', 'vendor/site:blog');

        self::assertSame(['project-site', 'project-template'], $GLOBALS['quiqqerTemplateMarkers']);
    }

    public function testUnregisteredAndTraversalMetadataDoNotIncludeMarkers(): void
    {
        $this->registerTemplate('vendor/template');
        $this->registerPackage('vendor/site');
        $this->marker('packages/template-evil/index.php', 'evil-template');
        $this->marker('packages/evil.php', 'evil-site');

        $this->render('vendor/not-registered', 'vendor/site:not-registered');
        self::assertSame([], $GLOBALS['quiqqerTemplateMarkers']);

        $this->siteTypes[] = 'vendor/site:../evil';
        $this->render('vendor/template', 'vendor/site:../evil');
        self::assertSame([], $GLOBALS['quiqqerTemplateMarkers']);
    }

    public function testTemplatePrefixSymlinkAndProjectIndexHandling(): void
    {
        $this->registerTemplate('vendor/template');
        $this->marker('packages/template-evil/index.php', 'evil-template');
        symlink($this->root . 'packages/template-evil/index.php', $this->root . 'packages/template/index.php');

        $this->render('vendor/template', 'standard');
        self::assertSame([], $GLOBALS['quiqqerTemplateMarkers']);

        $this->marker('usr/project/lib/index.php', 'project-index');
        $this->render('vendor/template', 'standard');
        self::assertSame(['project-index'], $GLOBALS['quiqqerTemplateMarkers']);
    }

    private function render(string $template, string $type): void
    {
        $Project = $this->createMock(Project::class);
        $Project->method('getTemplate')->willReturn($template);
        $Project->method('getName')->willReturn('project');

        $Site = $this->createMock(Site::class);
        $Site->method('getProject')->willReturn($Project);
        $Site->method('getAttribute')->willReturnCallback(
            static fn(string $name): mixed => match ($name) {
                'type' => $type,
                'quiqqer.site.template' => false,
                default => null
            }
        );

        (new FetchSiteTestTemplate($this->Engine, $this->root . 'usr/'))->fetchSite($Site);
    }

    private function registerTemplate(string $name, ?Package $Parent = null): Package
    {
        $Package = $this->registerPackage($name);
        $Package->method('hasTemplateParent')->willReturn($Parent !== null);
        $Package->method('getTemplateParent')->willReturn($Parent);
        $this->installedTemplates[] = $name;
        return $Package;
    }

    private function registerPackage(string $name): Package&MockObject
    {
        $directory = $this->root . 'packages/' . substr($name, strrpos($name, '/') + 1);
        mkdir($directory, 0777, true);
        $Package = $this->createMock(Package::class);
        $Package->method('getName')->willReturn($name);
        $Package->method('getDir')->willReturn($directory);
        $this->packages[$name] = $Package;
        return $Package;
    }

    private function marker(string $path, string $marker): void
    {
        $path = $this->root . $path;
        mkdir(dirname($path), 0777, true);
        file_put_contents(
            $path,
            '<?php $GLOBALS[\'quiqqerTemplateMarkers\'][] = ' . var_export($marker, true) . ';'
        );
    }
}
