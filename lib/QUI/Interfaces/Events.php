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
    public function getList();

    /**
     * Adds an event to the Class instance's event stack.
     *
     * @param String $event - The type of event (e.g. 'complete').
     * @param Function $fn - The function to execute.
     */
    public function addEvent($event, $fn);

    /**
     * The same as addEvent, but accepts an array to add multiple events at once.
     *
     * @param array $events
     */
    public function addEvents(array $events);

    /**
     * Removes an event from the stack of events of the Class instance.
     *
     * @param String $event - The type of event (e.g. 'complete').
     * @param Function $fn - (optional) The function to remove.
     */
    public function removeEvent($event, $fn=false);

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
     * @param String $event - The type of event (e.g. 'onComplete').
     * @param Array $args   - (optional) the argument(s) to pass to the function.
     *                        The arguments must be in an array.
     */
    public function fireEvent($event, $args=false);
}
