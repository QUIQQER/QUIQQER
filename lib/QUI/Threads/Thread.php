<?php

namespace QUI\Threads;

/**
 * Class Thread
 * @package QUI\Threads
 */
class Thread extends \Thread
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
     * Thread constructor.
     * @param int $threadId
     * @param callable $executable
     */
    public function __construct($threadId, $executable)
    {
        $this->threadId   = $threadId;
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
    public function isDone()
    {
        return $this->complete;
    }
}
