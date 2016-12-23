<?php

namespace QUI\Threads;

require_once 'polyfill.php';

/**
 * Class Worker
 * @package QUI\Threads
 */
class Worker extends \Threaded
{
    /**
     * @var callable
     */
    protected $executabel;

    /**
     * @var bool
     */
    protected $complete = false;

    /**
     * Threaded constructor.
     * @param callable $executable
     */
    public function __construct($executable)
    {
        $this->executabel = $executable;
    }

    /**
     * Execute the executabel
     */
    public function run()
    {
        if (is_callable($this->executabel)) {
            call_user_func($this->executabel);
        }

        $this->complete = true;
    }

    /**
     * Is the job done?
     *
     * @return boolean
     */
    public function isGarbage()
    {
        return $this->complete;
    }
}
