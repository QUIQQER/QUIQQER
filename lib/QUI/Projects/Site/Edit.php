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
use QUI\Lock\Locker;

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
    public $conf = [];

    /**
     * Constructor
     *
     * @param QUI\Projects\Project $Project
     * @param integer $id
     *
     * @throws QUI\Exception
     */
    public function __construct(Project $Project, $id)
    {
        parent::__construct($Project, $id);

        $this->refresh();

        QUI\Utils\System\File::mkdir(VAR_DIR.'admin/');
        QUI\Utils\System\File::mkdir(VAR_DIR.'lock/');

        $this->load();

        // onInit event
        $this->Events->fireEvent('init', [$this]);
        QUI::getEvents()->fireEvent('siteInit', [$this]);
    }

    /**
     * Fetch the data from the database
     *
     * @throws QUI\Exception
     * @throws QUI\Database\Exception
     */
    public function refresh()
    {
        $result = QUI::getDataBase()->fetch([
            'from'  => $this->TABLE,
            'where' => [
                'id' => $this->getId()
            ],
            'limit' => '1'
        ]);

        // Verknüpfung hohlen
        if ($this->getId() != 1) {
            $relresult = QUI::getDataBase()->fetch([
                'from'  => $this->RELTABLE,
                'where' => [
                    'child' => $this->getId()
                ]
            ]);

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
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.site.not.found'),
                705,
                [
                    'siteId'  => $this->getId(),
                    'project' => $this->getProject()->getName(),
                    'lang'    => $this->getProject()->getLang()
                ]
            );
        }

        foreach ($result[0] as $a_key => $a_val) {
            // Extra-Feld behandeln
            if ($a_key == 'extra') {
                if (empty($a_val)) {
                    continue;
                }

                // @todo get extra attribute list

                $extra = \json_decode($a_val, true);

                foreach ($extra as $key => $value) {
                    $this->setAttribute($key, $value);
                }

                continue;
            }

            // integer values
            switch ($a_key) {
                case 'active':
                case 'deleted':
                case 'id':
                case 'nav_hide':
                    $a_val = (int)$a_val;
                    break;
            }

            $this->setAttribute($a_key, $a_val);
        }
    }

    /**
     * Check if the release from date is in the future or the release until is in the past
     * throws exception if the site can't activated
     *
     * @throws QUI\Exception
     */
    protected function checkReleaseDate()
    {
        // check release date
        $release_from = $this->getAttribute('release_from');
        $release_to   = $this->getAttribute('release_to');

        if ($release_from && $release_to !== '0000-00-00 00:00:00') {
            $release_from = \strtotime($release_from);

            if ($release_from > \time()) {
                throw new QUI\Exception(
                    QUI::getLocale()->get(
                        'quiqqer/quiqqer',
                        'exception.site.release.from.inFuture'
                    ),
                    1119
                );
            }
        }

        if (!$release_to || $release_to === '0000-00-00 00:00:00') {
            return;
        }

        $release_to = \strtotime($release_to);

        if ($release_to && $release_to < \time()) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.release.to.inPast'
                ),
                1120
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
        if (!$User) {
            $User = QUI::getUserBySession();
        }

        try {
            $this->checkPermission('quiqqer.projects.site.edit', $User);
        } catch (QUI\Exception $Exception) {
            throw new QUI\Exception(
                QUI::getLocale()
                    ->get('quiqqer/quiqqer', 'exception.permissions.edit')
            );
        }

        $this->Events->fireEvent('checkActivate', [$this]);
        QUI::getEvents()->fireEvent('siteCheckActivate', [$this]);


        // check release date
        $this->checkReleaseDate();

        $releaseFrom = $this->getAttribute('release_from');

        if (!Orthos::checkMySqlDatetimeSyntax($releaseFrom)) {
            $releaseFrom = \date('Y-m-d H:i:s');
        }

        // save
        QUI::getDataBase()->update($this->TABLE, [
            'active'       => 1,
            'release_from' => $releaseFrom,
            'e_user'       => $User->getId()
        ], [
            'id' => $this->getId()
        ]);

        $this->setAttribute('active', 1);

        $this->Events->fireEvent('activate', [$this]);
        QUI::getEvents()->fireEvent('siteActivate', [$this]);

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
        if (!$User) {
            $User = QUI::getUserBySession();
        }

        try {
            // Prüfen ob der Benutzer die Seite bearbeiten darf
            $this->checkPermission('quiqqer.projects.site.edit', $User);
        } catch (QUI\Exception $Exception) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.permissions.edit'
                )
            );
        }

        QUI::getEvents()->fireEvent('siteCheckDeactivate', [$this]);

        // deactivate
        QUI::getDataBase()->exec([
            'update' => $this->TABLE,
            'set'    => [
                'active'       => 0,
                'release_from' => null,
                'e_user'       => $User->getId()
            ],
            'where'  => [
                'id' => $this->getId()
            ]
        ]);

        $this->setAttribute('active', 0);
        $this->getProject()->clearCache();

        //$this->deleteTemp();
        $this->deleteCache();


        $this->Events->fireEvent('deactivate', [$this]);
        QUI::getEvents()->fireEvent('siteDeactivate', [$this]);
    }

    /**
     * Zerstört die Seite
     * Die Seite wird komplett aus der DB gelöscht und auch alle Beziehungen
     * Funktioniert nur wenn die Seite gelöscht ist
     *
     * @throws QUI\Exception
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

            QUI::getDataBase()->delete($table, [
                'id' => $this->getId()
            ]);
        }


        // on destroy event
        try {
            $this->Events->fireEvent('destroy', [$this]);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        try {
            QUI::getEvents()->fireEvent('siteDestroy', [$this]);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }


        /**
         * Site destroy
         */

        // Daten löschen
        QUI::getDataBase()->delete($this->TABLE, [
            'id' => $this->getId()
        ]);

        // sich als Kind löschen
        QUI::getDataBase()->delete($this->RELTABLE, [
            'child' => $this->getId()
        ]);

        // sich als parent löschen
        QUI::getDataBase()->delete($this->RELTABLE, [
            'parent' => $this->getId()
        ]);

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
        if (!$SaveUser) {
            $SaveUser = QUI::getUserBySession();
        }

        try {
            // Prüfen ob der Benutzer die Seite bearbeiten darf
            $this->checkPermission('quiqqer.projects.site.edit', $SaveUser);
        } catch (QUI\Exception $Exception) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
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
                        'quiqqer/quiqqer',
                        'exception.site.is.being.edited.user',
                        [
                            'username' => $User->getName()
                        ]
                    ),
                    703
                );
            }

            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.site.is.being.edited'
                ),
                703
            );
        }

        /* @var $Project QUI\Projects\Project */
        $Project = $this->getProject();
        $name    = $this->getAttribute('name');

        if ($Project->getConfig('convertRomanLetters')) {
            $name = QUI\Utils\Convert::convertRoman($name); // cleanup name
        }

        $name = \trim($name);


        // checks if the name is conform and allowed to use
        QUI\Projects\Site\Utils::checkName($name);


        // check if a name in the same level exists
        // observed linked sites
        if ($this->getId() != 1) {
            $parent_ids = $this->getParentIds();

            foreach ($parent_ids as $pid) {
                $Parent = new QUI\Projects\Site\Edit($Project, $pid);

                try {
                    $childId = (int)$Parent->getChildIdByName($name);

                    if ($childId !== $this->id) {
                        throw new QUI\Exception(
                            QUI::getLocale()->get(
                                'quiqqer/quiqqer',
                                'exception.site.same.name',
                                [
                                    'id'   => $pid,
                                    'name' => $name
                                ]
                            ),
                            703
                        );
                    }
                } catch (QUI\Exception $Exception) {
                    if ($Exception->getCode() !== 705) {
                        throw $Exception;
                    }
                }
            }
        }

        // release dates
        $release_from = '';
        $release_to   = '';

        if ($this->getAttribute('release_from')
            && $this->getAttribute('release_from') != '0000-00-00 00:00:00'
        ) {
            $rf = \strtotime($this->getAttribute('release_from'));

            if ($rf) {
                $release_from = \date('Y-m-d H:i:s', $rf);
            }
        } else {
            if ($this->getAttribute('active')) {
                // nur bei aktiven seiten das e_date setzen
                // wenn der cron läuft, darf eine inaktive seite nicht sofort aktiviert werden
                // daher werden nur aktive seite beachten
                $release_from = \date(
                    'Y-m-d H:i:s',
                    \strtotime(date('Y-m-d H:i:s'))
                );
            }
        }

        if ($this->getAttribute('release_to')) {
            $rt = \strtotime($this->getAttribute('release_to'));

            if ($rt && $rt > 0) {
                $release_to = \date('Y-m-d H:i:s', $rt);
            } else {
                $release_to = '';
            }
        }

        if (empty($release_from)) {
            $release_from = null;
        }

        if (empty($release_to)) {
            $release_to = null;
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
            $this->Events->fireEvent('saveBefore', [$this]);
            QUI::getEvents()->fireEvent('siteSaveBefore', [$this]);
        } catch (QUI\ExceptionStack $Exception) {
            $list = $Exception->getExceptionList();

            foreach ($list as $Exc) {
                /* @var $Exc \Exception */
                QUI\System\Log::writeException($Exc);
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        // save extra package attributes (site.xml)
        $oldType = $this->getAttribute('type');

        $extraAttributes = Utils::getExtraAttributeListForSite($this);
        $siteExtra       = [];

        foreach ($extraAttributes as $data) {
            $attribute = $data['attribute'];
            $default   = $data['default'];

            if ($this->existsAttribute($attribute)) {
                $siteExtra[$attribute] = $this->getAttribute($attribute);
                continue;
            }

            $siteExtra[$attribute] = $default;
        }

        // order type
        $order_type = 'manuell';

        switch ($this->getAttribute('order_type')) {
            case 'manual':
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

        // clear paths
        // wenn sich der seitentyp geändert hat, muss ein clear path gemacht werden
        // somit bleiben keine alten register paths bestehen
        if ($oldType != $this->getAttribute('type')) {
            QUI::getRewrite()->unregisterPath($this);
        }

        $order_field = $this->getAttribute('order_field');

        if (\is_numeric($order_field)) {
            $order_field = (int)$order_field;
        }

        if (!$order_field) {
            $order_field = 0;
        }

        // save main data
        $update = QUI::getDataBase()->update(
            $this->TABLE,
            [
                'name'          => $name,
                'title'         => \trim($this->getAttribute('title')),
                'short'         => $this->getAttribute('short'),
                'content'       => $this->getAttribute('content'),
                'type'          => $this->getAttribute('type'),
                'layout'        => $this->getAttribute('layout'),
                'nav_hide'      => $this->getAttribute('nav_hide') ? 1 : 0,
                'e_user'        => $SaveUser->getId(),
                // ORDER
                'order_type'    => $order_type,
                'order_field'   => $order_field,
                // images
                'image_emotion' => $this->getAttribute('image_emotion'),
                'image_site'    => $this->getAttribute('image_site'),
                // release
                'release_from'  => $release_from,
                'release_to'    => $release_to,
                // Extra-Feld
                'extra'         => \json_encode($siteExtra),
                'auto_release'  => $this->getAttribute('auto_release') ? 1 : 0
            ],
            [
                'id' => $this->getId()
            ]
        );

        // save package automatic site data (database.xml)
        $dataList = Utils::getDataListForSite($this);

        foreach ($dataList as $dataEntry) {
            $data = [];

            $table     = $dataEntry['table'];
            $fieldList = $dataEntry['data'];
            $package   = $dataEntry['package'];
            $suffix    = $dataEntry['suffix'];

            $attributeSuffix = $package.'.'.$suffix.'.';
            $attributeSuffix = \str_replace('/', '.', $attributeSuffix);

            foreach ($fieldList as $siteAttribute) {
                $data[$siteAttribute] = $this->getAttribute(
                    $attributeSuffix.$siteAttribute
                );
            }

            $result = QUI::getDataBase()->fetch([
                'from'  => $table,
                'where' => [
                    'id' => $this->getId()
                ],
                'limit' => 1
            ]);

            if (!isset($result[0])) {
                QUI::getDataBase()->insert($table, [
                    'id' => $this->getId()
                ]);
            }

            QUI::getDataBase()->update($table, $data, [
                'id' => $this->getId()
            ]);
        }

        //$this->deleteTemp($User);
        if (!QUI::conf('globals', 'ignoreSiteCacheClearing')) {
            $Project->clearCache();
        }

        // Cache löschen
        $this->deleteCache();

        // Objektcache anlegen
        $this->refresh();
        $this->createCache();

        // Letztes Speichern
        $Project->setEditDate(\time());

        // on save event
        try {
            $this->Events->fireEvent('save', [$this]);
            QUI::getEvents()->fireEvent('siteSave', [$this]);
        } catch (QUI\ExceptionStack $Exception) {
            $list = $Exception->getExceptionList();

            foreach ($list as $Exc) {
                /* @var $Exc \Exception */
                QUI\System\Log::writeException($Exc);
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }


        if ($update) {
            QUI::getMessagesHandler()->addSuccess(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'message.site.save.success',
                    [
                        'id'    => $this->getId(),
                        'title' => $this->getAttribute('title'),
                        'name'  => $this->getAttribute('name')
                    ]
                )
            );

            return true;
        }

        throw new QUI\Exception(
            QUI::getLocale()->get(
                'quiqqer/quiqqer',
                'exception.site.save.error'
            ),
            self::ESAVE
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @param integer $pid - Parent - ID
     * @param array $params
     *
     * @return array
     *
     * @throws QUI\Database\Exception
     * @see Site::getChildrenIdsFromParentId()
     *
     */
    public function getChildrenIdsFromParentId($pid, $params = [])
    {
        $where_1 = [
            $this->RELTABLE.'.parent' => (int)$pid,
            $this->TABLE.'.deleted'   => 0,
            $this->RELTABLE.'.child'  => '`'.$this->TABLE.'.id`'
        ];

        if (isset($params['where']) && \is_array($params['where'])) {
            $where = \array_merge($where_1, $params['where']);
        } elseif (isset($params['where']) && \is_string($params['where'])) {
            // @todo where als param string
            QUI\System\Log::addDebug('WIRD NICHT verwendet'.$params['where']);
            $where = $where_1;
        } else {
            $where = $where_1;
        }

        $order = $this->TABLE.'.order_field';

        if (isset($params['order'])) {
            if (\strpos($params['order'], '.') !== false) {
                $order = $this->TABLE.'.'.$params['order'];
            } else {
                $order = $params['order'];
            }
        }

        $result = QUI::getDataBase()->fetch([
            'select' => $this->TABLE.'.id',
            'count'  => isset($params['count']) ? 'count' : false,
            'from'   => [
                $this->RELTABLE,
                $this->TABLE
            ],
            'order'  => $order,
            'limit'  => isset($params['limit']) ? $params['limit'] : false,
            'where'  => $where
        ]);

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

        $PDO       = QUI::getDataBase()->getPDO();
        $Statement = $PDO->prepare($query);

        $Statement->bindValue(':name', $name, \PDO::PARAM_STR);
        $Statement->execute();

        $result = $Statement->fetchAll(\PDO::FETCH_ASSOC);

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
     * @return array|int
     * @throws QUI\Exception
     *
     * @todo $recusiv parameter is not used and the interface defines it as a $load parameter
     *
     */
    public function getChildren($params = [], $recursiv = false)
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

                case 'manual':
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

        $children = [];
        $result   = $this->getChildrenIds($params);

        if (isset($params['count'])) {
            return (int)$result[0]['count'];
        }

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
     *
     * @throws QUI\Exception
     * @throws QUI\Database\Exception
     * @throws QUI\Permissions\Exception
     */
    public function addLanguageLink($lang, $id)
    {
        $this->checkPermission('quiqqer.projects.site.edit');

        $Project = $this->getProject();
        $p_lang  = $Project->getAttribute('lang');
        $id      = (int)$id;

        $result = QUI::getDataBase()->fetch([
            'from'  => $this->RELLANGTABLE,
            'where' => [
                $p_lang => $this->getId()
            ],
            'limit' => '1'
        ]);

        if (isset($result[0])) {
            return QUI::getDataBase()->exec([
                'update' => $this->RELLANGTABLE,
                'set'    => [
                    $lang => $id
                ],
                'where'  => [
                    $p_lang => $this->getId()
                ]
            ]);
        }

        return QUI::getDataBase()->exec([
            'insert' => $this->RELLANGTABLE,
            'set'    => [
                $p_lang => $this->getId(),
                $lang   => $id
            ]
        ]);
    }

    /**
     * Entfernt eine Verknüpfung zu einer Sprache
     *
     * @param string $lang
     *
     * @return \PDOStatement
     *
     * @throws QUI\Permissions\Exception
     * @throws QUI\Database\Exception
     * @throws QUI\Exception
     */
    public function removeLanguageLink($lang)
    {
        $this->checkPermission('quiqqer.projects.site.edit');

        $Project = $this->getProject();

        return QUI::getDataBase()->exec([
            'update' => $this->RELLANGTABLE,
            'set'    => [
                $lang => 0
            ],
            'where'  => [
                $Project->getAttribute('lang') => $this->getId()
            ]
        ]);
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
        $params = [],
        $childPermissions = [],
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
                $new_name = $old.' ('.$i.')';
                $i++;
            }
        } else {
            $new_name = $params['name'];
        }

        if ($this->existNameInChildren($new_name)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.site.same.name',
                    [
                        'name' => $new_name
                    ]
                )
            );
        }

        // can we use this name?
        QUI\Projects\Site\Utils::checkName($new_name);


        $childCount = $this->hasChildren(true);

        $_params = [
            'name'        => $new_name,
            'title'       => $new_name,
            'c_date'      => \date('Y-m-d H:i:s'),
            'e_user'      => $User->getId(),
            'c_user'      => $User->getId(),
            'c_user_ip'   => QUI\Utils\System::getClientIP(),
            'order_field' => $childCount + 1
        ];

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

        // something is wrong
        if ($newId == 0) {
            $max = $DataBase->fetch([
                'select' => ['field' => 'id', 'function' => 'MAX'],
                'from'   => $this->TABLE
            ]);

            $newId = (int)reset($max[0]) + 1;

            $DataBase->update(
                $this->TABLE,
                ['id' => $newId],
                ['id' => 0]
            );
        }

        $DataBase->insert($this->RELTABLE, [
            'parent' => $this->getId(),
            'child'  => $newId
        ]);

        // copy permissions to the child
        $PermManager    = QUI::getPermissionManager();
        $permissions    = $PermManager->getSitePermissions($this);
        $newPermissions = [];

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
        $this->Events->fireEvent('createChild', [$newId, $this]);
        QUI::getEvents()->fireEvent('siteCreateChild', [$newId, $this]);


        return $newId;
    }

    /**
     * Move the site to another parent
     *
     * @param integer $pid - Parent ID
     *
     * @return boolean
     *
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     */
    public function move($pid)
    {
        $this->checkPermission('quiqqer.projects.site.edit');

        $Project  = $this->getProject();
        $Parent   = $Project->get((int)$pid);// Check whether the parent exists
        $children = $this->getChildrenIds();

        if (\in_array($pid, $children) || $pid === $this->getId()) {
            return false;
        }


        QUI::getEvents()->fireEvent('siteMoveBefore', [$this, $this->getParent()->getId()]);

        // get new order_field if manually sorting
        if ($Parent->getAttribute('order_type') === ''
            || $Parent->getAttribute('order_type') === 'manuell'
            || $Parent->getAttribute('order_type') === 'manual'
            || !$Parent->getAttribute('order_type')) {
            $LastChild = $Parent->lastChild(['active' => '0&1']);

            if (!$LastChild) {
                $this->setAttribute('order_field', 1);
            } else {
                $this->setAttribute(
                    'order_field',
                    (int)$LastChild->getAttribute('order_field') + 1
                );
            }

            $this->save();
        }


        QUI::getDataBase()->update(
            $this->RELTABLE,
            ['parent' => $Parent->getId()],
            'child = '.$this->getId().' AND oparent IS NULL'
        );

        //$this->deleteTemp();
        $this->deleteCache();

        // remove internal parent ids
        $this->parents_id = false;
        $this->parent_id  = false;


        $this->Events->fireEvent('move', [$this, $pid]);
        QUI::getEvents()->fireEvent('siteMove', [$this, $pid]);

        return true;
    }

    /**
     * Copies the page
     *
     * @param integer $pid - ID of the parent under which the copy is to be mounted
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

        if (\get_class($Project) != Project::class) {
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
                'quiqqer/quiqqer',
                'projects.project.site.copy.text',
                ['name' => $name]
            );

            $newTitle = QUI::getLocale()->get(
                'quiqqer/quiqqer',
                'projects.project.site.copy.text',
                ['name' => $title]
            );

            // kind gefunden, wir brauchen ein neuen namen
            $i = 1;

            while ($Parent->existNameInChildren($newName)) {
                $newName = QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'projects.project.site.copy.text.count',
                    [
                        'name'  => $name,
                        'count' => $i
                    ]
                );

                $newTitle = QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'projects.project.site.copy.text.count',
                    [
                        'name'  => $title,
                        'count' => $i
                    ]
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
            [
                'name'  => $name,
                'title' => $title
            ],
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

        $table = QUI::getDBTableName(
            $Project->getAttribute('name').'_'.
            $Project->getAttribute('lang').'_sites_relations'
        );

        // Prüfen ob die Seite schon in dem Parent ist
        if ($this->getId() == $pid) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.site.linked.in.itself'),
                703
            );
        }

        // Prüfen ob die Seite schon in dem Parent ist
        if ($Parent->getId() == $pid) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/quiqqer', 'exception.site.linked.already.exists'),
                703
            );
        }

        $links = QUI::getDataBase()->fetch([
            'from'  => $table,
            'where' => [
                'child' => $this->getId()
            ]
        ]);

        foreach ($links as $entry) {
            if ($entry['parent'] == $pid) {
                throw new QUI\Exception(
                    QUI::getLocale()->get('quiqqer/quiqqer', 'exception.site.linked.already.exists'),
                    703
                );
            }
        }

        QUI::getDataBase()->insert($table, [
            'parent'  => $pid,
            'child'   => $this->getId(),
            'oparent' => $Parent->getId()
        ]);
    }

    /**
     * Delete all linked sites
     *
     * @param integer $pid - Parent ID
     * @param integer|boolean $all - (optional) Delete all linked sites and the original site
     * @param boolean $orig - (optional) Delete the original site, too
     *
     * @throws QUI\Exception
     * @throws QUI\Permissions\Exception
     */
    public function deleteLinked($pid, $all = false, $orig = false)
    {
        $this->checkPermission('quiqqer.projects.site.edit');


        $Project  = $this->getProject();
        $Parent   = $this->getParent();
        $DataBase = QUI::getDataBase();

        $table = QUI::getDBTableName(
            $Project->getAttribute('name').'_'.
            $Project->getAttribute('lang').'_sites_relations'
        );

        if (QUI\Utils\BoolHelper::JSBool($all)) {
            // Seite löschen
            $this->delete();

            // Alle Verknüpfungen
            $DataBase->delete($table, [
                'child'  => $this->getId(),
                'parent' => [
                    'value' => $Parent->getId(),
                    'type'  => 'NOT'
                ]
            ]);

            return;
        }

        // Einzelne Verknüpfung löschen
        if ($pid && $orig == false) {
            $DataBase->delete($table, [
                'child'  => $this->getId(),
                'parent' => (int)$pid
            ]);

            return;
        }

        $DataBase->delete($table, [
            'child'   => $this->getId(),
            'parent'  => (int)$pid,
            'oparent' => (int)$orig
        ]);
    }

    //region cache

    /**
     * Create the site cache
     */
    public function createCache()
    {
        // create object cache
        parent::createCache();

        // create url rewritten cache
        $linkCache = $this->getLinkCachePath(
            $this->getProject()->getName(),
            $this->getProject()->getLang(),
            $this->getId()
        );

        try {
            QUI\Cache\Manager::set($linkCache, $this->getLocation());
        } catch (\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
        }
    }

    //endregion

    /**
     * is the page currently edited from another user than me?
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

        try {
            $time = Locker::getLockTime(
                QUI::getPackage('quiqqer/quiqqer'),
                $this->getLockKey()
            );
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return true;
        }

        $max_life_time = QUI::conf('session', 'max_life_time');

        if ($time > $max_life_time) {
            $this->unlock();

            return false;
        }

        if (\is_array($uid) && isset($uid['id'])) {
            return $uid['id'];
        }

        return (int)$uid;
    }

    /**
     * is the page currently edited
     *
     * @return bool|string
     */
    public function isLocked()
    {
        try {
            return Locker::isLocked(
                QUI::getPackage('quiqqer/quiqqer'),
                $this->getLockKey()
            );
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return true;
        }
    }

    /**
     * Markiert die Seite -> die Seite wird gerade bearbeitet
     * Markiert nur wenn die Seite nicht markiert ist
     *
     * @return boolean - true if it worked, false if it not worked
     *
     * @throws QUI\Exception
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

        Locker::lock(
            QUI::getPackage('quiqqer/quiqqer'),
            $this->getLockKey()
        );

        return true;
    }

    /**
     * Demarkiert die Seite, die Seite wird nicht mehr bearbeitet
     */
    protected function unlock()
    {
        try {
            Locker::unlock(
                QUI::getPackage('quiqqer/quiqqer'),
                $this->getLockKey()
            );
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * Ein SuperUser kann eine Seite trotzdem demakieren wenn er möchte
     *
     * @throws QUI\Exception
     * @todo eigenes recht dafür einführen
     */
    public function unlockWithRights()
    {
        $this->unlock();
    }

    /**
     * Return the key for the lock file
     *
     * @return string
     */
    protected function getLockKey()
    {
        return $this->getProject()->getName().'_'.
               $this->getProject()->getLang().'_'.
               $this->getId();
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
     *
     * @throws QUI\Exception
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
     *
     * @throws QUI\Exception
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
     *
     * @throws QUI\Exception
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
     *
     * @throws QUI\Exception
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
     * @return boolean
     * @throws \QUI\Exception
     * @deprecated use \QUI\Projects\Site\Utils::checkName
     */
    public static function checkName($name)
    {
        return QUI\Projects\Site\Utils::checkName($name);
    }
}
