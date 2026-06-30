<?php

namespace QUI\System\Update;

class RunLauncher
{
    public function __construct(
        private readonly RunRepository $repository,
        private readonly string $publicRunsUrl,
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
        return rtrim($this->publicRunsUrl, '/') . '/'
            . rawurlencode($run->getState()->getId())
            . '/execute.php?token='
            . rawurlencode($run->getToken());
    }

    private function createCliCommand(Run $run): string
    {
        return escapeshellarg($this->phpBinary) . ' '
            . escapeshellarg($run->getExecuteFile()) . ' '
            . escapeshellarg($run->getToken());
    }
}
