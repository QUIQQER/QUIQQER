<?php

/**
 * This file contains QUI_Events_Manager
 */

/**
 * The Event Manager
 * Registered and set global events
 *
 * If you register event and the callback function is a string,
 * the callback funciton would be set to the database
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.events
 */
class QUI_Events_Manager implements Interface_Events
{
    /**
     * construct
     */
    public function __construct()
    {
        $list = \QUI::getDataBase()->fetch(array(
            'from' => self::Table()
        ));

        $this->_Events = new \QUI_Events_Event();

        foreach ( $list as $params )
        {
            $this->_Events->addEvent(
                $params['params'],
                $params['callback']
            );
        }
    }

    /**
     * Return the events db table name
     *
     * @return String
     */
    static function Table()
    {
        return QUI_DB_PRFX .'events';
    }

    /**
     * create the event table
     */
    static function setup()
    {
        $DBTable = \QUI::getDataBase()->Table();

        $DBTable->appendFields(self::Table(), array(
            'event'    => 'varchar(200)',
            'callback' => 'text'
        ));
    }

    /**
     * clear all events
     */
    static function clear()
    {
        \QUI::getDataBase()->Table()->truncate(
            self::Table()
        );
    }

    /**
     * Return a complete list of registered events
     *
     * @return Array
     */
    public function getList()
    {
        return $this->_Events->getList();
    }

    /**
     * Adds an event
     * If $fn is a string, the event would be save in the database
     * if you want to register events for the runtime, please use lambda function
     *
     * @example $EventManager->addEvent('myEvent', function() { });
     *
     * @param String $event - The type of event (e.g. 'complete').
     * @param Function $fn - The function to execute.
     */
    public function addEvent($event, $fn)
    {
        // add the event to the db
        if ( is_string( $fn ) )
        {
            \QUI::getDataBase()->insert(self::Table(), array(
                'event'    => $event,
                'callback' => $fn
            ));
        }

        $this->_Events->addEvent( $event, $fn );
    }

    /**
     * The same as addEvent, but accepts an array to add multiple events at once.
     *
     * @param array $events
     */
    public function addEvents(array $events)
    {
        $this->_Events->addEvents( $events );
    }

    /**
     * Removes an event from the stack of events
     * It remove the events from the database, too.
     *
     * @param String $event - The type of event (e.g. 'complete').
     * @param Function $fn - (optional) The function to remove.
     */
    public function removeEvent($event, $fn=false)
    {
        $this->_Events->removeEvent( $event, $fn );

        if ( $fn === false )
        {
            \QUI::getDataBase()->delete(self::Table(), array(
                'event' => $event
            ));
        }

        if ( is_string( $fn ) )
        {
            \QUI::getDataBase()->delete(self::Table(), array(
                'event'    => $event,
                'callback' => $fn
            ));
        }
    }

    /**
     * Removes all events of the given type from the stack of events of a Class instance.
     * If no $fn is specified, removes all events of the event.
     * It remove the events from the database, too.
     *
     * @param array $events - [optional] If not passed removes all events of all types.
     */
    public function removeEvents(array $events)
    {
        $this->_Events->removeEvents( $events );
    }

    /**
     * (non-PHPdoc)
     * @see Interface_Events::fireEvent()
     */
    public function fireEvent($event, $args=false)
    {
        $this->_Events->fireEvent( $event, $args );
    }
}

?>