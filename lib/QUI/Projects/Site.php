<?php

/**
 * This file contains the \QUI\Projects\Site
 */

namespace QUI\Projects;

use QUI;
use QUI\Utils\StringHelper as StringUtils;

/**
 * Site Objekt - eine einzelne Seite
 *
 * @author     www.pcsg.de (Henning Leutz)
 * @licence    For copyright and license information, please view the /README.md
 *
 * @errorcodes 7XX = Site Errors -> look at Site/Edit
 */
class Site extends QUI\QDOM implements QUI\Interfaces\Projects\Site
{
    /**
     * Edit site
     *
     * @var QUI\Projects\Site\Edit
     */
    protected $Edit = null;

    /**
     * $Events - manage and fires events
     *
     * @var QUI\Events\Event
     */
    public $Events;

    /**
     * @var Project
     */
    protected $Project;

    /**
     * The site id
     *
     * @var integer
     */
    protected $id;

    /**
     * parents array
     *
     * @var array
     */
    protected $parents;

    /**
     * the main parent id
     *
     * @var integer
     */
    protected $parent_id;

    /**
     * Parent ids
     *
     * @var array
     */
    protected $parents_id;

    /**
     * the children list
     *
     * @var array
     */
    protected $childs_container;

    /**
     * children
     *
     * @var array
     */
    protected $children = [];

    /**
     * the site url
     *
     * @var string
     */
    protected $url = false;

    /**
     * the ids of the pages in other languages
     *
     * @var array
     */
    protected $lang_ids = [];

    /**
     * the database table
     *
     * @var string
     */
    protected $TABLE = '';

    /**
     * the database relation table
     *
     * @var string
     */
    protected $RELTABLE = '';

    /**
     * the database relation language table
     *
     * @var string
     */
    protected $RELLANGTABLE = '';

    /**
     * Is the site loaded flag
     *
     * @var boolean
     */
    protected $loadFlag = false;

    /**
     * is the site a link
     *
     * @var boolean
     */
    protected $LINKED_PARENT = false;

    /**
     * tmp data from tables from the plugins
     *
     * @var array
     */
    protected $database_data = [];

    /**
     * Extend class string
     *
     * @var string
     */
    protected $extend = '';

    /**
     * Extend class object
     */
    protected $Extends = null;

    /**
     * Type string
     *
     * @var string|false
     */
    protected $type = false;

    /**
     * Package string
     *
     * @var string|false
     */
    protected $package = false;

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
        $this->id      = (int)$id;
        $this->Project = $Project;
        $this->Events  = new QUI\Events\Event();

        if (empty($this->id)) {
            throw new QUI\Exception('Site Error; No ID given:'.$id, 700);
        }

        // DB Tables
        $this->TABLE        = $Project->table();
        $this->RELTABLE     = $Project->table().'_relations';
        $this->RELLANGTABLE = QUI::getDBTableName($Project->getAttribute('name').'_multilingual');


        // view permission check
        $this->checkPermission('quiqqer.projects.site.view');


        // site events
        $events = QUI::getEvents()->getSiteListByType(
            $this->getAttribute('type')
        );

        foreach ($events as $event) {
            $this->Events->addEvent($event['event'], $event['callback']);
        }


        // for admins there is no cache
        if (\defined('ADMIN') && ADMIN == 1) {
            return true;
        }

        try {
            $this->decode(
                QUI\Cache\Manager::get($this->getCacheName())
            );
        } catch (QUI\Exception $Exception) {
            $this->refresh();
            $this->createCache();
        }

        // default type if no type is set
        if (!$this->getAttribute('type')) {
            $this->setAttribute('type', 'standard');
        }


        $this->Events->fireEvent('init', [$this]);
        QUI::getEvents()->fireEvent('siteInit', [$this]);

        return true;
    }

    /**
     * Returns the Edit Site object from this Site
     *
     * @return QUI\Projects\Site\Edit
     *
     * @throws QUI\Exception
     */
    public function getEdit()
    {
        if (\get_class($this) == 'QUI\Projects\Site\Edit') {
            /* @var QUI\Projects\Site\Edit $this */
            return $this;
        }

        if (!$this->Edit) {
            $this->Edit = new Site\Edit(
                $this->getProject(),
                $this->getId()
            );
        }

        return $this->Edit;
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
     * @return Site
     *
     * @throws QUI\Exception
     */
    public function load($plugin = false)
    {
        $this->loadFlag = true;

        $PackageManager = QUI::getPackageManager();
        $packages       = $PackageManager->getInstalled();

        foreach ($packages as $package) {
            if ($plugin && $plugin != $package['name']) {
                continue;
            }

            $dir = OPT_DIR.$package['name'].'/';

            $this->loadDatabases($dir, $package['name']);
        }

        // onLoad event
        $this->Events->fireEvent('load', [$this]);
        QUI::getEvents()->fireEvent('siteLoad', [$this]);


        // load type
        $type = $this->getAttribute('type');

        if (\strpos($type, ':') === false) {
            return $this;
        }

        // site.xml
        $explode = \explode(':', $type);
        $package = $explode[0];
        $type    = $explode[1];

        $this->type    = $type;
        $this->package = $package;

        $siteXml = OPT_DIR.$package.'/site.xml';

        $Dom   = QUI\Utils\Text\XML::getDomFromXml($siteXml);
        $XPath = new \DOMXPath($Dom);
        $Types = $XPath->query('//type[@type="'.$type.'"]');

        /* @var $Type \DOMElement */
        $Type   = $Types->item(0);
        $extend = false;

        if ($Type) {
            $extend = $Type->getAttribute('extend');

            if ($Type->hasAttribute('cache') && (int)$Type->getAttribute('cache') === 0) {
                $this->setAttribute('nocache', 1);
            }
        }

        if ($extend) {
            $this->extend  = $extend;
            $this->Extends = new $extend(); // @todo check if can be deleted
        }


        return $this;
    }

    /**
     * Load the Plugin databases
     * database.xml
     *
     * @param string $dir - Path to the package
     * @param string $package - name of the package
     *
     * @rewrite it to events
     *
     * @throws QUI\Exception
     */
    protected function loadDatabases($dir, $package)
    {
        $databaseXml = $dir.'database.xml';

        // database.xml
        if (!\file_exists($databaseXml)) {
            return;
        }

        $DataBaseXML = QUI\Utils\Text\XML::getDomFromXml($databaseXml);
        $projects    = $DataBaseXML->getElementsByTagName('projects');

        if (!$projects || !$projects->length) {
            return;
        }

        $project_name = $this->getProject()->getName();
        $project_lang = $this->getProject()->getLang();
        $siteType     = $this->getAttribute('type');

        /* @var $Projects \DOMElement */
        $Projects = $projects->item(0);
        $tables   = $Projects->getElementsByTagName('table');

        for ($i = 0; $i < $tables->length; $i++) {
            /* @var $tables \DOMNodeList */
            /* @var $Table \DOMElement */
            $Table = $tables->item($i);

            if ((int)$Table->getAttribute('no-site-reference') == 1) {
                continue;
            }

            if ((int)$Table->getAttribute('no-project-lang') === 1) {
                continue;
            }

            // type check
            $types = $Table->getAttribute('site-types');

            if ($types) {
                $types = \explode(',', $types);
            }

            if (!empty($types)) {
                foreach ($types as $allowedType) {
                    if (!StringUtils::match($allowedType, $siteType)) {
                        continue;
                    }
                }
            }

            // get database fields
            $fields = QUI\Utils\DOM::dbTableDomToArray($Table);

            if (!isset($fields['suffix']) || !isset($fields['fields'])) {
                continue;
            }

            // get data
            $tbl       = QUI::getDBTableName($project_name.'_'.$project_lang.'_'.$fields['suffix']);
            $fieldList = \array_keys($fields['fields']);

            $result = QUI::getDataBase()->fetch([
                'select' => $fieldList,
                'from'   => $tbl,
                'where'  => [
                    'id' => $this->getId()
                ],
                'limit'  => 1
            ]);

            // package.package.table.attribute
            $attributePrfx = \str_replace(
                '/',
                '.',
                $package.'.'.$fields['suffix']
            );

            foreach ($fieldList as $field) {
                if ($field == 'id') {
                    continue;
                }

                if (!isset($result[0][$field])) {
                    continue;
                }

                $this->setAttribute(
                    $attributePrfx.'.'.$field,
                    $result[0][$field]
                );
            }
        }
    }

    /**
     * Serialisierungsdaten
     *
     * @return string
     */
    public function encode()
    {
        $att = $this->getAttributes();

//         $att['extra']         = $this->_extra;
        $att['linked_parent'] = $this->LINKED_PARENT;
        $att['_type']         = $this->type;
        $att['_extend']       = $this->extend;

        unset($att['project']);

        return \json_encode($att);
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
        $decode = \json_decode($params, true);

        if ($decode['active'] != 1) {
            throw new QUI\Exception('Site not exist', 705);
        }

        if ($decode['deleted'] == 1) {
            throw new QUI\Exception('Site not exist', 705);
        }

        if (isset($decode['linked_parent'])) {
            $this->LINKED_PARENT = $decode['linked_parent'];
            unset($decode['linked_parent']);
        }

        $this->attributes = $decode;
    }

    /**
     * Hohlt frisch die Daten aus der DB
     *
     * @throws QUI\Exception
     */
    public function refresh()
    {
        $this->loadFlag = false;

        $result = QUI::getDataBase()->fetch([
            'from'  => $this->TABLE,
            'where' => [
                'id' => $this->getId()
            ],
            'limit' => '1'
        ]);

        if (!isset($result[0])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.site.not.found'
                ),
                705,
                [
                    'siteId'  => $this->getId(),
                    'project' => $this->getProject()->getName(),
                    'lang'    => $this->getProject()->getLang()
                ]
            );
        }

        $params = $result[0];

        if ($params['active'] != 1 && !defined('QUIQQER_PREVIEW')) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.site.not.found'
                ),
                705,
                [
                    'siteId'  => $this->getId(),
                    'project' => $this->getProject()->getName(),
                    'lang'    => $this->getProject()->getLang()
                ]
            );
        }

        if ($params['deleted'] == 1) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.site.not.found'
                ),
                705,
                [
                    'siteId'  => $this->getId(),
                    'project' => $this->getProject()->getName(),
                    'lang'    => $this->getProject()->getLang()
                ]
            );
        }


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

        if (isset($result[0]['extra']) && !empty($result[0]['extra'])) {
            $extra = \json_decode($result[0]['extra'], true);

            foreach ($extra as $key => $value) {
                $this->setAttribute($key, $value);
            }

            unset($result[0]['extra']);
        }

        $this->setAttributes($result[0]);
    }

    /**
     * Prüft ob es eine Verknüpfung ist
     *
     * @return boolean|integer
     */
    public function isLinked()
    {
        if ($this->LINKED_PARENT === false) {
            return false;
        }

        return $this->LINKED_PARENT;
    }

    /**
     * Prüft ob es die Seite auch in einer anderen Sprache gibt
     *
     * @param string $lang
     * @param boolean $check_only_active - check only active pages
     *
     * @return boolean
     *
     * @throws QUI\Exception
     */
    public function existLang($lang, $check_only_active = true)
    {
        if (empty($lang)) {
            return false;
        }

        $Project = $this->getProject();

        if ($lang == $Project->getAttribute('lang')) {
            return true;
        }

        // Sprachen ID rausbekommen
        $lang_id = $this->getId($lang);

        // Wenn keine ID dann gleich raus
        if ($lang_id == false) {
            return false;
        }

        // Wenn es egal ist ob aktive oder inaktive
        if ($check_only_active == false && $lang_id == true) {
            return true;
        }

        try {
            $_Project = QUI::getProject(
                $Project->getAttribute('name'),
                $lang
            );

            $_Project->get($lang_id);

            return true;
        } catch (QUI\Exception $Exception) {
            // nothing
        }

        return false;
    }

    /**
     * Gibt die IDs von Sprachverknüpfungen zurück
     *
     * @return array
     */
    public function getLangIds()
    {
        $result = [];

        try {
            $Project = $this->getProject();

            $dbResult = QUI::getDataBase()->fetch([
                'from'  => $Project->getAttribute('name').'_multilingual',
                'where' => [
                    $Project->getAttribute('lang') => $this->getId()
                ]
            ]);

            $languages = $Project->getLanguages();

            foreach ($languages as $lang) {
                if (isset($dbResult[0][$lang])) {
                    $result[$lang] = $dbResult[0][$lang];
                    continue;
                }

                $result[$lang] = false;
            }
        } catch (QUI\Exception $Exception) {
        }

        return $result;
    }

    /**
     * Return the ID of the site,
     * or the ID of the sibling (linked) site of another language
     *
     * @param string|boolean $lang - optional, if it is set, then the language of the wanted linked sibling site
     *
     * @return integer|boolean
     *
     * @throws QUI\Exception
     */
    public function getId($lang = false)
    {
        if ($lang === false) {
            return $this->id;
        }

        // other languages
        $Project = $this->getProject();

        if ($lang === $Project->getAttribute('lang')) {
            return $this->id;
        }

        // ???
        if (isset($this->lang_ids[$lang])) {
            return $this->lang_ids[$lang];
        }

        $availableLanguages = $Project->getLanguages();

        // @todo don`t throw an exception, implement existsLanguage()
        if ($lang && !\in_array($lang, $availableLanguages)) {
            throw new QUI\Exception(
                [
                    'quiqqer/system',
                    'exception.project.lang.not.found',
                    ['lang' => $lang]
                ],
                404
            );
        }

        $projectName = $Project->getAttribute('name');
        $projectLang = $Project->getAttribute('lang');

        $site_table = QUI::getDBTableName($projectName.'_'.$projectLang.'_sites');
        $lang_table = QUI::getDBTableName($projectName.'_'.$lang.'_sites');
        $rel_table  = QUI::getDBTableName($projectName.'_multilingual');

        $PDO = QUI::getPDO();

        $Statement = $PDO->prepare('
            SELECT `'.$rel_table.'`.`'.$lang.'`
            FROM `'.$site_table.'`, `'.$lang_table.'`, `'.$rel_table.'`
            WHERE
                `'.$rel_table.'`.`'.$lang.'` = `'.$lang_table.'`.`id` AND
                `'.$rel_table.'`.`'.$projectLang.'` = `'.$site_table.'`.`id` AND
                `'.$site_table.'`.`id` = :id
               LIMIT 1;
        ');

        $Statement->bindValue(':id', $this->getId(), \PDO::PARAM_INT);
        $Statement->execute();

        $result = $Statement->fetchAll(\PDO::FETCH_ASSOC);

        if (isset($result[0]) && isset($result[0][$lang])) {
            $this->lang_ids[$lang] = (int)$result[0][$lang];

            return $this->lang_ids[$lang];
        }

        return false;
    }

    /**
     * Gibt alle Kinder zurück
     *
     * @param array $params - Parameter für die Childrenausgabe
     *                      $params['where']
     *                      $params['limit']
     * @param boolean $load - Legt fest ob die Kinder die Plugins laden sollen
     *
     * @return array|integer
     *
     * @throws QUI\Exception
     */
    public function getChildren($params = [], $load = false)
    {
        if (!\is_array($params)) {
            $params = [];
        }

        // Falls kein Order übergeben wird das eingestellte Site Order
        if (!isset($params['order'])) {
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

                case 'manuel':
                case 'manuell':
                default:
                    $params['order'] = 'order_field';
                    break;
            }
        }

        if (!isset($params['order']) || empty($params['order'])) {
            $params['order'] = 'order_field';
        }

        $result = $this->getChildrenIds($params);

        if (isset($params['count'])) {
            return (int)$result;
        }


        $children = [];

        foreach ($result as $id) {
            try {
                $Child = $this->getChild((int)$id);

                if ($load) {
                    $Child->load();
                }

                $Child->setAttribute('parent', $this);

                $children[] = $Child;
            } catch (QUI\Exception $Exception) {
                if (DEBUG_MODE) {
                    QUI\System\Log::writeException($Exception);
                }
            }
        }

        return $children;
    }

    /**
     * Returns the next site
     *
     * @return QUI\Projects\Site
     * @throws QUI\Exception
     */
    public function nextSibling()
    {
        $Parent  = $this->getParent();
        $Project = $this->getProject();
        $list    = $Parent->getChildrenIds();

        foreach ($list as $key => $id) {
            if ($id == $this->getId() && isset($list[$key + 1])) {
                return $Project->get((int)$list[$key + 1]);
            }
        }

        throw new QUI\Exception(
            QUI::getLocale()->get('quiqqer/quiqqer', 'exception.site.no.next.sibling')
        );
    }

    /**
     * Return the next x sites
     *
     * @param integer $no
     * @return array
     *
     * @throws QUI\Exception
     */
    public function nextSiblings($no)
    {
        $no     = (int)$no;
        $result = [];

        $Parent  = $this->getParent();
        $Project = $this->getProject();
        $list    = $Parent->getChildrenIds();

        foreach ($list as $key => $id) {
            if ($id != $this->getId()) {
                continue;
            }

            // die nächsten x Kinder
            for ($i = 1; $i <= $no; $i++) {
                if (isset($list[$key + $i])) {
                    try {
                        $result[] = $Project->get((int)$list[$key + $i]);
                    } catch (QUI\Exception $Exception) {
                        if (\defined('DEBUG_MODE')) {
                            QUI\System\Log::writeException($Exception);
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Returns the previous site
     *
     * @return QUI\Projects\Site
     * @throws QUI\Exception
     */
    public function previousSibling()
    {
        $Parent  = $this->getParent();
        $Project = $this->getProject();
        $list    = $Parent->getChildrenIds();

        foreach ($list as $key => $id) {
            if ($id == $this->getId() && isset($list[$key - 1])) {
                return $Project->get((int)$list[$key - 1]);
            }
        }

        throw new QUI\Exception(
            QUI::getLocale()->get('quiqqer/quiqqer', 'exception.site.no.previous.sibling')
        );
    }

    /**
     * Returns the previous x sites
     *
     * @param integer $no
     * @return array
     *
     * @throws QUI\Exception
     */
    public function previousSiblings($no)
    {
        $no     = (int)$no;
        $result = [];

        $Parent  = $this->getParent();
        $Project = $this->getProject();
        $list    = $Parent->getChildrenIds();

        foreach ($list as $key => $id) {
            if ($id != $this->getId()) {
                continue;
            }

            // die nächsten x Kinder
            for ($i = 1; $i <= $no; $i++) {
                if (isset($list[$key - $i])) {
                    try {
                        $result[] = $Project->get((int)$list[$key - $i]);
                    } catch (QUI\Exception $Exception) {
                        if (\defined('DEBUG_MODE')) {
                            QUI\System\Log::writeException($Exception);
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Returns the first child of the site
     *
     * @param array $params
     * @return QUI\Projects\Site|false
     *
     * @throws QUI\Exception
     */
    public function firstChild($params = [])
    {
        if (!\is_array($params)) {
            $params = [];
        }

        $params['limit'] = '1';

        $children = $this->getChildren($params);

        if (isset($children[0])) {
            return $children[0];
        }

        return false;
    }

    /**
     * Return the last child
     *
     * @param array $params
     * @return bool|Site|Site\Edit
     *
     * @throws QUI\Exception
     */
    public function lastChild($params = [])
    {
        if (!\is_array($params)) {
            $params = [];
        }

        $params['limit'] = false;

        $result = $this->getChildrenIds($params);

        if (!\count($result)) {
            return false;
        }

        $last = \array_pop($result);

        try {
            return $this->getProject()->get($last);
        } catch (QUI\Exception $Exception) {
        }

        return false;
    }

    /**
     * Gibt die Kinder zurück achtet aber auf "Nicht in Navigation anzeigen" und Rechte
     *
     * @param array $params
     *
     * @return array|int
     *
     * @throws QUI\Exception
     */
    public function getNavigation($params = [])
    {
        if (!\is_array($params)) {
            $params = [];
        }

        $params['where']['nav_hide'] = 0;

        $children = $this->getChildren($params);

        if (isset($params['count'])) {
            return $children;
        }

        foreach ($children as $k => $Child) {
            if ($Child instanceof QUI\Projects\Site\PermissionDenied) {
                unset($children[$k]);
            }
        }

        return $children;
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
        $result = QUI::getDataBase()->fetch([
            'from'  => [
                $this->RELTABLE,
                $this->TABLE
            ],
            'where' => [
                $this->RELTABLE.'.parent' => $this->getId(),
                $this->TABLE.'.deleted'   => 0,
                $this->RELTABLE.'.child'  => '`'.$this->TABLE.'.id`',
                // LIKE muss bleiben wegen _,
                // sonst werden keine Seiten mehr gefunden
                $this->TABLE.'.name'      => [
                    'type'  => 'LIKE',
                    'value' => \str_replace('-', '_', $name)
                ]
            ],
            'limit' => 1
        ]);

        if (isset($result[0]) && isset($result[0]["id"])) {
            return $result[0]["id"];
        }

        throw new QUI\Exception(
            QUI::getLocale()->get('quiqqer/quiqqer', 'exception.site.child.by.name.not.found', [
                'name' => $name
            ]),
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
        $id = (int)$id;

        if ($id == $this->getId()) {
            throw new QUI\Exception('Page can not be a child of itself');
        }

        if (isset($this->children[$id])) {
            return $this->children[$id];
        }

        $result = QUI::getDataBase()->fetch([
            'from'  => $this->RELTABLE,
            'where' => [
                'parent' => $this->getId(),
                'child'  => $id
            ],
            'limit' => 1
        ]);

        if (!isset($result[0])) {
            throw new QUI\Exception('Child not found', 705);
        }

        $this->children[$id] = $this->getProject()->get($id);

        return $this->children[$id];
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
     *
     * @throws QUI\Exception
     */
    public function getChildrenIds($params = [])
    {
        if (!isset($params['order'])) {
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

        return $this->getProject()->getChildrenIdsFrom(
            $this->getId(),
            $params
        );
    }

    /**
     * Return ALL children ids under the site
     *
     * @param array $params - db parameter
     *
     * @return array
     *
     * @throws QUI\Exception
     */
    public function getChildrenIdsRecursive($params = [])
    {
        $this->childs_container = [];
        $this->recursivHelper($this->getId(), $params);

        return $this->childs_container;
    }

    /**
     * Bereitet die ID's der Kinder vor -> getChildrenIds()
     * Funktion welche rekursiv aufgerufen wird
     *
     * @param integer $pid
     * @param array $params
     */
    protected function recursivHelper($pid, $params = [])
    {
        $ids = $this->getProject()->getChildrenIdsFrom($pid, $params);

        if (empty($ids)) {
            return;
        }

        foreach ($ids as $id) {
            $this->childs_container[] = $id;
            $this->recursivHelper($id, $params);
        }
    }

    /**
     * Gibt zurück ob Site Kinder besitzt
     *
     * @param boolean $navhide - if navhide == false, navhide must be 0
     *
     * @return integer - Anzahl der Kinder
     *
     * @throws QUI\Exception
     */
    public function hasChildren($navhide = false)
    {
        // where
        $where = '`'.$this->RELTABLE.'`.`parent` = :pid AND '.
                 '`'.$this->TABLE.'`.`deleted` = :deleted AND '.
                 '`'.$this->RELTABLE.'`.`child` = `'.$this->TABLE.'`.`id`';

        if ($navhide === false) {
            $where .= ' AND `'.$this->TABLE.'`.`nav_hide` = :nav_hide';
        }

        // prepared
        $prepared = [
            ':pid'     => $this->getId(),
            ':deleted' => 0
        ];

        if ($navhide === false) {
            $prepared[':nav_hide'] = 0;
        }

        // statement
        $Statement = QUI::getPDO()->prepare(
            'SELECT COUNT(id) AS idc
            FROM `'.$this->RELTABLE.'`, `'.$this->TABLE.'`
            WHERE '.$where.'
            LIMIT 1'
        );

        $Statement->execute($prepared);
        $result = $Statement->fetchAll(\PDO::FETCH_ASSOC);

        return $result[0]['idc'];
    }

    /**
     * Setzt das delete Flag
     *
     * @todo move to Site/Edit
     *
     * @throws QUI\Exception
     */
    public function delete()
    {
        $Project = $this->getProject();

        QUI::getEvents()->fireEvent(
            'siteDeleteBefore',
            [$this->getId(), $Project]
        );

        QUI::getRewrite()->unregisterPath($this);

        if ($this->getAttribute('id') == 1) {
            return false;
        }

        // Rekursiv alle Kinder bekommen
        $children = $this->getChildrenIdsRecursive([
            'active' => '0&1'
        ]);

        foreach ($children as $childId) {
            try {
                $Edit = new QUI\Projects\Site\Edit($Project, $childId);

                if (!$Edit->isLinked()) {
                    continue;
                }

                $pids = $Edit->getParentIds();

                foreach ($pids as $pid) {
                    if (\in_array($pid, $children)) {
                        $Edit->deleteLinked($pid);
                        continue;
                    }

                    if ($pid == $this->getId()) {
                        $Edit->deleteLinked($pid);
                    }
                }
            } catch (QUI\Exception $Exception) {
            }
        }

        // kinder neu hohlen
        $children = $this->getChildrenIdsRecursive([
            'active' => '0&1'
        ]);

        QUI::getDataBase()->update(
            $this->TABLE,
            [
                'deleted' => 1,
                'active'  => -1
            ],
            ['id' => $this->getId()]
        );


        foreach ($children as $child) {
            QUI::getDataBase()->update(
                $this->TABLE,
                [
                    'deleted' => 1,
                    'active'  => -1
                ],
                ['id' => $child]
            );

            $this->Events->fireEvent('delete', [$child]);
            QUI::getEvents()->fireEvent('siteDelete', [$child, $Project]);
        }

        // on destroy event
        $this->Events->fireEvent('delete', [$this->getId()]);

        QUI::getEvents()
            ->fireEvent('siteDelete', [$this->getId(), $Project]);

        return true;
    }

    /**
     * Überprüft ob es den Namen bei den Kindern schon gibt
     *
     * @param string $name
     *
     * @return boolean
     *
     * @throws QUI\Exception
     */
    protected function existNameInChildren($name)
    {
        $result = QUI::getDataBase()->fetch([
            'from'  => [
                $this->RELTABLE,
                $this->TABLE
            ],
            'where' => [
                $this->RELTABLE.'.parent' => $this->getId(),
                $this->TABLE.'.deleted'   => 0,
                $this->RELTABLE.'.child'  => $this->TABLE.'.id',
                $this->TABLE.'.name'      => $name
            ],
            'limit' => '1'
        ]);

        return \count($result) ? true : false;
    }

    /**
     * Gibt die URL der Seite zurück
     *
     * @param array $pathParams
     * @param array $getParams
     *
     * @return string
     *
     * @throws QUI\Exception
     */
    public function getUrl($pathParams = [], $getParams = [])
    {
        $params = $pathParams;

//        $Rewrite = QUI::getRewrite();

        if (!\is_array($params)) {
            $params = [];
        }

//        if ($rewrited) {
//            $params['site'] = $this;
//
//            return $Rewrite->getUrlFromSite($params);
//        }

        $str = 'index.php?id='.$this->getId().
               '&project='.$this->getProject()->getName().
               '&lang='.$this->getProject()->getLang();

        foreach ($params as $param => $value) {
            if (empty($value)) {
                continue;
            }

            if (!\is_string($value) && !\is_numeric($value) && !\is_bool($value)) {
                continue;
            }

            if (!\is_string($param) && !\is_numeric($param) && !\is_bool($param)) {
                continue;
            }

            $str .= '&'.$param.'='.$value;
        }

        if (!empty($getParams)) {
            foreach ($getParams as $key => $value) {
                if (!\is_string($value) && !\is_numeric($value)) {
                    unset($getParams[$key]);
                }
            }

            $str .= '&_getParams='.\urlencode(\http_build_query($getParams));
        }

        return $str;
    }

    /**
     * Return site url without host, protocol or project
     * it returns only the site location from the project
     *
     * @param array $pathParams - Path params, params in the site title
     * @param array $getParams - GET params, params as get params eq: ?param=1
     *
     * @return string
     *
     * @throws QUI\Exception
     */
    public function getLocation($pathParams = [], $getParams = [])
    {
        $separator = QUI\Rewrite::URL_PARAM_SEPARATOR;
        $params    = $pathParams;

        if (isset($params['paramAsSites']) && $params['paramAsSites']) {
            $separator = '/';
            unset($params['paramAsSites']);
        }

        if ($this->getId() == 1) {
            if (empty($params)) {
                return '';
            }

            $url = '';

            // in rewrite zeile 1420 ->_extendUrlWidthPrams wird dies auch noch gemacht
            // somit kann ein url cache aufgebaut werden
            foreach ($params as $param => $value) {
                if (\is_integer($param)) {
                    $url .= $separator.$value;
                    continue;
                }

                if ($param == 'suffix') {
                    continue;
                }

                if (\is_int($param)) {
                    $url .= $separator.$value;
                    continue;
                }

                $url .= $separator.$param.$separator.$value;
            }

            if (isset($params['suffix'])) {
                return $url.'.'.$params['suffix'];
            }

            return $url.QUI\Rewrite::getDefaultSuffix();
        }

        $url = '';

        // Url zusammen bauen
        if ($this->url != false) {
            $url = $this->url;
        } else {
            if ($this->getParentId()
                && $this->getParentId() != 1
            ) {
                // Wenn parent nicht die Startseite ist, dann muss der Pfad generiert werden
                $this->getUrlHelper($this->getId());

                foreach (\array_reverse($this->parents) as $parent) {
                    $url .= QUI\Rewrite::replaceUrlSigns($parent, true).'/'; // URL auch Slash ersetzen
                }
            }

            $this->url = $url;
        }

        $url .= QUI\Rewrite::replaceUrlSigns($this->getAttribute('name'), true);

        foreach ($params as $param => $value) {
            if (\is_integer($param)) {
                $url .= $separator.$value;
                continue;
            }

            if ($param == 'suffix') {
                continue;
            }

            if (\is_int($param)) {
                $url .= $separator.$value;
                continue;
            }

            $url .= $separator.$param.$separator.$value;
        }

        if (isset($params['suffix'])) {
            return $url.'.'.$params['suffix'];
        }

        $result = $url.QUI\Rewrite::getDefaultSuffix();

        if (empty($getParams)) {
            return $result;
        }

        return $result.'?'.\http_build_query($getParams);
    }

    /**
     * Gibt eine sprechenden URL zurück
     * DB Abfragen werden gemacht - Hier auf Performance achten
     *
     * @param array $pathParams - Parameter welche in den namen der seite eingefügt werden
     * @param array $getParams - Parameter welche an die URL angehängt werden
     *
     * @return string
     *
     * @throws QUI\Exception
     */
    public function getUrlRewritten($pathParams = [], $getParams = [])
    {
        $pathParams['site'] = $this;

        $Output = QUI::getRewrite()->getOutput();

        if (QUI::getRewrite()->getProject()->toArray() != $this->getProject()->toArray()) {
            $Output = new QUI\Output();
            $Output->setProject($this->getProject());
        }

        return $Output->getSiteUrl($pathParams, $getParams);
    }

    /**
     * @deprecated use getUrlRewritten
     *
     * @param array $pathParams - Parameter welche in den namen der seite eingefügt werden
     * @param array $getParams - Parameter welche an die URL angehängt werden
     *
     * @return string
     *
     * @throws QUI\Exception
     */
    public function getUrlRewrited($pathParams = [], $getParams = [])
    {
        return $this->getUrlRewritten($pathParams, $getParams);
    }

    /**
     * Returns a "speeking" URL with host
     *
     * @param array $pathParams
     * @param array $getParams
     * @return string
     *
     * @throws QUI\Exception
     */
    public function getUrlRewrittenWithHost($pathParams = [], $getParams = [])
    {
        $url = $this->getUrlRewritten($pathParams, $getParams);

        if (\mb_strpos($url, 'http://') !== false || \mb_strpos($url, 'https://') !== false) {
            return $url;
        }

        $host = $this->getProject()->getVHost(true, true);
        $host = \rtrim($host, '/');

        $url = \ltrim($url, '/');

        return $host.URL_DIR.$url;
    }

    /**
     * rekursiver Aufruf getUrl
     *
     * @param integer $id - Site ID
     *
     * @throws QUI\Exception
     */
    protected function getUrlHelper($id)
    {
        if ($id != $this->getId()) {
            $this->parents[] = $this->getProject()->get($id)->getAttribute('name');
        }

        $pid = $this->getProject()->getParentIdFrom($id);

        if ($pid && $pid != 1) {
            $this->getUrlHelper($pid);
        }
    }

    /**
     * Return the Parent id from the site object
     *
     * @return integer
     *
     * @throws QUI\Exception
     */
    public function getParentId()
    {
        if ($this->getId() == 1) {
            return 0;
        }

        if ($this->parent_id) {
            return $this->parent_id;
        }

        $this->parent_id = $this->getProject()->getParentIdFrom($this->getId());

        return $this->parent_id;
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
     *
     * @throws QUI\Exception
     */
    public function getParentIds()
    {
        if ($this->getId() == 1) {
            return [];
        }

        if (\is_array($this->parents_id)) {
            return $this->parents_id;
        }

        $result = QUI::getDataBase()->fetch([
            'select' => 'parent',
            'from'   => $this->RELTABLE,
            'where'  => [
                'child' => $this->getId()
            ],
            'order'  => 'oparent ASC'
        ]);

        $pids = [];

        if (!isset($result[0])) {
            return $pids;
        }

        foreach ($result as $entry) {
            $pids[] = $entry['parent'];
        }

        $this->parents_id = $pids;

        return $pids;
    }

    /**
     * Return the Parent ID List
     *
     * @return array
     *
     * @throws QUI\Exception
     */
    public function getParentIdTree()
    {
        $Project = $this->getProject();
        $parents = [];

        $search = true;
        $id     = $this->getParentId();

        while ($search) {
            try {
                $Parent    = $Project->get($id);
                $parents[] = $id;

                $id = $Parent->getParentId();

                if ($id == 0) {
                    $search = false;
                }
            } catch (QUI\Exception $Exception) {
                $search = false;
            }
        }

        return \array_reverse($parents);
    }

    /**
     * Gibt das Parent Objekt zurück
     *
     * @return Site|bool
     *
     * @throws QUI\Exception
     */
    public function getParent()
    {
        if (!$this->getParentId()) {
            return false;
        }

        return $this->getProject()->get($this->getParentId());
    }

    /**
     * Gibt alle rekursive Parents als Objekte zurück
     * Site->Parent->ParentParent->ParentParentParent
     *
     * @return array
     *
     * @throws QUI\Exception
     */
    public function getParents()
    {
        $Project = $this->getProject();
        $parents = [];

        $search = true;
        $id     = $this->getParentId();

        while ($search) {
            try {
                $Parent    = $Project->get($id);
                $parents[] = $Parent;

                $id = $Parent->getParentId();

                if ($id == 0) {
                    $search = false;
                }
            } catch (QUI\Exception $e) {
                $search = false;
            }
        }

        return \array_reverse($parents);
    }

    /**
     * Stellt die Seite wieder her
     *
     * ??? wieso hier? und nicht im trash? O.o
     *
     * @throws QUI\Exception
     */
    public function restore()
    {
        QUI::getDataBase()->update(
            $this->TABLE,
            ['deleted' => 0],
            ['id' => $this->getId()]
        );
    }

    /**
     * Zerstört die Seite
     * Die Seite wird komplett aus der DB gelöscht und auch alle Beziehungen
     * Funktioniert nur wenn die Seite gelöscht ist
     *
     * @throws QUI\Database\Exception
     * @throws QUI\Exception
     */
    public function destroy()
    {
        if ($this->getAttribute('deleted') != 1) {
            return;
        }

        /**
         *  Plugins Tabellen löschen
         */
        $DataBase = QUI::getDataBase();

        // Daten löschen
        $DataBase->exec([
            'delete' => true,
            'from'   => $this->TABLE,
            'where'  => [
                'id' => $this->getId()
            ]
        ]);

        // sich als Kind löschen
        $DataBase->exec([
            'delete' => true,
            'from'   => $this->RELTABLE,
            'where'  => [
                'child' => $this->getId()
            ]
        ]);

        // sich als parent löschen
        $DataBase->exec([
            'delete' => true,
            'from'   => $this->RELTABLE,
            'where'  => [
                'parent' => $this->getId()
            ]
        ]);

        // @todo Rechte löschen

        // Cache löschen
        $this->deleteCache();
    }

    /**
     * Canonical URL - Um doppelte Inhalt zu vermeiden
     *
     * @return string
     *
     * @throws QUI\Exception
     */
    public function getCanonical()
    {
        if ($this->getAttribute('canonical')) {
            return $this->getAttribute('canonical');
        }

        $this->setAttribute('canonical', $this->getUrlRewritten());

        return $this->getAttribute('canonical');
    }

    //region cache

    /**
     * @param string $projectName
     * @param string $projectLang
     * @param string $id
     * @return string
     */
    public static function getLinkCachePath($projectName, $projectLang, $id)
    {
        $projectPath = Project::getProjectLanguageCachePath(
            $projectName,
            $projectLang
        );

        return $projectPath.'/urlRewritten/'.$id;
    }

    /**
     * @param string $projectName
     * @param string $projectLang
     * @param string $id
     * @return string
     */
    public static function getSiteCachePath($projectName, $projectLang, $id)
    {
        $projectPath = Project::getProjectLanguageCachePath(
            $projectName,
            $projectLang
        );

        return $projectPath.'/site/'.$id;
    }

    /**
     * Clears the complete site cache
     */
    public function deleteCache()
    {
        QUI\Cache\Manager::clear($this->getCachePath());
    }

    /**
     * Create cache for the site
     */
    public function createCache()
    {
        try {
            QUI\Cache\Manager::set($this->getCacheName(), $this->encode());
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * return the cache path
     */
    public function getCachePath()
    {
        return $this->getSiteCachePath(
            $this->getProject()->getName(),
            $this->getProject()->getLang(),
            $this->getId()
        );
    }

    /**
     * Return the cache name of the site
     *
     * @return string
     */
    protected function getCacheName()
    {
        return $this->getCachePath().'/data';
    }

    //endregion

    //region permissions

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
        return QUI\Permissions\Permission::hasSitePermission(
            $permission,
            $this,
            $User
        );
    }

    /**
     * Shortcut for QUI\Permissions\Permission::checkSitePermission
     *
     * @param string $permission - name of the permission
     * @param QUI\Users\User|boolean $User - optional
     *
     * @throws QUI\Permissions\Exception
     */
    public function checkPermission($permission, $User = false)
    {
        QUI\Permissions\Permission::checkSitePermission(
            $permission,
            $this,
            $User
        );
    }

    //endregion
}
