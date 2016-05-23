<?php

/**
 * File contains QUI\Projects\Site\Virtual
 */
namespace QUI\Projects\Site;

use QUI;

/**
 * Virtual site object
 * not a real site in the database
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Virtual extends QUI\QDOM implements QUI\Interfaces\Projects\Site
{
    /**
     * @var null|QUI\Projects\Site
     */
    protected $Parent = null;

    /**
     * Project
     *
     * @var null
     */
    protected $Project = null;

    /**
     * @param array $attributes
     * @param QUI\Projects\Project $Project
     * @param QUI\Projects\Site $Parent
     *
     * @throws QUI\Exception
     */
    public function __construct(
        $attributes = array(),
        QUI\Projects\Project $Project = null,
        QUI\Projects\Site $Parent = null
    ) {
        $this->Project = $Project;
        $this->Parent  = $Parent;

        $this->setAttributes($attributes);

        $needles = array('id', 'title', 'name', 'url');

        foreach ($needles as $needle) {
            if (!$this->getAttribute($needle)) {
                throw new QUI\Exception('Misisng attribute ' . $needle);
            }
        }
    }

    /**
     * Return the project object of the site
     *
     * @return QUI\Projects\Project
     */
    public function getProject()
    {
        return $this->Project;
    }

    /**
     * Lädt die Plugins der Seite
     *
     * @param string|boolean $plugin - Plugin welches geladen werden soll, optional, ansonsten werden alle geladen
     *
     * @return Virtual
     */
    public function load($plugin = false)
    {
        return $this;
    }

    /**
     * Serialisierungsdaten
     *
     * @return string
     */
    public function encode()
    {
        return json_encode($this->getAttributes());
    }

    /**
     * Setzt JSON Parameter
     *
     * @param string $params - JSON encoded string
     *
     * @throws QUI\Exception
     */
    public function decode($params)
    {
        $this->setAttributes(
            json_decode($params, true)
        );
    }

    /**
     * Hohlt frisch die Daten aus der DB
     */
    public function refresh()
    {
    }

    /**
     * Prüft ob es eine Verknüpfung ist
     *
     * @return boolean|integer
     */
    public function isLinked()
    {
        return false;
    }

    /**
     * Prüft ob es die Seite auch in einer anderen Sprache gibt
     *
     * @param string $lang
     * @param boolean $check_only_active - check only active pages
     *
     * @return boolean
     */
    public function existLang($lang, $check_only_active = true)
    {
        return false;
    }

    /**
     * Gibt die IDs von Sprachverknüpfungen zurück
     *
     * @return array
     */
    public function getLangIds()
    {
        return array();
    }

    /**
     * Return the ID of the site,
     * or the ID of the sibling (linked) site of another languager
     *
     * @param string|boolean $lang - optional, if it is set, then the language of the wanted linked sibling site
     *
     * @return integer
     */
    public function getId($lang = false)
    {
        return $this->getAttribute('id');
    }

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
    public function getChildren($params = array(), $load = false)
    {
        return array();
    }

    /**
     * Liefert die nächstfolgende Seite
     *
     * @return QUI\Projects\Site
     * @throws QUI\Exception
     */
    public function nextSibling()
    {
        throw new QUI\Exception('Die Seite besitzt keine nächstfolgende Seite');
    }

    /**
     * Die nächsten x Kinder
     *
     * @param integer $no
     *
     * @return array
     */
    public function nextSiblings($no)
    {
        return array();
    }

    /**
     * Liefert die vorhergehenden Seite
     *
     * @return QUI\Projects\Site
     * @throws QUI\Exception
     */
    public function previousSibling()
    {
        throw new QUI\Exception('Die Seite besitzt keine vorhergehenden Seite');
    }

    /**
     * Die x vorhergehenden Geschwister
     *
     * @param integer $no
     *
     * @return array
     */
    public function previousSiblings($no)
    {
        return array();
    }

    /**
     * Gibt das erste Kind der Seite zurück
     *
     * @param array $params
     *
     * @return QUI\Projects\Site | false
     */
    public function firstChild($params = array())
    {
        return false;
    }

    /**
     * Gibt die Kinder zurück achtet aber auf "Nicht in Navigation anzeigen" und Rechte
     *
     * @param array $params
     *
     * @return array
     */
    public function getNavigation($params = array())
    {
        return array();
    }

    /**
     * Gibt ein Kind zurück welches den Namen hat
     *
     * @param string $name
     *
     * @return integer
     * @throws QUI\Exception
     */
    public function getChildIdByName($name)
    {
        throw new QUI\Exception(
            'No Child found with name ' . $name,
            705
        );
    }

    /**
     * Return a children by id
     *
     * @param integer $id
     *
     * @return QUI\Projects\Site
     * @throws QUI\Exception
     */
    public function getChild($id)
    {
        throw new QUI\Exception('Child not found', 705);
    }

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
    public function getChildrenIds($params = array())
    {
        return array();
    }

    /**
     * Return ALL children ids under the site
     *
     * @param array $params - db parameter
     *
     * @return array
     */
    public function getChildrenIdsRecursive($params = array())
    {
        return array();
    }

    /**
     * Gibt zurück ob Site Kinder besitzt
     *
     * @param boolean $navhide - if navhide == false, navhide must be 0
     *
     * @return integer - Anzahl der Kinder
     */
    public function hasChildren($navhide = false)
    {
        return 0;
    }

    /**
     * Setzt das delete Flag
     *
     * @todo move to Site/Edit
     */
    public function delete()
    {
        return false;
    }

    /**
     * Gibt die URL der Seite zurück
     *
     * @param $params
     * @param $rewrited
     *
     * @return string
     */
    public function getUrl($params = array(), $rewrited = false)
    {
        return $this->getAttribute('url');
    }

    /**
     * Gibt eine sprechenden URL zurück
     * DB Abfragen werden gemacht - Hier auf Performance achten
     *
     * @param array $params - Parameter welche an die URL angehängt werden
     *
     * @return string
     */
    public function getUrlRewrited($params = array())
    {
        return $this->getAttribute('url');
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function getUrlRewritten($params = array())
    {
        return $this->getAttribute('url');
    }

    /**
     * Return the Parent id from the site object
     *
     * @return integer
     */
    public function getParentId()
    {
        if (!$this->Parent) {
            return 1;
        }

        return $this->Parent->getId();
    }

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
    public function getParentIds()
    {
        $parents   = $this->getParent()->getParentIds();
        $parents[] = $this->getParent()->getId();

        return $parents;
    }

    /**
     * Return the Parent ID List
     *
     * @return array
     */
    public function getParentIdTree()
    {
        return array();
    }

    /**
     * Gibt das Parent Objekt zurück
     *
     * @return QUI\Projects\Site
     */
    public function getParent()
    {
        if (!$this->Parent) {
            return $this->Project->firstChild();
        }

        return $this->Parent;
    }

    /**
     * Gibt alle rekursive Parents als Objekte zurück
     * Site->Parent->ParentParent->ParentParentParent
     *
     * @return array
     */
    public function getParents()
    {
        $parents   = $this->getParent()->getParents();
        $parents[] = $this->getParent();

        return $parents;
    }

    /**
     * Stellt die Seite wieder her
     *
     * ??? wieso hier? und nicht im trash? O.o
     */
    public function restore()
    {
    }

    /**
     * Zerstört die Seite
     * Die Seite wird komplett aus der DB gelöscht und auch alle Beziehungen
     * Funktioniert nur wenn die Seite gelöscht ist
     */
    public function destroy()
    {
    }

    /**
     * Canonical URL - Um doppelte Inhalt zu vermeiden
     *
     * @return string
     */
    public function getCanonical()
    {
        return $this->getAttribute('url');
    }

    /**
     * Löscht den Seitencache
     */
    public function deleteCache()
    {
    }

    /**
     * Löscht den Seitencache
     */
    public function createCache()
    {
    }

    /**
     * Shortcut for QUI\Permissions\Permission::hasSitePermission
     *
     * @param string $permission - name of the permission
     * @param QUI\Users\User|boolean $User - optional
     *
     * @return boolean|integer
     */
    public function hasPermission($permission, $User = false)
    {
        return true;
    }

    /**
     * Shortcut for QUI\Permissions\Permission::checkSitePermission
     *
     * @param string $permission - name of the permission
     * @param QUI\Users\User|boolean $User - optional
     *
     * @throws QUI\Exception
     */
    public function checkPermission($permission, $User = false)
    {

    }
}
