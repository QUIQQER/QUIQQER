<?php

namespace QUI\System\Update;

use Throwable;

class RunExecutor
{
    /**
     * @param array<string, RunActionInterface> $actions
     */
    public function __construct(
        private readonly RunRepository $repository,
        private readonly array $actions
    ) {
    }

    public function execute(string $id, string $token, ?int $now = null): RunState
    {
        $now ??= time();
        $lock = $this->repository->acquireLock($id);
        $isAuthorized = false;

        try {
            $state = $this->repository->load($id);
            $state->assertAuthorized($token, $now);
            $isAuthorized = true;
            $state->markRunning($now);
            $this->repository->save($state);

            $action = $this->actions[$state->getPhase()] ?? null;

            if (!$action) {
                throw new \RuntimeException('No update run action registered for phase: ' . $state->getPhase());
            }

            $result = $action->execute($state);

            if ($result->isRestartRequired()) {
                $state->markRestartRequired();
            } elseif ($result->isFinished()) {
                $state->markFinished($now);
            } elseif ($result->getNextPhase() !== null) {
                if ($result->getNextPhase() === RunState::PHASE_FINISHED) {
                    $state->markFinished($now);
                } else {
                    $state->transitionTo($result->getNextPhase());
                }
            }

            $this->repository->save($state);

            return $state;
        } catch (Throwable $Exception) {
            if ($isAuthorized && isset($state)) {
                $state->markFailed($Exception->getMessage(), $now);
                $this->repository->save($state);
            }

            throw $Exception;
        } finally {
            $this->repository->releaseLock($lock);
        }
    }
}
