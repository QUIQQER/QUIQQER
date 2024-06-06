<?php

/**
 * This file contains QUI\System\Console\Session
 */

namespace QUI\System\Console;

use Exception;
use QUI;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;

/**
 * Class Session
 * - Session placeholder for the CLI
 */
class Session
{
    protected array $params = [];

    protected string $id;

    protected MockFileSessionStorage $Storage;

    protected \Symfony\Component\HttpFoundation\Session\Session $Session;

    /**
     * Session constructor.
     */
    public function __construct()
    {
        $this->id = uniqid();

        $this->Storage = new MockFileSessionStorage();
        $this->Session = new \Symfony\Component\HttpFoundation\Session\Session($this->Storage);
    }

    /**
     * @param $name
     * @param $value
     */
    public function set($name, $value): void
    {
        $this->params[$name] = $value;
    }

    public function get($name): mixed
    {
        return $this->params[$name] ?? false;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function check(): bool
    {
        return true;
    }

    public function del(string $name): void
    {
        if (isset($this->params[$name])) {
            unset($this->params[$name]);
        }
    }

    public function remove(string $name): void
    {
        if (isset($this->params[$name])) {
            unset($this->params[$name]);
        }
    }

    /**
     * Destroy all variables in the cli session
     */
    public function destroy(): void
    {
        $this->params = [];
    }

    public function getLastRefreshFrom($sid): int
    {
        return time();
    }

    /**
     * Return the symfony session
     */
    public function getSymfonySession(): \Symfony\Component\HttpFoundation\Session\Session
    {
        return $this->Session;
    }

    //region Session API placeholder

    public function start()
    {
    }

    /**
     * @throws Exception
     */
    public function setup(): void
    {
        QUI\Session::setup();
    }

    public function refresh()
    {
    }

    // endregion
}
