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
 * Session placeholder for the CLI
 */
class Session
{
    /**
     * @var array
     */
    protected array $params = [];

    /**
     * @var string
     */
    protected string $id;

    /**
     * @var MockFileSessionStorage
     */
    protected MockFileSessionStorage $Storage;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\Session
     */
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
    public function set($name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function get($name)
    {
        return $this->params[$name] ?? false;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function check(): bool
    {
        return true;
    }

    /**
     * @param string $name
     */
    public function del(string $name)
    {
        if (isset($this->params[$name])) {
            unset($this->params[$name]);
        }
    }

    /**
     * @param string $name
     */
    public function remove(string $name)
    {
        if (isset($this->params[$name])) {
            unset($this->params[$name]);
        }
    }

    /**
     * Destroy all variables in the cli session
     */
    public function destroy()
    {
        $this->params = [];
    }

    /**
     * @param $sid
     * @return int
     */
    public function getLastRefreshFrom($sid): int
    {
        return time();
    }

    /**
     * Return the symfony session
     *
     * @return \Symfony\Component\HttpFoundation\Session\Session
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
    public function setup()
    {
        QUI\Session::setup();
    }

    public function refresh()
    {
    }

    // endregion
}
