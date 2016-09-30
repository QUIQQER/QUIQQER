<?php

/**
 * This file contains the QUI\Projects\Site\Edit
 */

namespace QUI\Projects\Site;

use QUI;
use QUI\Projects\Site;
use QUI\Projects\Project;
use QUI\Permissions\Permission;
use QUI\Users\User;
use QUI\Groups\Group;
use QUI\Utils\Security\Orthos;

/**
 * Site Objekt für den Adminbereich
 * Stellt Methoden für das Bearbeiten von einer Seite zur Verfügung
 *
 * @author     www.pcsg.de (Henning Leutz)
 * @licence    For copyright and license information, please view the /README.md
 *
 * @errorcodes 7XX = Site Errors
 * <ul>
 * <li>700 - Error Site: Bad Request; Aufruf ist falsch</li>
 * <li>701 - Error Site Name: 2 signs or lower</li>
 * <li>702 - Error Site Name: Not supported signs in Name</li>
 * <li>703 - Error Site Name: Duplicate Entry in Parent; Child width the same Name exist</li>
 * <li>704 - Error Site Name: 200 signs or higher</li>
 * <li>705 - Error Site  : Site not found</li>
 * </ul>
 *
 * @todo       Sortierung als eigene Methoden
 * @todo       Rechte Prüfung
 * @todo       translation der quelltext doku
 *
 * @qui-event  onSiteActivate [ \QUI\Projects\Site\Edit ]
 * @qui-event  onSiteDeactivate [ \QUI\Projects\Site\Edit ]
 * @qui-event  onSiteSave [ \QUI\Projects\Site\Edit ]
 *
 * @event      onSiteCreateChild [ integer $newId, \QUI\Projects\Site\Edit ]
 * @event      onActivate [ \QUI\Projects\Site\Edit ]
 * @event      onDeactivate [ \QUI\Projects\Site\Edit ]
 * @event      onSave [ \QUI\Projects\Site\Edit ]
 */
class Edit extends Site
{
    /**
     *
     */
    const ESAVE = 3;

    /**
     *
     */
    const EACCES = 4;

    /**
     * Project conf <<------ ??? why here
     *
     * @var array
     */
    public $conf = array();

    /**
     * lock file path
     * @var array|string
     */
    protected $lockfile = array();

    /**
     * Konstruktor
     *
     * @param QUI\Projects\Project $Project
     * @param integer $id
     */
    public function __construct(Project $Project, $id)
    {
        parent::__construct($Project, $id);

        $this->refresh();

        $id = $this->getId();

        $this->lockfile = VAR_DIR . 'lock/' .
                          $Project->getAttribute('name') . '_' .
                          $id . '_' . $Project->getAttribute('lang');

        // Temp Dir abfragen ob existiert
        QUI\Utils\System\File::mkdir(VAR_DIR . 'admin/');
        QUI\Utils\System\File::mkdir(VAR_DIR . 'lock/');

        $this->load();

        // onInit event
        $this->Events->fireEvent('init', array($this));
        QUI::getEvents()->fireEvent('siteInit', array($this));
    }

    /**
     * Hohlt frisch die Daten aus der DB
     */
    public function refresh()
    {
        $result = QUI::getDataBase()->fetch(array(
            'from'  => $this->TABLE,
            'where' => array(
                'id' => $this->getId()
            ),
            'limit' => '1'
        ));

        // Verknüpfung hohlen
        if ($this->getId() != 1) {
            $relresult = QUI::getDataBase()->fetch(array(
                'from'  => $this->RELTABLE,
                'where' => array(
                    'child' => $this->getId()
                )
            ));

            if (isset($relresult[0])) {
                foreach ($relresult as $entry) {
                    if (!isset($entry['oparent'])) {
                        continue;
                    }

                    $this->LINKED_PARENT = $entry['oparent'];
                }
            }
        }

        if (!isset($result[0])) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/system', 'exception.site.not.found'),
                705,
                array(
                    'siteId'  => $this->getId(),
                    'project' => $this->getProject()->getName(),
                    'lang'    => $this->getProject()->getLang()
                )
            );
        }

        foreach ($result[0] as $a_key => $a_val) {
            // Extra-Feld behandeln
            if ($a_key == 'extra') {
                if (empty($a_val)) {
                    continue;
                }

                // @todo get extra attribute list

                $extra = json_decode($a_val, true);

                foreach ($extra as $key => $value) {
                    $this->setAttribute($key, $value);
                }

                continue;
            }

            $this->setAttribute($a_key, $a_val);
        }
    }

    /**
     * Check if the release from date is in the future or the release until is in the past
     * throws exception if the site cant activated
     *
     * @throws QUI\Exception
     */
    protected function checkReleaseDate()
    {
        // check release date
        $release_from = $this->getAttribute('release_from');
        $release_to   = $this->getAttribute('release_to');

        if (!$release_from || $release_from == '0000-00-00 00:00:00') {
            $release_from = date('Y-m-d H:i:s');
            $this->setAttribute('release_from', $release_from);
        }

        $release_from = strtotime($release_from);

        if ($release_from && $release_from > time()) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.release.from.inFuture'
                )
            );
        }

        if (!$release_to || $release_to == '0000-00-00 00:00:00') {
            return;
        }

        $release_to = strtotime($release_to);

        if ($release_to && $release_to < time()) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.release.to.inPast'
                )
            );
        }
    }

    /**
     * Activate a site
     *
     * @param QUI\Interfaces\Users\User|boolean $User - [optional] User to save
     *
     * @throws QUI\Exception
     */
    public function activate($User = false)
    {
        try {
            $this->checkPermission('quiqqer.projects.site.edit', $User);
        } catch (QUI\Exception $Exception) {
            throw new QUI\Exception(
                QUI::getLocale()
                    ->get('quiqqer/system', 'exception.permissions.edit')
            );
        }

        $this->Events->fireEvent('checkActivate', array($this));
        QUI::getEvents()->fireEvent('siteCheckActivate', array($this));


        // check release date
        $this->checkReleaseDate();

        $releaseFrom = $this->getAttribute('release_from');

        if (!Orthos::checkMySqlDatetimeSyntax($releaseFrom)) {
            $releaseFrom = date('Y-m-d H:i:s');
        }

        // save
        QUI::getDataBase()->update($this->TABLE, array(
            'active'       => 1,
            'release_from' => $releaseFrom
        ), array(
            'id' => $this->getId()
        ));

        $this->setAttribute('active', 1);

        $this->Events->fireEvent('activate', array($this));
        QUI::getEvents()->fireEvent('siteActivate', array($this));

        $this->deleteCache();
        $this->getProject()->clearCache();
    }

    /**
     * Deactivate a site
     *
     * @param QUI\Interfaces\Users\User|boolean $User - [optional] User to save
     *
     * @throws QUI\Exception
     */
    public function deactivate($User = false)
    {
        try {
            // Prüfen ob der Benutzer die Seite bearbeiten darf
            $this->checkPermission('quiqqer.projects.site.edit', $User);
        } catch (QUI\Exception $Exception) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.permissions.edit'
                )
            );
        }

        QUI::getEvents()->fireEvent('siteCheckDeactivate', array($this));

        // deactivate
        QUI::getDataBase()->exec(array(
            'update' => $this->TABLE,
            'set'    => array(
                'active' => 0
            ),
            'where'  => array(
                'id' => $this->getId()
            )
        ));

        $this->setAttribute('active', 0);
        $this->getProject()->clearCache();

        //$this->deleteTemp();
        $this->deleteCache();


        $this->Events->fireEvent('deactivate', array($this));
        QUI::getEvents()->fireEvent('siteDeactivate', array($this));
    }

    /**
     * Zerstört die Seite
     * Die Seite wird komplett aus der DB gelöscht und auch alle Beziehungen
     * Funktioniert nur wenn die Seite gelöscht ist
     */
    public function destroy()
    {
        if ($this->getAttribute('deleted') != 1) {
            return;
        }

        QUI::getRewrite()->unregisterPath($this);

        /**
         * package destroy
         */
        $dataList = Utils::getDataListForSite($this);

        foreach ($dataList as $dataEntry) {
            $table = $dataEntry['table'];

            QUI::getDataBase()->delete($table, array(
                'id' => $this->getId()
            ));
        }


        // on destroy event
        try {
            $this->Events->fireEvent('destroy', array($this));
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        try {
            QUI::getEvents()->fireEvent('siteDestroy', array($this));
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }


        /**
         * Site destroy
         */

        // Daten löschen
        QUI::getDataBase()->delete($this->TABLE, array(
            'id' => $this->getId()
        ));

        // sich als Kind löschen
        QUI::getDataBase()->delete($this->RELTABLE, array(
            'child' => $this->getId()
        ));

        // sich als parent löschen
        QUI::getDataBase()->delete($this->RELTABLE, array(
            'parent' => $this->getId()
        ));

        // Rechte löschen
        $Manager = QUI::getPermissionManager();
        $Manager->removeSitePermissions($this);

        // Cache löschen
        $this->deleteCache();
    }

    /**
     * Saves the site
     *
     * @param QUI\Interfaces\Users\User|boolean $SaveUser - [optional] User to save
     *
     * @return boolean
     * @throws QUI\Exception
     */
    public function save($SaveUser = false)
    {
        try {
            // Prüfen ob der Benutzer die Seite bearbeiten darf
            $this->checkPermission('quiqqer.projects.site.edit', $SaveUser);
        } catch (QUI\Exception $Exception) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.permissions.edit'
                )
            );
        }

        $mid = $this->isLockedFromOther();

        if ($mid) {
            try {
                $User = QUI::getUsers()->get((int)$mid);
            } catch (QUI\Exception $Exception) {
            }

            if (isset($User)) {
                throw new QUI\Exception(
                    QUI::getLocale()->get(
                        'quiqqer/system',
                        'exception.site.is.being.edited.user',
                        array(
                            'username' => $User->getName()
                        )
                    ),
                    703
                );
            }

            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.site.is.being.edited'
                ),
                703
            );
        }

        // check the name, unallowed signs?
        $name = $this->getAttribute('name');

        QUI\Projects\Site\Utils::checkName($name);


        /* @var $Project QUI\Projects\Project */
        $Project = $this->getProject();

        // check if a name in the same level exists
        // observed linked sites
        if ($this->getId() != 1) {
            $parent_ids = $this->getParentIds();

            foreach ($parent_ids as $pid) {
                $Parent = new QUI\Projects\Site\Edit($Project, $pid);

                if ($Parent->existNameInChildren($name) > 1) {
                    throw new QUI\Exception(
                        QUI::getLocale()->get(
                            'quiqqer/system',
                            'exception.site.same.name',
                            array(
                                'id'   => $pid,
                                'name' => $name
                            )
                        ),
                        703
                    );
                }
            }
        }

        // order type
        $order_type = 'manuell';

        switch ($this->getAttribute('order_type')) {
            case 'manuell':
            case 'name ASC':
            case 'name DESC':
            case 'title ASC':
            case 'title DESC':
            case 'c_date ASC':
            case 'c_date DESC':
            case 'd_date ASC':
            case 'd_date DESC':
            case 'release_from ASC':
            case 'release_from DESC':
                $order_type = $this->getAttribute('order_type');
                break;
        }

        // release dates
        $release_from = '';
        $release_to   = '';

        if ($this->getAttribute('release_from')
            && $this->getAttribute('release_from') != '0000-00-00 00:00:00'
        ) {
            $rf = strtotime($this->getAttribute('release_from'));

            if ($rf) {
                $release_from = date('Y-m-d H:i:s', $rf);
            }
        } else {
            if ($this->getAttribute('active')) {
                // nur bei aktiven seiten das e_date setzen
                // wenn der cron läuft, darf eine inaktive seite nicht sofort aktiviert werden
                // daher werden nur aktive seite beachten
                $release_from = date(
                    'Y-m-d H:i:s',
                    strtotime($this->getAttribute('e_date'))
                );
            }
        }

        if ($this->getAttribute('release_to')) {
            $rt = strtotime($this->getAttribute('release_to'));

            if ($rt && $rt > 0) {
                $release_to = date('Y-m-d H:i:s', $rt);
            } else {
                $release_to = '';
            }
        }

        $this->setAttribute('release_from', $release_from);
        $this->setAttribute('release_to', $release_to);


        try {
            $this->checkReleaseDate();
        } catch (QUI\Exception $Exception) {
            // if release date trigger an error, deactivate the site
            $this->deactivate($SaveUser);
        }

        // on save before event
        try {
            $this->Events->fireEvent('saveBefore', array($this));
            QUI::getEvents()->fireEvent('siteSaveBefore', array($this));
        } catch (QUI\ExceptionStack $Exception) {
            $list = $Exception->getExceptionList();

            foreach ($list as $Exc) {
                /* @var $Exc \Exception */
                QUI\System\Log::addWarning($Exc->getMessage());
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addWarning($Exception->getMessage());
        }

        // save extra package attributes (site.xml)
        $oldType = $this->getAttribute('type');

        $extraAttributes = Utils::getExtraAttributeListForSite($this);
        $siteExtra       = array();

        foreach ($extraAttributes as $data) {
            $attribute = $data['attribute'];
            $default   = $data['default'];

            if ($this->existsAttribute($attribute)) {
                $siteExtra[$attribute] = $this->getAttribute($attribute);
                continue;
            }

            $siteExtra[$attribute] = $default;
        }


        // clear paths
        // wenn sich der seitentyp geändert hat, muss ein clear path gemacht werden
        // somit bleiben keine alten register paths bestehen
        if ($oldType != $this->getAttribute('type')) {
            QUI::getRewrite()->unregisterPath($this);
        }

        // save main data
        $update = QUI::getDataBase()->update(
            $this->TABLE,
            array(
                'name'          => trim($this->getAttribute('name')),
                'title'         => trim($this->getAttribute('title')),
                'short'         => $this->getAttribute('short'),
                'content'       => $this->getAttribute('content'),
                'type'          => $this->getAttribute('type'),
                'layout'        => $this->getAttribute('layout'),
                'nav_hide'      => $this->getAttribute('nav_hide') ? 1 : 0,
                'e_user'        => QUI::getUserBySession()->getId(),
                // ORDER
                'order_type'    => $order_type,
                'order_field'   => $this->getAttribute('order_field'),
                // images
                'image_emotion' => $this->getAttribute('image_emotion'),
                'image_site'    => $this->getAttribute('image_site'),
                // release
                'release_from'  => $release_from,
                'release_to'    => $release_to,
                // Extra-Feld
                'extra'         => json_encode($siteExtra)
            ),
            array(
                'id' => $this->getId()
            )
        );

        // save package automatic site data (database.xml)
        $dataList = Utils::getDataListForSite($this);

        foreach ($dataList as $dataEntry) {
            $data = array();

            $table     = $dataEntry['table'];
            $fieldList = $dataEntry['data'];
            $package   = $dataEntry['package'];
            $suffix    = $dataEntry['suffix'];

            $attributeSuffix = $package . '.' . $suffix . '.';
            $attributeSuffix = str_replace('/', '.', $attributeSuffix);

            foreach ($fieldList as $siteAttribute) {
                $data[$siteAttribute] = $this->getAttribute(
                    $attributeSuffix . $siteAttribute
                );
            }

            $result = QUI::getDataBase()->fetch(array(
                'from'  => $table,
                'where' => array(
                    'id' => $this->getId()
                ),
                'limit' => 1
            ));

            if (!isset($result[0])) {
                QUI::getDataBase()->insert($table, array(
                    'id' => $this->getId()
                ));
            }

            QUI::getDataBase()->update($table, $data, array(
                'id' => $this->getId()
            ));
        }

        //$this->deleteTemp($User);
        $Project->clearCache();

        // Cache löschen
        $this->deleteCache();

        // Objektcache anlegen
        $this->refresh();
        $this->createCache();

        // Letztes Speichern
        $Project->setEditDate(time());

        // on save event
        try {
            $this->Events->fireEvent('save', array($this));
            QUI::getEvents()->fireEvent('siteSave', array($this));
        } catch (QUI\ExceptionStack $Exception) {
            $list = $Exception->getExceptionList();

            foreach ($list as $Exc) {
                /* @var $Exc \Exception */
                QUI\System\Log::addWarning($Exc->getMessage());
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::addWarning($Exception->getMessage());
        }


        if ($update) {
            QUI::getMessagesHandler()->addSuccess(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'message.site.save.success',
                    array(
                        'id'    => $this->getId(),
                        'title' => $this->getAttribute('title'),
                        'name'  => $this->getAttribute('name')
                    )
                )
            );

            return true;
        }

        throw new QUI\Exception(
            QUI::getLocale()->get(
                'quiqqer/system',
                'exception.site.save.error'
            ),
            self::ESAVE
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @see Site::getChildrenIdsFromParentId()
     *
     * @param integer $pid - Parent - ID
     * @param array $params
     *
     * @return array
     */
    public function getChildrenIdsFromParentId($pid, $params = array())
    {
        $where_1 = array(
            $this->RELTABLE . '.parent' => (int)$pid,
            $this->TABLE . '.deleted'   => 0,
            $this->RELTABLE . '.child'  => '`' . $this->TABLE . '.id`'
        );

        if (isset($params['where']) && is_array($params['where'])) {
            $where = array_merge($where_1, $params['where']);
        } elseif (isset($params['where']) && is_string($params['where'])) {
            // @todo where als param string
            QUI\System\Log::addDebug('WIRD NICHT verwendet' . $params['where']);
            $where = $where_1;
        } else {
            $where = $where_1;
        }

        $order = $this->TABLE . '.order_field';

        if (isset($params['order'])) {
            if (strpos($params['order'], '.') !== false) {
                $order = $this->TABLE . '.' . $params['order'];
            } else {
                $order = $params['order'];
            }
        }

        $result = QUI::getDataBase()->fetch(array(
            'select' => $this->TABLE . '.id',
            'count'  => isset($params['count']) ? 'count' : false,
            'from'   => array(
                $this->RELTABLE,
                $this->TABLE
            ),
            'order'  => $order,
            'limit'  => isset($params['limit']) ? $params['limit'] : false,
            'where'  => $where
        ));

        return $result;
    }

    /**
     * Checks if a site with the name in the children exists
     *
     * @param string $name
     *
     * @return boolean
     */
    public function existNameInChildren($name)
    {
        $query = "
            SELECT COUNT({$this->TABLE}.id) AS count
            FROM `{$this->RELTABLE}`,`{$this->TABLE}`
            WHERE `{$this->RELTABLE}`.`parent` = {$this->getId()} AND
                  `{$this->RELTABLE}`.`child` = `{$this->TABLE}`.`id` AND
                  `{$this->TABLE}`.`name` = :name AND
                  `{$this->TABLE}`.`deleted` = 0
        ";

        $PDO   = QUI::getDataBase()->getPDO();
        $Stmnt = $PDO->prepare($query);

        $Stmnt->bindValue(':name', $name, \PDO::PARAM_STR);
        $Stmnt->execute();

        $result = $Stmnt->fetchAll(\PDO::FETCH_ASSOC);

        if (!isset($result[0])) {
            return false;
        }

        return (int)$result[0]['count'] ? (int)$result[0]['count'] : false;
    }

    /**
     * Return the children
     *
     * @param array $params Parameter für die Childrenausgabe
     *                        $params['where']
     *                        $params['limit']
     * @param boolean $recursiv Rekursiv alle Kinder IDs bekommen
     *
     * @return array;
     */
    public function getChildren($params = array(), $recursiv = false)
    {
        if (!isset($params['order'])) {
            // Falls kein order übergeben wird, wird das eingestellte Site order
            switch ($this->getAttribute('order_type')) {
                case 'name ASC':
                case 'name DESC':
                case 'title ASC':
                case 'title DESC':
                case 'c_date ASC':
                case 'c_date DESC':
                case 'd_date ASC':
                case 'd_date DESC':
                case 'release_from ASC':
                case 'release_from DESC':
                    $params['order'] = $this->getAttribute('order_type');
                    break;

                case 'manuell':
                default:
                    $params['order'] = 'order_field';
                    break;
            }
        }

        $Project = $this->getProject();

        // if active = '0&1', project -> getchildren returns all children
        if (!isset($params['active'])) {
            $params['active'] = '0&1';
        }

        $children = array();
        $result   = $this->getChildrenIds($params);

        if (isset($result[0])) {
            foreach ($result as $id) {
                $child      = new Edit($Project, (int)$id);
                $children[] = $child;
            }
        }

        return $children;
    }

    /**
     * Fügt eine Verknüpfung zu einer anderen Sprache ein
     *
     * @param string $lang - Sprache zu welcher verknüpft werden soll
     * @param string $id - ID zu welcher verknüpft werden soll
     *
     * @return \PDOStatement
     */
    public function addLanguageLink($lang, $id)
    {
        $this->checkPermission('quiqqer.projects.site.edit');

        $Project = $this->getProject();
        $p_lang  = $Project->getAttribute('lang');

        $id = (int)$id;

        $result = QUI::getDataBase()->fetch(array(
            'from'  => $this->RELLANGTABLE,
            'where' => array(
                $p_lang => $this->getId()
            ),
            'limit' => '1'
        ));

        if (isset($result[0])) {
            return QUI::getDataBase()->exec(array(
                'update' => $this->RELLANGTABLE,
                'set'    => array(
                    $lang => $id
                ),
                'where'  => array(
                    $p_lang => $this->getId()
                )
            ));
        }

        return QUI::getDataBase()->exec(array(
            'insert' => $this->RELLANGTABLE,
            'set'    => array(
                $p_lang => $this->getId(),
                $lang   => $id
            )
        ));
    }

    /**
     * Entfernt eine Verknüpfung zu einer Sprache
     *
     * @param string $lang
     *
     * @return \PDOStatement
     */
    public function removeLanguageLink($lang)
    {
        $this->checkPermission('quiqqer.projects.site.edit');

        $Project = $this->getProject();

        return QUI::getDataBase()->exec(array(
            'update' => $this->RELLANGTABLE,
            'set'    => array(
                $lang => 0
            ),
            'where'  => array(
                $Project->getAttribute('lang') => $this->getId()
            )
        ));
    }

    /**
     * Erstellt ein neues Kind
     *
     * @param array $params
     * @param array $childPermissions - [optional] permissions for the child
     * @param boolean|QUI\Users\User|QUI\Users\SystemUser $User - [optional] the user which create the site, optional
     *
     * @return Int
     * @throws QUI\Exception
     */
    public function createChild(
        $params = array(),
        $childPermissions = array(),
        $User = false
    ) {
        if ($User == false) {
            $User = QUI::getUserBySession();
        }

        $this->checkPermission('quiqqer.projects.site.new', $User);


        //$newid    = $Project->getNewId();
        $new_name = 'Neue Seite';   // @todo multilingual
        $old      = $new_name;

        // Namen vergeben falls existiert
        $i = 1;

        if (!isset($params['name']) || empty($params['name'])) {
            while ($this->existNameInChildren($new_name)) {
                $new_name = $old . ' (' . $i . ')';
                $i++;
            }
        } else {
            $new_name = $params['name'];
        }

        if ($this->existNameInChildren($new_name)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.site.same.name',
                    array(
                        'name' => $new_name
                    )
                )
            );
        }

        // can we use this name?
        QUI\Projects\Site\Utils::checkName($new_name);


        $childCount = $this->hasChildren(true);

        $_params = array(
            'name'        => $new_name,
            'title'       => $new_name,
            'c_date'      => date('Y-m-d H:i:s'),
            'e_user'      => $User->getId(),
            'c_user'      => $User->getId(),
            'c_user_ip'   => QUI\Utils\System::getClientIP(),
            'order_field' => $childCount + 1
        );

        if (isset($params['title'])) {
            $_params['title'] = $params['title'];
        }

        if (isset($params['short'])) {
            $_params['short'] = $params['short'];
        }

        if (isset($params['content'])) {
            $_params['content'] = $params['content'];
        }

        $DataBase = QUI::getDataBase();
        $DataBase->insert($this->TABLE, $_params);

        $newId = $DataBase->getPDO()->lastInsertId();

        $DataBase->insert($this->RELTABLE, array(
            'parent' => $this->getId(),
            'child'  => $newId
        ));

        // copy permissions to the child
        $PermManager    = QUI::getPermissionManager();
        $permissions    = $PermManager->getSitePermissions($this);
        $newPermissions = array();

        // parent permissions
        foreach ($permissions as $permission => $value) {
            if (empty($value)) {
                continue;
            }

            $newPermissions[$permission] = $value;
        }

        // optional new permission
        foreach ($childPermissions as $permission => $value) {
            if (empty($value)) {
                continue;
            }

            $newPermissions[$permission] = $value;
        }

        if (!empty($newPermissions)) {
            $Child = new Edit($this->getProject(), $newId);

            $PermManager->setSitePermissions(
                $Child,
                $newPermissions,
                QUI::getUsers()->getSystemUser()
            );
        }

        // Aufruf der createChild Methode im TempSite - für den Adminbereich
        $this->Events->fireEvent('createChild', array($newId, $this));
        QUI::getEvents()->fireEvent('siteCreateChild', array($newId, $this));


        return $newId;
    }

    /**
     * Move the site to another parent
     *
     * @param integer $pid - Parent ID
     *
     * @return boolean
     */
    public function move($pid)
    {
        $this->checkPermission('quiqqer.projects.site.edit');

        $Project = $this->getProject();
        $Parent  = $Project->get((int)$pid);// Prüfen ob das Parent existiert

        $children = $this->getChildrenIds();

        if (!in_array($pid, $children) && $pid != $this->getId()) {
            QUI::getDataBase()->update(
                $this->RELTABLE,
                array('parent' => $Parent->getId()),
                'child = ' . $this->getId() . ' AND oparent IS NULL'
            );

            //$this->deleteTemp();
            $this->deleteCache();

            // remove internal parent ids
            $this->parents_id = false;
            $this->parent_id  = false;


            $this->Events->fireEvent('move', array($this, $pid));
            QUI::getEvents()->fireEvent('siteMove', array($this, $pid));

            return true;
        }

        return false;
    }

    /**
     * Kopiert die Seite
     *
     * @param integer $pid - ID des Parents unter welches die Kopie eingehängt werden soll
     * @param \QUI\Projects\Project|boolean $Project - (optional) Parent Project
     *
     * @return QUI\Projects\Site\Edit
     * @throws QUI\Exception
     *
     * @todo Rekursiv kopieren
     */
    public function copy($pid, $Project = false)
    {
        // Edit Rechte prüfen
        $this->checkPermission('quiqqer.projects.site.edit');

        if (!$Project) {
            $Project = $this->getProject();
        }

        if (get_class($Project) != Project::class) {
            throw new QUI\Exception(
                'Site copy: Project not found',
                404
            );
        }

        $Parent    = new QUI\Projects\Site\Edit($Project, (int)$pid);
        $attribues = $this->getAttributes();
        $name      = $this->getAttribute('name');
        $title     = $this->getAttribute('title');

        // Prüfen ob es eine Seite mit dem gleichen Namen im Parent schon gibt
        if ($Parent->existNameInChildren($name)) {
            $newName = QUI::getLocale()->get(
                'quiqqer/system',
                'projects.project.site.copy.text',
                array('name' => $name)
            );

            $newTitle = QUI::getLocale()->get(
                'quiqqer/system',
                'projects.project.site.copy.text',
                array('name' => $title)
            );

            // kind gefunden, wir brauchen ein neuen namen
            $i = 1;

            while ($Parent->existNameInChildren($newName)) {
                $newName = QUI::getLocale()->get(
                    'quiqqer/system',
                    'projects.project.site.copy.text.count',
                    array(
                        'name'  => $name,
                        'count' => $i
                    )
                );

                $newTitle = QUI::getLocale()->get(
                    'quiqqer/system',
                    'projects.project.site.copy.text.count',
                    array(
                        'name'  => $title,
                        'count' => $i
                    )
                );

                $i++;
            }

            $name  = $newName;
            $title = $newTitle;
        }

        // kopiervorgang beginnen
        $PermManager = QUI::getPermissionManager();
        $permissions = $PermManager->getSitePermissions($this);

        $site_id = $Parent->createChild(
            array(
                'name'  => $name,
                'title' => $title
            ),
            $permissions
        );

        // Erstmal Seitentyp setzn
        $Site = new QUI\Projects\Site\Edit($Project, (int)$site_id);
        $Site->setAttribute('type', $this->getAttribute('type'));
        $Site->setAttribute('title', $title);
        $Site->save();

        // Alle Attribute setzen
        $Site = new QUI\Projects\Site\Edit($Project, (int)$site_id);

        foreach ($attribues as $key => $value) {
            if ($key == 'name' || $key == 'title' || $key == 'type') {
                continue;
            }
            $Site->setAttribute($key, $value);
        }

        $Site->save();

        return $Site;
    }

    /**
     * Erstellt eine Verknüpfung
     *
     * @param integer $pid
     *
     * @throws QUI\Exception
     */
    public function linked($pid)
    {
        $Project = $this->getProject();
        $Parent  = $this->getParent();

        $table = $Project->getAttribute('name') . '_' .
                 $Project->getAttribute('lang') . '_sites_relations';

        // Prüfen ob die Seite schon in dem Parent ist
        // #locale
        if ($Parent->getId() == $pid) {
            throw new QUI\Exception(
                'Es kann keine Verknüpfung in dieser Ebene erstellt werden,
                da eine Verknüpfung oder die original Seite bereits in dieser Ebene existiert',
                703
            );
        }

        $links = QUI::getDataBase()->fetch(array(
            'from'  => $table,
            'where' => array(
                'child' => $this->getId()
            )
        ));

        // #locale
        foreach ($links as $entry) {
            if ($entry['parent'] == $pid) {
                throw new QUI\Exception(
                    'Es kann keine Verknüpfung in dieser Ebene erstellt werden,
                    da eine Verknüpfung oder die original Seite bereits in dieser Ebene existiert',
                    703
                );
            }
        }

        QUI::getDataBase()->insert($table, array(
            'parent'  => $pid,
            'child'   => $this->getId(),
            'oparent' => $Parent->getId()
        ));
    }

    /**
     * Delete all linked sites
     *
     * @param integer $pid - Parent ID
     * @param integer|boolean $all - (optional) Delete all linked sites and the original site
     * @param boolean $orig - (optional) Delete the original site, too
     */
    public function deleteLinked($pid, $all = false, $orig = false)
    {
        $this->checkPermission('quiqqer.projects.site.edit');

        $Project  = $this->getProject();
        $Parent   = $this->getParent();
        $DataBase = QUI::getDataBase();

        $table = $Project->getAttribute('name') . '_' .
                 $Project->getAttribute('lang') . '_sites_relations';

        if (QUI\Utils\BoolHelper::JSBool($all)) {
            // Seite löschen
            $this->delete();

            // Alle Verknüpfungen
            $DataBase->delete($table, array(
                'child'  => $this->getId(),
                'parent' => array(
                    'value' => $Parent->getId(),
                    'type'  => 'NOT'
                )
            ));
            return;
        }

        // Einzelne Verknüpfung löschen
        if ($pid && $orig == false) {
            $DataBase->delete($table, array(
                'child'  => $this->getId(),
                'parent' => (int)$pid
            ));
            return;
        }

        $DataBase->delete($table, array(
            'child'   => $this->getId(),
            'parent'  => (int)$pid,
            'oparent' => (int)$orig
        ));
    }

    /**
     * Löscht den Site Cache
     *
     * @todo -> use internal caching system
     */
    public function deleteCache()
    {
        // Seiten Cache löschen
        parent::deleteCache();

        // Link Cache löschen
        $Project = $this->getProject();

        $link_cache_dir = VAR_DIR . 'cache/links/' . $Project->getAttribute('name') . '/';

        $link_cache_file = $link_cache_dir . $this->getId() . '_' . $Project->getAttribute('name')
                           . '_' . $Project->getAttribute('lang');

        if (file_exists($link_cache_file)) {
            unlink($link_cache_file);
        }
    }

    /**
     * Erstellt den Site Cache
     *
     * @todo -> use internal caching system
     */
    public function createCache()
    {
        // Objekt Cache
        parent::createCache();


        // Link Cache
        $Project = $this->getProject();

        $link_cache_dir = VAR_DIR . 'cache/links/' .
                          $Project->getAttribute('name') . '/';

        $link_cache_file = $link_cache_dir . $this->getId() . '_' .
                           $Project->getAttribute('name') . '_' .
                           $Project->getAttribute('lang');

        QUI\Utils\System\File::mkdir($link_cache_dir);

        file_put_contents($link_cache_file, $this->getLocation());
    }

    /**
     * is the page currently edited from another user than me?
     *
     * @todo muss überarbeitet werden, file operationen?
     *
     * @return bool|integer
     */
    public function isLockedFromOther()
    {
        $uid = $this->isLocked();

        if ($uid === false) {
            return false;
        }

        if (QUI::getUserBySession()->getId() == $uid) {
            return false;
        }

        $time          = time() - filemtime($this->lockfile);
        $max_life_time = QUI::conf('session', 'max_life_time');

        if ($time > $max_life_time) {
            $this->unlock();

            return false;
        }

        return (int)$uid;
    }

    /**
     * is the page currently edited
     *
     * @todo muss überarbeitet werden, file operationen?
     *
     * @return bool|string
     */
    public function isLocked()
    {
        if (!file_exists($this->lockfile)) {
            return false;
        }

        return file_get_contents($this->lockfile);
    }

    /**
     * Markiert die Seite -> die Seite wird gerade bearbeitet
     * Markiert nur wenn die Seite nicht markiert ist
     *
     * @todo muss überarbeitet werden, file operationen?
     *
     * @return boolean - true if it worked, false if it not worked
     */
    public function lock()
    {
        if ($this->isLockedFromOther()) {
            return false;
        }

        if ($this->isLocked()) {
            return true;
        }

        try {
            $this->checkPermission('quiqqer.projects.site.edit');
        } catch (QUI\Exception $Exception) {
            return false;
        }

        file_put_contents($this->lockfile, QUI::getUserBySession()->getId());

        return true;
    }

    /**
     * Demarkiert die Seite, die Seite wird nicht mehr bearbeitet
     */
    protected function unlock()
    {
        if (file_exists($this->lockfile)) {
            unlink($this->lockfile);
        }
    }

    /**
     * Ein SuperUser kann eine Seite trotzdem demakieren wenn er möchte
     *
     * @todo eigenes recht dafür einführen
     */
    public function unlockWithRights()
    {
        $lock = $this->isLocked();

        if (!$lock) {
            return;
        }

        if (QUI::getUserBySession()->isSU()) {
            if (file_exists($this->lockfile)) {
                unlink($this->lockfile);
            }

            return;
        }

        if ($lock === QUI::getUserBySession()->getId()) {
            if (file_exists($this->lockfile)) {
                unlink($this->lockfile);
            }
        }
    }

    /**
     * permissions
     */

    /**
     * Add an user to the permission
     *
     * @param string $permission - name of the permission
     * @param User $User - User Object
     * @param boolean|\QUI\Users\User $EditUser
     */
    public function addUserToPermission(User $User, $permission, $EditUser = false)
    {
        Permission::addUserToSitePermission($User, $this, $permission, $EditUser);
    }

    /**
     * add an group to the permission
     *
     * @param Group $Group
     * @param string $permission
     * @param boolean|\QUI\Users\User $EditUser
     */
    public function addgroupToPermission(Group $Group, $permission, $EditUser = false)
    {
        Permission::addGroupToSitePermission($Group, $this, $permission, $EditUser);
    }

    /**
     * Remove an user from the permission
     *
     * @param string $permission - name of the permission
     * @param User $User - User Object#
     * @param boolean|\QUI\Users\User $EditUser
     */
    public function removeUserFromSitePermission(User $User, $permission, $EditUser = false)
    {
        Permission::removeUserFromSitePermission($User, $this, $permission, $EditUser);
    }

    /**
     * Remove a group from the permission
     *
     * @param Group $Group
     * @param string $permission - name of the permission
     * @param boolean|\QUI\Users\User $EditUser
     */
    public function removeGroupFromSitePermission(Group $Group, $permission, $EditUser = false)
    {
        Permission::removeGroupFromSitePermission($Group, $this, $permission, $EditUser);
    }

    /**
     * Utils
     */

    /**
     * Säubert eine URL macht sie schön
     *
     * @param string $url
     * @param QUI\Projects\Project $Project - Project clear extension
     *
     * @return string
     * @deprecated use QUI\Projects\Site\Utils::clearUrl
     */
    public static function clearUrl($url, QUI\Projects\Project $Project)
    {
        return QUI\Projects\Site\Utils::clearUrl($url, $Project);
    }

    /**
     * Prüft ob der Name erlaubt ist
     *
     * @param string $name
     *
     * @throws \QUI\Exception
     * @return boolean
     * @deprecated use \QUI\Projects\Site\Utils::checkName
     */
    public static function checkName($name)
    {
        return QUI\Projects\Site\Utils::checkName($name);
    }
}
