<?php

namespace QUI\System\Update\Action;

use PHPUnit\Framework\TestCase;
use QUI\System\Update\Fixtures\FakeComposerPharManager;
use QUI\System\Update\RunActionResult;
use QUI\System\Update\RunState;

require_once dirname(__DIR__) . '/Fixtures/FakeComposerPharManager.php';

class ComposerToolUpdateActionTest extends TestCase
{
    public function testExecuteEnsuresUpdatesAndRequestsRestart(): void
    {
        $manager = new FakeComposerPharManager();
        $action = new ComposerToolUpdateAction($manager);
        $state = RunState::create(str_repeat('a', 32), hash('sha256', 'token'), 1000, 600);

        $result = $action->execute($state);

        $this->assertInstanceOf(RunActionResult::class, $result);
        $this->assertTrue($result->isRestartRequired());
        $this->assertSame(1, $manager->ensureCalls);
        $this->assertSame(1, $manager->updateCalls);
    }

    public function testExecuteUpdatesEvenIfEnsureCreatedPhar(): void
    {
        $manager = new FakeComposerPharManager();
        $manager->ensureResult = true;
        $action = new ComposerToolUpdateAction($manager);
        $state = RunState::create(str_repeat('a', 32), hash('sha256', 'token'), 1000, 600);

        $result = $action->execute($state);

        $this->assertTrue($result->isRestartRequired());
        $this->assertSame(1, $manager->ensureCalls);
        $this->assertSame(1, $manager->updateCalls);
    }
}
