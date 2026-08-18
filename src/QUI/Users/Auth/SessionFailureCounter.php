<?php

namespace QUI\Users\Auth;

use InvalidArgumentException;
use QUI\Session;
use QUI\System\Console\Session as ConsoleSession;

class SessionFailureCounter
{
    public const STEP_PRIMARY = 'primary';
    public const STEP_SECONDARY = 'secondary';
    public const MAX_FAILURES = 3;

    private const SESSION_KEY_PREFIX = 'auth-failures-';

    public function __construct(private readonly Session | ConsoleSession $Session)
    {
    }

    public function recordFailure(string $step): void
    {
        $sessionKey = $this->getSessionKey($step);
        $failures = (int)$this->Session->get($sessionKey) + 1;

        if ($failures >= self::MAX_FAILURES) {
            $this->Session->destroy();
            return;
        }

        $this->Session->set($sessionKey, $failures);
    }

    public function reset(string $step): void
    {
        $this->Session->remove($this->getSessionKey($step));
    }

    private function getSessionKey(string $step): string
    {
        if (!in_array($step, [self::STEP_PRIMARY, self::STEP_SECONDARY], true)) {
            throw new InvalidArgumentException('Unknown authentication step: ' . $step);
        }

        return self::SESSION_KEY_PREFIX . $step;
    }
}
