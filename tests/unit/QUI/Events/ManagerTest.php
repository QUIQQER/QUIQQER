<?php

namespace QUI\Events;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Config;

#[RunTestsInSeparateProcesses]
class ManagerTest extends TestCase
{
    protected string $cacheFile;

    protected ?string $cacheBackup = null;

    protected ?Manager $originalGlobalEvents = null;

    public static function persistedNoArgsHandler(): string
    {
        return 'persisted';
    }

    public static function persistedWithArgsHandler(string $value): string
    {
        return 'persisted:' . $value;
    }

    protected function setUp(): void
    {
        $this->cacheFile = $this->invokeProtectedStatic(Manager::class, 'getCacheFile');
        $this->originalGlobalEvents = QUI::$Events;

        QUI::$Conf = $this->createMock(Config::class);

        if (file_exists($this->cacheFile)) {
            $this->cacheBackup = file_get_contents($this->cacheFile) ?: '';
        }

        @mkdir(dirname($this->cacheFile), 0777, true);
        file_put_contents($this->cacheFile, "<?php\n\nreturn ['events' => [], 'siteEvents' => []];\n");
        QUI::$Events = null;
    }

    protected function tearDown(): void
    {
        if ($this->cacheBackup !== null) {
            file_put_contents($this->cacheFile, $this->cacheBackup);
        } elseif (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }

        QUI::$Events = $this->originalGlobalEvents;
    }

    public function testAddEventPersistsStringCallbackAndLoadsIntoRuntime(): void
    {
        $sut = new Manager();

        $sut->addEvent('onPersist', self::class . '::persistedNoArgsHandler', 8, 'pkg/test');

        $data = require $this->cacheFile;

        $this->assertSame('onPersist', $data['events'][0]['event']);
        $this->assertSame(self::class . '::persistedNoArgsHandler', $data['events'][0]['callback']);
        $this->assertSame('pkg/test', $data['events'][0]['package']);
        $this->assertSame(8, $data['events'][0]['priority']);
        $this->assertArrayHasKey('onPersist', $sut->getList());
    }

    public function testAddEventWithClosureOnlyUpdatesRuntime(): void
    {
        $sut = new Manager();
        $sut->addEvent('onRuntime', static function (): string {
            return 'runtime';
        });

        $data = require $this->cacheFile;

        $this->assertSame([], $data['events']);
        $this->assertArrayHasKey('onRuntime', $sut->getList());
    }

    public function testAddEventReplacesExistingPersistedEntry(): void
    {
        $sut = new Manager();

        $sut->addEvent('onPersist', self::class . '::persistedNoArgsHandler', 1, 'pkg/test');
        $sut->addEvent('onPersist', self::class . '::persistedNoArgsHandler', 9, 'pkg/test');

        $data = require $this->cacheFile;

        $this->assertCount(1, $data['events']);
        $this->assertSame(9, $data['events'][0]['priority']);
    }

    public function testAddSiteEventPersistsStringCallback(): void
    {
        $sut = new Manager();

        $sut->addSiteEvent('onSite', self::class . '::persistedNoArgsHandler', 'site/type', 6);
        $data = require $this->cacheFile;

        $this->assertSame('onSite', $data['siteEvents'][0]['event']);
        $this->assertSame('site/type', $data['siteEvents'][0]['sitetype']);
        $this->assertSame(6, $data['siteEvents'][0]['priority']);
        $this->assertSame($data['siteEvents'], $sut->getSiteListByType('site/type'));
    }

    public function testAddSiteEventIgnoresNonStringCallbacks(): void
    {
        $sut = new Manager();

        $sut->addSiteEvent('onSite', static function (): void {
        }, 'site/type');

        $data = require $this->cacheFile;

        $this->assertSame([], $data['siteEvents']);
    }

    public function testRemoveEventRemovesPersistedStringCallback(): void
    {
        $sut = new Manager();
        $sut->addEvent('onPersist', self::class . '::persistedNoArgsHandler', 0, 'pkg/test');

        $sut->removeEvent('onPersist', self::class . '::persistedNoArgsHandler', 'pkg/test');
        $data = require $this->cacheFile;

        $this->assertSame([], $data['events']);
    }

    public function testRemoveEventWithoutCallbackRemovesWholeEvent(): void
    {
        $sut = new Manager();
        $sut->addEvent('onPersist', self::class . '::persistedNoArgsHandler', 0, 'pkg/test');
        $sut->addEvent('onPersist', self::class . '::persistedWithArgsHandler', 0, 'pkg/test');

        $sut->removeEvent('onPersist');
        $data = require $this->cacheFile;

        $this->assertSame([], $data['events']);
    }

    public function testRemovePackageEventsRemovesRuntimeAndPersistedEntries(): void
    {
        $sut = new Manager();
        $sut->addEvent('onOne', self::class . '::persistedNoArgsHandler', 0, 'pkg/remove');
        $sut->addEvent('onTwo', self::class . '::persistedWithArgsHandler', 0, 'pkg/keep');

        $package = $this->getMockBuilder(\QUI\Package\Package::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getName'])
            ->getMock();
        $package->method('getName')->willReturn('pkg/remove');

        $sut->removePackageEvents($package);
        $data = require $this->cacheFile;

        $this->assertCount(1, $data['events']);
        $this->assertSame('pkg/keep', $data['events'][0]['package']);
        $this->assertArrayNotHasKey('onOne', $sut->getList());
    }

    public function testRemoveEventsOnlyAffectsRuntimeStack(): void
    {
        $sut = new Manager();
        $sut->addEvent('onPersist', self::class . '::persistedNoArgsHandler', 0, 'pkg/test');

        $sut->removeEvents([
            'onPersist' => self::class . '::persistedNoArgsHandler'
        ]);

        $data = require $this->cacheFile;

        $this->assertCount(1, $data['events']);
        $this->assertArrayNotHasKey('onPersist', $sut->getList());
    }

    public function testFireEventDelegatesToRuntimeEvents(): void
    {
        $sut = new Manager();
        $sut->addEvent('onPersist', self::class . '::persistedWithArgsHandler');

        $result = $sut->fireEvent('onPersist', ['value']);

        $this->assertSame([self::class . '::persistedWithArgsHandler' => 'persisted:value'], $result);
    }

    public function testFireEventSwallowsOnFireEventFailures(): void
    {
        $sut = new Manager();
        $sut->addEvent('onFireEvent', static function (): void {
            throw new \RuntimeException('observer failed');
        });
        $sut->addEvent('onPersist', self::class . '::persistedNoArgsHandler');

        $result = $sut->fireEvent('onPersist');

        $this->assertSame([self::class . '::persistedNoArgsHandler' => 'persisted'], $result);
    }

    public function testIgnoreAndClearIgnoreDelegateToRuntimeEventStack(): void
    {
        $sut = new Manager();
        $sut->addEvent('onPersist', self::class . '::persistedNoArgsHandler', 0, 'pkg/test');

        $sut->ignore('pkg/test');
        $this->assertSame([], $sut->fireEvent('onPersist'));

        $sut->clearIgnore();
        $this->assertSame([self::class . '::persistedNoArgsHandler' => 'persisted'], $sut->fireEvent('onPersist'));
    }

    public function testClearRemovesAllPersistedEventsAndResetsGlobalManager(): void
    {
        $sut = new Manager();
        $sut->addEvent('onPersist', self::class . '::persistedNoArgsHandler', 0, 'pkg/test');
        $sut->addSiteEvent('onSite', self::class . '::persistedNoArgsHandler', 'site/type');
        QUI::$Events = $sut;

        Manager::clear();
        $data = require $this->cacheFile;

        $this->assertSame([], $data['events']);
        $this->assertSame([], $data['siteEvents']);
        $this->assertSame([], QUI::$Events->getList());
    }

    public function testClearForPackageRemovesOnlyMatchingPersistedEvents(): void
    {
        $sut = new Manager();
        $sut->addEvent('onOne', self::class . '::persistedNoArgsHandler', 0, 'pkg/remove');
        $sut->addEvent('onTwo', self::class . '::persistedWithArgsHandler', 0, 'pkg/keep');
        QUI::$Events = $sut;

        Manager::clear('pkg/remove');
        $data = require $this->cacheFile;

        $this->assertCount(1, $data['events']);
        $this->assertSame('pkg/keep', $data['events'][0]['package']);
        $this->assertArrayNotHasKey('onOne', QUI::$Events->getList());
    }

    public function testManagerLoadsPersistedEventsFromCacheOnConstruction(): void
    {
        file_put_contents(
            $this->cacheFile,
            "<?php\n\nreturn " . var_export([
                'events' => [[
                    'event' => 'onPersist',
                    'callback' => self::class . '::persistedNoArgsHandler',
                    'package' => 'pkg/test',
                    'priority' => 4
                ]],
                'siteEvents' => [[
                    'event' => 'onSite',
                    'callback' => self::class . '::persistedNoArgsHandler',
                    'sitetype' => 'site/type',
                    'priority' => 3
                ]]
            ], true) . ";\n"
        );

        $sut = new Manager();

        $this->assertArrayHasKey('onPersist', $sut->getList());
        $this->assertCount(1, $sut->getSiteListByType('site/type'));
    }

    public function testManagerRebuildsMissingCacheFileFromXmlDefinitions(): void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }

        $sut = new Manager();
        $data = require $this->cacheFile;

        $this->assertFileExists($this->cacheFile);
        $this->assertNotEmpty($data['events']);
        $this->assertArrayHasKey('onPackageSetup', $sut->getList());
    }

    public function testReadCacheDataReturnsEmptyArraysForInvalidCacheFile(): void
    {
        file_put_contents($this->cacheFile, "<?php\n\nreturn 'invalid';\n");

        $data = $this->invokeProtectedStatic(Manager::class, 'readCacheData');

        $this->assertSame(['events' => [], 'siteEvents' => []], $data);
    }
    protected function invokeProtectedStatic(string $class, string $method, mixed ...$args): mixed
    {
        $ReflectionMethod = new \ReflectionMethod($class, $method);
        $ReflectionMethod->setAccessible(true);

        return $ReflectionMethod->invokeArgs(null, $args);
    }
}
