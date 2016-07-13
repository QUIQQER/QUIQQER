<?php

/**
 * This file contains \QUI\Events\Manager
 */

namespace QUI\Events;

use QUI;

/**
 * The Event Manager
 * Registered and set global events
 *
 * If you register event and the callback function is a string,
 * the callback function would be set to the database
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Manager implements QUI\Interfaces\Events
{
    /**
     * Site Events
     *
     * @var array
     */
    protected $siteEvents = array();

    /**
     * @var Event
     */
    protected $Events;

    /**
     * construct
     */
    public function __construct()
    {
        $this->Events = new Event();

        try {
            if (!QUI::getDataBase()->table()->exist(self::table())) {
                return;
            }

            $list = QUI::getDataBase()->fetch(array(
                'from'  => self::table(),
                'where' => array(
                    'sitetype' => null
                )
            ));

            foreach ($list as $params) {
                $this->Events->addEvent(
                    $params['event'],
                    $params['callback']
                );
            }

            $list = QUI::getDataBase()->fetch(array(
                'from'  => self::table(),
                'where' => array(
                    'sitetype' => array(
                        'type'  => 'NOT',
                        'value' => null
                    )
                )
            ));

            $this->siteEvents = $list;

        } catch (QUI\Database\Exception $Exception) {
        }
    }

    /**
     * Return the events db table name
     *
     * @return string
     */
    public static function table()
    {
        return QUI_DB_PRFX . 'events';
    }

    /**
     * create the event table
     */
    public static function setup()
    {
        $DBTable = QUI::getDataBase()->table();

        $DBTable->addColumn(self::table(), array(
            'event'    => 'varchar(200)',
            'callback' => 'text',
            'sitetype' => 'text'
        ));

        self::clear();
    }

    /**
     * clear all events
     */
    public static function clear()
    {
        QUI::getDataBase()->table()->truncate(
            self::table()
        );
    }

    /**
     * Return a complete list of registered events
     *
     * @return array
     */
    public function getList()
    {
        return $this->Events->getList();
    }

    /**
     * Return a complete list of registered events for a specific site type
     *
     * @param string $type
     *
     * @return array
     */
    public function getSiteListByType($type)
    {
        $result = array();

        foreach ($this->siteEvents as $event) {
            if ($event['sitetype'] == $type) {
                $result[] = $type;
            }
        }

        return $result;
    }

    /**
     * Adds an event
     * If $fn is a string, the event would be save in the database
     * if you want to register events for the runtime, please use lambda function
     *
     * @example $EventManager->addEvent('myEvent', function() { });
     *
     * @param string $event - The type of event (e.g. 'complete').
     * @param callback $fn - The function to execute.
     */
    public function addEvent($event, $fn)
    {
        // add the event to the db
        if (is_string($fn)) {
            QUI::getDataBase()->insert(self::table(), array(
                'event'    => $event,
                'callback' => $fn
            ));
        }

        $this->Events->addEvent($event, $fn);
    }

    /**
     * Adds an site event entry
     *
     * @example $EventManager->addEvent('onSave', '\Namespace\Class::exec', 'quiqqer/blog:blog/entry' });
     *
     * @param string $event - The type of event (e.g. 'complete').
     * @param callback $fn - The function to execute.
     * @param string $sitetype - type of the site
     */
    public function addSiteEvent($event, $fn, $sitetype)
    {
        if (!is_string($fn)) {
            return;
        }

        QUI::getDataBase()->insert(self::table(), array(
            'event'    => $event,
            'callback' => $fn,
            'sitetype' => $sitetype
        ));
    }

    /**
     * The same as addEvent, but accepts an array to add multiple events at once.
     *
     * @param array $events
     */
    public function addEvents(array $events)
    {
        $this->Events->addEvents($events);
    }

    /**
     * Removes an event from the stack of events
     * It remove the events from the database, too.
     *
     * @param string $event - The type of event (e.g. 'complete').
     * @param callback|boolean $fn - (optional) The function to remove.
     */
    public function removeEvent($event, $fn = false)
    {
        $this->Events->removeEvent($event, $fn);

        if ($fn === false) {
            QUI::getDataBase()->delete(self::table(), array(
                'event' => $event
            ));
        }

        if (is_string($fn)) {
            QUI::getDataBase()->delete(self::table(), array(
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
        $this->Events->removeEvents($events);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Interfaces\Events::fireEvent()
     *
     * @param string $event - The type of event (e.g. 'onComplete').
     * @param array|boolean $args - (optional) the argument(s) to pass to the function.
     *                          The arguments must be in an array.
     */
    public function fireEvent($event, $args = false)
    {
        // event onFireEvent
        $fireArgs = $args;

        if (!is_array($fireArgs)) {
            $fireArgs = array();
        }

        $this->Events->fireEvent('onFireEvent', array($event, $fireArgs));
        $this->Events->fireEvent($event, $fireArgs);
    }
}
