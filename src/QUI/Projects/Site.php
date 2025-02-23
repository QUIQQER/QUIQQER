<?php

/**
 * This file contains the \QUI\Projects\Site
 */

namespace QUI\Projects;

use DOMElement;
use DOMNodeList;
use DOMXPath;
use PDO;
use QUI;
use QUI\Database\Exception;
use QUI\ExceptionStack;
use QUI\Interfaces\Users\User;
use QUI\Projects\Site\Edit;
use QUI\Utils\StringHelper as StringUtils;

use function array_keys;
use function array_pop;
use function array_reverse;
use function count;
use function defined;
use function explode;
use function file_exists;
use function http_build_query;
use function in_array;
use function is_array;
use function is_bool;
use function is_int;
use function is_integer;
use function is_numeric;
use function is_string;
use function json_decode;
use function json_encode;
use function ltrim;
use function mb_strpos;
use function md5;
use function rtrim;
use function str_replace;
use function urlencode;

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
     * $Events - manage and fires events
     */
    public QUI\Events\Event $Events;

    /**
     * Edit site
     */
    protected ?Site\Edit $Edit = null;

    protected Project $Project;

    /**
     * The site id
     */
    protected int $id;

    /**
     * parents array
     */
    protected array $parents;

    /**
     * the main parent id
     */
    protected ?int $parent_id = null;

    /**
     * Parent ids
     */
    protected ?array $parents_id = null;

    /**
     * the children list
     */
    protected array $childs_container;

    /**
     * children
     */
    protected array $children = [];

    /**
     * the site url
     */
    protected string | bool $url = false;

    /**
     * the ids of the pages in other languages
     */
    protected array $lang_ids = [];

    /**
     * the database table
     */
    protected string $TABLE = '';

    /**
     * the database relation table
     */
    protected string $RELTABLE = '';

    /**
     * the database relation language table
     */
    protected string $RELLANGTABLE = '';

    /**
     * Is the site loaded flag
     */
    protected bool $loadFlag = false;

    /**
     * is the site a link
     */
    protected int | bool $LINKED_PARENT = false;

    /**
     * tmp data from tables from the plugins
     */
    protected array $database_data = [];

    /**
     * Extend class string
     */
    protected string $extend = '';

    /**
     * Type string
     */
    protected string | false $type = false;

    /**
     * Package string
     */
    protected string | false $package = false;

    /**
     * Constructor
     *
     *
     * @throws QUI\Exception
     */
    public function __construct(Project $Project, int $id)
    {
        $this->id = $id;
        $this->Project = $Project;
        $this->Events = new QUI\Events\Event();

        if (empty($this->id)) {
            throw new QUI\Exception('Site Error; No ID given:' . $id, 700);
        }

        // DB Tables
        $this->TABLE = $Project->table();
        $this->RELTABLE = $Project->table() . '_relations';
        $this->RELLANGTABLE = QUI::getDBTableName($Project->getAttribute('name') . '_multilingual');


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
        if (defined('ADMIN') && ADMIN == 1) {
            return;
        }

        try {
            $this->decode(
                QUI\Cache\Manager::get($this->getCacheName())
            );
        } catch (QUI\Exception) {
            $this->refresh();
            $this->createCache();
        }

        // default type if no type is set
        if (!$this->getAttribute('type')) {
            $this->setAttribute('type', 'standard');
        }


        $this->Events->fireEvent('init', [$this]);
        QUI::getEvents()->fireEvent('siteInit', [$this]);
    }

    /**
     * Shortcut for QUI\Permissions\Permission::checkSitePermission
     *
     * @param string $permission - name of the permission
     * @param QUI\Users\User|boolean $User - optional
     *
     * @throws QUI\Permissions\Exception
     */
    public function checkPermission(string $permission, $User = false): void
    {
        QUI\Permissions\Permission::checkSitePermission(
            $permission,
            $this,
            $User
        );
    }

    /**
     * Setzt JSON Parameter
     *
     * @param string $params - JSON encoded string
     *
     * @throws QUI\Exception
     */
    public function decode(string $params): void
    {
        $decode = json_decode($params, true);

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
     * Return the cache name of the site
     */
    protected function getCacheName(): string
    {
        return $this->getCachePath() . '/data';
    }

    /**
     * return the cache path
     */
    public function getCachePath(): string
    {
        return self::getSiteCachePath($this->getProject()->getName(), $this->getProject()->getLang(), $this->getId());
    }

    public static function getSiteCachePath(string $projectName, string $projectLang, int | string $id): string
    {
        $projectPath = Project::getProjectLanguageCachePath(
            $projectName,
            $projectLang
        );

        return $projectPath . '/site/' . $id;
    }

    /**
     * Return the project object of the site
     */
    public function getProject(): Project
    {
        return $this->Project;
    }

    /**
     * Return the ID of the site,
     * or the ID of the sibling (linked) site of another language
     *
     * @param boolean|string $lang - optional, if it is set, then the language of the wanted linked sibling site
     *
     * @return integer
     *
     * @throws QUI\Exception
     */
    public function getId(bool | string $lang = false): int
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
        if ($lang && !in_array($lang, $availableLanguages)) {
            throw new QUI\Exception(
                [
                    'quiqqer/core',
                    'exception.project.lang.not.found',
                    ['lang' => $lang]
                ],
                404
            );
        }

        $projectName = $Project->getAttribute('name');
        $projectLang = $Project->getAttribute('lang');

        $site_table = QUI::getDBTableName($projectName . '_' . $projectLang . '_sites');
        $lang_table = QUI::getDBTableName($projectName . '_' . $lang . '_sites');
        $rel_table = QUI::getDBTableName($projectName . '_multilingual');

        $PDO = QUI::getPDO();

        $Statement = $PDO->prepare(
            '
            SELECT `' . $rel_table . '`.`' . $lang . '`
            FROM `' . $site_table . '`, `' . $lang_table . '`, `' . $rel_table . '`
            WHERE
                `' . $rel_table . '`.`' . $lang . '` = `' . $lang_table . '`.`id` AND
                `' . $rel_table . '`.`' . $projectLang . '` = `' . $site_table . '`.`id` AND
                `' . $site_table . '`.`id` = :id
               LIMIT 1;
        '
        );

        $Statement->bindValue(':id', $this->getId(), PDO::PARAM_INT);
        $Statement->execute();

        $result = $Statement->fetchAll(PDO::FETCH_ASSOC);

        if (isset($result[0][$lang])) {
            $this->lang_ids[$lang] = (int)$result[0][$lang];

            return $this->lang_ids[$lang];
        }

        return $this->id;
    }

    /**
     * Hohlt frisch die Daten aus der DB
     *
     * @throws QUI\Exception
     */
    public function refresh(): void
    {
        $this->loadFlag = false;

        $result = QUI::getDataBase()->fetch([
            'from' => $this->TABLE,
            'where' => [
                'id' => $this->getId()
            ],
            'limit' => '1'
        ]);

        if (!isset($result[0])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.site.not.found'
                ),
                705,
                [
                    'siteId' => $this->getId(),
                    'project' => $this->getProject()->getName(),
                    'lang' => $this->getProject()->getLang()
                ]
            );
        }

        $params = $result[0];

        if ($params['active'] != 1 && !defined('QUIQQER_PREVIEW')) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.site.not.found'
                ),
                705,
                [
                    'siteId' => $this->getId(),
                    'project' => $this->getProject()->getName(),
                    'lang' => $this->getProject()->getLang()
                ]
            );
        }

        if ($params['deleted'] == 1) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.site.not.found'
                ),
                705,
                [
                    'siteId' => $this->getId(),
                    'project' => $this->getProject()->getName(),
                    'lang' => $this->getProject()->getLang()
                ]
            );
        }


        // Verknüpfung hohlen
        if ($this->getId() != 1) {
            $relresult = QUI::getDataBase()->fetch([
                'from' => $this->RELTABLE,
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

        if (!empty($result[0]['extra'])) {
            $extra = json_decode($result[0]['extra'], true);

            foreach ($extra as $key => $value) {
                $this->setAttribute($key, $value);
            }

            unset($result[0]['extra']);
        }

        $this->setAttributes($result[0]);
    }

    /**
     * Create cache for the site
     */
    public function createCache(): void
    {
        try {
            QUI\Cache\Manager::set($this->getCachePath(), $this->encode());
        } catch (\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
        }
    }

    /**
     * serialize
     */
    public function encode(): string
    {
        $att = $this->getAttributes();
        $att['linked_parent'] = $this->LINKED_PARENT;
        $att['_type'] = $this->type;

        unset($att['project']);

        return json_encode($att);
    }

    /**
     * Returns the Edit Site object from this Site
     *
     * @throws QUI\Exception
     */
    public function getEdit(): ?Site\Edit
    {
        if ($this::class === Edit::class) {
            /* @var Edit $this */
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
     * Prüft ob es die Seite auch in einer anderen Sprache gibt
     *
     * @throws QUI\Exception
     */
    public function existLang(string $lang, bool $check_only_active = true): bool
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
        if (!$lang_id) {
            return false;
        }

        // Wenn es egal ist ob aktive oder inaktive
        if (!$check_only_active) {
            return true;
        }

        try {
            $_Project = QUI::getProject(
                $Project->getAttribute('name'),
                $lang
            );

            $_Project->get($lang_id);

            return true;
        } catch (QUI\Exception) {
            // nothing
        }

        return false;
    }

    /**
     * Gibt die IDs von Sprachverknüpfungen zurück
     */
    public function getLangIds(): array
    {
        $result = [];

        try {
            $Project = $this->getProject();

            $dbResult = QUI::getDataBase()->fetch([
                'from' => $Project->getAttribute('name') . '_multilingual',
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
        } catch (QUI\Exception) {
        }

        return $result;
    }

    /**
     * Returns the next site
     *
     * @throws QUI\Exception
     */
    public function nextSibling(): Site
    {
        $Parent = $this->getParent();
        $Project = $this->getProject();
        $list = $Parent->getChildrenIds();

        foreach ($list as $key => $id) {
            if ($id != $this->getId()) {
                continue;
            }

            if (!isset($list[$key + 1])) {
                continue;
            }

            return $Project->get((int)$list[$key + 1]);
        }

        throw new QUI\Exception(
            QUI::getLocale()->get('quiqqer/core', 'exception.site.no.next.sibling')
        );
    }

    /**
     * @throws QUI\Exception
     */
    public function getParent(): bool | Site
    {
        if (!$this->getParentId()) {
            return false;
        }

        return $this->getProject()->get($this->getParentId());
    }

    /**
     * @throws Exception
     * @throws QUI\Exception
     */
    public function getParentId(): int
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
     * Gibt die ID's der Kinder zurück
     * Wenn nur die ID's verwendet werden sollte dies vor getChildren verwendet werden
     *
     * @param array $params Parameter für die Childrenausgabe
     *                      $params['where']
     *                      $params['limit']
     *
     * @return array|int
     *
     * @throws QUI\Exception
     */
    public function getChildrenIds(array $params = []): array | int
    {
        $order = $this->getAttribute('order_type');

        if (isset($params['order'])) {
            $order = $params['order'];
        }

        $params['order'] = match ($order) {
            'name ASC', 'name DESC', 'title ASC', 'title DESC', 'c_date ASC', 'c_date DESC', 'd_date ASC', 'd_date DESC', 'release_from ASC', 'release_from DESC' => $order,
            default => 'order_field',
        };

        return $this->getProject()->getChildrenIdsFrom(
            $this->getId(),
            $params
        );
    }

    /**
     * Return the next x sites
     *
     * @throws QUI\Exception
     */
    public function nextSiblings(int $no): array
    {
        $result = [];

        $Parent = $this->getParent();
        $Project = $this->getProject();
        $list = $Parent->getChildrenIds();

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
                        if (defined('DEBUG_MODE')) {
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
     * @throws QUI\Exception
     */
    public function previousSibling(): Site
    {
        $Parent = $this->getParent();
        $Project = $this->getProject();
        $list = $Parent->getChildrenIds();

        foreach ($list as $key => $id) {
            if ($id != $this->getId()) {
                continue;
            }

            if (!isset($list[$key - 1])) {
                continue;
            }

            return $Project->get((int)$list[$key - 1]);
        }

        throw new QUI\Exception(
            QUI::getLocale()->get('quiqqer/core', 'exception.site.no.previous.sibling')
        );
    }

    /**
     * Returns the previous x sites
     *
     * @throws QUI\Exception
     */
    public function previousSiblings(int $no): array
    {
        $result = [];

        $Parent = $this->getParent();
        $Project = $this->getProject();
        $list = $Parent->getChildrenIds();

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
                        if (defined('DEBUG_MODE')) {
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
     * @throws QUI\Exception
     */
    public function firstChild(array $params = []): bool | Site
    {
        if (!is_array($params)) {
            $params = [];
        }

        $params['limit'] = '1';

        $children = $this->getChildren($params);

        return $children[0] ?? false;
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
    public function getChildren(array $params = [], bool $load = false): array | int
    {
        if (!is_array($params)) {
            $params = [];
        }

        // Falls kein Order übergeben wird das eingestellte Site Order
        if (!isset($params['order'])) {
            $params['order'] = match ($this->getAttribute('order_type')) {
                'name ASC', 'name DESC', 'title ASC', 'title DESC', 'c_date ASC', 'c_date DESC', 'd_date ASC', 'd_date DESC', 'release_from ASC', 'release_from DESC' => $this->getAttribute(
                    'order_type'
                ),
                default => 'order_field',
            };
        }

        if (empty($params['order'])) {
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
     * @throws QUI\Exception
     */
    public function getChild(int $id): Site
    {
        if ($id === $this->getId()) {
            throw new QUI\Exception('Page can not be a child of itself');
        }

        if (isset($this->children[$id])) {
            return $this->children[$id];
        }

        $result = QUI::getDataBase()->fetch([
            'from' => $this->RELTABLE,
            'where' => [
                'parent' => $this->getId(),
                'child' => $id
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
     * Lädt die Plugins der Seite
     *
     * @param boolean|string $plugin - Plugin welches geladen werden soll, optional, ansonsten werden alle geladen
     *
     * @return Site
     *
     * @throws QUI\Exception
     */
    public function load(bool | string $plugin = false): Site
    {
        $this->loadFlag = true;
        $cacheDbPackageCacheName = $this->getCachePath() . '/dbPackageFiles';

        try {
            $dbCache = QUI\Cache\Manager::get($cacheDbPackageCacheName);
        } catch (QUI\Exception) {
            $dbCache = [];

            $PackageManager = QUI::getPackageManager();
            $packages = $PackageManager->getInstalled();

            foreach ($packages as $package) {
                if ($plugin && $plugin != $package['name']) {
                    continue;
                }

                $dbCache[] = [
                    'dir' => OPT_DIR . $package['name'] . '/',
                    'name' => $package['name']
                ];
            }

            QUI\Cache\Manager::set($cacheDbPackageCacheName, $dbCache);
        }

        foreach ($dbCache as $dbEntry) {
            $this->loadDatabases($dbEntry['dir'], $dbEntry['name']);
        }


        // onLoad event
        $this->Events->fireEvent('load', [$this]);
        QUI::getEvents()->fireEvent('siteLoad', [$this]);

        $attributes = QUI\Projects\Site\Utils::getExtraAttributeListForSite($this);

        // load type
        $type = $this->getAttribute('type');

        if (!str_contains($type, ':')) {
            // set defaults
            foreach ($attributes as $attribute) {
                $attr = $attribute['attribute'];

                if ($this->existsAttribute($attr) === false) {
                    $this->setAttribute($attr, $attribute['default']);
                }
            }

            return $this;
        }

        // site.xml
        if (!$this->existsAttribute('nocache')) {
            $explode = explode(':', $type);
            $package = $explode[0];
            $type = $explode[1];

            $this->type = $type;
            $this->package = $package;

            $cacheName = 'quiqqer/package/quiqqer/core/type/' . md5($type) . '/nocache';

            try {
                $noCache = QUI\Cache\Manager::get($cacheName);
            } catch (QUI\Exception) {
                $noCache = 0;
                $siteXml = OPT_DIR . $package . '/' . QUI\Package\Package::SITE_XML;

                $Dom = QUI\Utils\Text\XML::getDomFromXml($siteXml);
                $XPath = new DOMXPath($Dom);
                $Types = $XPath->query('//type[@type="' . $type . '"]');

                $Type = $Types->item(0);

                if (
                    $Type instanceof DOMElement
                    && $Type->hasAttribute('cache')
                    && (int)$Type->getAttribute('cache') === 0
                ) {
                    $noCache = 1;
                }

                QUI\Cache\Manager::set($cacheName, $noCache);
            }

            $this->setAttribute('nocache', $noCache);
        }

        // set defaults
        foreach ($attributes as $attribute) {
            $attr = $attribute['attribute'];

            if ($this->existsAttribute($attr) === false) {
                $this->setAttribute($attr, $attribute['default']);
            }
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
    protected function loadDatabases(string $dir, string $package): void
    {
        $databaseXml = $dir . 'database.xml';

        // database.xml
        if (!file_exists($databaseXml)) {
            return;
        }

        $DataBaseXML = QUI\Utils\Text\XML::getDomFromXml($databaseXml);
        $projects = $DataBaseXML->getElementsByTagName('projects');

        if (!$projects->length) {
            return;
        }

        $project_name = $this->getProject()->getName();
        $project_lang = $this->getProject()->getLang();
        $siteType = $this->getAttribute('type');

        /* @var $Projects DOMElement */
        $Projects = $projects->item(0);
        $tables = $Projects->getElementsByTagName('table');

        for ($i = 0; $i < $tables->length; $i++) {
            /* @var $tables DOMNodeList */
            /* @var $Table DOMElement */
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
                $types = explode(',', $types);
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

            if (!isset($fields['suffix'])) {
                continue;
            }

            if (!isset($fields['fields'])) {
                continue;
            }

            // get data
            $tbl = QUI::getDBTableName($project_name . '_' . $project_lang . '_' . $fields['suffix']);
            $fieldList = array_keys($fields['fields']);

            $result = QUI::getDataBase()->fetch([
                'select' => $fieldList,
                'from' => $tbl,
                'where' => [
                    'id' => $this->getId()
                ],
                'limit' => 1
            ]);

            // package.package.table.attribute
            $attributePrfx = str_replace(
                '/',
                '.',
                $package . '.' . $fields['suffix']
            );

            foreach ($fieldList as $field) {
                if ($field == 'id') {
                    continue;
                }

                if (!isset($result[0][$field])) {
                    continue;
                }

                $this->setAttribute(
                    $attributePrfx . '.' . $field,
                    $result[0][$field]
                );
            }
        }
    }

    /**
     * @throws QUI\Exception
     */
    public function lastChild(array $params = []): bool | Edit | Site
    {
        if (!is_array($params)) {
            $params = [];
        }

        $params['limit'] = false;

        $result = $this->getChildrenIds($params);

        if (!count($result)) {
            return false;
        }

        $last = array_pop($result);

        try {
            return $this->getProject()->get($last);
        } catch (QUI\Exception) {
        }

        return false;
    }

    /**
     * Gibt die Kinder zurück achtet aber auf "Nicht in Navigation anzeigen" und Rechte
     *
     * @throws QUI\Exception
     */
    public function getNavigation(array $params = []): array | int
    {
        if (!is_array($params)) {
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
     * @throws QUI\Exception
     */
    public function getChildIdByName(string $name): int
    {
        $result = QUI::getDataBase()->fetch([
            'from' => [
                $this->RELTABLE,
                $this->TABLE
            ],
            'where' => [
                $this->RELTABLE . '.parent' => $this->getId(),
                $this->TABLE . '.deleted' => 0,
                $this->RELTABLE . '.child' => '`' . $this->TABLE . '.id`',
                // LIKE muss bleiben wegen _,
                // sonst werden keine Seiten mehr gefunden
                $this->TABLE . '.name' => [
                    'type' => 'LIKE',
                    'value' => str_replace('-', '_', $name)
                ]
            ],
            'limit' => 1
        ]);

        if (isset($result[0]["id"])) {
            return $result[0]["id"];
        }

        throw new QUI\Exception(
            QUI::getLocale()->get('quiqqer/core', 'exception.site.child.by.name.not.found', [
                'name' => $name
            ]),
            705
        );
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
    public function hasChildren(bool $navhide = false): int
    {
        // where
        $where = '`' . $this->RELTABLE . '`.`parent` = :pid AND ' .
            '`' . $this->TABLE . '`.`deleted` = :deleted AND ' .
            '`' . $this->RELTABLE . '`.`child` = `' . $this->TABLE . '`.`id`';

        if ($navhide === false) {
            $where .= ' AND `' . $this->TABLE . '`.`nav_hide` = :nav_hide';
        }

        // prepared
        $prepared = [
            ':pid' => $this->getId(),
            ':deleted' => 0
        ];

        if ($navhide === false) {
            $prepared[':nav_hide'] = 0;
        }

        // statement
        $Statement = QUI::getPDO()->prepare(
            'SELECT COUNT(id) AS idc
            FROM `' . $this->RELTABLE . '`, `' . $this->TABLE . '`
            WHERE ' . $where . '
            LIMIT 1'
        );

        $Statement->execute($prepared);

        $result = $Statement->fetchAll(PDO::FETCH_ASSOC);

        return $result[0]['idc'];
    }

    /**
     * Setzt das delete Flag
     *
     * @throws QUI\Exception
     * @todo move to Site/Edit
     *
     */
    public function delete(): bool
    {
        $this->checkPermission('quiqqer.projects.site.del');


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
                $Edit = new Edit($Project, $childId);

                if (!$Edit->isLinked()) {
                    continue;
                }

                $pids = $Edit->getParentIds();

                foreach ($pids as $pid) {
                    if (in_array($pid, $children)) {
                        $Edit->deleteLinked($pid);
                        continue;
                    }

                    if ($pid == $this->getId()) {
                        $Edit->deleteLinked($pid);
                    }
                }
            } catch (QUI\Exception) {
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
                'active' => -1
            ],
            ['id' => $this->getId()]
        );


        foreach ($children as $child) {
            QUI::getDataBase()->update(
                $this->TABLE,
                [
                    'deleted' => 1,
                    'active' => -1
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
     * Return ALL children ids under the site
     *
     * @param array $params - db parameter
     *
     * @throws QUI\Exception
     */
    public function getChildrenIdsRecursive(array $params = []): array
    {
        $this->childs_container = [];
        $this->recursiveHelper($this->getId(), $params);

        return $this->childs_container;
    }

    /**s
     * Bereitet die ID's der Kinder vor -> getChildrenIds()
     * Funktion welche rekursiv aufgerufen wird
     *
     * @param integer $pid
     * @param array $params
     * @throws Exception
     */
    protected function recursiveHelper(int $pid, array $params = []): void
    {
        $ids = $this->getProject()->getChildrenIdsFrom($pid, $params);

        if (empty($ids)) {
            return;
        }

        foreach ($ids as $id) {
            $this->childs_container[] = $id;
            $this->recursiveHelper($id, $params);
        }
    }

    /**
     * Prüft ob es eine Verknüpfung ist
     */
    public function isLinked(): bool | int
    {
        if ($this->LINKED_PARENT === false) {
            return false;
        }

        return $this->LINKED_PARENT;
    }

    /**
     * Gibt alle direkten Eltern Ids zurück
     *
     * Site
     * ->Parent
     * ->Parent
     * ->Parent
     *
     * @throws QUI\Exception
     */
    public function getParentIds(): array
    {
        if ($this->getId() == 1) {
            return [];
        }

        if (is_array($this->parents_id)) {
            return $this->parents_id;
        }

        $result = QUI::getDataBase()->fetch([
            'select' => 'parent',
            'from' => $this->RELTABLE,
            'where' => [
                'child' => $this->getId()
            ],
            'order' => 'oparent ASC'
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
     * Gibt die URL der Seite zurück
     *
     * @throws QUI\Exception
     */
    public function getUrl(array $params = [], array $getParams = []): string
    {
        if (!is_array($params)) {
            $params = [];
        }

        $str = 'index.php?id=' . $this->getId() .
            '&project=' . $this->getProject()->getName() .
            '&lang=' . $this->getProject()->getLang();

        foreach ($params as $param => $value) {
            if (empty($value)) {
                continue;
            }

            if (!is_string($value) && !is_numeric($value) && !is_bool($value)) {
                continue;
            }

            $str .= '&' . $param . '=' . $value;
        }

        if (!empty($getParams)) {
            foreach ($getParams as $key => $value) {
                if (is_string($value)) {
                    continue;
                }

                if (is_numeric($value)) {
                    continue;
                }

                unset($getParams[$key]);
            }

            $str .= '&_getParams=' . urlencode(http_build_query($getParams));
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
    public function getLocation(array $pathParams = [], array $getParams = []): string
    {
        $separator = QUI\Rewrite::URL_PARAM_SEPARATOR;
        $params = $pathParams;

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
                if (is_int($param)) {
                    $url .= $separator . $value;
                    continue;
                }

                if ($param === 'suffix') {
                    continue;
                }

                $url .= $separator . $param . $separator . $value;
            }

            if (isset($params['suffix'])) {
                return $url . '.' . $params['suffix'];
            }

            return $url . QUI\Rewrite::getDefaultSuffix();
        }

        $url = '';

        // Url zusammen bauen
        if ($this->url) {
            $url = $this->url;
        } else {
            if ($this->getParentId() && $this->getParentId() != 1) {
                // Wenn parent nicht die Startseite ist, dann muss der Pfad generiert werden
                $this->getUrlHelper($this->getId());

                foreach (array_reverse($this->parents) as $parent) {
                    $url .= QUI\Rewrite::replaceUrlSigns($parent, true) . '/'; // URL auch Slash ersetzen
                }
            }

            $this->url = $url;
        }

        $url .= QUI\Rewrite::replaceUrlSigns($this->getAttribute('name'), true);

        foreach ($params as $param => $value) {
            if (is_int($param)) {
                $url .= $separator . $value;
                continue;
            }

            if ($param == 'suffix') {
                continue;
            }

            $url .= $separator . $param . $separator . $value;
        }

        if (isset($params['suffix'])) {
            return $url . '.' . $params['suffix'];
        }

        $result = $url . QUI\Rewrite::getDefaultSuffix();

        if (empty($getParams)) {
            return $result;
        }

        return $result . '?' . http_build_query($getParams);
    }

    /**
     * rekursiver Aufruf getUrl
     *
     * @param integer $id - Site ID
     *
     * @throws QUI\Exception
     */
    protected function getUrlHelper(int $id): void
    {
        if ($id !== $this->getId()) {
            $this->parents[] = $this->getProject()->get($id)->getAttribute('name');
        }

        $pid = $this->getProject()->getParentIdFrom($id);

        if (!$pid) {
            return;
        }

        if ($pid == 1) {
            return;
        }

        $this->getUrlHelper($pid);
    }

    /**
     * Gibt eine sprechenden URL zurück
     * DB Abfragen werden gemacht - Hier auf Performance achten
     *
     * @param array $params - Parameter welche in den namen der seite eingefügt werden
     * @param array $getParams
     * @return string
     *
     * @throws QUI\Exception
     * @throws ExceptionStack
     */
    public function getUrlRewritten(array $params = [], array $getParams = []): string
    {
        $eventResult = false;

        $this->Events->fireEvent('getUrlRewritten', [$this, &$eventResult]);
        QUI::getEvents()->fireEvent('siteGetUrlRewritten', [$this, &$eventResult]);

        // @phpstan-ignore-next-line ($eventResult is passed by reference to events and thus may not always be false)
        if (!empty($eventResult)) {
            return $eventResult;
        }

        $params['site'] = $this;

        $Output = QUI::getRewrite()->getOutput();

        if (QUI::getRewrite()->getProject()->toArray() != $this->getProject()->toArray()) {
            $Output = new QUI\Output();
            $Output->setProject($this->getProject());
        }

        return $Output->getSiteUrl($params, $getParams);
    }

    /**
     * Returns a "speaking" URL with host
     *
     * @throws QUI\Exception
     */
    public function getUrlRewrittenWithHost(array $pathParams = [], array $getParams = []): string
    {
        $url = $this->getUrlRewritten($pathParams, $getParams);

        if (mb_strpos($url, 'http://') !== false || mb_strpos($url, 'https://') !== false) {
            return $url;
        }

        $host = $this->getProject()->getVHost(true, true);
        $host = rtrim($host, '/');

        $url = ltrim($url, '/');

        return $host . URL_DIR . $url;
    }

    /**
     * Return the Parent ID List
     *
     * @throws QUI\Exception
     */
    public function getParentIdTree(): array
    {
        $Project = $this->getProject();
        $parents = [];

        $search = true;
        $id = $this->getParentId();

        while ($search) {
            try {
                $Parent = $Project->get($id);
                $parents[] = $id;

                $id = $Parent->getParentId();

                if ($id == 0) {
                    $search = false;
                }
            } catch (QUI\Exception) {
                $search = false;
            }
        }

        return array_reverse($parents);
    }

    //region cache

    /**
     * Gibt alle rekursive Parents als Objekte zurück
     * Site->Parent->ParentParent->ParentParentParent
     *
     *
     * @throws QUI\Exception
     */
    public function getParents(): array
    {
        $Project = $this->getProject();
        $parents = [];

        $search = true;
        $id = $this->getParentId();

        while ($search) {
            try {
                $Parent = $Project->get($id);
                $parents[] = $Parent;

                $id = $Parent->getParentId();

                if ($id == 0) {
                    $search = false;
                }
            } catch (QUI\Exception) {
                $search = false;
            }
        }

        return array_reverse($parents);
    }

    /**
     * Stellt die Seite wieder her
     *
     * ??? wieso hier? und nicht im trash? O.o
     *
     * @throws QUI\Exception
     */
    public function restore(): void
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
    public function destroy(): void
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
            'from' => $this->TABLE,
            'where' => [
                'id' => $this->getId()
            ]
        ]);

        // sich als Kind löschen
        $DataBase->exec([
            'delete' => true,
            'from' => $this->RELTABLE,
            'where' => [
                'child' => $this->getId()
            ]
        ]);

        // sich als parent löschen
        $DataBase->exec([
            'delete' => true,
            'from' => $this->RELTABLE,
            'where' => [
                'parent' => $this->getId()
            ]
        ]);

        // @todo Rechte löschen

        // Cache löschen
        $this->deleteCache();
    }

    /**
     * Clears the complete site cache
     */
    public function deleteCache(): void
    {
        QUI\Cache\Manager::clear($this->getCachePath());

        $Project = $this->getProject();

        // Clear the URL caches - required when the URL changes (e.g. when moving a site)
        // See quiqqer/core#1129 for more information
        QUI::getRewrite()->getOutput()->removeRewrittenUrlCache($this);
        QUI\Cache\Manager::clear(
            QUI\Projects\Site::getLinkCachePath(
                $Project->getName(),
                $Project->getLang(),
                $this->getId()
            )
        );
    }

    public static function getLinkCachePath(string $projectName, string $projectLang, $id): string
    {
        $projectPath = Project::getProjectLanguageCachePath(
            $projectName,
            $projectLang
        );

        return $projectPath . '/urlRewritten/' . $id;
    }

    /**
     * Canonical URL - Um doppelte Inhalt zu vermeiden
     *
     * @throws QUI\Exception
     */
    public function getCanonical(): string
    {
        if ($this->getAttribute('meta.canonical')) {
            $this->setAttribute('canonical', $this->getAttribute('meta.canonical'));

            return $this->getAttribute('meta.canonical');
        }

        if ($this->getAttribute('canonical')) {
            return $this->getAttribute('canonical');
        }

        $this->setAttribute('canonical', $this->getUrlRewritten());

        return $this->getAttribute('canonical');
    }

    //endregion
    //region permissions
    /**
     * Shortcut for QUI\Permissions\Permission::hasSitePermission
     */
    public function hasPermission(string $permission, null | QUI\Interfaces\Users\User $User = null): bool | int
    {
        return QUI\Permissions\Permission::hasSitePermission(
            $permission,
            $this,
            $User
        );
    }

    /**
     * Überprüft ob es den Namen bei den Kindern schon gibt
     *
     * @throws QUI\Exception
     */
    protected function existNameInChildren(string $name): bool
    {
        $result = QUI::getDataBase()->fetch([
            'from' => [
                $this->RELTABLE,
                $this->TABLE
            ],
            'where' => [
                $this->RELTABLE . '.parent' => $this->getId(),
                $this->TABLE . '.deleted' => 0,
                $this->RELTABLE . '.child' => $this->TABLE . '.id',
                $this->TABLE . '.name' => $name
            ],
            'limit' => '1'
        ]);

        return (bool)count($result);
    }

    //endregion
}
