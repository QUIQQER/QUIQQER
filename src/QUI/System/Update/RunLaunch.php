<?php

namespace QUI\System\Update;

class RunLaunch
{
    public function __construct(
        private readonly Run $run,
        private readonly string $webUrl,
        private readonly string $cliCommand,
        private readonly string $webToken = ''
    ) {
    }

    public function getRun(): Run
    {
        return $this->run;
    }

    public function getWebUrl(): string
    {
        return $this->webUrl;
    }

    public function getCliCommand(): string
    {
        return $this->cliCommand;
    }

    public function getWebToken(): string
    {
        return $this->webToken;
    }
}
