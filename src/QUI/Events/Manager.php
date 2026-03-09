<?php

/**
 * This file contains \QUI\Events\Manager
 */

namespace QUI\Events;

use FilesystemIterator;
use DOMDocument;
use QUI;
use QUI\ExceptionStack;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

use function basename;
use function dirname;
use function file_exists;
use function file_put_contents;
use function is_array;
use function is_dir;
use function is_file;
use function is_string;
use function rename;
use function str_replace;
use function sys_get_temp_dir;
use function tempnam;
use function trim;
use function unlink;
use function var_export;

/**
 * The Event Manager
 * Registered and set global events
 *
 * If you register an event and the callback function is a string,
 * the callback function is persisted to a generated PHP cache file.
 */
class Manager implements QUI\Interfaces\Events
{
    protected const CACHE_FILE = 'cache/events.php';

    /**
     * @var array<int, array{
     *     event: string,
     *     callback: string,
     *     sitetype: string,
     *     priority: int
     * }>
     */
    protected array $siteEvents = [];

    protected Event $Events;

    /**
     * Load persisted string events and site events from the generated cache file.
     */
    public function __construct()
    {
        $this->Events = new Event();

        if (!QUI::$Conf) {
            return;
        }

        if (!is_file(self::getCacheFile())) {
            self::rebuildCache();
        }

        $this->loadFromCache();
    }

    /**
     * Add an event listener.
     * String callbacks are persisted to the generated cache file.
     * Runtime-only callbacks should be passed as closures or callables.
     *
     * @param string $event Event name such as `onSave`
     * @param callable|string $fn Event handler
     * @param int $priority Lower values run earlier
     * @param string $package Owning package for persisted string callbacks
     * @example $EventManager->addEvent('myEvent', function() { });
     */
    public function addEvent(
        string $event,
        callable | string $fn,
        int $priority = 0,
        string $package = ''
    ): void {
        if (is_string($fn)) {
            $event = trim($event);
            $fn = trim($fn);
            $package = trim($package);

            // Replace an existing persisted entry so repeated setup/import runs do not duplicate cache entries.
            $this->deletePersistedEvent($event, $fn, null, $package);

            $this->persistedEvents[] = [
                'event' => trim($event),
                'callback' => trim($fn),
                'package' => trim($package),
                'priority' => $priority
            ];

            $this->writeCache();
        }

        $this->Events->addEvent($event, $fn, $priority, $package);
    }

    /**
     * Prepare the cache directory, initialize the persisted event file and
     * remove the legacy events table if it still exists.
     */
    public static function setup(): void
    {
        try {
            $table = QUI::getDBTableName('events');

            if (QUI::getDataBase()->table()->exist($table)) {
                QUI::getDataBase()->table()->delete($table);
            }
        } catch (Throwable $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        self::ensureCacheDirectory();
        self::clear();
    }

    /**
     * Clear all persisted events or only the persisted events of one package.
     *
     * @param bool|string $package Package name or `false` for a full clear
     */
    public static function clear(bool | string $package = false): void
    {
        $data = self::readCacheData();

        if (empty($package) || !is_string($package)) {
            if (QUI::$Events instanceof self) {
                QUI::$Events->Events = new Event();
                QUI::$Events->persistedEvents = [];
                QUI::$Events->siteEvents = [];
            }

            self::writeCacheData([
                'events' => [],
                'siteEvents' => []
            ]);
            return;
        }

        $data['events'] = array_values(
            array_filter(
                $data['events'],
                static function (array $event) use ($package): bool {
                    return $event['package'] !== $package;
                }
            )
        );

        if (QUI::$Events instanceof self) {
            QUI::$Events->removePackageEventsByName($package);
        }

        self::writeCacheData($data);
    }

    /**
     * Return all runtime-loaded event listeners.
     *
     * @return array<string, array<int, array{
     *     callable: callable|string,
     *     priority: int,
     *     package: string
     * }>>
     */
    public function getList(): array
    {
        return $this->Events->getList();
    }

    /**
     * Return the persisted site event entries registered for a site type.
     *
     * @return array<int, array{
     *     event: string,
     *     callback: string,
     *     sitetype: string,
     *     priority: int
     * }>
     */
    public function getSiteListByType(string $type): array
    {
        $result = [];

        foreach ($this->siteEvents as $event) {
            if ($event['sitetype'] == $type) {
                $result[] = $event;
            }
        }

        return $result;
    }

    /**
     * Add a persisted site event entry.
     *
     * @param string $event Event name such as `onSave`
     * @param callable|string $fn Event handler
     * @param string $siteType Site type identifier
     * @param int $priority Lower values run earlier
     *
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

        $event = trim($event);
        $fn = trim($fn);
        $siteType = trim($siteType);

        $this->deletePersistedEvent($event, $fn, $siteType);
        $this->siteEvents[] = [
            'event' => trim($event),
            'callback' => trim($fn),
            'sitetype' => trim($siteType),
            'priority' => $priority
        ];

        $this->writeCache();
    }

    /**
     * Add multiple runtime-only events at once.
     *
     * @param array<string, callable|string|array{0: callable|string, 1: int, 2: string}> $events
     */
    public function addEvents(array $events): void
    {
        $this->Events->addEvents($events);
    }

    /**
     * Remove an event listener from the runtime stack and persisted cache.
     *
     * @param string $event Event name
     * @param callable|bool $fn Specific callback or `false` to remove the whole event
     * @param string $package Package name for persisted string callbacks
     */
    public function removeEvent(
        string $event,
        callable | bool $fn = false,
        string $package = ''
    ): void {
        $this->Events->removeEvent($event, $fn);

        if ($fn === false) {
            $this->deletePersistedEvent(trim($event));
            $this->writeCache();
        }

        if (is_string($fn)) {
            $this->deletePersistedEvent(trim($event), trim($fn), null, trim($package));
            $this->writeCache();
        }
    }

    /**
     * Remove all persisted event listeners of a package.
     */
    public function removePackageEvents(QUI\Package\Package $Package): void
    {
        $this->removePackageEventsByName($Package->getName());

        $this->writeCache();
    }

    /**
     * Removes all events of the given type from the stack of events of a Class instance.
     * If no $fn is specified, removes all events of the event.
     * It removes the events from the runtime stack only.
     *
     * @param array<string, callable|false> $events
     */
    public function removeEvents(array $events): void
    {
        $this->Events->removeEvents($events);
    }

    /**
     * Fire an event with optional arguments
     *
     * @param string $event The name of the event to fire
     * @param bool|array<array-key, mixed> $args Optional arguments to pass to the event handlers
     *
     * @return array<string, mixed> Results indexed by callback name
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

        try {
            $this->Events->fireEvent('onFireEvent', [$event, $fireArgs]);
        } catch (Throwable $Exception) {
            error_log(
                '[QUI::Events::Manager] onFireEvent failed for "' . $event . '": '
                . $Exception->getMessage()
            );
        }

        return $this->Events->fireEvent($event, $fireArgs, $force);
    }


    //region ignore

    /**
     * Ignore all handlers of a package while firing events.
     */
    public function ignore(string $packageName): void
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

    /**
     * @var array<int, array{
     *     event: string,
     *     callback: string,
     *     package: string,
     *     priority: int
     * }>
     */
    protected array $persistedEvents = [];

    /**
     * Load persisted listeners into the runtime event stack.
     */
    protected function loadFromCache(): void
    {
        $data = self::readCacheData();

        $this->persistedEvents = $data['events'];
        $this->siteEvents = $data['siteEvents'];

        foreach ($this->persistedEvents as $params) {
            if (
                empty($params['event'])
                || empty($params['callback'])
            ) {
                continue;
            }

            $this->Events->addEvent(
                trim($params['event']),
                trim($params['callback']),
                (int)($params['priority'] ?? 0),
                trim($params['package'] ?? '')
            );
        }
    }

    /**
     * Return the absolute path of the generated event cache file.
     */
    protected static function getCacheFile(): string
    {
        return VAR_DIR . self::CACHE_FILE;
    }

    /**
     * Ensure the cache directory for the generated event file exists.
     */
    protected static function ensureCacheDirectory(): void
    {
        $dir = dirname(self::getCacheFile());

        if (is_dir($dir)) {
            return;
        }

        QUI\Utils\System\File::mkdir($dir);
    }

    /**
     * Read the persisted event cache.
     *
     * @return array{
     *     events: array<int, array{
     *         event: string,
     *         callback: string,
     *         package: string,
     *         priority: int
     *     }>,
     *     siteEvents: array<int, array{
     *         event: string,
     *         callback: string,
     *         sitetype: string,
     *         priority: int
     *     }>
     * }
     */
    protected static function readCacheData(): array
    {
        $cacheFile = self::getCacheFile();

        if (!is_file($cacheFile)) {
            return [
                'events' => [],
                'siteEvents' => []
            ];
        }

        try {
            $data = require $cacheFile;

            if (
                !is_array($data)
                || !isset($data['events'])
                || !isset($data['siteEvents'])
                || !is_array($data['events'])
                || !is_array($data['siteEvents'])
            ) {
                return [
                    'events' => [],
                    'siteEvents' => []
                ];
            }

            return $data;
        } catch (Throwable $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            return [
                'events' => [],
                'siteEvents' => []
            ];
        }
    }

    /**
     * Persist the current in-memory event state to the generated cache file.
     */
    protected function writeCache(): void
    {
        self::writeCacheData([
            'events' => $this->persistedEvents,
            'siteEvents' => $this->siteEvents
        ]);
    }

    /**
     * Write the full persisted event state to disk.
     *
     * @param array{
     *     events: array<int, array{
     *         event: string,
     *         callback: string,
     *         package: string,
     *         priority: int
     *     }>,
     *     siteEvents: array<int, array{
     *         event: string,
     *         callback: string,
     *         sitetype: string,
     *         priority: int
     *     }>
     * } $data
     */
    protected static function writeCacheData(array $data): void
    {
        self::ensureCacheDirectory();

        $cacheFile = self::getCacheFile();
        $tempFile = tempnam(dirname($cacheFile), 'events.');

        if ($tempFile === false) {
            $tempFile = tempnam(sys_get_temp_dir(), 'events.');
        }

        if ($tempFile === false) {
            QUI\System\Log::addError('Unable to create temporary events cache file.');
            return;
        }

        $content = "<?php\n\nreturn " . var_export($data, true) . ";\n";

        file_put_contents($tempFile, $content);
        rename($tempFile, $cacheFile);

        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }

    /**
     * Remove persisted event entries matching the given filters.
     */
    protected function deletePersistedEvent(
        string $event,
        string | null $callback = null,
        string | null $siteType = null,
        string $package = ''
    ): void {
        $this->persistedEvents = array_values(
            array_filter(
                $this->persistedEvents,
                static function (array $entry) use ($event, $callback, $package): bool {
                    if ($entry['event'] !== $event) {
                        return true;
                    }

                    if ($callback !== null && $entry['callback'] !== $callback) {
                        return true;
                    }

                    if ($package !== '' && $entry['package'] !== $package) {
                        return true;
                    }

                    return false;
                }
            )
        );

        $this->siteEvents = array_values(
            array_filter(
                $this->siteEvents,
                static function (array $entry) use ($event, $callback, $siteType): bool {
                    if ($entry['event'] !== $event) {
                        return true;
                    }

                    if ($callback !== null && $entry['callback'] !== $callback) {
                        return true;
                    }

                    if ($siteType !== null && $entry['sitetype'] !== $siteType) {
                        return true;
                    }

                    return false;
                }
            )
        );
    }

    /**
     * Remove all runtime and persisted event listeners belonging to a package.
     */
    protected function removePackageEventsByName(string $packageName): void
    {
        foreach ($this->Events->getList() as $event => $entries) {
            foreach ($entries as $entry) {
                if ($entry['package'] !== $packageName) {
                    continue;
                }

                $this->Events->removeEvent($event, $entry['callable']);
            }
        }

        $this->persistedEvents = array_values(
            array_filter(
                $this->persistedEvents,
                static function (array $event) use ($packageName): bool {
                    return $event['package'] !== $packageName;
                }
            )
        );
    }

    /**
     * Rebuild the generated event cache from available XML files.
     */
    protected static function rebuildCache(): void
    {
        $data = [
            'events' => [],
            'siteEvents' => []
        ];

        foreach (self::getEventXmlFiles() as $file => $packageName) {
            self::appendEventsFromXml($data, $file, $packageName);
        }

        foreach (self::getSiteXmlFiles() as $file) {
            self::appendSiteEventsFromXml($data, $file);
        }

        self::writeCacheData($data);
    }

    /**
     * Return all `events.xml` files together with their package name.
     *
     * @return array<string, string>
     */
    protected static function getEventXmlFiles(): array
    {
        $files = [];
        $rootEvents = CMS_DIR . 'events.xml';

        if (is_file($rootEvents)) {
            $files[$rootEvents] = 'quiqqer/core';
        }

        foreach (self::findPackageXmlFiles('events.xml') as $file) {
            $files[$file] = self::detectPackageNameByXmlFile($file);
        }

        return $files;
    }

    /**
     * Return all `site.xml` files that may contain site event definitions.
     *
     * @return array<int, string>
     */
    protected static function getSiteXmlFiles(): array
    {
        $files = [];
        $rootSite = CMS_DIR . 'site.xml';

        if (is_file($rootSite)) {
            $files[] = $rootSite;
        }

        foreach (self::findPackageXmlFiles('site.xml') as $file) {
            $files[] = $file;
        }

        return $files;
    }

    /**
     * Find package XML files below `OPT_DIR`.
     *
     * @return array<int, string>
     */
    protected static function findPackageXmlFiles(string $fileName): array
    {
        $result = [];

        if (!defined('OPT_DIR') || !is_dir(OPT_DIR)) {
            return $result;
        }

        $Directory = new RecursiveDirectoryIterator(
            OPT_DIR,
            FilesystemIterator::SKIP_DOTS
        );
        $Iterator = new RecursiveIteratorIterator($Directory);

        foreach ($Iterator as $File) {
            if (!$File->isFile() || $File->getFilename() !== $fileName) {
                continue;
            }

            if (str_contains($File->getPathname(), '/composer/')) {
                continue;
            }

            $result[] = $File->getPathname();
        }

        return $result;
    }

    /**
     * Derive the package name from an XML file path below `OPT_DIR`.
     */
    protected static function detectPackageNameByXmlFile(string $file): string
    {
        $path = str_replace(OPT_DIR, '', dirname($file));
        $path = trim(str_replace('\\', '/', $path), '/');

        if ($path === '') {
            return basename(dirname($file));
        }

        return $path;
    }

    /**
     * Append event definitions from an `events.xml` file.
     *
     * @param array{
     *     events: array<int, array{
     *         event: string,
     *         callback: string,
     *         package: string,
     *         priority: int
     *     }>,
     *     siteEvents: array<int, array{
     *         event: string,
     *         callback: string,
     *         sitetype: string,
     *         priority: int
     *     }>
     * } $data
     * @param string $file
     * @param string $packageName
     */
    protected static function appendEventsFromXml(array &$data, string $file, string $packageName): void
    {
        try {
            $Dom = new DOMDocument();
            $Dom->load($file);

            foreach ($Dom->getElementsByTagName('event') as $Event) {
                $on = trim($Event->getAttribute('on'));
                $fire = trim($Event->getAttribute('fire'));

                if ($on === '' || $fire === '') {
                    continue;
                }

                $priority = (int)$Event->getAttribute('priority');

                $data['events'][] = [
                    'event' => $on,
                    'callback' => $fire,
                    'package' => $packageName,
                    'priority' => $priority
                ];
            }
        } catch (Throwable $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }
    }

    /**
     * Append site event definitions from a `site.xml` file.
     *
     * @param array{
     *     events: array<int, array{
     *         event: string,
     *         callback: string,
     *         package: string,
     *         priority: int
     *     }>,
     *     siteEvents: array<int, array{
     *         event: string,
     *         callback: string,
     *         sitetype: string,
     *         priority: int
     *     }>
     * } $data
     * @param string $file
     */
    protected static function appendSiteEventsFromXml(array &$data, string $file): void
    {
        try {
            $Dom = new DOMDocument();
            $Dom->load($file);

            foreach ($Dom->getElementsByTagName('event') as $Event) {
                $type = trim($Event->getAttribute('type'));
                $on = trim($Event->getAttribute('on'));
                $fire = trim($Event->getAttribute('fire'));

                if ($type === '' || $on === '' || $fire === '') {
                    continue;
                }

                $priority = (int)$Event->getAttribute('priority');

                $data['siteEvents'][] = [
                    'event' => $on,
                    'callback' => $fire,
                    'sitetype' => $type,
                    'priority' => $priority
                ];
            }
        } catch (Throwable $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }
    }
}
