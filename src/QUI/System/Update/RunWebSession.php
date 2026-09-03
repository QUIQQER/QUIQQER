<?php

namespace QUI\System\Update;

class RunWebSession
{
    public const COOKIE_NAME = 'QUIQQER_UPDATE_RUN';

    public function __construct(private readonly RunRepository $repository)
    {
    }

    /**
     * @return array{token: string, expiresAt: int}
     */
    public function exchange(string $id, string $webToken, ?int $now = null): array
    {
        $now ??= time();
        $lock = $this->repository->acquireLock($id);

        try {
            $State = $this->repository->load($id);
            $sessionToken = bin2hex(random_bytes(32));

            $State->exchangeWebToken($webToken, $sessionToken, $now);
            $this->repository->save($State);

            return [
                'token' => $sessionToken,
                'expiresAt' => (int)$State->getWebSessionExpiresAt()
            ];
        } finally {
            $this->repository->releaseLock($lock);
        }
    }

    public function authenticate(string $id, string $sessionToken, ?int $now = null): RunState
    {
        $now ??= time();
        $State = $this->repository->load($id);
        $State->assertWebSession($sessionToken, $now);

        return $State;
    }
}
