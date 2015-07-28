<?php

/**
 * This file contains the \QUI\Interfaces\Projects\Site
 */

namespace QUI\Interfaces\Projects;

use QUI;

/**
 * Site Objekt - eine einzelne Seite
 *
 * @author     www.pcsg.de (Henning Leutz)
 * @licence    For copyright and license information, please view the /README.md
 *
 * @errorcodes 7XX = Site Errors -> look at Site/Edit
 */
interface Site
{
    /**
     * Return the project object of the site
     *
     * @return QUI\Projects\Project
     */
    public function getProject();

    /**
     * Lädt die Plugins der Seite
     *
     * @param String|bool $plugin - Plugin welches geladen werden soll, optional, ansonsten werden alle geladen
     *
     * @return Site
     */
    public function load($plugin = false);

    /**
     * Serialisierungsdaten
     *
     * @return String
     */
    public function encode();

    /**
     * Setzt JSON Parameter
     *
     * @param String $params - JSON encoded string
     *
     * @throws QUI\Exception
     */
    public function decode($params);

    /**
     * Hohlt frisch die Daten aus der DB
     */
    public function refresh();

    /**
     * Prüft ob es eine Verknüpfung ist
     *
     * @return Bool|Integer
     */
    public function isLinked();

    /**
     * Prüft ob es die Seite auch in einer anderen Sprache gibt
     *
     * @param String $lang
     * @param Bool   $check_only_active - check only active pages
     *
     * @return Bool
     */
    public function existLang($lang, $check_only_active = true);

    /**
     * Gibt die IDs von Sprachverknüpfungen zurück
     *
     * @return Array
     */
    public function getLangIds();

    /**
     * Return the ID of the site,
     * or the ID of the sibling (linked) site of another languager
     *
     * @param String|bool $lang - optional, if it is set, then the language of the wanted linked sibling site
     *
     * @return Integer
     */
    public function getId($lang = false);

    /**
     * Gibt alle Kinder zurück
     *
     * @param array $params - Parameter für die Childrenausgabe
     *                      $params['where']
     *                      $params['limit']
     * @param Bool  $load   - Legt fest ob die Kinder die Plugins laden sollen
     *
     * @return Array;
     */
    public function getChildren($params = array(), $load = false);

    /**
     * Liefert die nächstfolgende Seite
     *
     * @return QUI\Projects\Site
     * @throws QUI\Exception
     */
    public function nextSibling();

    /**
     * Die nächsten x Kinder
     *
     * @param Integer $no
     *
     * @return Array
     */
    public function nextSiblings($no);

    /**
     * Liefert die vorhergehenden Seite
     *
     * @return QUI\Projects\Site
     * @throws QUI\Exception
     */
    public function previousSibling();

    /**
     * Die x vorhergehenden Geschwister
     *
     * @param Integer $no
     *
     * @return Array
     */
    public function previousSiblings($no);

    /**
     * Gibt das erste Kind der Seite zurück
     *
     * @param Array $params
     *
     * @return QUI\Projects\Site | false
     */
    public function firstChild($params = array());

    /**
     * Gibt die Kinder zurück achtet aber auf "Nicht in Navigation anzeigen" und Rechte
     *
     * @param array $params
     *
     * @return array
     */
    public function getNavigation($params = array());

    /**
     * Gibt ein Kind zurück welches den Namen hat
     *
     * @param String $name
     *
     * @return Integer
     * @throws QUI\Exception
     */
    public function getChildIdByName($name);

    /**
     * Return a children by id
     *
     * @param Integer $id
     *
     * @return QUI\Projects\Site
     * @throws QUI\Exception
     */
    public function getChild($id);

    /**
     * Gibt die ID's der Kinder zurück
     * Wenn nur die ID's verwendet werden sollte dies vor getChildren verwendet werden
     *
     * @param Array $params Parameter für die Childrenausgabe
     *                      $params['where']
     *                      $params['limit']
     *
     * @return Array
     */
    public function getChildrenIds($params = array());

    /**
     * Return ALL children ids under the site
     *
     * @param array $params - db parameter
     *
     * @return array
     */
    public function getChildrenIdsRecursive($params = array());

    /**
     * Gibt zurück ob Site Kinder besitzt
     *
     * @param Bool $navhide - if navhide == false, navhide must be 0
     *
     * @return Integer - Anzahl der Kinder
     */
    public function hasChildren($navhide = false);

    /**
     * Setzt das delete Flag
     *
     * @todo move to Site/Edit
     */
    public function delete();

    /**
     * Gibt die URL der Seite zurück
     *
     * @param $params
     * @param $rewrited
     *
     * @return String
     */
    public function getUrl($params = array(), $rewrited = false);

    /**
     * Gibt eine sprechenden URL zurück
     * DB Abfragen werden gemacht - Hier auf Performance achten
     *
     * @param Array $params - Parameter welche an die URL angehängt werden
     *
     * @return String
     */
    public function getUrlRewrited($params = array());

    /**
     * Return the Parent id from the site object
     *
     * @return Integer
     */
    public function getParentId();

    /**
     * Gibt alle direkten Eltern Ids zurück
     *
     * Site
     * ->Parent
     * ->Parent
     * ->Parent
     *
     * @return Array
     */
    public function getParentIds();

    /**
     * Return the Parent ID List
     *
     * @return Array
     */
    public function getParentIdTree();

    /**
     * Gibt das Parent Objekt zurück
     *
     * @return Site
     */
    public function getParent();

    /**
     * Gibt alle rekursive Parents als Objekte zurück
     * Site->Parent->ParentParent->ParentParentParent
     *
     * @return Array
     */
    public function getParents();

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
     * @return String
     */
    public function getCanonical();

    /**
     * Löscht den Seitencache
     */
    public function deleteCache();

    /**
     * Löscht den Seitencache
     */
    public function createCache();

    /**
     * Shortcut for QUI\Rights\Permission::hasSitePermission
     *
     * @param string              $permission - name of the permission
     * @param QUI\Users\User|Bool $User       - optional
     *
     * @return Bool|Integer
     */
    public function hasPermission($permission, $User = false);

    /**
     * Shortcut for QUI\Rights\Permission::checkSitePermission
     *
     * @param string              $permission - name of the permission
     * @param QUI\Users\User|Bool $User       - optional
     *
     * @throws QUI\Exception
     */
    public function checkPermission($permission, $User = false);
}
