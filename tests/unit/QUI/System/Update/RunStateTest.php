<?php

namespace QUI\System\Update;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RunStateTest extends TestCase
{
    public function testTokenValidationAcceptsOriginalToken(): void
    {
        $token = 'secret-token';
        $state = RunState::create(str_repeat('a', 32), hash('sha256', $token), 1000, 600);

        $state->assertToken($token);

        $this->addToAssertionCount(1);
    }

    public function testTokenValidationRejectsWrongToken(): void
    {
        $state = RunState::create(str_repeat('a', 32), hash('sha256', 'secret-token'), 1000, 600);

        $this->expectException(InvalidArgumentException::class);

        $state->assertToken('wrong-token');
    }

    public function testExpirationRejectsExpiredRun(): void
    {
        $state = RunState::create(str_repeat('a', 32), hash('sha256', 'secret-token'), 1000, 600);

        $this->expectException(InvalidArgumentException::class);

        $state->assertNotExpired(1601);
    }

    public function testAllowsValidPhaseTransition(): void
    {
        $state = RunState::create(str_repeat('a', 32), hash('sha256', 'secret-token'), 1000, 600);

        $state->transitionTo(RunState::PHASE_PREPARED);

        $this->assertSame(RunState::PHASE_PREPARED, $state->getPhase());
    }

    public function testRejectsInvalidPhaseTransition(): void
    {
        $state = RunState::create(str_repeat('a', 32), hash('sha256', 'secret-token'), 1000, 600);

        $this->expectException(InvalidArgumentException::class);

        $state->transitionTo(RunState::PHASE_FINISHED);
    }

    public function testMarkFailedSetsTerminalState(): void
    {
        $state = RunState::create(str_repeat('a', 32), hash('sha256', 'secret-token'), 1000, 600);

        $state->markFailed('failed', 1200);

        $this->assertSame(RunState::PHASE_FAILED, $state->getPhase());
        $this->assertSame(RunState::STATUS_FAILED, $state->getStatus());
        $this->assertSame('failed', $state->toArray()['errorMessage']);
    }
}
