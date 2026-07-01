<?php

namespace QUI\System\Update;

class RunLauncher
{
    public function __construct(
        private readonly RunRepository $repository,
        private readonly string $webRunnerUrl,
        private readonly string $phpBinary
    ) {
    }

    public function create(?int $now = null, array $metadata = []): RunLaunch
    {
        $run = $this->repository->create($now, $metadata);
        $webUrl = $this->createWebUrl($run);
        $cliCommand = $this->createCliCommand($run);
        $state = $run->getState();

        $state->setMetadataValue('webUrl', $webUrl);
        $state->setMetadataValue('cliCommand', $cliCommand);
        $this->repository->save($state);

        return new RunLaunch(
            $run,
            $webUrl,
            $cliCommand
        );
    }

    private function createWebUrl(Run $run): string
    {
        return $this->webRunnerUrl
            . '?id='
            . rawurlencode($run->getState()->getId())
            . '&token='
            . rawurlencode($run->getToken());
    }

    private function createCliCommand(Run $run): string
    {
        return CliEnvironment::createShellPrefix()
            . escapeshellarg($this->phpBinary) . ' '
            . escapeshellarg($run->getExecuteFile()) . ' '
            . escapeshellarg($run->getToken());
    }
}
