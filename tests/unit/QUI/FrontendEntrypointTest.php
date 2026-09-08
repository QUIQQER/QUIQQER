<?php

namespace QUITests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FrontendEntrypointTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function errors(): iterable
    {
        yield 'page exception' => ['page', 'RuntimeException', true];
        yield 'page type error' => ['page', 'TypeError', true];
        yield 'page error' => ['page', 'Error', true];
        yield 'page parse error from included code' => ['page', 'ParseError', true];
        yield 'routing type error stays in global handler' => ['routing', 'TypeError', false];
        yield 'routing error stays in global handler' => ['routing', 'Error', false];
        yield 'routing exception keeps existing error page' => ['routing', 'RuntimeException', true];
    }

    #[DataProvider('errors')]
    public function testErrorResponse(string $phase, string $class, bool $expectHtml): void
    {
        $root = dirname(__DIR__, 3);
        $environment = getenv();
        $environment['QUIQQER_TEST_ERROR_PHASE'] = $phase;
        $environment['QUIQQER_TEST_ERROR_CLASS'] = $class;

        $process = proc_open(
            [PHP_BINARY, $root . '/index.php'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            __DIR__ . '/Fixtures/FrontendEntrypoint',
            $environment
        );
        self::assertIsResource($process);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $errorOutput = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        self::assertSame(0, $exitCode, (string)$errorOutput);
        self::assertSame($class . ': private error details', $errorOutput);
        self::assertIsString($output);
        self::assertStringNotContainsString('private error details', $output);

        if ($expectHtml) {
            self::assertSame(file_get_contents($root . '/src/templates/error.html'), $output);
        } else {
            self::assertSame(['error' => true, 'message' => 'global handler'], json_decode($output, true));
        }
    }
}
