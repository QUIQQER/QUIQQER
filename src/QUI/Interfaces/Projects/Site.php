<?php

/**
 * This file contains the \QUI\Interfaces\Projects\Site
 */

namespace QUI\Interfaces\Projects;

use QUI;

/**
 * Site Object - eine einzelne Seite
 *
 * @author     www.pcsg.de (Henning Leutz)
 * @licence    For copyright and license information, please view the /README.md
 *
 * @errorcodes 7XX = Site Errors -> look at Site/Edit
 */
interface Site extends QUI\QDOMInterface
{
    public function getProject(): QUI\Projects\Project;

    /**
     * Lädt die Plugins der Seite
     *
     * @param boolean|string $plugin - Plugin welches geladen werden soll, optional, ansonsten werden alle geladen
     *
     * @return Site
     */
    public function load(bool | string $plugin = false): Site;

    /**
     * Serialisierungsdaten
     */
    public function encode(): string;

    /**
     * Setzt JSON Parameter
     *
     * @param string $params - JSON encoded string
     *
     * @return void
     *
     * @throws QUI\Exception
     */
    public function decode(string $params);

    /**
     * Holt frisch die Daten aus der DB
     *
     * @return void
     */
    public function refresh();

    /**
     * Prüft ob es eine Verknüpfung ist
     */
    public function isLinked(): bool | int;

    /**
     * Prüft ob es die Seite auch in einer anderen Sprache gibt
     */
    public function existLang(string $lang, bool $check_only_active = true): bool;

    /**
     * Gibt die IDs von Sprachverknüpfungen zurück
     *
     * @return array<string, int|false>
     */
    public function getLangIds(): array;

    /**
     * Return the ID of the site,
     * or the ID of the sibling (linked) site of another language
     *
     * @param boolean|string $lang - optional, if it is set, then the language of the wanted to be linked sibling site
     *
     * @return integer
     */
    public function getId(bool | string $lang = false): int;

    /**
     * Gibt alle Kinder zurück
     *
     * @param array<string, mixed> $params - Parameter für die Childrenausgabe
     *                      $params['where']
     *                      $params['limit']
     * @param boolean $load - Legt fest ob die Kinder die Plugins laden sollen
     *
     * @return array<int, mixed>|int
     */
    public function getChildren(array $params = [], bool $load = false): array | int;

    /**
     * Liefert die nächstfolgende Seite
     *
     * @return QUI\Projects\Site
     * @throws QUI\Exception
     */
    public function nextSibling(): Site;

    /**
     * Die nächsten x Kinder
     *
     * @return array<int, Site>
     */
    public function nextSiblings(int $no): array;

    /**
     * Liefert die vorhergehenden Seite
     *
     * @return QUI\Projects\Site
     * @throws QUI\Exception
     */
    public function previousSibling(): Site;

    /**
     * Die x vorhergehenden Geschwister
     *
     * @return array<int, Site>
     */
    public function previousSiblings(int $no): array;

    /**
     * Gibt das erste Kind der Seite zurück
     *
     * @param array<string, mixed> $params
     */
    public function firstChild(array $params = []): bool | Site;

    /**
     * Gibt die Kinder zurück achtet aber auf "Nicht in Navigation anzeigen" und Rechte
     *
     * @param array<string, mixed> $params
     *
     * @return array<int, mixed>|int
     */
    public function getNavigation(array $params = []): array | int;

    /**
     * @throws QUI\Exception
     */
    public function getChildIdByName(string $name): int;

    /**
     * @throws QUI\Exception
     */
    public function getChild(int $id): Site;

    /**
     * Gibt die ID's der Kinder zurück
     * Wenn nur die ID's verwendet werden sollte dies vor getChildren verwendet werden
     *
     * @param array<string, mixed> $params Parameter für die Childrenausgabe
     *                      $params['where']
     *                      $params['limit']
     *
     * @return array<int, int>|int
     */
    public function getChildrenIds(array $params = []): array | int;

    /**
     * Return ALL children ids under the site
     *
     * @param array<string, mixed> $params - db parameter
     *
     * @return array<int, int>
     */
    public function getChildrenIdsRecursive(array $params = []): array;

    /**
     * Gibt zurück ob Site Kinder besitzt
     *
     * @param boolean $navhide - if navhide == false, navhide must be 0
     *
     * @return integer - Anzahl der Kinder
     */
    public function hasChildren(bool $navhide = false): int;

    /**
     * Setzt das delete Flag
     *
     * @return bool
     *
     * @todo move to Site/Edit
     */
    public function delete();

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $getParams
     */
    public function getUrl(array $params = [], array $getParams = []): string;

    /**
     * @param array<string, mixed> $params
     */
    public function getUrlRewritten(array $params = []): string;

    public function getParentId(): int;

    /**
     * @return array<int, int>
     */
    public function getParentIds(): array;

    /**
     * @return array<int, int>
     */
    public function getParentIdTree(): array;

    /**
     * Gibt das Parent Objekt zurück.
     * Wenn kein Parent Objekt existiert wird false zurückgegeben.
     */
    public function getParent(): Site | bool;

    /**
     * @return array<int, Site>
     */
    public function getParents(): array;

    /**
     * @return void
     */
    public function restore();

    /**
     * Zerstört die Seite
     * Die Seite wird komplett aus der DB gelöscht und auch alle Beziehungen
     * Funktioniert nur wenn die Seite gelöscht ist
     *
     * @return void
     */
    public function destroy();

    /**
     * Canonical URL - Um doppelte Inhalt zu vermeiden
     */
    public function getCanonical(): string;

    /**
     * @return void
     */
    public function deleteCache();

    /**
     * @return void
     */
    public function createCache();

    public function hasPermission(
        string $permission,
        null | QUI\Interfaces\Users\User $User = null
    ): bool | int;

    /**
     * @return void
     *
     * @throws QUI\Exception
     */
    public function checkPermission(
        string $permission,
        null | QUI\Interfaces\Users\User $User = null
    );
}
