<?php

namespace QUI\Lock;

use PHPUnit\Framework\TestCase;

class ProcessLockConcurrencyTest extends TestCase
{
    public function testParallelProcessesSerializeTheSameResource(): void
    {
        $directory = sys_get_temp_dir() . '/quiqqer-lock-race-' . bin2hex(random_bytes(8));
        mkdir($directory, 0700);
        file_put_contents($directory . '/counter', '0');
        $processes = [];

        try {
            for ($i = 0; $i < 4; $i++) {
                $process = proc_open(
                    [PHP_BINARY, __DIR__ . '/Fixtures/process-lock-worker.php', $directory, (string)$i],
                    [1 => ['file', $directory . '/output-' . $i, 'w'], 2 => ['file', $directory . '/error-' . $i, 'w']],
                    $pipes
                );
                self::assertIsResource($process);
                $processes[] = $process;
            }

            $deadline = microtime(true) + 20;

            while (count(glob($directory . '/ready-*') ?: []) < 4) {
                if (microtime(true) >= $deadline) {
                    self::fail('Workers did not reach the barrier: ' . $this->workerOutput($directory));
                }

                usleep(10000);
            }

            file_put_contents($directory . '/go', 'go');

            while (count(glob($directory . '/done-*') ?: []) < 4) {
                if (microtime(true) >= $deadline) {
                    self::fail('Workers did not complete: ' . $this->workerOutput($directory));
                }

                usleep(10000);
            }

            foreach ($processes as $process) {
                self::assertSame(0, proc_close($process), $this->workerOutput($directory));
            }

            $processes = [];
            self::assertSame('4', file_get_contents($directory . '/counter'));
        } finally {
            foreach ($processes as $process) {
                if (is_resource($process)) {
                    proc_terminate($process);
                    proc_close($process);
                }
            }

            foreach (glob($directory . '/*') ?: [] as $file) {
                unlink($file);
            }

            rmdir($directory);
        }
    }

    private function workerOutput(string $directory): string
    {
        $output = '';

        foreach (array_merge(glob($directory . '/output-*') ?: [], glob($directory . '/error-*') ?: []) as $file) {
            $output .= file_get_contents($file);
        }

        return $output;
    }
}
