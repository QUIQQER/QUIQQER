<?php

/**
 * This file contains \QUI\Events\Manager
 */

namespace QUI\Events;

use QUI;
use QUI\Database\Exception;
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
     */
    protected array $siteEvents = [];

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

                QUI::$Conf->setValue('globals', 'eventsCreated', $exists ? 1 : 0);

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
                    trim($params['package'] ?? '')
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
     */
    public static function table(): string
    {
        return QUI::getDBTableName('events');
    }

    /**
     * Adds an event
     * If $fn is a string, the event would save via the database
     * if you want to register events for the runtime, please use lambda function
     *
     * @param string $event - The type of event (e.g. 'complete').
     * @param callable|string $fn - The function to execute.
     * @param int $priority
     * @param string $package
     * @throws Exception
     * @example $EventManager->addEvent('myEvent', function() { });
     */
    public function addEvent(
        string $event,
        callable | string $fn,
        int $priority = 0,
        string $package = ''
    ): void {
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
    public static function setup(): void
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
     * @param bool|string $package - name of the package, default = false => complete clear
     * @throws QUI\Exception
     */
    public static function clear(bool | string $package = false): void
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
     */
    public function getList(): array
    {
        return $this->Events->getList();
    }

    /**
     * Return a complete list of registered events for a specific site type
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
     * @param callable|string $fn - The function to execute.
     * @param string $siteType - type of the site
     * @param int $priority - Event priority
     *
     * @throws Exception
     * @example $EventManager->addEvent('onSave', '\Namespace\Class::exec', 'quiqqer/blog:blog/entry' });
     */
    public function addSiteEvent(
        string $event,
        callable | string $fn,
        string $siteType,
        int $priority = 0
    ): void {
        if (!is_string($fn)) {
            return;
        }

        QUI::getDataBase()->insert(self::table(), [
            'event' => trim($event),
            'callback' => trim($fn),
            'sitetype' => trim($siteType),
            'priority' => $priority
        ]);
    }

    /**
     * The same as addEvent, but accepts an array to add multiple events at once.
     */
    public function addEvents(array $events): void
    {
        $this->Events->addEvents($events);
    }

    /**
     * Removes an event from the stack of events
     * It removes the events from the database, too.
     *
     * @param string $event - The type of event (e.g. 'complete').
     * @param callable|boolean $fn - (optional) The function to remove.
     *
     * @throws QUI\Exception
     */
    public function removeEvent(
        string $event,
        callable | bool $fn = false,
        string $package = ''
    ): void {
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
     * @throws QUI\Exception
     */
    public function removePackageEvents(QUI\Package\Package $Package): void
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
    public function removeEvents(array $events): void
    {
        $this->Events->removeEvents($events);
    }

    /**
     * Fire an event with optional arguments
     *
     * @param string $event The name of the event to fire
     * @param bool|array $args Optional arguments to pass to the event handlers
     *
     * @return array         An array containing the results of the event handlers
     * @throws ExceptionStack
     */
    public function fireEvent(
        string $event,
        bool | array $args = false,
        bool $force = false
    ): array {
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
    public function ignore($packageName): void
    {
        $this->Events->ignore($packageName);
    }

    /**
     * Resets the ignore list
     */
    public function clearIgnore(): void
    {
        $this->Events->clearIgnore();
    }

    //endregion
}
