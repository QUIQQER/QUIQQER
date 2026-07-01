<?php

namespace QUI\System\Update;

use PHPUnit\Framework\TestCase;
use QUI\System\Update\Fixtures\RecordingUpdateRunAction;
use RuntimeException;

require_once __DIR__ . '/Fixtures/RecordingUpdateRunAction.php';

class RunProcessorTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = '/tmp/quiqqer_update_runner_' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->root)) {
            $this->deleteDirectory($this->root);
        }
    }

    public function testProcessRunsUntilFinished(): void
    {
        $repository = new RunRepository($this->root, 600);
        $run = $repository->create(1000);
        $createdAction = new RecordingUpdateRunAction(RunActionResult::next(RunState::PHASE_PREPARED));
        $preparedAction = new RecordingUpdateRunAction(RunActionResult::finished());
        $processor = new RunProcessor($repository, [
            RunState::PHASE_CREATED => $createdAction,
            RunState::PHASE_PREPARED => $preparedAction
        ]);

        $state = $processor->process($run->getState()->getId(), $run->getToken(), 1001);

        $this->assertSame(RunState::STATUS_FINISHED, $state->getStatus());
        $this->assertSame([$run->getState()->getId()], $createdAction->executedRunIds);
        $this->assertSame([$run->getState()->getId()], $preparedAction->executedRunIds);
    }

    public function testProcessStopsAtRestartRequired(): void
    {
        $repository = new RunRepository($this->root, 600);
        $run = $repository->create(1000);
        $createdAction = new RecordingUpdateRunAction(RunActionResult::next(RunState::PHASE_PREPARED));
        $preparedAction = new RecordingUpdateRunAction(RunActionResult::next(RunState::PHASE_COMPOSER_UPDATE));
        $composerAction = new RecordingUpdateRunAction(RunActionResult::restartRequired());
        $systemAction = new RecordingUpdateRunAction(RunActionResult::finished());
        $processor = new RunProcessor($repository, [
            RunState::PHASE_CREATED => $createdAction,
            RunState::PHASE_PREPARED => $preparedAction,
            RunState::PHASE_COMPOSER_UPDATE => $composerAction,
            RunState::PHASE_SYSTEM_UPDATE => $systemAction
        ]);

        $state = $processor->process($run->getState()->getId(), $run->getToken(), 1001);

        $this->assertSame(RunState::STATUS_RESTART_REQUIRED, $state->getStatus());
        $this->assertSame([], $systemAction->executedRunIds);
    }

    public function testProcessResumesFromRestartRequiredOnNextInvocation(): void
    {
        $repository = new RunRepository($this->root, 600);
        $run = $repository->create(1000);
        $state = $run->getState();
        $state->transitionTo(RunState::PHASE_PREPARED);
        $state->transitionTo(RunState::PHASE_COMPOSER_UPDATE);
        $state->markRestartRequired();
        $repository->save($state);

        $restartAction = new RecordingUpdateRunAction(RunActionResult::next(RunState::PHASE_SYSTEM_UPDATE));
        $systemAction = new RecordingUpdateRunAction(RunActionResult::finished());
        $processor = new RunProcessor($repository, [
            RunState::PHASE_RESTART_REQUIRED => $restartAction,
            RunState::PHASE_SYSTEM_UPDATE => $systemAction
        ]);

        $result = $processor->process($run->getState()->getId(), $run->getToken(), 1001);

        $this->assertSame(RunState::STATUS_FINISHED, $result->getStatus());
        $this->assertSame([$run->getState()->getId()], $restartAction->executedRunIds);
        $this->assertSame([$run->getState()->getId()], $systemAction->executedRunIds);
    }

    public function testDefaultActionsStopBeforeSystemUpdateOnFirstInvocation(): void
    {
        $repository = new RunRepository($this->root, 600);
        $run = $repository->create(1000);
        $processor = new RunProcessor($repository, DefaultRunActions::create());

        $state = $processor->process($run->getState()->getId(), $run->getToken(), 1001);

        $this->assertSame(RunState::STATUS_RESTART_REQUIRED, $state->getStatus());
        $this->assertSame(RunState::PHASE_RESTART_REQUIRED, $state->getPhase());
    }

    public function testProcessRejectsTooManySteps(): void
    {
        $repository = new RunRepository($this->root, 600);
        $run = $repository->create(1000);
        $processor = new RunProcessor(
            $repository,
            [
                RunState::PHASE_CREATED => new RecordingUpdateRunAction(RunActionResult::next(RunState::PHASE_PREPARED)),
                RunState::PHASE_PREPARED => new RecordingUpdateRunAction(RunActionResult::next(RunState::PHASE_SYSTEM_UPDATE)),
                RunState::PHASE_SYSTEM_UPDATE => new RecordingUpdateRunAction(RunActionResult::next(RunState::PHASE_CLEANUP)),
                RunState::PHASE_CLEANUP => new RecordingUpdateRunAction(RunActionResult::next(RunState::PHASE_FINISHED))
            ],
            1
        );

        $this->expectException(RuntimeException::class);

        $processor->process($run->getState()->getId(), $run->getToken(), 1001);
    }

    private function deleteDirectory(string $directory): void
    {
        $items = new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);

        foreach ($items as $item) {
            if ($item->isDir()) {
                $this->deleteDirectory($item->getPathname());
                continue;
            }

            unlink($item->getPathname());
        }

        rmdir($directory);
    }
}
