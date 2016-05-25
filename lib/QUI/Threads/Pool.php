<?php

namespace QUI\Threads;

require_once 'polyfill.php';

/**
 * Class Pool
 */
class Pool
{
    /**
     * @var array
     */
    protected $tasks = array();

    /**
     * Return all finished jobs
     *
     * @return array
     */
    public function process()
    {
        while ($this->isRunning()) {
        }

        $this->shutdown();

        return $this->tasks;
    }

    /**
     * Submit a task to the pool
     *
     * @param \Threaded $Task
     * @return void
     */
    public function submit(\Threaded $Task)
    {
        $this->tasks[] = $Task;
        $Task->run();
    }

    /**
     *
     */
    public function shutdown()
    {
        unset($this->tasks);
    }

    /**
     * number of jobs in the queue
     */
    public function count()
    {
        return count($this->tasks);
    }

    /**
     * Are some tasks still running?
     *
     * @return boolean
     */
    public function isRunning()
    {
        $running = array();

        /* @var $Task \QUI\Threads\Worker */
        foreach ($this->tasks as $Task) {
            if (!$Task->isGarbage()) {
                $running[] = $Task;
            }
        }

        return count($running) ? true : false;
    }
}
