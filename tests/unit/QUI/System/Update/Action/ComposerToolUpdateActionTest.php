<?php

namespace QUI\System\Update\Action;

use PHPUnit\Framework\TestCase;
use QUI\System\Update\Fixtures\FakeComposerPharManager;
use QUI\System\Update\RunActionResult;
use QUI\System\Update\RunState;

require_once dirname(__DIR__) . '/Fixtures/FakeComposerPharManager.php';

class ComposerToolUpdateActionTest extends TestCase
{
    public function testExecuteUpdatesExistingPharAndRequestsRestart(): void
    {
        $manager = new FakeComposerPharManager();
        $action = new ComposerToolUpdateAction($manager);
        $state = RunState::create(str_repeat('a', 32), hash('sha256', 'token'), 1000, 600);

        $result = $action->execute($state);

        $this->assertInstanceOf(RunActionResult::class, $result);
        $this->assertTrue($result->isRestartRequired());
        $this->assertSame(0, $manager->ensureCalls);
        $this->assertSame(1, $manager->updateCalls);
    }

    public function testExecuteSkipsComposerUpdateIfPharIsMissing(): void
    {
        $manager = new FakeComposerPharManager();
        $manager->existsResult = false;
        $action = new ComposerToolUpdateAction($manager);
        $state = RunState::create(str_repeat('a', 32), hash('sha256', 'token'), 1000, 600);

        $result = $action->execute($state);

        $this->assertFalse($result->isRestartRequired());
        $this->assertSame(RunState::PHASE_SYSTEM_UPDATE, $result->getNextPhase());
        $this->assertSame(0, $manager->ensureCalls);
        $this->assertSame(0, $manager->updateCalls);
    }

    public function testExecuteContinuesWithoutRestartIfComposerUpdateFails(): void
    {
        $manager = new FakeComposerPharManager();
        $manager->throwOnUpdate = true;
        $action = new ComposerToolUpdateAction($manager);
        $state = RunState::create(str_repeat('a', 32), hash('sha256', 'token'), 1000, 600);

        $result = $action->execute($state);

        $this->assertFalse($result->isRestartRequired());
        $this->assertSame(RunState::PHASE_SYSTEM_UPDATE, $result->getNextPhase());
        $this->assertSame(1, $manager->updateCalls);
    }
}
