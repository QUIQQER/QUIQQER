<?php

namespace QUI\Log;

final class ErrorHandler
{
    public static function logUncaughtException(\Throwable $Exception): void
    {
        fwrite(STDERR, get_class($Exception) . ': ' . $Exception->getMessage());
    }
}
