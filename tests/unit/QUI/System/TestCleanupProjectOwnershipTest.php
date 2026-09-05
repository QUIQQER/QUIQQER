<?php

declare(strict_types=1);

namespace QUI\System;

use PHPUnit\Framework\TestCase;

final class TestCleanupProjectOwnershipTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . '/quiqqer-project-owner-' . bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/phpunit-project-locks/*.lock') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir($this->directory . '/phpunit-project-locks')) {
            rmdir($this->directory . '/phpunit-project-locks');
        }

        rmdir($this->directory);
        parent::tearDown();
    }

    public function testAnotherTestRunCannotRemoveAnActiveProject(): void
    {
        [$Owner, $pipes] = $this->startProcess('hold');

        try {
            self::assertSame("claimed\n", fgets($pipes[1]));
            self::assertSame("[]\n", $this->runProcess('cleanup'));
            self::assertSame("busy\n", $this->runProcess('claim'));
        } finally {
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }

            proc_close($Owner);
        }

        self::assertSame("claimed\n", $this->runProcess('claim'));
    }

    public function testUnexpectedOwnerTerminationReleasesTheProject(): void
    {
        [$Owner, $pipes] = $this->startProcess('hold');

        try {
            self::assertSame("claimed\n", fgets($pipes[1]));
            self::assertTrue(proc_terminate($Owner));
        } finally {
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }

            proc_close($Owner);
        }

        self::assertSame("claimed\n", $this->runProcess('claim'));
    }

    public function testForkedProcessDoesNotRunItsParentsCleanup(): void
    {
        if (!function_exists('pcntl_fork')) {
            self::markTestSkipped('pcntl is required to verify cleanup after fork.');
        }

        $output = $this->runProcess('fork');
        self::assertMatchesRegularExpression('/\Aparent:(\d+)\ncleanup:\1\n\z/', $output);
    }

    /** @return array{resource, array<int, resource>} */
    private function startProcess(string $mode): array
    {
        $Process = proc_open([
            PHP_BINARY,
            dirname(__DIR__, 3) . '/fixtures/project-owner.php.fixture',
            dirname(__DIR__, 4) . '/src/QUI/System/TestCleanup.php',
            $this->directory,
            $mode
        ], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($Process);

        return [$Process, $pipes];
    }

    private function runProcess(string $mode): string
    {
        [$Process, $pipes] = $this->startProcess($mode);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($Process);
        self::assertSame(0, $status, $errors);
        self::assertSame('', $errors);

        return $output;
    }
}
