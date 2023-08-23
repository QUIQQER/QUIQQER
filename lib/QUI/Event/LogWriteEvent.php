<?php

namespace QUI\Event;

use Psr\Log\LogLevel;

class LogWriteEvent implements Event
{
    public string $message;
    public string $logLevel;

    public function __construct(string $message, string $logLevel)
    {
        $this->message = $message;
        $this->logLevel = $logLevel;
    }
}
