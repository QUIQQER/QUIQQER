<?php

/**
 * This file contains \QUI\Interfaces\Events
 */

namespace QUI\Interfaces;

/**
 * Event Interface
 *
 * The event interface defines the general event handling on an object (Class)
 *
 * @author www.pcsg.de (Henning Leutz)
 */
interface Events
{
    /**
     * Return all registered events
     * @return array
     */
    public function getList(): array;

    /**
     * Adds an event to the Class instance's event stack.
     *
     * @param string $event - The type of event (e.g. 'complete').
     * @param callable|string $fn - Function which should be executed
     * @param int $priority
     * @param string $package
     */
    public function addEvent(string $event, callable|string $fn, int $priority = 0, string $package = '');

    /**
     * The same as addEvent, but accepts an array to add multiple events at once.
     *
     * @param array $events
     */
    public function addEvents(array $events);

    /**
     * Removes an event from the stack of events of the Class instance.
     *
     * @param string $event - The type of event (e.g. 'complete').
     * @param callable|bool $fn - (optional) Function which should be removed
     */
    public function removeEvent(string $event, callable|bool $fn = false);

    /**
     * Removes all events of the given type from the stack of events of a Class instance.
     * If no type is specified, removes all events of all types.
     *
     * @param array $events - [optional] If not passed removes all events of all types.
     */
    public function removeEvents(array $events);

    /**
     * Fires all events of the specified type in the Class instance.
     *
     * @param string $event - The type of event (e.g. 'onComplete').
     * @param bool|array $args - (optional) the argument(s) to pass to the function.
     *                        The arguments must be in an array.
     */
    public function fireEvent(string $event, bool|array $args = false, bool $force = false);
}
