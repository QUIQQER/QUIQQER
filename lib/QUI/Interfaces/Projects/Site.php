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
interface Site extends QUI\QDOMInterface
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
     * @param string|boolean $plugin - Plugin welches geladen werden soll, optional, ansonsten werden alle geladen
     *
     * @return Site
     */
    public function load($plugin = false);

    /**
     * Serialisierungsdaten
     *
     * @return string
     */
    public function encode();

    /**
     * Setzt JSON Parameter
     *
     * @param string $params - JSON encoded string
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
     * @return boolean|integer
     */
    public function isLinked();

    /**
     * Prüft ob es die Seite auch in einer anderen Sprache gibt
     *
     * @param string $lang
     * @param boolean $check_only_active - check only active pages
     *
     * @return boolean
     */
    public function existLang($lang, $check_only_active = true);

    /**
     * Gibt die IDs von Sprachverknüpfungen zurück
     *
     * @return array
     */
    public function getLangIds();

    /**
     * Return the ID of the site,
     * or the ID of the sibling (linked) site of another languager
     *
     * @param string|boolean $lang - optional, if it is set, then the language of the wanted linked sibling site
     *
     * @return integer
     */
    public function getId($lang = false);

    /**
     * Gibt alle Kinder zurück
     *
     * @param array $params - Parameter für die Childrenausgabe
     *                      $params['where']
     *                      $params['limit']
     * @param boolean $load - Legt fest ob die Kinder die Plugins laden sollen
     *
     * @return array;
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
     * @param integer $no
     *
     * @return array
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
     * @param integer $no
     *
     * @return array
     */
    public function previousSiblings($no);

    /**
     * Gibt das erste Kind der Seite zurück
     *
     * @param array $params
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
     * @param string $name
     *
     * @return integer
     * @throws QUI\Exception
     */
    public function getChildIdByName($name);

    /**
     * Return a children by id
     *
     * @param integer $id
     *
     * @return QUI\Projects\Site
     * @throws QUI\Exception
     */
    public function getChild($id);

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
     * @param boolean $navhide - if navhide == false, navhide must be 0
     *
     * @return integer - Anzahl der Kinder
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
     * @return string
     */
    public function getUrl($params = array(), $rewrited = false);

    /**
     * Gibt eine sprechenden URL zurück
     * DB Abfragen werden gemacht - Hier auf Performance achten
     *
     * @param array $params - Parameter welche an die URL angehängt werden
     *
     * @return string
     * @deprecated
     */
//    public function getUrlRewrited($params = array());

    /**
     * @param array $params
     * @return mixed
     */
    public function getUrlRewritten($params = array());

    /**
     * Return the Parent id from the site object
     *
     * @return integer
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
     * @return array
     */
    public function getParentIds();

    /**
     * Return the Parent ID List
     *
     * @return array
     */
    public function getParentIdTree();

    /**
     * Gibt das Parent Objekt zurück.
     * Wenn kein Parent Objekt existiert wird false zurückgegeben.
     *
     * @return Site|false
     */
    public function getParent();

    /**
     * Gibt alle rekursive Parents als Objekte zurück
     * Site->Parent->ParentParent->ParentParentParent
     *
     * @return array
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
     * @return string
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
     * Shortcut for QUI\Permissions\Permission::hasSitePermission
     *
     * @param string $permission - name of the permission
     * @param QUI\Users\User|boolean $User - optional
     *
     * @return boolean|integer
     */
    public function hasPermission($permission, $User = false);

    /**
     * Shortcut for QUI\Permissions\Permission::checkSitePermission
     *
     * @param string $permission - name of the permission
     * @param QUI\Users\User|boolean $User - optional
     *
     * @throws QUI\Exception
     */
    public function checkPermission($permission, $User = false);
}
