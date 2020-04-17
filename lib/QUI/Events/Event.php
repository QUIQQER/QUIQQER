<?php

/**
 * This file contains \QUI\Events\Event
 */

namespace QUI\Events;

use QUI;

/**
 * Events Handling
 * Extends a class with the events interface
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Event implements QUI\Interfaces\Events
{
    /**
     * Registered events
     *
     * @var array
     */
    protected $events = [];

    /**
     * @var array
     */
    protected $currentRunning = [];

    /**
     * @var array
     */
    protected $ignore = [];

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Events::getList()
     */
    public function getList()
    {
        return $this->events;
    }

    /**
     * (non-PHPdoc)
     *
     * @param string $event - The type of event (e.g. 'complete').
     * @param callable $fn - The function to execute.
     * @param int $priority - optional, Priority of the event
     * @param string $package - optional, name of the package
     *
     * @see \QUI\Interfaces\Events::addEvent()
     *
     */
    public function addEvent($event, $fn, $priority = 0, $package = '')
    {
        if (!isset($this->events[$event])) {
            $this->events[$event] = [];
        }

        // don't add double events
        foreach ($this->events[$event] as $params) {
            if ($params['callable'] == $fn) {
                return;
            }
        }

        $this->events[$event][] = [
            'callable' => $fn,
            'priority' => $priority,
            'package'  => $package
        ];
    }

    /**
     * (non-PHPdoc)
     *
     * @param array $events
     * @see \QUI\Interfaces\Events::addEvents()
     *
     */
    public function addEvents(array $events)
    {
        foreach ($events as $event => $fn) {
            if (\is_array($fn)) {
                $this->addEvent($event, $fn[0], $fn[1], $fn[2]);
                continue;
            }

            $this->addEvent($event, $fn);
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @param string $event - The type of event (e.g. 'complete').
     * @param callable|boolean $fn - (optional) The function to remove.
     * @see \QUI\Interfaces\Events::removeEvent()
     *
     */
    public function removeEvent($event, $fn = false)
    {
        if (!isset($this->events[$event])) {
            return;
        }

        if (!$fn) {
            unset($this->events[$event]);

            return;
        }

        foreach ($this->events[$event] as $k => $_fn) {
            if ($_fn == $fn) {
                unset($this->events[$event][$k]);
            }
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @param array $events - (optional) If not passed removes all events of all types.
     * @see \QUI\Interfaces\Events::removeEvents()
     *
     */
    public function removeEvents(array $events)
    {
        foreach ($events as $event => $fn) {
            $this->removeEvent($event, $fn);
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @param string $event - The type of event (e.g. 'onComplete').
     * @param array|boolean $args - (optional) the argument(s) to pass to the function.
     *                            The arguments must be in an array.
     * @param boolean $force - (optional) no recursion check, optional, default = false
     *
     * @return array - Event results, assoziative array
     *
     * @throws QUI\ExceptionStack
     * @see \QUI\Interfaces\Events::fireEvent()
     *
     */
    public function fireEvent($event, $args = false, $force = false)
    {
        $results = [];

        if (\strpos($event, 'on') !== 0) {
            $event = 'on'.\ucfirst($event);
        }


        // recursion check
        if (isset($this->currentRunning[$event])
            && $this->currentRunning[$event]
            && $force === false
        ) {
            return $results;
        }

        if (!isset($this->events[$event])) {
            return $results;
        }

        $this->currentRunning[$event] = true;

        $Stack  = new QUI\ExceptionStack();
        $events = $this->events[$event];

        // sort
        \usort($events, function ($a, $b) {
            if ($a['priority'] == $b['priority']) {
                return 0;
            }

            return $a['priority'] < $b['priority'] ? -1 : 1;
        });

        // execute events
        foreach ($events as $data) {
            $fn  = $data['callable'];
            $pkg = $data['package'];

            if (isset($this->ignore[$pkg])) {
                continue;
            }

            try {
                if (!\is_string($fn)) {
                    if ($args === false) {
                        $fn();
                        continue;
                    }

                    \call_user_func_array($fn, $args);
                    continue;
                }

                $fn = \preg_replace('/[\\\\]{2,}/', '\\', $fn);

                if ($args === false) {
                    $results[$fn] = \call_user_func($fn);
                    continue;
                }

                $results[$fn] = \call_user_func_array($fn, $args);
            } catch (QUI\Exception $Exception) {
                $message = $Exception->getMessage();

                if (\is_string($fn)) {
                    $message .= ' :: '.$fn;
                }

                $Clone = new QUI\Exception(
                    $message,
                    $Exception->getCode(),
                    ['trace' => $Exception->getTraceAsString()]
                );

                $Stack->addException($Clone);
            } catch (\Exception $Exception) {
                $message = $Exception->getMessage();

                if (\is_string($fn)) {
                    $message .= ' :: '.$fn;
                }

                $Clone = new QUI\Exception(
                    $message,
                    $Exception->getCode(),
                    ['trace' => $Exception->getTraceAsString()]
                );

                $Stack->addException($Clone);
            }
        }

        $this->currentRunning[$event] = false;

        if (!$Stack->isEmpty()) {
            throw $Stack;
        }

        return $results;
    }

    //region ignore

    /**
     * sets which package names should be ignored at a fire event
     *
     * @param $packageName
     */
    public function ignore($packageName)
    {
        $this->ignore[$packageName] = true;
    }

    /**
     * Resets the ignore list
     */
    public function clearIgnore()
    {
        $this->ignore = [];
    }

    //endregion
}
