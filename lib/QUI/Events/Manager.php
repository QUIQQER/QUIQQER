<?php

/**
 * This file contains \QUI\Events\Manager
 */

namespace QUI\Events;

use QUI;
use QUI\ExceptionStack;

use function is_array;
use function is_string;

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
    protected array $siteEvents = [];

    /**
     * @var Event
     */
    protected Event $Events;

    /**
     * construct
     */
    public function __construct()
    {
        $this->Events = new Event();

        if (!QUI::$Conf) {
            return;
        }

        try {
            if (
                !QUI::$Conf->existValue('globals', 'eventsCreated')
                || !QUI::$Conf->getValue('globals', 'eventsCreated')
            ) {
                $exists = QUI::getDataBase()->table()->exist(self::table());

                QUI::$Conf->setValue('globals', 'eventsCreated', $exists);

                try {
                    QUI::$Conf->save();
                } catch (QUi\Exception $Exception) {
                    QUI\System\Log::writeDebugException($Exception);
                }

                if (!$exists) {
                    return;
                }
            }

            if (!QUI::$Conf->getValue('globals', 'eventsCreated')) {
                return;
            }


            $list = QUI::getDataBase()->fetch([
                'from' => self::table(),
                'where' => [
                    'sitetype' => null
                ],
                'order' => 'priority ASC'
            ]);

            foreach ($list as $params) {
                $this->Events->addEvent(
                    trim($params['event']),
                    trim($params['callback']),
                    $params['priority'] ?? 0,
                    trim($params['package']) ?? ''
                );
            }

            $list = QUI::getDataBase()->fetch([
                'from' => self::table(),
                'where' => [
                    'sitetype' => [
                        'type' => 'NOT',
                        'value' => null
                    ]
                ],
                'order' => 'priority ASC'
            ]);

            $this->siteEvents = $list;
        } catch (QUI\Database\Exception) {
        }
    }

    /**
     * Return the events db table name
     *
     * @return string
     */
    public static function table(): string
    {
        return QUI::getDBTableName('events');
    }

    /**
     * Adds an event
     * If $fn is a string, the event would be save in the database
     * if you want to register events for the runtime, please use lambda function
     *
     * @param string $event - The type of event (e.g. 'complete').
     * @param string|callable $fn - The function to execute.
     * @param string $package - Name of the package
     * @param int $priority - Event priority
     *
     * @throws QUI\Exception
     * @example $EventManager->addEvent('myEvent', function() { });
     *
     */
    public function addEvent($event, $fn, string $package = '', int $priority = 0)
    {
        if (!is_string($package)) {
            $package = '';
        }

        // add the event to the db
        if (is_string($fn)) {
            QUI::getDataBase()->insert(self::table(), [
                'event' => trim($event),
                'callback' => trim($fn),
                'package' => trim($package),
                'priority' => $priority
            ]);
        }

        $this->Events->addEvent($event, $fn, $priority);
    }

    /**
     * create the event table
     *
     * @throws QUI\Exception
     */
    public static function setup()
    {
        $DBTable = QUI::getDataBase()->table();

        $DBTable->addColumn(self::table(), [
            'event' => 'VARCHAR(255)',
            'callback' => 'TEXT NULL',
            'sitetype' => 'TEXT NULL',
            'package' => 'TEXT NULL',
            'priority' => 'INT DEFAULT 0'
        ]);

        QUI::getDataBase()->Table()->setFulltext(self::table(), 'sitetype');
        QUI::getDataBase()->Table()->setIndex(self::table(), 'event');

        self::clear();
    }

    /**
     * clear all events
     *
     * @param string|bool $package - name of the package, default = false => complete clear
     * @throws QUI\Exception
     */
    public static function clear($package = false)
    {
        if (empty($package) || !is_string($package)) {
            QUI::getDataBase()->table()->truncate(
                self::table()
            );

            return;
        }

        QUI::getDataBase()->delete(self::table(), [
            'package' => $package
        ]);
    }

    /**
     * Return a complete list of registered events
     *
     * @return array
     */
    public function getList(): array
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
    public function getSiteListByType(string $type): array
    {
        $result = [];

        foreach ($this->siteEvents as $event) {
            if ($event['sitetype'] == $type) {
                $result[] = $type;
            }
        }

        return $result;
    }

    /**
     * Adds a site event entry
     *
     * @param string $event - The type of event (e.g. 'complete').
     * @param callable $fn - The function to execute.
     * @param string $siteType - type of the site
     * @param int $priority - Event priority
     *
     * @throws QUI\Exception
     * @example $EventManager->addEvent('onSave', '\Namespace\Class::exec', 'quiqqer/blog:blog/entry' });
     *
     */
    public function addSiteEvent(string $event, callable $fn, string $siteType, int $priority = 0)
    {
        if (!is_string($fn)) {
            return;
        }

        QUI::getDataBase()->insert(self::table(), [
            'event' => trim($event),
            'callback' => trim($fn),
            'sitetype' => trim($siteType),
            'priority' => trim($priority)
        ]);
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
     * It removes the events from the database, too.
     *
     * @param string $event - The type of event (e.g. 'complete').
     * @param callable|boolean $fn - (optional) The function to remove.
     * @param string $package - Name of the package
     *
     * @throws QUI\Exception
     */
    public function removeEvent($event, $fn = false, string $package = '')
    {
        $this->Events->removeEvent($event, $fn);

        if ($fn === false) {
            QUI::getDataBase()->delete(self::table(), [
                'event' => $event
            ]);
        }

        if (is_string($fn)) {
            QUI::getDataBase()->delete(self::table(), [
                'event' => trim($event),
                'callback' => trim($fn),
                'package' => trim($package)
            ]);
        }
    }

    /**
     * @param QUI\Package\Package $Package
     * @throws QUI\Exception
     */
    public function removePackageEvents(QUI\Package\Package $Package)
    {
        QUI::getDataBase()->delete(self::table(), [
            'package' => $Package->getName()
        ]);
    }

    /**
     * Removes all events of the given type from the stack of events of a Class instance.
     * If no $fn is specified, removes all events of the event.
     * It removes the events from the database, too.
     *
     * @param array $events - [optional] If not passed removes all events of all types.
     */
    public function removeEvents(array $events)
    {
        $this->Events->removeEvents($events);
    }

    /**
     * Fire an event with optional arguments
     *
     * @param string $event The name of the event to fire
     * @param mixed $args Optional arguments to pass to the event handlers
     * @param bool $force Whether to force the event handlers to execute even if they are not enabled
     *
     * @return array         An array containing the results of the event handlers
     * @throws ExceptionStack
     */
    public function fireEvent($event, $args = false, bool $force = false): array
    {
        // event onFireEvent
        $fireArgs = $args;

        if (!is_array($fireArgs)) {
            $fireArgs = [];
        }

        $this->Events->fireEvent('onFireEvent', [$event, $fireArgs]);

        return $this->Events->fireEvent($event, $fireArgs, $force);
    }


    //region ignore

    /**
     * sets which package names should be ignored at a fire event
     *
     * @param $packageName
     */
    public function ignore($packageName)
    {
        $this->Events->ignore($packageName);
    }

    /**
     * Resets the ignore list
     */
    public function clearIgnore()
    {
        $this->Events->clearIgnore();
    }

    //endregion
}
