<?php

namespace QUI\System;

use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\Config;
use QUI\Log\Logger;
use RuntimeException;

class LogTest extends TestCase
{
    private MonologLogger $OriginalLogger;

    private TestHandler $Handler;

    private Config $LogConfig;

    /**
     * @var array<string, mixed>
     */
    private array $OriginalLogLevels;

    protected function setUp(): void
    {
        $this->OriginalLogger = Logger::getLogger();
        $this->Handler = new TestHandler();
        Logger::$Logger = new MonologLogger('test', [$this->Handler]);

        $LogConfig = Logger::getPackage()->getConfig();
        self::assertNotNull($LogConfig);
        $this->LogConfig = $LogConfig;
        $this->OriginalLogLevels = $LogConfig->get('log_levels');
    }

    protected function tearDown(): void
    {
        Logger::$Logger = $this->OriginalLogger;
        $this->LogConfig->setSection('log_levels', $this->OriginalLogLevels);
    }

    /**
     * @return iterable<string, array{int, Level}>
     */
    public static function logLevelProvider(): iterable
    {
        yield 'debug' => [Log::LEVEL_DEBUG, Level::Debug];
        yield 'deprecated' => [Log::LEVEL_DEPRECATED, Level::Warning];
        yield 'info' => [Log::LEVEL_INFO, Level::Info];
        yield 'notice' => [Log::LEVEL_NOTICE, Level::Notice];
        yield 'warning' => [Log::LEVEL_WARNING, Level::Warning];
        yield 'error' => [Log::LEVEL_ERROR, Level::Error];
        yield 'critical' => [Log::LEVEL_CRITICAL, Level::Critical];
        yield 'alert' => [Log::LEVEL_ALERT, Level::Alert];
        yield 'emergency' => [Log::LEVEL_EMERGENCY, Level::Emergency];
        yield 'unknown levels retain the legacy error fallback' => [999, Level::Error];
    }

    #[DataProvider('logLevelProvider')]
    public function testWriteMapsQuiqqerLevelsToMonologLevels(int $logLevel, Level $expectedLevel): void
    {
        Log::write('test', $logLevel, force: true);

        self::assertSame($expectedLevel, $this->Handler->getRecords()[0]->level);
    }

    public function testDeprecatedLogsAlwaysUseTheDeprecatedFilename(): void
    {
        Log::write(
            'Deprecated call',
            Log::LEVEL_DEPRECATED,
            ['filename' => 'context'],
            'argument',
            true
        );

        self::assertSame('deprecated', $this->Handler->getRecords()[0]->context['filename']);
    }

    public function testFalseFilenameDoesNotAddFilenameContext(): void
    {
        Log::write('test', Log::LEVEL_INFO, filename: false, force: true);

        self::assertArrayNotHasKey('filename', $this->Handler->getRecords()[0]->context);
    }

    public function testDisabledLogLevelsAreFilteredBeforeCallingMonolog(): void
    {
        $this->LogConfig->setValue('log_levels', 'warning', 0);

        Log::write('test', Log::LEVEL_WARNING);

        self::assertSame([], $this->Handler->getRecords());
    }

    public function testForceBypassesDisabledLogLevels(): void
    {
        $this->LogConfig->setValue('log_levels', 'warning', 0);

        Log::write('test', Log::LEVEL_WARNING, force: true);

        self::assertSame(Level::Warning, $this->Handler->getRecords()[0]->level);
    }

    public function testUnknownLogLevelsAreFilteredUnlessForced(): void
    {
        Log::write('filtered', 999);
        Log::write('forced', 999, force: true);

        $records = $this->Handler->getRecords();
        self::assertCount(1, $records);
        self::assertSame('forced', $records[0]->message);
    }

    public function testWriteExceptionUsesStructuredContextWithoutDuplicatingTheMessage(): void
    {
        $Exception = new \QUI\Exception('Something failed', 42, ['operation' => 'test']);

        Log::writeException(
            $Exception,
            Log::LEVEL_ERROR,
            ['exception' => 'stale', 'exceptionContext' => 'stale'],
            force: true
        );
        $record = $this->Handler->getRecords()[0];

        self::assertSame(
            [
                'message' => 'Something failed',
                'exception' => $Exception,
                'exceptionContext' => ['operation' => 'test']
            ],
            [
                'message' => $record->message,
                'exception' => $record->context['exception'],
                'exceptionContext' => $record->context['exceptionContext']
            ]
        );
    }

    public function testWriteExceptionSupportsThrowablesWithoutQuiqqerContext(): void
    {
        $Exception = new RuntimeException('Something failed', 42);

        Log::writeException($Exception, force: true);
        $record = $this->Handler->getRecords()[0];

        self::assertSame($Exception, $record->context['exception']);
        self::assertArrayNotHasKey('exceptionContext', $record->context);
    }

    public function testWriteDebugExceptionIsFilteredByTheRequestedLevelInsteadOfDebugMode(): void
    {
        $Exception = new RuntimeException('Debug details');

        Log::writeDebugException($Exception, Log::LEVEL_ERROR, force: true);

        self::assertSame($Exception, $this->Handler->getRecords()[0]->context['exception']);
    }

    public function testWriteRecursiveSerializesValuesWithPrintR(): void
    {
        Log::writeRecursive(['operation' => 'login'], force: true);

        self::assertStringContainsString('[operation] => login', $this->Handler->getRecords()[0]->message);
    }
}
