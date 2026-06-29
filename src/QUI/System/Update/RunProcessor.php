<?php

namespace QUI\System\Update;

class RunProcessor
{
    /**
     * @param array<string, RunActionInterface> $actions
     */
    public function __construct(
        private readonly RunRepository $repository,
        private readonly array $actions,
        private readonly int $maxSteps = 20
    ) {
    }

    public function process(string $id, string $token, ?int $now = null): RunState
    {
        $executor = new RunExecutor($this->repository, $this->actions);
        $state = $this->repository->load($id);

        for ($step = 0; $step < $this->maxSteps; $step++) {
            if ($this->isBoundaryState($state)) {
                if ($step === 0 && $state->getStatus() === RunState::STATUS_RESTART_REQUIRED) {
                    $state = $executor->execute($id, $token, $now);
                    continue;
                }

                return $state;
            }

            $state = $executor->execute($id, $token, $now);
        }

        throw new \RuntimeException('Update run exceeded maximum processing steps.');
    }

    private function isBoundaryState(RunState $state): bool
    {
        return in_array(
            $state->getStatus(),
            [
                RunState::STATUS_RESTART_REQUIRED,
                RunState::STATUS_FINISHED,
                RunState::STATUS_FAILED,
                RunState::STATUS_CANCELLED
            ],
            true
        );
    }
}
