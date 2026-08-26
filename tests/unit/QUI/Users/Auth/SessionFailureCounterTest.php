<?php

namespace QUI\Users\Auth;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use QUI\Session;

class SessionFailureCounterTest extends TestCase
{
    public function testFirstAndSecondFailureAreStored(): void
    {
        $Session = $this->createMock(Session::class);
        $Session->expects(self::exactly(2))
            ->method('get')
            ->with('auth-failures-primary')
            ->willReturnOnConsecutiveCalls(false, 1);
        $Session->expects(self::exactly(2))
            ->method('set')
            ->willReturnCallback(static function (string $key, int $failures): void {
                static $expectedFailure = 1;

                self::assertSame('auth-failures-primary', $key);
                self::assertSame($expectedFailure, $failures);
                $expectedFailure++;
            });
        $Session->expects(self::never())->method('destroy');

        $Counter = new SessionFailureCounter($Session);
        $Counter->recordFailure(SessionFailureCounter::STEP_PRIMARY);
        $Counter->recordFailure(SessionFailureCounter::STEP_PRIMARY);
    }

    public function testThirdFailureDestroysSession(): void
    {
        $Session = $this->createMock(Session::class);
        $Session->expects(self::once())
            ->method('get')
            ->with('auth-failures-primary')
            ->willReturn(2);
        $Session->expects(self::never())->method('set');
        $Session->expects(self::once())->method('destroy');

        $Counter = new SessionFailureCounter($Session);
        $Counter->recordFailure(SessionFailureCounter::STEP_PRIMARY);
    }

    public function testPrimaryAndSecondaryFailuresAreCountedSeparately(): void
    {
        $failures = [
            'auth-failures-primary' => 2,
            'auth-failures-secondary' => 0
        ];

        $Session = $this->createMock(Session::class);
        $Session->expects(self::exactly(2))
            ->method('get')
            ->willReturnCallback(static fn(string $key): int => $failures[$key]);
        $Session->expects(self::once())
            ->method('set')
            ->with('auth-failures-secondary', 1);
        $Session->expects(self::once())->method('destroy');

        $Counter = new SessionFailureCounter($Session);
        $Counter->recordFailure(SessionFailureCounter::STEP_SECONDARY);
        $Counter->recordFailure(SessionFailureCounter::STEP_PRIMARY);
    }

    public function testSuccessfulStepResetsOnlyItsCounter(): void
    {
        $Session = $this->createMock(Session::class);
        $Session->expects(self::once())
            ->method('remove')
            ->with('auth-failures-secondary');

        $Counter = new SessionFailureCounter($Session);
        $Counter->reset(SessionFailureCounter::STEP_SECONDARY);
    }

    public function testUnknownAuthenticationStepIsRejected(): void
    {
        $Session = $this->createMock(Session::class);
        $Counter = new SessionFailureCounter($Session);

        $this->expectException(InvalidArgumentException::class);
        $Counter->recordFailure('unknown');
    }
}
