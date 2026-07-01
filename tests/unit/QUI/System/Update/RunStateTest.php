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

    public function testMarkCancelledSetsTerminalStateAndKeepsProcessData(): void
    {
        $state = RunState::create(str_repeat('a', 32), hash('sha256', 'secret-token'), 1000, 600);
        $state->setProcess(1234, 'php execute.php token', 1001);

        $state->markCancelled('cancelled', 1200);

        $this->assertSame(RunState::PHASE_CANCELLED, $state->getPhase());
        $this->assertSame(RunState::STATUS_CANCELLED, $state->getStatus());
        $this->assertSame('cancelled', $state->toArray()['errorMessage']);
        $this->assertSame(1234, $state->getProcess()['pid']);
    }

    public function testPublicArrayDoesNotExposeRunnerSecrets(): void
    {
        $state = RunState::create(str_repeat('a', 32), hash('sha256', 'secret-token'), 1000, 600, [
            'type' => 'web',
            'webUrl' => 'https://example.test/update-run.php?id=run&token=secret-token',
            'cliCommand' => 'php execute.php secret-token'
        ]);
        $state->setProcess(1234, 'php execute.php secret-token', 1001);

        $public = $state->toPublicArray();

        $this->assertArrayNotHasKey('tokenHash', $public);
        $this->assertArrayNotHasKey('cliCommand', $public['metadata']);
        $this->assertArrayNotHasKey('command', $public['process']);
        $this->assertSame('https://example.test/update-run.php?id=run&token=secret-token', $public['metadata']['webUrl']);
        $this->assertSame('web', $public['metadata']['type']);
        $this->assertSame(1234, $public['process']['pid']);
    }
}
