<?php

namespace QUI\Lock;

use QUI;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/** Persistent editing leases. Symfony process locks serialize all ownership changes. */
class EditingLocks
{
    public const LIFETIME = 120;

    /** @var array<string, true> */
    private array $active = [];

    public function __construct(private AdapterInterface $Store)
    {
    }

    /** @return array{owner: string, token: string, expires: int}|null */
    private function read(string $resource): ?array
    {
        $Item = $this->Store->getItem(hash('sha256', $resource));

        if (!$Item->isHit()) {
            return null;
        }

        $data = $Item->get();

        if (
            !is_array($data) || !is_string($data['owner'] ?? null) || !is_string($data['token'] ?? null)
            || !is_int($data['expires'] ?? null)
        ) {
            throw new Exception('Invalid editing lock record.', 503);
        }

        if ($data['expires'] <= time()) {
            return null;
        }

        return $data;
    }

    /** @param array{owner: string, token: string, expires: int} $record */
    private function write(string $resource, array $record): void
    {
        $Item = $this->Store->getItem(hash('sha256', $resource));
        $Item->set($record)->expiresAfter(self::LIFETIME);

        if (!$this->Store->save($Item)) {
            throw new Exception('Unable to persist editing lock.', 503);
        }
    }

    /** @return array{owner: string, expires: int}|null */
    public function status(string $resource): ?array
    {
        $record = $this->read($resource);

        if ($record === null) {
            return null;
        }

        return ['owner' => $record['owner'], 'expires' => $record['expires']];
    }

    public function acquire(string $resource, string $owner, string $token): bool
    {
        if ($resource === '' || $owner === '' || !preg_match('/^[a-f0-9]{32,128}$/D', $token)) {
            throw new \InvalidArgumentException('Editing locks require a resource, owner and random token.', 400);
        }

        return $this->guard($resource, function () use ($resource, $owner, $token): bool {
            $record = $this->read($resource);

            if ($record !== null && !$this->owns($record, $owner, $token)) {
                return false;
            }

            $this->write($resource, ['owner' => $owner, 'token' => $token, 'expires' => time() + self::LIFETIME]);
            return true;
        });
    }

    public function refresh(string $resource, string $owner, string $token): bool
    {
        return $this->guard($resource, function () use ($resource, $owner, $token): bool {
            $record = $this->read($resource);

            if ($record === null || !$this->owns($record, $owner, $token)) {
                return false;
            }

            $record['expires'] = time() + self::LIFETIME;
            $this->write($resource, $record);
            return true;
        });
    }

    public function release(string $resource, string $owner, string $token, bool $force = false): bool
    {
        return $this->guard($resource, function () use ($resource, $owner, $token, $force): bool {
            $record = $this->read($resource);

            if ($record === null || (!$force && !$this->owns($record, $owner, $token))) {
                return false;
            }

            if (!$this->Store->deleteItem(hash('sha256', $resource))) {
                throw new Exception('Unable to release editing lock.', 503);
            }

            return true;
        });
    }

    /**
     * Validate ownership and keep transfers excluded for the entire write operation.
     * A null token supports trusted, non-editor callers, but still respects other users' leases.
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function run(string $resource, string $owner, ?string $token, callable $callback): mixed
    {
        return $this->guard($resource, function () use ($resource, $owner, $token, $callback): mixed {
            $record = $this->read($resource);

            if ($token !== null && ($record === null || !$this->owns($record, $owner, $token))) {
                throw new Exception(['quiqqer/core', 'exception.site.is.being.edited'], 703);
            }

            if ($record !== null && $record['owner'] !== $owner) {
                throw new Exception(['quiqqer/core', 'exception.site.is.being.edited'], 703);
            }

            if ($record !== null) {
                $record['expires'] = time() + self::LIFETIME;
                $this->write($resource, $record);
            }

            return $callback();
        });
    }

    /** @param array{owner: string, token: string, expires: int} $record */
    private function owns(array $record, string $owner, string $token): bool
    {
        return $record['owner'] === $owner && hash_equals($record['token'], $token);
    }

    /** @template T
     * @param callable(): T $callback
     * @return T
     */
    private function guard(string $resource, callable $callback): mixed
    {
        if (isset($this->active[$resource])) {
            return $callback();
        }

        return Locker::synchronized('editing:' . $resource, function () use ($resource, $callback): mixed {
            $this->active[$resource] = true;

            try {
                return $callback();
            } finally {
                unset($this->active[$resource]);
            }
        });
    }
}
