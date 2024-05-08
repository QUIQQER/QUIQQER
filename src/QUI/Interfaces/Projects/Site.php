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
    /**
     * Return the project object of the site
     *
     * @return QUI\Projects\Project
     */
    public function getProject(): QUI\Projects\Project;

    /**
     * Lädt die Plugins der Seite
     *
     * @param boolean|string $plugin - Plugin welches geladen werden soll, optional, ansonsten werden alle geladen
     *
     * @return Site
     */
    public function load(bool|string $plugin = false): Site;

    /**
     * Serialisierungsdaten
     *
     * @return string
     */
    public function encode(): string;

    /**
     * Setzt JSON Parameter
     *
     * @param string $params - JSON encoded string
     *
     * @throws QUI\Exception
     */
    public function decode(string $params);

    /**
     * Hohlt frisch die Daten aus der DB
     */
    public function refresh();

    /**
     * Prüft ob es eine Verknüpfung ist
     *
     * @return boolean|integer
     */
    public function isLinked(): bool|int;

    /**
     * Prüft ob es die Seite auch in einer anderen Sprache gibt
     *
     * @param string $lang
     * @param boolean $check_only_active - check only active pages
     *
     * @return boolean
     */
    public function existLang(string $lang, bool $check_only_active = true): bool;

    /**
     * Gibt die IDs von Sprachverknüpfungen zurück
     *
     * @return array
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
    public function getId(bool|string $lang = false): int;

    /**
     * Gibt alle Kinder zurück
     *
     * @param array $params - Parameter für die Childrenausgabe
     *                      $params['where']
     *                      $params['limit']
     * @param boolean $load - Legt fest ob die Kinder die Plugins laden sollen
     *
     * @return array|int
     */
    public function getChildren(array $params = [], bool $load = false): array|int;

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
     * @param integer $no
     *
     * @return array
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
     * @param integer $no
     *
     * @return array
     */
    public function previousSiblings(int $no): array;

    /**
     * Gibt das erste Kind der Seite zurück
     *
     * @param array $params
     *
     * @return QUI\Projects\Site | false
     */
    public function firstChild(array $params = []): bool|Site;

    /**
     * Gibt die Kinder zurück achtet aber auf "Nicht in Navigation anzeigen" und Rechte
     *
     * @param array $params
     *
     * @return array|int
     */
    public function getNavigation(array $params = []): array|int;

    /**
     * Gibt ein Kind zurück welches den Namen hat
     *
     * @param string $name
     *
     * @return integer
     * @throws QUI\Exception
     */
    public function getChildIdByName(string $name): int;

    /**
     * Return a children by id
     *
     * @param integer $id
     *
     * @return QUI\Projects\Site
     * @throws QUI\Exception
     */
    public function getChild(int $id): Site;

    /**
     * Gibt die ID's der Kinder zurück
     * Wenn nur die ID's verwendet werden sollte dies vor getChildren verwendet werden
     *
     * @param array $params Parameter für die Childrenausgabe
     *                      $params['where']
     *                      $params['limit']
     *
     * @return array
     */
    public function getChildrenIds(array $params = []): array;

    /**
     * Return ALL children ids under the site
     *
     * @param array $params - db parameter
     *
     * @return array
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
     * @todo move to Site/Edit
     */
    public function delete();

    /**
     * Gibt die URL der Seite zurück
     *
     * @param array $params
     * @param array $getParams
     *
     * @return string
     */
    public function getUrl(array $params = [], array $getParams = []): string;

    /**
     * @param array $params
     * @return string
     */
    public function getUrlRewritten(array $params = []): string;

    /**
     * Return the Parent id from the site object
     *
     * @return integer
     */
    public function getParentId(): int;

    /**
     * Gibt alle direkten Eltern Ids zurück
     *
     * Site
     * ->Parent
     * ->Parent
     * ->Parent
     *
     * @return array
     */
    public function getParentIds(): array;

    /**
     * Return the Parent ID List
     *
     * @return array
     */
    public function getParentIdTree(): array;

    /**
     * Gibt das Parent Objekt zurück.
     * Wenn kein Parent Objekt existiert wird false zurückgegeben.
     *
     * @return Site|false
     */
    public function getParent(): Site|bool;

    /**
     * Gibt alle rekursive Parents als Objekte zurück
     * Site->Parent->ParentParent->ParentParentParent
     *
     * @return array
     */
    public function getParents(): array;

    /**
     * Stellt die Seite wieder her
     *
     * ??? wieso hier? und nicht im trash? O.o
     */
    public function restore();

    /**
     * Zerstört die Seite
     * Die Seite wird komplett aus der DB gelöscht und auch alle Beziehungen
     * Funktioniert nur wenn die Seite gelöscht ist
     */
    public function destroy();

    /**
     * Canonical URL - Um doppelte Inhalt zu vermeiden
     *
     * @return string
     */
    public function getCanonical(): string;

    /**
     * Löscht den Seitencache
     */
    public function deleteCache();

    /**
     * Löscht den Seitencache
     */
    public function createCache();

    /**
     * Shortcut for QUI\Permissions\Permission::hasSitePermission
     *
     * @param string $permission - name of the permission
     * @param ?QUI\Interfaces\Users\User $User - optional
     *
     * @return boolean|integer
     */
    public function hasPermission(string $permission, QUI\Interfaces\Users\User $User = null): bool|int;

    /**
     * Shortcut for QUI\Permissions\Permission::checkSitePermission
     *
     * @param string $permission - name of the permission
     * @param QUI\Interfaces\Users\User|null $User - optional
     *
     * @throws QUI\Exception
     */
    public function checkPermission(string $permission, QUI\Interfaces\Users\User $User = null);
}
