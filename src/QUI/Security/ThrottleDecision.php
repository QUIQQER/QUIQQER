<?php

namespace QUI\Security;

/**
 * Result of an atomic throttle acquisition.
 */
final class ThrottleDecision
{
    private bool $released = false;

    private function __construct(
        private readonly bool $allowed,
        private readonly int $retryAt,
        private readonly ?string $throttleKey = null,
        private readonly ?string $reservationId = null
    ) {
    }

    /**
     * @internal Created by Throttle only.
     */
    public static function allowed(string $throttleKey, string $reservationId, int $retryAt): self
    {
        return new self(true, $retryAt, $throttleKey, $reservationId);
    }

    /**
     * @internal Created by Throttle only.
     */
    public static function denied(int $retryAt): self
    {
        return new self(false, $retryAt);
    }

    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    /**
     * Unix timestamp at which this action can be acquired again.
     */
    public function getRetryAt(): int
    {
        return $this->retryAt;
    }

    /**
     * Release an allowed decision if its protected operation failed.
     */
    public function release(): void
    {
        if (
            !$this->allowed
            || $this->released
            || $this->throttleKey === null
            || $this->reservationId === null
        ) {
            return;
        }

        Throttle::releaseReservation($this->throttleKey, $this->reservationId);
        $this->released = true;
    }
}
