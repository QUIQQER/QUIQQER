<?php

/**
 * This file contains the \QUI\Projects\Project
 */

namespace QUI\Projects;

use DOMElement;
use Exception;
use PDO;
use PDOException;
use QUI;
use QUI\Groups\Group;
use QUI\Permissions\Permission;
use QUI\Projects\Site\Edit;
use QUI\Projects\Site\PermissionDenied;
use QUI\Users\User;
use QUI\Utils\Text\XML;

use function array_merge;
use function array_reverse;
use function array_unique;
use function date;
use function defined;
use function dirname;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function in_array;
use function is_array;
use function is_dir;
use function is_string;
use function is_writable;
use function json_encode;
use function str_replace;
use function strlen;
use function substr;

use const USR_DIR;

/**
 * A project
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 *
 * @errorcodes
 * <ul>
 * <li>
 * <li>801 - Project Create Error: name must longer than two signs</li>
 * <li>802 - Project Create Error: not allowed signs</li>
 * <li>803 - Project Error: Project has no languages</li>
 * <li>804 - Project Error: Project not found</li>
 * <li>805 - Project Error: Project has no default language</li>
 * <li>806 - Project Error: Project language not found</li>
 * </ul>
 */
class Project implements \Stringable
{
    /**
     * caching files
     */
    protected array $cache_files = [];

    protected ?Media $Media = null;

    /**
     * The project site table
     */
    private string $TABLE;

    /**
     * The project site relation table
     */
    private string $RELTABLE;

    /**
     * The project site relation language table
     */
    private string $RELLANGTABLE;

    /**
     * configuration
     */
    private array $config;

    /**
     * default language
     */
    private string $default_lang;

    /**
     * All languages of the project
     */
    private array $langs;

    /**
     * loaded sites
     */
    private array $children = [];

    /**
     * first child
     */
    private Site|QUI\Projects\Site\Edit|null $firstchild = null;

    /**
     * Constructor
     *
     * @param string $name - Name of the Project
     * @param boolean|string $lang - (optional) Language of the Project - optional
     * @param boolean|string $template - (optional) Template of the Project
     *
     * @throws QUI\Exception
     */
    public function __construct(
        private string $name,
        private bool|string $lang = false,
        private bool|string $template = false
    ) {
        try {
            $this->refresh();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);

            $this->name = '';
            $this->lang = '';
            $this->template = '';

            throw $Exception;
        }
    }

    /**
     * Refresh the config
     *
     * @throws QUI\Exception
     */
    public function refresh(): void
    {
        $config = Manager::getConfig()->toArray();

        $name = $this->name;
        $lang = (string)$this->lang;
        $template = (string)$this->template;

        if (!isset($config[$name])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.project.not.found',
                    ['name' => $name]
                ),
                804
            );
        }

        $this->config = $config[$name];
        $this->name = $name;

        if (!isset($this->config['langs'])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.project.has.no.langs'
                ),
                803
            );
        }

        $this->langs = explode(',', $this->config['langs']);

        // Default Lang
        if (!isset($this->config['default_lang'])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.project.lang.no.default'
                ),
                805
            );
        }

        $this->default_lang = $this->config['default_lang'];

        // Sprache
        if ($lang) {
            if (!in_array($lang, $this->langs)) {
                throw new QUI\Exception(
                    QUI::getLocale()->get(
                        'quiqqer/core',
                        'exception.project.lang.not.found',
                        [
                            'lang' => $lang
                        ]
                    ),
                    806
                );
            }

            $this->lang = $lang;
        } else {
            if (!isset($this->config['default_lang'])) {
                throw new QUI\Exception(
                    QUI::getLocale()->get(
                        'quiqqer/core',
                        'exception.project.lang.no.default'
                    ),
                    805
                );
            }

            $this->lang = $this->config['default_lang'];
        }

        // Template
        if (empty($template)) {
            $this->template = $config[$name]['template'];
        } else {
            $this->template = $template;
        }

        // defaults
        if (!isset($this->config['adminSitemapMax']) || !$this->config['adminSitemapMax']) {
            $this->config['adminSitemapMax'] = 20;
        }

        // vhosts
        $vhosts = QUI::vhosts();

        foreach ($vhosts as $host => $vhost) {
            if ((int)$host) {
                // falls 404 oder 301 oder sonst irgendein apache code eingetragen ist,
                //dann nicht weiter
                continue;
            }

            if (!isset($vhost['project'])) {
                continue;
            }

            if (!isset($vhost['lang'])) {
                continue;
            }

            if ($vhost['lang'] != $this->lang) {
                continue;
            }

            if ($vhost['project'] != $this->name) {
                continue;
            }

            $this->config['vhost'] = $host;
        }

        // tabellen setzen
        $this->TABLE = QUI_DB_PRFX . $this->name . '_' . $this->lang . '_sites';
        $this->RELTABLE = $this->TABLE . '_relations';
        $this->RELLANGTABLE = QUI_DB_PRFX . $this->name . '_multilingual';


        // cache files
        // @todo move to the cache
        $this->cache_files = [
            'types' => 'projects.' . $this->getAttribute('name') . '.types',
            'gtypes' => 'projects.' . $this->getAttribute('name') . '.globaltypes'
        ];
    }

    /**
     * Project Array Notation
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getAttribute('name'),
            'lang' => $this->getAttribute('lang')
        ];
    }

    /**
     * Name of the project
     *
     * @param string $att -
     *                    name = Name des Projectes
     *                    lang = Aktuelle Sprache
     *                    db_table = Standard Datebanktabelle, please use this->table()
     *
     * @return string|int|bool|array
     */
    public function getAttribute(string $att): string|int|bool|array
    {
        return match ($att) {
            "name" => $this->getName(),
            "lang" => $this->getLang(),
            "e_date" => $this->getLastEditDate(),
            "config" => $this->config,
            "default_lang" => $this->default_lang,
            "langs" => $this->langs,
            "template" => $this->template,
            "db_table" => $this->name . '_' . $this->lang . '_sites',
            "media_table" => $this->name . '_de_media',
            default => false,
        };
    }

    /**
     * Return the project name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Return the project lang
     */
    public function getLang(): string
    {
        return $this->lang;
    }

    /**
     * Return the last edit date in the project
     */
    public function getLastEditDate(): int
    {
        try {
            return (int)QUI\Cache\Manager::get($this->getEDateCacheName());
        } catch (QUI\Exception) {
        }

        return 0;
    }

    /**
     * Return a site
     *
     * @param integer $id - ID of the Site
     *
     * @return Site|Site\Edit
     * @throws QUI\Exception
     */
    public function get(int $id): Site\Edit|Site
    {
        if (
            (defined('ADMIN') && ADMIN == 1)
            || (defined('QUIQQER_CONSOLE') && QUIQQER_CONSOLE == 1)
        ) {
            return new Site\Edit($this, $id);
        }

        if (isset($this->children[$id])) {
            return $this->children[$id];
        }

        try {
            $Site = new Site($this, $id);
        } catch (QUI\Exception $Exception) {
            if ($Exception->getCode() !== 403) {
                throw $Exception;
            }

            $Site = new PermissionDenied($this, $id);
        }

        $this->children[$id] = $Site;
        return $Site;
    }

    protected function getEDateCacheName(): string
    {
        return $this->getCachePath() . '/edate/';
    }

    /**
     * Return the project cache path
     */
    public function getCachePath(): string
    {
        return self::getProjectCachePath($this->getName());
    }

    /**
     * Return the cache path for a project (without language)
     */
    public static function getProjectCachePath(string $projectName): string
    {
        return 'quiqqer/projects/' . $projectName;
    }

    /**
     * Gibt die gesuchte Einstellung vom Projekt zurück
     *
     * @param boolean|string $name - name of the config, default = false, returns complete configs
     *
     * @return mixed
     */
    public function getConfig(bool|string $name = false): mixed
    {
        if (!$name) {
            return $this->config;
        }

        if (isset($this->config[$name])) {
            return $this->config[$name];
        }

        // default Werte
        return match ($name) {
            "sheets" => 5,
            "archive" => 10,
            default => false,
        };
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        unset($this->config);
    }

    public function __toString(): string
    {
        return 'Object ' . $this::class . '(' . $this->name . ',' . $this->lang . ')';
    }

    public function toJSON(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Return all languages in the project
     */
    public function getLanguages(): array
    {
        $languages = $this->getAttribute('langs');

        if (is_string($languages)) {
            $languages = explode(',', $languages);
        }

        if (!is_array($languages)) {
            $languages = [];
        }

        return $languages;
    }

    /**
     * Return the project title
     * Locale->get('project/NAME', 'title') or getName()
     */
    public function getTitle(): string
    {
        $group = 'project/' . $this->getName();

        if (QUI::getLocale()->exists($group, 'title')) {
            return QUI::getLocale()->get($group, 'title');
        }

        return $this->getName();
    }

    /**
     * Durchsucht das Projekt nach Seiten
     *
     * @param string $search - Suchwort
     * @param boolean|array $select - (optional) in welchen Feldern gesucht werden soll
     *                                array('name', 'title', 'short', 'content')
     *
     * @return array
     */
    public function search(string $search, bool|array $select = false): array
    {
        $query = 'SELECT id FROM ' . $this->table();
        $where = ' WHERE name LIKE :search';

        $allowed = ['id', 'name', 'title', 'short', 'content'];

        if (is_array($select)) {
            $where = ' WHERE (';

            foreach ($select as $field) {
                if (!in_array($field, $allowed)) {
                    continue;
                }

                $where .= ' ' . $field . ' LIKE :search OR ';
            }

            $where = substr($where, 0, -4) . ')';

            if (strlen($where) < 6) {
                $where = ' WHERE name LIKE :search';
            }
        }

        $query = $query . $where . ' AND deleted = 0 LIMIT 0, 50';

        $PDO = QUI::getDataBase()->getPDO();
        $Statement = $PDO->prepare($query);

        $Statement->bindValue(':search', '%' . $search . '%');
        $Statement->execute();

        $dbResult = $Statement->fetchAll(PDO::FETCH_ASSOC);
        $result = [];

        foreach ($dbResult as $entry) {
            try {
                $result[] = $this->get($entry['id']);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addDebug($Exception->getMessage());
            }
        }

        return $result;
    }

    public function table(): string
    {
        return QUI::getDBTableName($this->name . '_' . $this->lang . '_sites');
    }

    public function hasVHost(): bool
    {
        $Hosts = QUI::getRewrite()->getVHosts();

        foreach ($Hosts as $url => $params) {
            if ($url == 404 || $url == 301) {
                continue;
            }

            if (empty($params['project'])) {
                continue;
            }

            if (empty($params['lang'])) {
                continue;
            }

            $project = $params['project'];

            if ($project != $this->getName()) {
                continue;
            }

            if (empty($params[$this->getLang()])) {
                return false;
            }

            return true;
        }

        return false;
    }

    //region cache

    /**
     * Gibt den allgemein gültigen Host vom Projekt zurück
     */
    public function getHost(): string
    {
        if (isset($this->config['vhost'])) {
            return $this->config['vhost'];
        }

        if (isset($this->config['host'])) {
            return $this->config['host'];
        }

        $host = QUI::conf('globals', 'host');

        if (!empty($host)) {
            return $host;
        }

        return $_SERVER['HTTP_HOST'];
    }

    /**
     * Get the Trash from the Project
     */
    public function getTrash(): Trash
    {
        return new Trash($this);
    }

    /**
     * Gibt alle Attribute vom Projekt zurück
     */
    public function getAllAttributes(): array
    {
        return [
            'config' => $this->config,
            'lang' => $this->lang,
            'langs' => $this->langs,
            'name' => $this->name,
            'sheets' => $this->getConfig('sheets'),
            'archive' => $this->getConfig('archive')
        ];
    }

    /**
     * Erste Seite des Projektes
     *
     * @$pluginload boolean
     *
     * @throws QUI\Exception
     */
    public function firstChild(): Site\Edit|Site
    {
        if ($this->firstchild === null) {
            $this->firstchild = $this->get(1);
        }

        return $this->firstchild;
    }

    /**
     * Clears the project cache path
     *
     * @param boolean $link - Clears the site link cache
     * @param boolean $site - Clears the site cache
     *
     * @todo muss überarbeitet werden
     */
    public function clearCache(bool $link = true, bool $site = true): void
    {
        $cachePath = $this->getCacheLanguagePath();

        if ($link === true) {
            QUI\Cache\Manager::clear($cachePath . '/urlRewritten');
        }

        if ($site === true) {
            QUI\Cache\Manager::clear($cachePath . '/site');
        }

        foreach ($this->cache_files as $cache) {
            QUI\Cache\Manager::clear($cache);
        }
    }

    //endregion

    /**
     * Return the project cache path with the language path
     */
    public function getCacheLanguagePath(): string
    {
        return self::getProjectLanguageCachePath($this->getName(), $this->getLang());
    }

    /**
     * Return the cache path with the language path for a project
     */
    public static function getProjectLanguageCachePath(string $projectName, string $projectLang): string
    {
        return self::getProjectCachePath($projectName) . '/' . $projectLang;
    }

    /**
     * Return all available layouts
     */
    public function getLayouts(): array
    {
        $VHosts = new QUI\System\VhostManager();
        $vhostList = $VHosts->getHostsByProject($this->getName());
        $template = OPT_DIR . $this->getAttribute('template');

        $siteXMLs = [
            $template . '/site.xml'
        ];

        // inheritance
        try {
            $Package = QUI::getPackage($this->getAttribute('template'));
            $Parent = $Package->getTemplateParent();
            $siteXml = false;

            if ($Parent) {
                $siteXml = $Parent->getXMLFilePath('site.xml');
            }

            if ($siteXml) {
                $siteXMLs[] = $siteXml;
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        foreach ($vhostList as $vhost) {
            $hostData = $VHosts->getVhost($vhost);

            if (!empty($hostData['template'])) {
                $siteXMLs[] = OPT_DIR . $hostData['template'] . '/site.xml';
            }
        }

        $result = [];
        $_resTemp = [];
        $siteXMLs = array_unique($siteXMLs);

        foreach ($siteXMLs as $siteXML) {
            $layouts = XML::getLayoutsFromXml($siteXML);

            foreach ($layouts as $Layout) {
                /* @var $Layout DOMElement */
                if (isset($_resTemp[$Layout->getAttribute('type')])) {
                    continue;
                }

                $data = [
                    'type' => $Layout->getAttribute('type'),
                    'title' => '',
                    'description' => '',
                    'image' => ''
                ];

                $_resTemp[$Layout->getAttribute('type')] = true;

                $title = $Layout->getElementsByTagName('title');
                $desc = $Layout->getElementsByTagName('description');

                if ($title->length) {
                    $data['title'] = QUI\Utils\DOM::getTextFromNode($title->item(0));
                }

                if ($desc->length) {
                    $data['description'] = QUI\Utils\DOM::getTextFromNode($desc->item(0));
                }

                if ($Layout->getAttribute('image')) {
                    $path = dirname($siteXML);
                    $path = str_replace(OPT_DIR, '', $path);

                    $file = OPT_DIR . $path . '/' . $Layout->getAttribute('image');

                    if (file_exists($file)) {
                        $data['image'] = URL_OPT_DIR . $path . '/' . $Layout->getAttribute('image');
                    }
                }

                $result[] = $data;
            }
        }


        return $result;
    }

    /**
     * VHost zurück geben
     *
     * @param boolean $with_protocol - Mit oder ohne http -> standard = ohne
     * @param boolean $ssl - with or without ssl
     *
     * @return boolean|string
     */
    public function getVHost(bool $with_protocol = false, bool $ssl = false): bool|string
    {
        if (QUI::conf("webserver", "forceHttps")) {
            $ssl = true;
        }

        $Hosts = QUI::getRewrite()->getVHosts();

        foreach ($Hosts as $url => $params) {
            if ($url == 404 || $url == 301) {
                continue;
            }

            if (!isset($params['project'])) {
                continue;
            }

            if (
                $params['project'] == $this->getAttribute('name')
                && $params['lang'] == $this->getAttribute('lang')
            ) {
                if ($ssl && !empty($params['httpshost'])) {
                    return $with_protocol ? 'https://' . $params['httpshost'] : $params['httpshost'];
                }

                if (QUI::conf("webserver", "forceHttps")) {
                    return $with_protocol ? 'https://' . $url : $url;
                }

                return $with_protocol ? 'https://' . $url : $url;
            }
        }

        try {
            $StandardProject = QUI::getProjectManager()->getStandard();
        } catch (Exception $exception) {
            QUI\System\Log::addError($exception->getMessage());
            return HOST;
        }

        if ($StandardProject->getName() === $this->getName()) {
            return HOST;
        }

        return HOST . '/' . QUI\Rewrite::URL_PROJECT_CHARACTER . $this->getName() . '/';
    }

    /**
     * Return the children ids from a site
     *
     * @param integer $parentid - The parent site ID
     * @param array $params - extra db statements, like order, where, count, limit
     *
     * @return array|integer
     * @throws QUI\Database\Exception
     */
    public function getChildrenIdsFrom(int $parentid, array $params = []): array|int
    {
        $where_1 = [
            $this->RELTABLE . '.parent' => $parentid,
            $this->TABLE . '.deleted' => 0,
            $this->TABLE . '.active' => 1,
            $this->RELTABLE . '.child' => '`' . $this->TABLE . '.id`'
        ];

        if (isset($params['active']) && $params['active'] === '0&1') {
            $where_1 = [
                $this->RELTABLE . '.parent' => $parentid,
                $this->TABLE . '.deleted' => 0,
                $this->RELTABLE . '.child' => '`' . $this->TABLE . '.id`'
            ];
        }

        if (isset($params['where']) && is_array($params['where'])) {
            $where = array_merge($where_1, $params['where']);
        } elseif (isset($params['where']) && is_string($params['where'])) {
            // @todo where als param string
            QUI\System\Log::addDebug(
                'Project->getChildrenIdsFrom WIRD NICHT verwendet' . $params['where']
            );

            $where = $where_1;
        } else {
            $where = $where_1;
        }

        $order = $this->TABLE . '.order_field';

        if (isset($params['order'])) {
            if (str_contains($params['order'], '.')) {
                $order = $this->TABLE . '.' . $params['order'];
            } else {
                $order = $params['order'];
            }
        }

        if ($order === 'manuell') {
            $order = 'order_field';
        }

        $result = QUI::getDataBase()->fetch([
            'select' => $this->TABLE . '.id',
            'count' => isset($params['count']) ? 'count' : false,
            'from' => [
                $this->RELTABLE,
                $this->TABLE
            ],
            'order' => $order,
            'limit' => $params['limit'] ?? false,
            'where' => $where
        ]);

        if (isset($params['count'])) {
            return (int)$result[0]['count'];
        }

        $ids = [];

        foreach ($result as $entry) {
            if (isset($entry['id'])) {
                $ids[] = (int)$entry['id'];
            }
        }

        return $ids;
    }

    /**
     * Returns the parent id from a site
     *
     * @throws QUI\Database\Exception
     * @deprecated
     */
    public function getParentId(int $id): int
    {
        return $this->getParentIdFrom($id);
    }

    /**
     * Returns the parent id from a site
     *
     * @param integer $id - Child id
     *
     * @return integer ID of the Parent
     * @throws QUI\Database\Exception
     */
    public function getParentIdFrom(int $id): int
    {
        if ($id <= 0) {
            return 0;
        }

        $result = QUI::getDataBase()->fetch([
            'select' => 'parent',
            'from' => $this->RELTABLE,
            'where' => [
                'child' => $id
            ],
            'order' => 'oparent ASC',
            'limit' => '1'
        ]);

        if (isset($result[0]) && $result[0]['parent']) {
            return (int)$result[0]['parent'];
        }

        return 0;
    }

    /**
     * Gibt alle Parent IDs zurück
     *
     * @param integer $id - child id
     * @param boolean $reverse - revers the result
     *
     * @throws QUI\Database\Exception
     */
    public function getParentIds(int $id, bool $reverse = false): array
    {
        $ids = [];
        $pid = $this->getParentIdFrom($id);

        while ($pid != 1) {
            $ids[] = $pid;
            $pid = $this->getParentIdFrom($pid);
        }

        if ($reverse) {
            $ids = array_reverse($ids);
        }

        return $ids;
    }

    /**
     * Alle Seiten bekommen
     *
     * @return array|integer - if count is given, return is an integer, otherwise an array
     * @throws QUI\Database\Exception
     */
    public function getSites(bool|array $params = false): array|int
    {
        // Falls kein Query dann alle Seiten hohlen
        // @notice - Kann performancefressend sein

        $s = $this->getSitesIds($params);

        if (empty($s)) {
            return [];
        }

        if (isset($params['count'])) {
            if (isset($s[0]['count'])) {
                return $s[0]['count'];
            }

            return 0;
        }

        $sites = [];

        foreach ($s as $site_id) {
            try {
                $sites[] = $this->get((int)$site_id['id']);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return $sites;
    }

    /**
     * Ids von bestimmten Seiten bekommen
     *
     * @throws QUI\Database\Exception
     * @todo Muss mal echt überarbeitet werden, bad code
     */
    public function getSitesIds(array $params = []): array
    {
        if (empty($params) || !is_array($params)) {
            // Falls kein Query dann alle Seiten hohlen
            // @notice - Kann performancefressend sein
            return QUI::getDataBase()->fetch([
                'select' => 'id',
                'from' => $this->table()
            ]);
        }

        $order = 'order_field';

        if (isset($params['order'])) {
            switch ($params['order']) {
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
                    $order = $params['order'];
                    break;

                case 'manuell':
                default:
                    $order = 'order_field';
                    break;
            }
        }

        $params['order'] = $order;

        $sql = [
            'select' => 'id',
            'from' => $this->table()
        ];

        if (isset($params['where'])) {
            $sql['where'] = $params['where'];
        }

        if (isset($params['where_or'])) {
            $sql['where_or'] = $params['where_or'];
        }

        // Aktivflag abfragen
        if (isset($sql['where']) && is_array($sql['where']) && !isset($sql['where']['active'])) {
            $sql['where']['active'] = 1;
        } elseif (isset($sql['where']['active']) && $sql['where']['active'] == -1) {
            unset($sql['where']['active']);
        } elseif (isset($sql['where']) && is_string($sql['where'])) {
            $sql['where'] .= ' AND active = 1';
        } elseif (!isset($sql['where']['active'])) {
            $sql['where']['active'] = 1;
        }

        // Deletedflag abfragen
        if (
            isset($sql['where']) && is_array($sql['where'])
            && !isset($sql['where']['deleted'])
        ) {
            $sql['where']['deleted'] = 0;
        } elseif (
            isset($sql['where']['deleted'])
            && $sql['where']['deleted'] == -1
        ) {
            unset($sql['where']['deleted']);
        } elseif (is_string($sql['where'])) {
            $sql['where'] .= ' AND deleted = 0';
        } elseif (!isset($sql['where']['deleted'])) {
            $sql['where']['deleted'] = 0;
        }

        if (isset($params['count'])) {
            $sql['count'] = [
                'select' => 'id',
                'as' => 'count'
            ];

            unset($sql['select']);
        } else {
            $sql['select'] = 'id';
        }

        if (isset($params['limit'])) {
            $sql['limit'] = $params['limit'];
        }

        $sql['order'] = $params['order'];
        
        if (isset($params['debug'])) {
            $sql['debug'] = true;

            QUI\System\Log::writeRecursive($sql);
        }

        if (isset($params['where_relation'])) {
            $sql['where_relation'] = $params['where_relation'];
        }

        return QUI::getDataBase()->fetch($sql);
    }

    /**
     * Execute the project setup
     *
     * @param array $setupOptions - options for the package setup
     *                              -> [executePackagesSetup => true]
     *
     * @throws Exception
     * @throws QUI\Exception
     * @throws QUI\ExceptionStack
     * @throws QUI\DataBase\Exception
     */
    public function setup(array $setupOptions = []): void
    {
        if (!isset($setupOptions['executePackagesSetup'])) {
            $setupOptions['executePackagesSetup'] = true;
        }


        QUI::getEvents()->fireEvent('projectSetupBegin', [$this]);

        $DataBase = QUI::getDataBase();
        $Table = $DataBase->table();
        $User = QUI::getUserBySession();

        // multi lingual table
        $multiLingualTable = QUI_DB_PRFX . $this->name . '_multilingual';

        $Table->addColumn($multiLingualTable, [
            'id' => 'bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY'
        ]);


        foreach ($this->langs as $lang) {
            $table = QUI_DB_PRFX . $this->name . '_' . $lang . '_sites';

            $Table->addColumn($table, [
                'id' => 'bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'name' => 'varchar(255) NOT NULL',
                'title' => 'tinytext NULL',
                'short' => 'text NULL',
                'content' => 'longtext NULL',
                'type' => 'varchar(255) DEFAULT NULL',
                'layout' => 'varchar(255) DEFAULT NULL',
                'active' => 'tinyint(1) NOT NULL DEFAULT 0',
                'deleted' => 'tinyint(1) NOT NULL DEFAULT 0',
                'c_date' => 'timestamp NULL DEFAULT NULL',
                'e_date' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
                'c_user' => 'varchar(50) DEFAULT NULL',
                'e_user' => 'varchar(50) DEFAULT NULL',
                'nav_hide' => 'tinyint(1) NOT NULL DEFAULT 0',
                'order_type' => 'varchar(255) NULL',
                'order_field' => 'bigint(20) NULL',
                'extra' => 'text NULL',
                'c_user_ip' => 'varchar(40) NULL',
                'image_emotion' => 'text NULL',
                'image_site' => 'text NULL',
                'release_from' => 'DATETIME NULL DEFAULT NULL',
                'release_to' => 'DATETIME NULL DEFAULT NULL',
                'auto_release' => 'int(1) DEFAULT 0'
            ]);

            // fix for old tables
            $DataBase->getPDO()->exec(
                "ALTER TABLE `$table` 
                CHANGE `name` `name` VARCHAR(255) NOT NULL,
                CHANGE `order_type` `order_type` VARCHAR(255) NULL DEFAULT NULL,
                CHANGE `release_from` `release_from` DATETIME NULL DEFAULT NULL,
                CHANGE `release_to` `release_to` DATETIME NULL DEFAULT NULL,
                CHANGE `type` `type` VARCHAR(255) NULL DEFAULT NULL;"
            );


            // Patch mysql strict
            try {
                $DataBase->getPDO()->exec(
                    "
                    UPDATE `$table` 
                    SET release_from = null 
                    WHERE 
                        release_from = '0000-00-00 00:00:00' OR 
                        release_from = '';

                    UPDATE `$table` 
                    SET release_to = null 
                    WHERE 
                        release_to = '0000-00-00 00:00:00' OR
                        release_to = '';
                "
                );
            } catch (PDOException) {
            }

            if (!$Table->issetPrimaryKey($table, 'id')) {
                $Table->setPrimaryKey($table, 'id');
            }

            $Table->setIndex($table, 'name');
            $Table->setIndex($table, 'active');
            $Table->setIndex($table, 'deleted');
            $Table->setIndex($table, 'order_field');
            $Table->setIndex($table, 'type');
            $Table->setIndex($table, 'c_date');
            $Table->setIndex($table, 'e_date');


            // create first site -> id 1 if not exist
            $firstChildResult = $DataBase->fetch([
                'from' => $table,
                'where' => [
                    'id' => 1
                ],
                'limit' => 1
            ]);

            if (!isset($firstChildResult[0])) {
                $DataBase->insert($table, [
                    'id' => 1,
                    'name' => 'start',
                    'title' => 'Start',
                    'type' => 'standard',
                    'c_date' => date('Y-m-d H:i:s'),
                    'c_user' => $User->getUUID(),
                    'c_user_ip' => QUI\Utils\System::getClientIP()
                ]);
            }

            // Beziehungen
            $table = QUI_DB_PRFX . $this->name . '_' . $lang . '_sites_relations';

            $Table->addColumn($table, [
                'parent' => 'bigint(20)',
                'child' => 'bigint(20)',
                'oparent' => 'bigint(20)'
            ]);

            $Table->setIndex($table, 'parent');
            $Table->setIndex($table, 'child');

            // multilingual field
            $Table->addColumn(
                $multiLingualTable,
                [$lang => 'bigint(20)']
            );

            // Translation Setup
            QUI\Translator::addLang($lang);
        }

        // Media Setup
        $this->getMedia()->setup();

        // read xml files
        $dir = USR_DIR . $this->name . '/';

        // @todo only for project
        QUI\Update::importDatabase($dir . 'database.xml');
        QUI\Update::importTemplateEngines($dir . 'engines.xml');
        QUI\Update::importEditors($dir . 'wysiwyg.xml');
        QUI\Update::importMenu($dir . 'menu.xml');
        QUI\Update::importPermissions(
            $dir . 'permissions.xml',
            'project/' . $this->name
        );

        QUI\Update::importEvents($dir . 'events.xml');
        QUI\Update::importMenu($dir . 'menu.xml');

        // translations project names etc.
        $translationGroup = 'project/' . $this->getName();
        $translationVar = 'title';

        $translation = QUI\Translator::get($translationGroup, $translationVar);

        if (!isset($translation[0])) {
            QUI\Translator::add($translationGroup, $translationVar);
        }

        // set default settings and current settings
        QUI\Cache\Manager::clear(
            'qui/projects/' . $this->getName()
        );

        $defaults = QUI\Projects\Manager::getProjectConfigList($this);
        $Config = Manager::getConfig();
        $projects = $Config->toArray();
        $config = [];

        if (isset($projects[$this->getName()])) {
            $config = $projects[$this->getName()];
        }

        foreach ($defaults as $key => $value) {
            if (!isset($config[$key])) {
                $value = QUI\Utils\Security\Orthos::removeHTML($value);
                $value = QUI\Utils\Security\Orthos::clearPath($value);

                $Config->setValue($this->getName(), $key, $value);
            }
        }

        $Config->save();

        if (!empty($setupOptions['executePackagesSetup'])) {
            QUI\Setup::executeEachPackageSetup();
        }


        QUI::getEvents()->fireEvent('projectSetupEnd', [$this]);
    }

    /**
     * Return the media object from the project
     */
    public function getMedia(): Media
    {
        if ($this->Media === null) {
            $this->Media = new QUI\Projects\Media($this);
        }

        return $this->Media;
    }

    /**
     * Set the last edit date in the project
     */
    public function setEditDate(int $date): void
    {
        try {
            QUI\Cache\Manager::set(
                $this->getEDateCacheName(),
                $date
            );
        } catch (Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }
    }

    /**
     * Set custom CSS for the project -> set it to the custom.css file
     *
     * @param string $css - CSS Data
     *
     * @throws QUI\Exception
     */
    public function setCustomCSS(string $css): void
    {
        Permission::checkProjectPermission(
            'quiqqer.projects.editCustomCSS',
            $this
        );

        $file = USR_DIR . $this->getName() . '/bin/custom.css';

        QUI\Utils\System\File::mkfile($file);

        if (!is_writable($file)) {
            throw new QUI\Exception([
                'quiqqer/core',
                'exception.custom.css.is.not.writeable',
                ['file' => $file]
            ]);
        }

        file_put_contents($file, $css);
    }

    /**
     * Return the custom css for the project
     */
    public function getCustomCSS(): string
    {
        if (file_exists(USR_DIR . $this->getName() . '/bin/custom.css')) {
            return file_get_contents(USR_DIR . $this->getName() . '/bin/custom.css');
        }

        return '';
    }

    /**
     * Set custom CSS for the project -> set it to the custom.css file
     *
     * @param string $javascript - CSS Data
     *
     * @throws QUI\Exception
     */
    public function setCustomJavaScript(string $javascript): void
    {
        Permission::checkProjectPermission(
            'quiqqer.projects.editCustomJS',
            $this
        );

        $file = USR_DIR . $this->getName() . '/bin/custom.js';

        QUI\Utils\System\File::mkfile($file);

        if (!is_writable($file)) {
            throw new QUI\Exception([
                'quiqqer/core',
                'exception.custom.javascript.is.not.writeable',
                ['file' => $file]
            ]);
        }

        file_put_contents($file, $javascript);
    }

    /**
     * Return the custom js for the project
     */
    public function getCustomJavaScript(): string
    {
        if (file_exists(USR_DIR . $this->getName() . '/bin/custom.js')) {
            return file_get_contents(USR_DIR . $this->getName() . '/bin/custom.js');
        }

        return '';
    }

    /**
     * permissions
     */

    /**
     * Add a user to the project permission
     *
     * @param string $permission - name of the permission
     * @param User $User - User Object
     *
     * @throws QUI\Exception
     */
    public function addUserToPermission(User $User, string $permission): void
    {
        Permission::addUserToProjectPermission($User, $this, $permission);
    }

    /**
     * Add a group to the project permission
     *
     * @param string $permission - name of the permission
     * @param Group $Group - Group Object
     *
     * @throws QUI\Exception
     */
    public function addGroupToPermission(Group $Group, string $permission): void
    {
        Permission::addGroupToProjectPermission($Group, $this, $permission);
    }

    /**
     * Remove the user from the project permission
     *
     * @param string $permission - name of the permission
     * @param User $User - User Object
     *
     * @throws QUI\Exception
     */
    public function removeUserFromPermission(User $User, string $permission): void
    {
        Permission::removeUserFromProjectPermission($User, $this, $permission);
    }

    /**
     * Renames the project
     *
     * @throws QUI\Exception
     */
    public function rename(string $newName): void
    {
        QUI\Utils\Project::validateProjectName($newName);

        // ----------------------------- //
        //              Config           //
        // ----------------------------- //

        // File: etc/projects.ini.php
        $filename = ETC_DIR . "projects.ini.php";
        $content = file_get_contents($filename);

        $content = str_replace('[' . $this->name . ']', '[' . $newName . ']', $content);
        file_put_contents($filename, $content);


        // File: etc/vhosts.ini.php
        $filename = ETC_DIR . "vhosts.ini.php";
        $content = file_get_contents($filename);

        $content = str_replace($this->name, $newName, $content);
        file_put_contents($filename, $content);


        // ----------------------------- //
        //            Database           //
        // ----------------------------- //

        $tables = [];

        $Stmt = QUI::getDataBase()->getPDO()->prepare("SHOW TABLES;");
        $Stmt->execute();

        $result = $Stmt->fetchAll();

        foreach ($result as $row) {
            $tables[] = $row[0];
        }

        foreach ($tables as $oldTableName) {
            if (!str_contains($oldTableName . "_", $this->name)) {
                continue;
            }

            $newTableName = str_replace($this->name . "_", $newName . "_", $oldTableName);

            $sql = "ALTER TABLE " . $oldTableName . " RENAME " . $newTableName . ";";
            $Stmt = QUI::getDataBase()->getPDO()->prepare($sql);

            try {
                $Stmt->execute();
            } catch (Exception $Exception) {
                QUI\System\Log::writeRecursive(
                    "Could not rename Table '" . $oldTableName . "': " . $Exception->getMessage()
                );
            }
        }


        // ----------------------------- //
        //              Media           //
        // ----------------------------- //

        $sourceDir = CMS_DIR . "media/sites/" . $this->name;
        $targetDir = CMS_DIR . "media/sites/" . $newName;

        if (is_dir($sourceDir)) {
            QUI\Utils\System\File::move($sourceDir, $targetDir);
        }

        // ----------------------------- //
        //              USR           //
        // ----------------------------- //
        $sourceDir = USR_DIR . $this->name;
        $targetDir = USR_DIR . $newName;

        if (is_dir($sourceDir)) {
            QUI\Utils\System\File::move($sourceDir, $targetDir);
        }

        // ----------------------------- //
        //              Cache           //
        // ----------------------------- //
        QUI\Cache\Manager::clearCompleteQuiqqerCache();

        // ----------------------------- //
        //              Finish           //
        // ----------------------------- //

        QUI::getEvents()->fireEvent("projectRenamed", [
            $this,
            $this->name,
            $newName
        ]);


        $this->TABLE = str_replace($this->name . "_", $newName . "_", $this->TABLE);
        $this->RELTABLE = str_replace($this->name . "_", $newName . "_", $this->RELTABLE);
        $this->RELLANGTABLE = str_replace($this->name . "_", $newName . "_", $this->RELLANGTABLE);

        $this->name = $newName;
    }

    /**
     * Explicitly set the project template for the runtime.
     */
    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }
}
