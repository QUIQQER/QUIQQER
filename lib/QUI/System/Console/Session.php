<?php

/**
 * This file contains QUI\System\Console\Session
 */

namespace QUI\System\Console;

use QUI;

/**
 * Class Session
 * Session placeholder for the CLI
 */
class Session
{
    /**
     * @var array
     */
    protected $params = array();

    /**
     * @var string
     */
    protected $id;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage
     */
    protected $Storage;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\Session
     */
    protected $Session;

    /**
     * Session constructor.
     */
    public function __construct()
    {
        $this->id = uniqid();

        $this->Storage = new \Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage();
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
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }

        return false;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function check()
    {
        return true;
    }

    /**
     * @param string $name
     */
    public function del($name)
    {
        if (isset($this->params[$name])) {
            unset($this->params[$name]);
        }
    }

    /**
     * @param string $name
     */
    public function remove($name)
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
        $this->params = array();
    }

    /**
     * @param $sid
     * @return int
     */
    public function getLastRefreshFrom($sid)
    {
        return time();
    }

    /**
     * Return the symfony session
     *
     * @return \Symfony\Component\HttpFoundation\Session\Session
     */
    public function getSymfonySession()
    {
        return $this->Session;
    }

    //region Session API placeholder

    public function start()
    {
    }

    public function setup()
    {
    }

    public function refresh()
    {
    }

    // endregion
}
