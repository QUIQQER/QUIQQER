<?php

namespace QUI\Threads;

/**
 * Class Pool
 */
class Pool extends \Pool
{
    /**
     * @var array
     */
    public $finished = array();

    /**
     * @return array
     */
    public function process()
    {
        while (count($this->work)) {
            $this->collect(function (Thread $Task) {
                if ($Task->isDone()) {
                    $this->finished[] = $Task;
                }

                return $Task->isDone();
            });
        }

        $this->shutdown();

        return $this->finished;
    }

    /**
     * number of jobs in the queue
     */
    public function count()
    {
        return count($this->workers);
    }

    /**
     *
     */
    public function isRunning()
    {

    }
}
