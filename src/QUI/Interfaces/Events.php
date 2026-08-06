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
     *
     * @return array<array-key, mixed>
     */
    public function getList(): array;

    /**
     * Adds an event to the Class instance's event stack.
     *
     * @param string $event - The type of event (e.g. 'complete').
     * @param callable|string $fn - Function which should be executed
     * @param int $priority
     * @param string $package
     *
     * @return void
     */
    public function addEvent(string $event, callable|string $fn, int $priority = 0, string $package = '');

    /**
     * The same as addEvent, but accepts an array to add multiple events at once.
     *
     * @param array<array-key, mixed> $events
     *
     * @return void
     */
    public function addEvents(array $events);

    /**
     * Removes an event from the stack of events of the Class instance.
     *
     * @param string $event - The type of event (e.g. 'complete').
     * @param callable|string|false $fn - (optional) Function which should be removed
     *
     * @return void
     */
    public function removeEvent(string $event, callable|string|false $fn = false);

    /**
     * Removes all events of the given type from the stack of events of a Class instance.
     * If no type is specified, removes all events of all types.
     *
     * @param array<array-key, mixed> $events - [optional] If not passed removes all events of all types.
     *
     * @return void
     */
    public function removeEvents(array $events);

    /**
     * Fires all events of the specified type in the Class instance.
     *
     * @param string $event - The type of event (e.g. 'onComplete').
     * @param false|array<array-key, mixed> $args - (optional) the argument(s) to pass to the function.
     *                        The arguments must be in an array.
     *
     * @return array<string, mixed>
     */
    public function fireEvent(string $event, false|array $args = false, bool $force = false);
}
