<?php

/**
 * This file contains QUI_Events
 */

/**
 * Events Handling
 * Extends a class with the events interface
 *
 * @author www.pcsg.de (Henning Leutz)
 */

class QUI_Events_Event implements Interface_Events
{
    /**
     * Registered events
     * @var Array
     */
    protected $_events = array();

    /**
     * (non-PHPdoc)
     * @see Interface_Events::getList()
     */
    public function getList()
    {
        return $this->_events;
    }

    /**
     * (non-PHPdoc)
     * @see Interface_Events::addEvent()
     */
    public function addEvent($event, $fn)
    {
        $this->_events[ $event ][] = $fn;
    }

    /**
     * (non-PHPdoc)
     * @see Interface_Events::addEvents()
     */
    public function addEvents(array $events)
    {
        foreach ( $events as $event => $fn ) {
            $this->addEvent( $event, $fn );
        }
    }

    /**
     * (non-PHPdoc)
     * @see Interface_Events::removeEvent()
     */
    public function removeEvent($event, $fn=false)
    {
        if ( !isset( $this->_events[ $event ] ) ) {
            return;
        }

        if ( !$fn )
        {
            unset( $this->_events[ $event ] );
            return;
        }

        foreach ( $this->_events[ $event ] as $k => $_fn )
        {
            if ( $_fn == $fn ) {
                unset( $this->_events[ $event ][ $k ] );
            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see Interface_Events::removeEvents()
     */
    public function removeEvents(array $events)
    {
        foreach ( $events as $event => $fn ) {
            $this->removeEvent( $event, $fn );
        }
    }

    /**
     * (non-PHPdoc)
     * @see Interface_Events::fireEvent()
     */
    public function fireEvent($event, $args=false)
    {
        if ( strpos( $event, 'on' ) !== 0 ) {
            $event = 'on'. ucfirst( $event );
        }

        if ( !isset( $this->_events[ $event ] ) ) {
            return;
        }

        foreach ( $this->_events[ $event ] as $fn )
        {
            if ( $args === false )
            {
                $fn();
                continue;
            }

            call_user_func_array( $fn, $args );
        }
    }
}
