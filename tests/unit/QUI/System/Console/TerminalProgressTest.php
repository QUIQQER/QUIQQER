<?php

namespace QUI\System\Console;

use PHPUnit\Framework\TestCase;
use RuntimeException;

use function fclose;
use function fopen;
use function rewind;
use function stream_get_contents;

class TerminalProgressTest extends TestCase
{
    public function testRunReportsIndeterminateProgressAndHidesItAfterwards(): void
    {
        $output = fopen('php://memory', 'w+');
        $Progress = new TerminalProgress($output, true, 'core', false);

        $result = $Progress->run(static fn(): string => 'done');

        rewind($output);

        $this->assertSame('done', $result);
        $this->assertSame(
            "\033[22;0t\033]9;4;3;0\033\\\033]0;⠋ QUIQQER (core)\007\033]9;4;0;0\033\\\033[23;0t",
            stream_get_contents($output)
        );

        fclose($output);
    }

    public function testRunHidesProgressWhenCallbackThrows(): void
    {
        $output = fopen('php://memory', 'w+');
        $Progress = new TerminalProgress($output, true, 'core', false);

        try {
            $Progress->run(static function (): void {
                throw new RuntimeException('Test exception');
            });

            $this->fail('Expected callback exception was not thrown.');
        } catch (RuntimeException $Exception) {
            $this->assertSame('Test exception', $Exception->getMessage());
        }

        rewind($output);

        $this->assertSame(
            "\033[22;0t\033]9;4;3;0\033\\\033]0;⠋ QUIQQER (core)\007\033]9;4;0;0\033\\\033[23;0t",
            stream_get_contents($output)
        );

        fclose($output);
    }

    public function testNonInteractiveOutputDoesNotReceiveControlSequences(): void
    {
        $output = fopen('php://memory', 'w+');
        $Progress = new TerminalProgress($output);

        $Progress->run(static fn(): string => 'done');

        rewind($output);

        $this->assertSame('', stream_get_contents($output));

        fclose($output);
    }
}
