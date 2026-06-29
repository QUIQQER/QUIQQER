<?php

namespace QUI\System\Update;

class Run
{
    public function __construct(
        private readonly RunState $state,
        private readonly string $token,
        private readonly string $directory,
        private readonly string $executeFile
    ) {
    }

    public function getState(): RunState
    {
        return $this->state;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function getExecuteFile(): string
    {
        return $this->executeFile;
    }
}
