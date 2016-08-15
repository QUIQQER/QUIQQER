<?php

/**
 * This file contains the \QUI\Projects\Project
 */

namespace QUI\Projects;

use QUI;
use QUI\Permissions\Permission;
use QUI\Users\User;
use QUI\Groups\Group;

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
class Project
{
    /**
     * The project site table
     *
     * @var string
     */
    private $TABLE;

    /**
     * The project site relation table
     *
     * @var string
     */
    private $RELTABLE;

    /**
     * The project site relation language table
     *
     * @var string
     */
    private $RELLANGTABLE;

    /**
     * configuration
     *
     * @var array
     */
    private $config;

    /**
     * project name
     *
     * @var string
     */
    private $name;

    /**
     * Project language
     *
     * @var string
     */
    private $lang;

    /**
     * default language
     *
     * @var string
     */
    private $default_lang;

    /**
     * All languages of the project
     *
     * @var array
     */
    private $langs;

    /**
     * template of the project
     *
     * @var array
     */
    private $template;

    /**
     * layout of the project
     *
     * @var array
     */
    private $layout = '';

    /**
     * loaded sites
     *
     * @var array
     */
    private $children = array();

    /**
     * loaded edit_sites
     *
     * @var array
     */
    private $children_tmp = array();

    /**
     * first child
     *
     * @var \QUI\Projects\Site
     */
    private $firstchild = null;

    /**
     * caching files
     *
     * @var array
     */
    protected $cache_files = array();

    /**
     * Konstruktor eines Projektes
     *
     * @param string $name - Name of the Project
     * @param string|boolean $lang - (optional) Language of the Project - optional
     * @param string|boolean $template - (optional) Template of the Project
     *
     * @throws QUI\Exception
     */
    public function __construct($name, $lang = false, $template = false)
    {
        $config = Manager::getConfig()->toArray();
        $name   = (string)$name;

        // Konfiguration einlesen
        if (!isset($config[$name])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.project.not.found',
                    array('name' => $name)
                ),
                804
            );
        }

        $this->config = $config[$name];
        $this->name   = $name;

        // Langs
        if (!isset($this->config['langs'])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
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
                    'quiqqer/system',
                    'exception.project.lang.no.default'
                ),
                805
            );
        }

        $this->default_lang = $this->config['default_lang'];

        if (isset($this->config['layout'])) {
            $this->layout = $this->config['layout'];
        }

        // Sprache
        if ($lang != false) {
            if (!in_array($lang, $this->langs)) {
                throw new QUI\Exception(
                    QUI::getLocale()->get(
                        'quiqqer/system',
                        'exception.project.lang.not.found',
                        array(
                            'lang' => $lang
                        )
                    ),
                    806
                );
            }

            $this->lang = $lang;
        } else {
            // Falls keine Sprache angegeben wurde wird die Standardsprache verwendet
            if (!isset($this->config['default_lang'])) {
                throw new QUI\Exception(
                    QUI::getLocale()->get(
                        'quiqqer/system',
                        'exception.project.lang.no.default'
                    ),
                    805
                );
            }

            $this->lang = $this->config['default_lang'];
        }

        // Template
        if ($template === false) {
            $this->template = $config[$name]['template'];
        } else {
            $this->template = $template;
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

            if ($vhost['lang'] == $this->lang
                && $vhost['project'] == $this->name
            ) {
                $this->config['vhost'] = $host;

                if (isset($vhost['layout'])) {
                    $this->layout = $vhost['layout'];
                }
            }
        }

        // tabellen setzen
        $this->TABLE        = QUI_DB_PRFX . $this->name . '_' . $this->lang . '_sites';
        $this->RELTABLE     = QUI_DB_PRFX . $this->TABLE . '_relations';
        $this->RELLANGTABLE = QUI_DB_PRFX . $this->name . '_multilingual';


        // cache files
        $this->cache_files = array(
            'types'  => 'projects.' . $this->getAttribute('name') . '.types',
            'gtypes' => 'projects.' . $this->getAttribute('name') . '.globaltypes'
        );
    }

    /**
     * Destruktor
     */
    public function __destruct()
    {
        unset($this->config);
        unset($this->children_tmp);
    }

    /**
     * Tostring
     *
     * @return string
     */
    public function __toString()
    {
        return 'Object ' . get_class($this) . '(' . $this->name . ',' . $this->lang . ')';
    }

    /**
     * Projekt JSON Notation
     *
     * @return string
     */
    public function toJSON()
    {
        return json_encode($this->toArray());
    }

    /**
     * Projekt Array Notation
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'name' => $this->getAttribute('name'),
            'lang' => $this->getAttribute('lang')
        );
    }

    /**
     * Return the project name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return the project lang
     *
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Return all languages in the project
     *
     * @return array
     */
    public function getLanguages()
    {
        $langs = $this->getAttribute('langs');

        if (is_string($langs)) {
            $langs = explode(',', $langs);
        }

        return $langs;
    }

    /**
     * Return the project title
     * Locale->get('project/NAME', 'title') or getName()
     *
     * @return string
     */
    public function getTitle()
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
     * @param array|boolean $select - (optional) in welchen Feldern gesucht werden soll
     *                                array('name', 'title', 'short', 'content')
     *
     * @return array
     */
    public function search($search, $select = false)
    {
        $table = $this->getAttribute('db_table');

        $query = 'SELECT id FROM ' . $table;
        $where = ' WHERE name LIKE :search';

        $allowed = array('id', 'name', 'title', 'short', 'content');

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

        $PDO       = QUI::getDataBase()->getPDO();
        $Statement = $PDO->prepare($query);

        $Statement->bindValue(':search', '%' . $search . '%', \PDO::PARAM_STR);
        $Statement->execute();

        $dbresult = $Statement->fetchAll(\PDO::FETCH_ASSOC);
        $result   = array();

        foreach ($dbresult as $entry) {
            $result[] = $this->get($entry['id']);
        }

        return $result;
    }

    /**
     * VHost zurück geben
     *
     * @param boolean $with_protocol - Mit oder ohne http -> standard = ohne
     * @param boolean $ssl - mit oder ohne ssl
     *
     * @return boolean | string
     */
    public function getVHost($with_protocol = false, $ssl = false)
    {
        $Hosts = QUI::getRewrite()->getVHosts();

        foreach ($Hosts as $url => $params) {
            if ($url == 404 || $url == 301) {
                continue;
            }

            if (!isset($params['project'])) {
                continue;
            }

            if ($params['project'] == $this->getAttribute('name')
                && $params['lang'] == $this->getAttribute('lang')
            ) {
                if ($ssl && isset($params['httpshost'])
                    && !empty($params['httpshost'])
                ) {
                    return $with_protocol ? 'https://' . $params['httpshost']
                        : $params['httpshost'];
                }

                return $with_protocol ? 'http://' . $url : $url;
            }
        }

        return HOST;
    }

    /**
     * Namen des Projektes
     *
     * @param string $att -
     *                    name = Name des Projectes
     *                    lang = Aktuelle Sprache
     *                    db_table = Standard Datebanktabelle
     *
     * @return string|false
     */
    public function getAttribute($att)
    {
        switch ($att) {
            case "name":
                return $this->getName();
                break;

            case "lang":
                return $this->getLang();
                break;

            case "e_date":
                return $this->getLastEditDate();
                break;

            case "config":
                return $this->config;
                break;

            case "default_lang":
                return $this->default_lang;
                break;

            case "langs":
                return $this->langs;
                break;

            case "template":
                return $this->template;
                break;

            case "layout":
                return $this->layout;
                break;

            case "db_table":
                # Anzeigen demo_de_sites
                return $this->name . '_' . $this->lang . '_sites';
                break;

            case "media_table":
                # Anzeigen demo_de_sites
                return $this->name . '_de_media';
                break;

            default:
                return false;
                break;
        }
    }

    /**
     * Gibt die gesuchte Einstellung vom Projekt zurück
     *
     * @param string|boolean $name - name of the config, default = false, returns complete configs
     *
     * @return false|string|array
     */
    public function getConfig($name = false)
    {
        if (!$name) {
            return $this->config;
        }

        if (isset($this->config[$name])) {
            return $this->config[$name];
        }

        // default Werte
        switch ($name) {
            case "sheets": // Blätterfunktion
                return 5;
                break;

            case "archive": // Archiveinträge
                return 10;
                break;
        }

        return false;
    }

    /**
     * Gibt den allgemein gültigen Host vom Projekt zurück
     *
     * @return string
     */
    public function getHost()
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
     *
     * @return QUI\Projects\Trash
     */
    public function getTrash()
    {
        return new Trash($this);
    }

    /**
     * Gibt alle Attribute vom Projekt zurück
     *
     * @return array
     */
    public function getAllAttributes()
    {
        return array(
            'config'  => $this->config,
            'lang'    => $this->lang,
            'langs'   => $this->langs,
            'name'    => $this->name,
            'sheets'  => $this->getConfig('sheets'),
            'archive' => $this->getConfig('archive')
        );
    }

    /**
     * Erste Seite des Projektes
     *
     * @$pluginload boolean
     *
     * @return Site
     */
    public function firstChild()
    {
        if ($this->firstchild === null) {
            $this->firstchild = $this->get(1);
        }

        return $this->firstchild;
    }

    /**
     * Leert den Cache des Objektes
     *
     * @param boolean $link - Link Cache löschen
     * @param boolean $site - Site Cache löschen
     *
     * @todo muss überarbeitet werden
     */
    public function clearCache($link = true, $site = true)
    {
        if ($link == true) {
            $cache = VAR_DIR . 'cache/links/' . $this->getAttribute('name') . '/';
            $files = QUI\Utils\System\File::readDir($cache);

            foreach ($files as $file) {
                QUI\Utils\System\File::unlink($cache . $file);
            }
        }

        if ($site == true) {
            $cache = VAR_DIR . 'cache/sites/' . $this->getAttribute('name') . '/';
            $files = QUI\Utils\System\File::readDir($cache);

            foreach ($files as $file) {
                QUI\Utils\System\File::unlink($cache . $file);
            }
        }

        foreach ($this->cache_files as $cache) {
            QUI\Cache\Manager::clear($cache);
        }
    }

    /**
     * Eine Seite bekommen
     *
     * @param integer $id - ID der Seite
     *
     * @return Site|Site\Edit
     */
    public function get($id)
    {
        if (defined('ADMIN') && ADMIN == 1) {
            return new Site\Edit($this, (int)$id);
        }

        if (isset($this->children[$id])) {
            return $this->children[$id];
        }

        $Site                = new Site($this, (int)$id);
        $this->children[$id] = $Site;

        return $Site;
    }

    /**
     * Name einer bestimmten ID bekommen
     *
     * @param integer $id
     *
     * @return string
     * @deprecated
     */
    public function getNameById($id)
    {
        $result = QUI::getDataBase()->fetch(array(
            'select' => 'name',
            'from'   => $this->TABLE,
            'where'  => array(
                'id' => $id
            ),
            'limit'  => '1'
        ));

        if (isset($result[0]) && is_array($result)) {
            return $result[0]['name'];
        }

        return '';
    }

    /**
     * Gibt eine neue ID zurück
     *
     * @deprecated
     */
    public function getNewId()
    {
        $maxid = QUI::getDataBase()->fetch(array(
            'select' => 'id',
            'from'   => $this->getAttribute('db_table'),
            'limit'  => '0,1',
            'order'  => array(
                'id' => 'DESC'
            )
        ));

        return (int)$maxid[0]['id'] + 1;
    }

    /**
     * Media Objekt zum Projekt bekommen
     *
     * @return QUI\Projects\Media
     */
    public function getMedia()
    {
        return new QUI\Projects\Media($this);
    }

    /**
     *
     */
    public function getLayouts()
    {
        $VHosts    = new QUI\System\VhostManager();
        $vhostList = $VHosts->getHostsByProject($this->getName());
        $template  = OPT_DIR . $this->getAttribute('template');

        $siteXMLs = array(
            $template . '/site.xml'
        );

        foreach ($vhostList as $vhost) {
            $hostData = $VHosts->getVhost($vhost);

            if (isset($hostData['template']) && !empty($hostData['template'])) {
                $siteXMLs[] = OPT_DIR . $hostData['template'] . '/site.xml';
            }
        }

        $result   = array();
        $_resTemp = array();
        $siteXMLs = array_unique($siteXMLs);

        foreach ($siteXMLs as $siteXML) {
            $layouts = QUI\Utils\XML::getLayoutsFromXml($siteXML);

            foreach ($layouts as $Layout) {
                /* @var $Layout \DOMElement */
                if (isset($_resTemp[$Layout->getAttribute('type')])) {
                    continue;
                }

                $data = array(
                    'type'        => $Layout->getAttribute('type'),
                    'title'       => '',
                    'description' => ''
                );

                $_resTemp[$Layout->getAttribute('type')] = true;

                $title = $Layout->getElementsByTagName('title');
                $desc  = $Layout->getElementsByTagName('description');

                if ($title->length) {
                    $data['title'] = QUI\Utils\DOM::getTextFromNode($title->item(0));
                }

                if ($desc->length) {
                    $data['description'] = QUI\Utils\DOM::getTextFromNode($desc->item(0));
                }

                $result[] = $data;
            }
        }


        return $result;
    }


    /**
     * Gibt die Namen der eingebundenen Plugins zurück
     *
     * @return array
     */
//     public function getPlugins()
//     {
//         if ( !is_null( $this->plugins ) ) {
//             return $this->plugins;
//         }

//         $Plugins = QUI::getPlugins();

//         if ( !isset( $this->config['plugins'] ) )
//         {
//               // Falls für das Projekt keine Plugins freigeschaltet wurden dann alle
//             $this->plugins = $Plugins->get();
//             return $this->plugins;
//         }

//         // Plugins einlesen falls dies noch nicht getan wurde
//         $_plugins = explode( ',', trim( $this->config['plugins'], ',' ) );

//         for ( $i = 0, $len = count($_plugins); $i < $len; $i++ )
//         {
//             try
//             {
//                 $this->plugins[ $_plugins[$i] ] = $Plugins->get( $_plugins[$i] );

//             } catch ( QUI\Exception $Exception )
//             {
//                 //nothing
//             }
//         }

//         return $this->plugins;
//     }

    /**
     * Return the children ids from a site
     *
     * @param integer $parentid - The parent site ID
     * @param array $params - extra db statemens, like order, where, count, limit
     *
     * @return array|integer
     */
    public function getChildrenIdsFrom($parentid, $params = array())
    {
        $where_1 = array(
            $this->RELTABLE . '.parent' => $parentid,
            $this->TABLE . '.deleted'   => 0,
            $this->TABLE . '.active'    => 1,
            $this->RELTABLE . '.child'  => '`' . $this->TABLE . '.id`'
        );

        if (isset($params['active']) && $params['active'] === '0&1') {
            $where_1 = array(
                $this->RELTABLE . '.parent' => $parentid,
                $this->TABLE . '.deleted'   => 0,
                $this->RELTABLE . '.child'  => '`' . $this->TABLE . '.id`'
            );
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

        if (isset($params['count'])) {
            return (int)$result[0]['count'];
        }

        $ids = array();

        foreach ($result as $entry) {
            if (isset($entry['id'])) {
                $ids[] = $entry['id'];
            }
        }

        return $ids;
    }

    /**
     * Returns the parent id from a site
     *
     * @param integer $id
     *
     * @return integer
     * @deprecated
     */
    public function getParentId($id)
    {
        return $this->getParentIdFrom($id);
    }

    /**
     * Returns the parent id from a site
     *
     * @param integer $id - Child id
     *
     * @return integer Id of the Parent
     */
    public function getParentIdFrom($id)
    {
        if ($id <= 0) {
            return 0;
        }

        $result = QUI::getDataBase()->fetch(array(
            'select' => 'parent',
            'from'   => $this->RELTABLE,
            'where'  => array(
                'child' => (int)$id
            ),
            'order'  => 'oparent ASC',
            'limit'  => '1'
        ));

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
     * @return array
     */
    public function getParentIds($id, $reverse = false)
    {
        $ids = array();
        $pid = $this->getParentIdFrom($id);

        while ($pid != 1) {
            array_push($ids, $pid);
            $pid = $this->getParentIdFrom($pid);
        }

        if ($reverse) {
            $ids = array_reverse($ids);
        }

        return $ids;
    }

    /**
     * Ids von bestimmten Seiten bekommen
     *
     * @param array $params
     *
     * @todo Muss mal echt überarbeitet werden, bad code
     * @return array
     */
    public function getSitesIds($params = array())
    {
        if (empty($params) || !is_array($params)) {
            // Falls kein Query dann alle Seiten hohlen
            // @notice - Kann performancefressend sein
            return QUI::getDataBase()->fetch(array(
                'select' => 'id',
                'from'   => $this->getAttribute('db_table')
            ));
        }

        $sql = array(
            'select' => 'id',
            'from'   => $this->getAttribute('db_table')
        );

        if (isset($params['where'])) {
            $sql['where'] = $params['where'];
        }

        if (isset($params['where_or'])) {
            $sql['where_or'] = $params['where_or'];
        }

        // Aktivflag abfragen
        if (isset($sql['where']) && is_array($sql['where'])
            && !isset($sql['where']['active'])
        ) {
            $sql['where']['active'] = 1;
        } elseif (isset($sql['where']['active'])
                  && $sql['where']['active'] == -1
        ) {
            unset($sql['where']['active']);
        } elseif (isset($sql['where']) && is_string($sql['where'])) {
            $sql['where'] .= ' AND active = 1';
        } elseif (!isset($sql['where']['active'])) {
            $sql['where']['active'] = 1;
        }

        // Deletedflag abfragen
        if (isset($sql['where']) && is_array($sql['where'])
            && !isset($sql['where']['deleted'])
        ) {
            $sql['where']['deleted'] = 0;
        } elseif (isset($sql['where']['deleted'])
                  && $sql['where']['deleted'] == -1
        ) {
            unset($sql['where']['deleted']);
        } elseif (is_string($sql['where'])) {
            $sql['where'] .= ' AND deleted = 0';
        } elseif (!isset($sql['where']['deleted'])) {
            $sql['where']['deleted'] = 0;
        }

        if (isset($params['count'])) {
            $sql['count'] = array(
                'select' => 'id',
                'as'     => 'count'
            );

            unset($sql['select']);
        } else {
            $sql['select'] = 'id';
            unset($sql['count']);
        }

        if (isset($params['limit'])) {
            $sql['limit'] = $params['limit'];
        }

        if (isset($params['order'])) {
            $sql['order'] = $params['order'];
        } else {
            $sql['order'] = 'order_field';
        }

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
     * Alle Seiten bekommen
     *
     * @param array|boolean $params
     *
     * @return array|integer - if count is given, return is an integer, otherwise an array
     */
    public function getSites($params = false)
    {
        // Falls kein Query dann alle Seiten hohlen
        // @notice - Kann performancefressend sein

        $s = $this->getSitesIds($params);

        if (empty($s) || !is_array($s)) {
            return array();
        }

        if (isset($params['count'])) {
            if (isset($s[0]) && isset($s[0]['count'])) {
                return $s[0]['count'];
            }

            return 0;
        }

        $sites = array();

        foreach ($s as $site_id) {
            $sites[] = $this->get((int)$site_id['id']);
        }

        return $sites;
    }

    /**
     * Execute the project setup
     */
    public function setup()
    {
        $DataBase = QUI::getDataBase();
        $Table    = $DataBase->table();
        $User     = QUI::getUserBySession();

        // multi lingual table
        $multiLingualTable = QUI_DB_PRFX . $this->name . '_multilingual';

        $Table->addColumn($multiLingualTable, array(
            'id' => 'bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY'
        ));


        foreach ($this->langs as $lang) {
            $table = QUI_DB_PRFX . $this->name . '_' . $lang . '_sites';

            $Table->addColumn($table, array(
                'id'            => 'bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY',
                'name'          => 'varchar(255) NOT NULL',
                'title'         => 'tinytext',
                'short'         => 'text',
                'content'       => 'longtext',
                'type'          => 'varchar(255) default NULL',
                'layout'        => 'varchar(255) default NULL',
                'active'        => 'tinyint(1) NOT NULL',
                'deleted'       => 'tinyint(1) NOT NULL',
                'c_date'        => 'timestamp NULL default NULL',
                'e_date'        => 'timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP',
                'c_user'        => 'int(11) default NULL',
                'e_user'        => 'int(11) default NULL',
                'nav_hide'      => 'tinyint(1) NOT NULL',
                'order_type'    => 'varchar(255) default NULL',
                'order_field'   => 'bigint(20) default NULL',
                'extra'         => 'text NULL',
                'c_user_ip'     => 'varchar(40)',
                'image_emotion' => 'text',
                'image_site'    => 'text',
                'release_from'  => 'timestamp NULL default NULL',
                'release_to'    => 'timestamp NULL default NULL'
            ));

            // fix for old tables
            $DataBase->getPDO()->exec(
                'ALTER TABLE `' . $table
                . '` CHANGE `name` `name` VARCHAR( 255 ) NOT NULL'
            );

            $DataBase->getPDO()->exec(
                'ALTER TABLE `' . $table
                . '` CHANGE `order_type` `order_type` VARCHAR( 255 ) NULL DEFAULT NULL'
            );

            $DataBase->getPDO()->exec(
                'ALTER TABLE `' . $table
                . '` CHANGE `type` `type` VARCHAR( 255 ) NULL DEFAULT NULL'
            );

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
            $firstChildResult = $DataBase->fetch(array(
                'from'  => $table,
                'where' => array(
                    'id' => 1
                ),
                'limit' => 1
            ));

            if (!isset($firstChildResult[0])) {
                $DataBase->insert($table, array(
                    'id'        => 1,
                    'name'      => 'start',
                    'title'     => 'Start',
                    'type'      => 'standard',
                    'c_date'    => date('Y-m-d H:i:s'),
                    'c_user'    => $User->getId(),
                    'c_user_ip' => QUI\Utils\System::getClientIP()
                ));
            }

            // Beziehungen
            $table = QUI_DB_PRFX . $this->name . '_' . $lang . '_sites_relations';

            $Table->addColumn($table, array(
                'parent'  => 'bigint(20)',
                'child'   => 'bigint(20)',
                'oparent' => 'bigint(20)'
            ));

            $Table->setIndex($table, 'parent');
            $Table->setIndex($table, 'child');

            // multilingual field
            $Table->addColumn(
                $multiLingualTable,
                array($lang => 'bigint(20)')
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
        $translationVar   = 'title';

        $translation = QUI\Translator::get($translationGroup, $translationVar);

        if (!isset($translation[0])) {
            QUI\Translator::add($translationGroup, $translationVar);
        }

        // settings
        if (!file_exists($dir . 'settings.xml')) {
            return;
        }

//         $defaults = QUI\Utils\XML::getConfigParamsFromXml( $dir .'settings.xml' );
//         $Config   = QUI\Utils\XML::getConfigFromXml( $dir .'settings.xml' );

//         if ( $Config ) {
//             $Config->save();
//         }
    }

    /**
     * Set the last edit date in the project
     *
     * @param integer $date
     */
    public function setEditDate($date)
    {
        QUI\Cache\Manager::set(
            'projects/edate/' . md5($this->getName() . '_' . $this->getLang()),
            (int)$date
        );
    }

    /**
     * Set custom CSS for the project -> set it to the custom.css file
     *
     * @param string $css - CSS Data
     */
    public function setCustomCSS($css)
    {
        Permission::checkProjectPermission(
            'quiqqer.projects.editCustomCSS',
            $this
        );

        QUI\Utils\System\File::mkfile(USR_DIR . $this->getName()
                                      . '/bin/custom.css');

        file_put_contents(
            USR_DIR . $this->getName() . '/bin/custom.css',
            $css
        );
    }

    /**
     * Return the custom css for the project
     *
     * @return string
     */
    public function getCustomCSS()
    {
        if (file_exists(USR_DIR . $this->getName() . '/bin/custom.css')) {
            return file_get_contents(USR_DIR . $this->getName()
                                     . '/bin/custom.css');
        }

        return '';
    }

    /**
     * Return the last edit date in the project
     *
     * @return integer
     */
    public function getLastEditDate()
    {
        try {
            return (int)QUI\Cache\Manager::get(
                'projects/edate/' . md5($this->getName() . '_' . $this->getLang())
            );
        } catch (QUI\Exception $Exception) {
        }

        return 0;
    }

    /**
     * permissions
     */

    /**
     * Add an user to the project permission
     *
     * @param string $permission - name of the permission
     * @param User $User - User Object
     */
    public function addUserToPermission(User $User, $permission)
    {
        Permission::addUserToProjectPermission($User, $this, $permission);
    }

    /**
     * Add an group to the project permission
     *
     * @param string $permission - name of the permission
     * @param Group $Group - Group Object
     */
    public function addGroupToPermission(Group $Group, $permission)
    {
        Permission::addGroupToProjectPermission($Group, $this, $permission);
    }

    /**
     * Remove the user from the project permission
     *
     * @param string $permission - name of the permission
     * @param User $User - User Object
     */
    public function removeUserFromPermission(User $User, $permission)
    {
        Permission::removeUserFromProjectPermission($User, $this, $permission);
    }
}
