<?php

/**
 * This file contains \QUI\Events\Event
 */

namespace QUI\Events;

use QUI;
use ReflectionMethod;
use Throwable;

use function call_user_func;
use function call_user_func_array;
use function explode;
use function is_array;
use function is_string;
use function preg_replace;
use function str_contains;
use function ucfirst;
use function usort;

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
     */
    protected array $events = [];

    protected array $currentRunning = [];

    protected array $ignore = [];

    public function getList(): array
    {
        return $this->events;
    }

    public function addEvents(array $events): void
    {
        foreach ($events as $event => $fn) {
            if (is_array($fn)) {
                $this->addEvent($event, $fn[0], $fn[1], $fn[2]);
                continue;
            }

            $this->addEvent($event, $fn);
        }
    }

    /**
     * @param string $event - The type of event (e.g. 'complete').
     * @param callable|string $fn - The function to execute.
     * @param int $priority
     * @param string $package
     */
    public function addEvent(
        string $event,
        callable|string $fn,
        int $priority = 0,
        string $package = ''
    ): void {
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
            'package' => $package
        ];
    }

    /**
     * @param array $events - (optional) If not passed removes all events of all types.
     */
    public function removeEvents(array $events): void
    {
        foreach ($events as $event => $fn) {
            $this->removeEvent($event, $fn);
        }
    }

    /**
     * @param string $event - The type of event (e.g. 'complete').
     * @param callable|boolean $fn - (optional) The function to remove.
     */
    public function removeEvent(string $event, callable|bool $fn = false): void
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
     * @param string $event - The type of event (e.g. 'onComplete').
     * @param boolean|array $args - (optional) the argument(s) to pass to the function.
     *                            The arguments must be in an array.
     *
     * @return array - Event results, associative array
     *
     * @throws QUI\ExceptionStack
     */
    public function fireEvent(
        string $event,
        bool|array $args = false,
        bool $force = false
    ): array {
        $results = [];

        if (!str_starts_with($event, 'on')) {
            $event = 'on' . ucfirst($event);
        }


        // recursion check
        if (
            isset($this->currentRunning[$event])
            && $this->currentRunning[$event]
            && $force === false
        ) {
            return $results;
        }

        if (!isset($this->events[$event])) {
            return $results;
        }

        $this->currentRunning[$event] = true;

        $Stack = new QUI\ExceptionStack();
        $events = $this->events[$event];

        // sort
        usort($events, static function (array $a, array $b): int {
            if ($a['priority'] == $b['priority']) {
                return 0;
            }

            return $a['priority'] < $b['priority'] ? -1 : 1;
        });

        // execute events
        foreach ($events as $data) {
            $fn = $data['callable'];
            $pkg = $data['package'];

            if (isset($this->ignore[$pkg])) {
                continue;
            }

            try {
                if (!is_string($fn)) {
                    if ($args === false) {
                        $fn();
                        continue;
                    }

                    call_user_func_array($fn, $args);
                    continue;
                }

                $fn = preg_replace('/[\\\\]{2,}/', '\\', $fn);

                if ($args === false) {
                    $results[$fn] = call_user_func($fn);
                    continue;
                }

                if (str_contains($fn, '::')) {
                    $parts = explode('::', $fn);
                    $className = $parts[0];
                    $methodName = $parts[1];

                    $reflectionMethod = new ReflectionMethod($className, $methodName);

                    if (!$reflectionMethod->getNumberOfParameters()) {
                        $results[$fn] = call_user_func($fn);
                        continue;
                    }
                }

                $results[$fn] = call_user_func_array($fn, $args);
            } catch (QUI\Exception $Exception) {
                $message = $Exception->getMessage();

                $Clone = new QUI\Exception(
                    $message,
                    $Exception->getCode(),
                    [
                        'trace' => $Exception->getTraceAsString(),
                        'fn' => is_string($fn) ? $fn : ''
                    ]
                );

                $Stack->addException($Clone);
            } catch (Throwable $Exception) {
                $message = $Exception->getMessage();

                $Clone = new QUI\Exception(
                    $message,
                    $Exception->getCode(),
                    [
                        'trace' => $Exception->getTraceAsString(),
                        'functionType' => gettype($fn),
                        'fn' => is_string($fn) ? $fn : ''
                    ]
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
    public function ignore($packageName): void
    {
        $this->ignore[$packageName] = true;
    }

    /**
     * Resets the ignore list
     */
    public function clearIgnore(): void
    {
        $this->ignore = [];
    }

    //endregion
}
