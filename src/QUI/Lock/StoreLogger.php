<?php

namespace QUI\Lock;

use Psr\Log\AbstractLogger;

/** Cache adapter failures must never be interpreted as an available editing lease. */
class StoreLogger extends AbstractLogger
{
    /** @param string|\Stringable $message */
    public function log($level, $message, array $context = []): void
    {
        // Adapter messages may contain connection credentials.
        throw new Exception('Editing lock storage is unavailable.', 503);
    }
}
