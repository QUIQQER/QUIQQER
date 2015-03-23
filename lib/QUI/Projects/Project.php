<?php

/**
 * This file contains the \QUI\Projects\Project
 */

namespace QUI\Projects;

use QUI;
use QUI\Rights\Permission;
use QUI\Users\User;
use QUI\Groups\Group;

/**
 * A project
 *
 * @author www.pcsg.de (Henning Leutz)
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
     * @var String
     */
    private $_TABLE;

    /**
     * The project site relation table
     * @var String
     */
    private $_RELTABLE;

    /**
     * The project site relation language table
     * @var String
     */
    private $_RELLANGTABLE;

    /**
     * configuration
     * @var array
     */
    private $_config;

    /**
     * project name
     * @var String
     */
    private $_name;

    /**
     * Project language
     * @var String
     */
    private $_lang;

    /**
     * default language
     * @var String
     */
    private $_default_lang;

    /**
     * All languages of the project
     * @var array
     */
    private $_langs;

    /**
     * template of the project
     * @var array
     */
    private $_template;

    /**
     * layout of the project
     * @var array
     */
    private $_layout = '';

    /**
     * loaded sites
     * @var array
     */
    private $_children = array();

    /**
     * loaded edit_sites
     * @var array
     */
    private $_children_tmp = array();

    /**
     * first child
     * @var \QUI\Projects\Site
     */
    private $_firstchild = null;

    /**
     * caching files
     * @var array
     */
    protected $_cache_files = array();

    /**
     * Konstruktor eines Projektes
     *
     * @param String $name - Name of the Project
     * @param string|bool $lang - (optional) Language of the Project - optional
     * @param string|bool $template - (optional) Template of the Project
     * @throws QUI\Exception
     */
    public function __construct($name, $lang=false, $template=false)
    {
        $config = Manager::getConfig()->toArray();
        $name   = (string)$name;

        // Konfiguration einlesen
        if ( !isset( $config[ $name ] ) )
        {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.project.not.found',
                    array( 'name' => $name )
                ),
                804
            );
        }

        $this->_config = $config[ $name ];
        $this->_name   = $name;

        // Langs
        if ( !isset( $this->_config[ 'langs' ] ) )
        {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.project.has.no.langs'
                ),
                803
            );
        }

        $this->_langs = explode( ',', $this->_config[ 'langs' ] );

        // Default Lang
        if ( !isset( $this->_config[ 'default_lang' ] ) )
        {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.project.lang.no.default'
                ),
                805
            );
        }

        $this->_default_lang = $this->_config['default_lang'];

        if ( isset( $this->_config['layout'] ) ) {
            $this->_layout = $this->_config['layout'];
        }

        // Sprache
        if ( $lang != false )
        {
            if ( !in_array( $lang, $this->_langs ) )
            {
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

            $this->_lang = $lang;

        } else
        {
            // Falls keine Sprache angegeben wurde wird die Standardsprache verwendet
            if ( !isset( $this->_config['default_lang'] ) )
            {
                throw new QUI\Exception(
                    QUI::getLocale()->get(
                        'quiqqer/system',
                        'exception.project.lang.no.default'
                    ),
                    805
                );
            }

            $this->_lang = $this->_config['default_lang'];
        }

        // Template
        if ( $template === false )
        {
            $this->_template = $config[ $name ]['template'];
        } else
        {
            $this->_template = $template;
        }

        // vhosts
        $vhosts = QUI::vhosts();

        foreach ( $vhosts as $host => $vhost )
        {
            if ( (int)$host )
            {
                // falls 404 oder 301 oder sonst irgendein apache code eingetragen ist,
                //dann nicht weiter
                continue;
            }

            if ( !isset( $vhost['project'] ) ) {
                continue;
            }

            if ( !isset( $vhost['lang'] ) ) {
                continue;
            }

            if ( $vhost['lang'] == $this->_lang &&
                 $vhost['project'] == $this->_name )
            {
                $this->_config['vhost'] = $host;

                if ( isset( $vhost['layout'] ) ) {
                    $this->_layout = $vhost['layout'];
                }
            }
        }

        // tabellen setzen
        $this->_TABLE        = QUI_DB_PRFX . $this->_name .'_'. $this->_lang .'_sites';
        $this->_RELTABLE     = QUI_DB_PRFX . $this->_TABLE .'_relations';
        $this->_RELLANGTABLE = QUI_DB_PRFX . $this->_name .'_multilingual';


        // cache files
        $this->_cache_files = array(
            'types'  => 'projects.'. $this->getAttribute('name') .'.types',
            'gtypes' => 'projects.'. $this->getAttribute('name') .'.globaltypes'
        );
    }

    /**
     * Destruktor
     */
    public function __destruct()
    {
        unset( $this->_config );
        unset( $this->_children_tmp );
    }

    /**
     * ToString
     * @return String
     */
    public function __toString()
    {
        return 'Object '. get_class( $this ) .'('. $this->_name .','. $this->_lang .')';
    }

    /**
     * Projekt JSON Notation
     *
     * @return string
     */
    public function toJSON()
    {
        return json_encode( $this->toArray() );
    }

    /**
     * Projekt Array Notation
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'name'  => $this->getAttribute( 'name' ),
            'lang'  => $this->getAttribute( 'lang' )
        );
    }

    /**
     * Return the project name
     *
     * @return String
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Return the project lang
     *
     * @return String
     */
    public function getLang()
    {
        return $this->_lang;
    }

    /**
     * Durchsucht das Projekt nach Seiten
     *
     * @param string $search - Suchwort
     * @param array|bool $select - (optional) in welchen Feldern gesucht werden soll array('name', 'title', 'short', 'content')
     *
     * @return array
     */
    public function search($search, $select=false)
    {
        $table = $this->getAttribute( 'db_table' );

        $query = 'SELECT id FROM '. $table;
        $where = ' WHERE name LIKE :search';

        $allowed = array( 'id', 'name', 'title', 'short', 'content' );

        if ( is_array( $select ) )
        {
            $where   = ' WHERE (';

            foreach ( $select as $field )
            {
                if ( !in_array( $field, $allowed ) ) {
                    continue;
                }

                $where .= ' '. $field .' LIKE :search OR ';
            }

            $where = substr( $where, 0, -4 ) .')';

            if ( strlen( $where ) < 6 ) {
                $where = ' WHERE name LIKE :search';
            }
        }

        $query = $query . $where .' AND deleted = 0 LIMIT 0, 50';

        $PDO       = QUI::getDataBase()->getPDO();
        $Statement = $PDO->prepare( $query );

        $Statement->bindValue( ':search', '%'. $search .'%', \PDO::PARAM_STR );
        $Statement->execute();

        $dbresult = $Statement->fetchAll( \PDO::FETCH_ASSOC );
        $result   = array();

        foreach ( $dbresult as $entry ) {
            $result[] = $this->get( $entry['id'] );
        }

        return $result;
    }

    /**
     * Rechteprüfung
     *
     * @return Bool
     */
    protected function _checkRights()
    {
        if ( !defined('ADMIN') ) {
            return true;
        }

        // Falls keine Rechte gesetzt sind
        if ( !$this->getConfig('rights') ) {
            return true;
        }

        $User = QUI::getUsers()->getUserBySession();

        if ( !$User->getId() ) {
            return false;
        }

        $Groups   = $User->getGroups();
        $children = array();

        foreach ( $Groups as $Group )
        {
            /* @var $Group QUI\Groups\Group */
            $childids   = $Group->getChildrenIds(true);
            $childids[] = $Group->getId();

            $children = array_merge($children, $childids);
        }

        $rights = explode(',', trim($this->getConfig('rights'), ',') );

        foreach ( $children as $child )
        {
            if ( in_array( $child, $rights ) )
            {
                return true;
                break;
            }
        }

        return false;
    }

    /**
     * VHost zurück geben
     *
     * @param Bool $with_protocol - Mit oder ohne http -> standard = ohne
     * @param Bool $ssl - mit oder ohne ssl
     * @return Bool | String
     */
    public function getVHost($with_protocol=false, $ssl=false)
    {
        $Hosts = QUI::getRewrite()->getVHosts();

        foreach ( $Hosts as $url => $params )
        {
            if ( $url == 404 || $url == 301 ) {
                continue;
            }

            if ( !isset( $params['project'] ) ) {
                continue;
            }

            if ( $params['project'] == $this->getAttribute('name') &&
                 $params['lang'] == $this->getAttribute('lang') )
            {
                if ( $ssl && isset( $params['httpshost'] ) && !empty( $params['httpshost'] ) ) {
                    return $with_protocol ? 'https://'. $params['httpshost'] : $params['httpshost'];
                }

                return $with_protocol ? 'http://'. $url : $url;
            }
        }

        return HOST;
    }

    /**
     * Namen des Projektes
     * @param String $att -
     * 		name = Name des Projectes
     * 		lang = Aktuelle Sprache
     * 		db_table = Standard Datebanktabelle
     *
     * @return String|false
     */
    public function getAttribute($att)
    {
        switch ( $att )
        {
            case "name":
                return $this->_name;
            break;

            case "config":
                return $this->_config;
            break;

            case "lang":
                return $this->_lang;
            break;

            case "default_lang":
                return $this->_default_lang;
            break;

            case "langs":
                return $this->_langs;
            break;

            case "template":
                return $this->_template;
            break;

            case "layout":
                return $this->_layout;
            break;

            case "db_table":
                # Anzeigen demo_de_sites
                return $this->_name .'_'. $this->_lang .'_sites';
            break;

            case "media_table":
                # Anzeigen demo_de_sites
                return $this->_name .'_de_media';
            break;

            case "e_date":
                return $this->getLastEditDate();
            break;

            default:
                return false;
            break;
        }
    }

    /**
     * Gibt die gesuchte Einstellung vom Projekt zurück
     *
     * @param string|bool $name - name of the config, default = false, returns complete configs
     * @return false|String|Array
     */
    public function getConfig($name=false)
    {
        if ( !$name ) {
            return $this->_config;
        }

        if ( isset( $this->_config[ $name ] ) ) {
            return $this->_config[ $name ];
        }

        // default Werte
        switch ( $name )
        {
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
     * @return String
     */
    public function getHost()
    {
        if ( isset( $this->_config['vhost'] ) ) {
            return $this->_config['vhost'];
        }

        if ( isset( $this->_config['host'] ) ) {
            return $this->_config['host'];
        }

        $host = QUI::conf( 'globals', 'host' );

        if ( !empty( $host ) ) {
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
        return new Trash( $this );
    }

    /**
     * Gibt alle Attribute vom Projekt zurück
     *
     * @return Array
     */
    public function getAllAttributes()
    {
        return array(
            'config'  => $this->_config,
            'lang'    => $this->_lang,
            'langs'   => $this->_langs,
            'name'    => $this->_name,
            'sheets'  => $this->getConfig( 'sheets' ),
            'archive' => $this->getConfig( 'archive' )
        );
    }

    /**
     * Erste Seite des Projektes
     *
     * @$pluginload Bool
     * @return Site
     */
    public function firstChild()
    {
        if ( is_null( $this->_firstchild ) ) {
            $this->_firstchild = $this->get( 1 );
        }

        return $this->_firstchild;
    }

    /**
     * Leert den Cache des Objektes
     *
     * @param Bool $link - Link Cache löschen
     * @param Bool $site - Site Cache löschen
     *
     * @todo muss überarbeitet werden
     */
    public function clearCache($link=true, $site=true)
    {
        if ( $link == true )
        {
            $cache = VAR_DIR.'cache/links/'. $this->getAttribute('name') .'/';
            $files = QUI\Utils\System\File::readDir($cache);

            foreach ( $files as $file ) {
                QUI\Utils\System\File::unlink( $cache . $file );
            }
        }

        if ( $site == true )
        {
            $cache = VAR_DIR.'cache/sites/'. $this->getAttribute('name') .'/';
            $files = QUI\Utils\System\File::readDir($cache);

            foreach ( $files as $file ) {
                QUI\Utils\System\File::unlink( $cache . $file );
            }
        }

        foreach ( $this->_cache_files as $cache ) {
            QUI\Cache\Manager::clear( $cache );
        }
    }

    /**
     * Eine Seite bekommen
     *
     * @param Integer $id - ID der Seite
     * @return Site|Site\Edit
     */
    public function get($id)
    {
        if ( defined('ADMIN') && ADMIN == 1 ) {
            return new Site\Edit( $this, (int)$id );
        }

        if ( isset( $this->_children[ $id ] ) ) {
            return $this->_children[ $id ];
        }

        $Site = new Site( $this, (int)$id );
        $this->_children[ $id ] = $Site;

        return $Site;
    }

    /**
     * Name einer bestimmten ID bekommen
     *
     * @param Integer $id
     * @return String
     * @deprecated
     */
    public function getNameById( $id )
    {
        $result = QUI::getDataBase()->fetch(array(
            'select' => 'name',
            'from'   => $this->_TABLE,
            'where'  => array(
                'id' => $id
            ),
            'limit' => '1'
        ));

        if ( isset( $result[0] ) && is_array( $result ) ) {
            return $result[0]['name'];
        }

        return '';
    }

    /**
     * Gibt eine neue ID zurück
     * @deprecated
     */
    public function getNewId()
    {
        $maxid = QUI::getDataBase()->fetch(array(
            'select' => 'id',
            'from'   => $this->getAttribute('db_table'),
            'limit'  => '0,1',
            'order'  => array(
                'id' =>  'DESC'
            )
        ));

        return (int)$maxid[0]['id']+1;
    }

    /**
     * Media Objekt zum Projekt bekommen
     *
     * @return QUI\Projects\Media
     */
    public function getMedia()
    {
        return new QUI\Projects\Media( $this );
    }

    /**
     *
     */
    public function getLayouts()
    {
        $VHosts    = new QUI\System\VhostManager();
        $vhostList = $VHosts->getHostsByProject( $this->getName() );
        $template  = OPT_DIR . $this->getAttribute( 'template' );

        $siteXMLs = array(
            $template .'/site.xml'
        );

        foreach ( $vhostList as $vhost )
        {
            $hostData = $VHosts->getVhost( $vhost );

            if ( isset( $hostData['template'] ) && !empty( $hostData['template'] ) ) {
                $siteXMLs[] = OPT_DIR . $hostData['template'] .'/site.xml';
            }
        }

        $result   = array();
        $_resTemp = array();
        $siteXMLs = array_unique( $siteXMLs );

        foreach ( $siteXMLs as $siteXML )
        {
            $layouts = QUI\Utils\XML::getLayoutsFromXml( $siteXML );

            foreach ( $layouts as $Layout )
            {
                if ( isset( $_resTemp[ $Layout->getAttribute( 'type' ) ] ) ) {
                    continue;
                }

                $data = array(
                    'type'  => $Layout->getAttribute( 'type' ),
                    'title' => '',
                    'description' => ''
                );

                $_resTemp[ $Layout->getAttribute( 'type' ) ] = true;

                $title = $Layout->getElementsByTagName( 'title' );
                $desc  = $Layout->getElementsByTagName( 'description' );

                if ( $title->length ) {
                    $data['title'] = QUI\Utils\DOM::getTextFromNode( $title->item( 0 ) );
                }

                if ( $desc->length ) {
                    $data['description'] = QUI\Utils\DOM::getTextFromNode( $desc->item( 0 ) );
                }

                $result[] = $data;
            }
        }


        return $result;
    }


    /**
     * Gibt die Namen der eingebundenen Plugins zurück
     *
     * @return Array
     */
//     public function getPlugins()
//     {
//         if ( !is_null( $this->_plugins ) ) {
//             return $this->_plugins;
//         }

//         $Plugins = QUI::getPlugins();

//         if ( !isset( $this->_config['plugins'] ) )
//         {
//               // Falls für das Projekt keine Plugins freigeschaltet wurden dann alle
//             $this->_plugins = $Plugins->get();
//             return $this->_plugins;
//         }

//         // Plugins einlesen falls dies noch nicht getan wurde
//         $_plugins = explode( ',', trim( $this->_config['plugins'], ',' ) );

//         for ( $i = 0, $len = count($_plugins); $i < $len; $i++ )
//         {
//             try
//             {
//                 $this->_plugins[ $_plugins[$i] ] = $Plugins->get( $_plugins[$i] );

//             } catch ( QUI\Exception $Exception )
//             {
//                 //nothing
//             }
//         }

//         return $this->_plugins;
//     }

    /**
     * Return the children ids from a site
     *
     * @param Integer $parentid - The parent site ID
     * @param Array $params 	- extra db statemens, like order, where, count, limit
     * @return array
     */
    public function getChildrenIdsFrom($parentid, $params=array())
    {
        $where_1 = array(
            $this->_RELTABLE .'.parent' => $parentid,
            $this->_TABLE .'.deleted'   => 0,
            $this->_TABLE .'.active'    => 1,
            $this->_RELTABLE .'.child'  => '`'. $this->_TABLE .'.id`'
        );

        if ( isset( $params['active'] ) && $params['active'] === '0&1' )
        {
            $where_1 = array(
                $this->_RELTABLE .'.parent' => $parentid,
                $this->_TABLE .'.deleted'   => 0,
                $this->_RELTABLE .'.child'  => '`'. $this->_TABLE .'.id`'
            );
        }

        if ( isset( $params['where'] ) && is_array( $params['where'] ) )
        {
            $where = array_merge($where_1, $params['where']);

        } elseif ( isset($params['where'] ) && is_string( $params['where'] ) )
        {
            // @todo where als param String
            QUI\System\Log::write(
                'Project->getChildrenIdsFrom WIRD NICHT verwendet'. $params['where'],
                'message'
            );

            $where = $where_1;
        } else
        {
            $where = $where_1;
        }

        $order = $this->_TABLE .'.order_field';

        if ( isset( $params['order'] ) )
        {
            if ( strpos( $params['order'], '.' ) !== false )
            {
                $order = $this->_TABLE .'.'. $params['order'];
            } else
            {
                $order = $params['order'];
            }
        }

        $result = QUI::getDataBase()->fetch(array(
            'select' => $this->_TABLE .'.id',
            'count'  => isset( $params['count'] ) ? 'count' : false,
            'from' 	 => array(
                $this->_RELTABLE,
                $this->_TABLE
            ),
            'order' => $order,
            'limit' => isset( $params['limit'] ) ? $params['limit'] : false,
            'where' => $where
        ));

        if ( isset( $params['count'] ) ) {
            return (int)$result[0]['count'];
        }

        $ids = array();

        foreach ( $result as $entry )
        {
            if ( isset( $entry['id'] ) ) {
                $ids[] = $entry['id'];
            }
        }

        return $ids;
    }

    /**
     * Returns the parent id from a site
     *
     * @param Integer $id
     * @return Integer
     * @deprecated
     */
    public function getParentId($id)
    {
        return $this->getParentIdFrom( $id );
    }

    /**
     * Returns the parent id from a site
     *
     * @param Integer $id - Child id
     * @return Integer Id of the Parent
     */
    public function getParentIdFrom( $id )
    {
        if ( $id <= 0 ) {
            return 0;
        }

        $result = QUI::getDataBase()->fetch(array(
            'select' => 'parent',
            'from' 	 => $this->_RELTABLE,
            'where'  => array(
                'child' => (int)$id
            ),
            'order'  => 'oparent ASC',
            'limit'  => '1'
        ));

        if ( isset($result[0]) && $result[0]['parent'] ) {
            return (int)$result[0]['parent'];
        }

        return 0;
    }

    /**
     * Gibt alle Parent IDs zurück
     *
     * @param Integer $id - child id
     * @param Bool $reverse - revers the result
     *
     * @return Array
     */
    public function getParentIds($id, $reverse=false)
    {
        $ids = array();
        $pid = $this->getParentIdFrom( $id );

        while ( $pid != 1 )
        {
            array_push( $ids, $pid );
            $pid = $this->getParentIdFrom( $pid );
        }

        if ( $reverse ) {
            $ids = array_reverse( $ids );
        }

        return $ids;
    }

    /**
     * Seitentypen im Projekt bekommen
     *
     * @return Array
     */
//     public function getTypes()
//     {
//         try
//         {
//             return QUI\Cache\Manager::get( $this->_cache_files['types'] );

//         } catch ( QUI\Cache\Exception $Exception )
//         {

//         }

//         $types   = array();
//         $Plugins = QUI::getPlugins(); /* @var $Plugins Plugins */
//         $plugins = $Plugins->get();

//         foreach ( $plugins as $Plugin )
//         {
//             /* @var $Plugin Plugin */
//             $name = $Plugin->getAttribute( 'name' );

//             // Ajax Skripte aufnehmen
//             if ( $Plugin->getAttribute('types') ) {
//                 $types[$name]['types'] = $Plugin->getAttribute( 'types' );
//             }

//             $config = $Plugin->getAttribute( 'config' );
//             $types[$name]['name'] = '';

//             if ( isset($config['name']) ) {
//                 $types[$name]['name'] = $config['name'];
//             }
//         }

//         // Cache erstellen
//         QUI\Cache\Manager::set( $this->_cache_files['types'], $types );

//         return $types;
//     }

    /**
     * Globale Sachen der Seitentypen bekommen
     *
     * @return Array
     */
//     public function getGlobalTypes()
//     {
//         $dir   = VAR_DIR .'cache/projects/';
//         $cache = $dir . $this->getAttribute('name') .'_globaltypes';

//         try
//         {
//             return QUI\Cache\Manager::get( $this->_cache_files['gtypes'] );

//         } catch ( QUI\Cache\Exception $Exception )
//         {

//         }

//         $globaltypes = array();
//         $Plugins     = QUI::getPlugins(); /* @var $Plugins Plugins */
//         $plugins     = $Plugins->get();

//         foreach ( $plugins as $Plugin )
//         {
//             /* @var $Plugin Plugin */
//             $name = $Plugin->getAttribute('name');

//             // Ajax Skripte aufnehmen
//             if ( $Plugin->getAttribute('global_ajax') ) {
//                 $globaltypes['ajax'][$name][] = $Plugin->getAttribute('global_ajax');
//             }

//             // Admin Skripte aufnehmen
//             if ( $Plugin->getAttribute('admin') ) {
//                 $globaltypes['admin'][$name][] = $Plugin->getAttribute('admin');
//             }

//             // Upload Skripte aufnehmen
//             if ( $Plugin->getAttribute('upload') ) {
//                 $globaltypes['upload'][$name][] = $Plugin->getAttribute('upload');
//             }

//             $config = $Plugin->getAttribute('config');

//             if ( isset($config['name']) )
//             {
//                 $globaltypes['upload'][$name]['name'] = $config['name'];
//             } else
//             {
//                 $globaltypes['upload'][$name]['name'] = $name;
//             }
//         }

//         // Cachefile anlegen
//         QUI\Cache\Manager::set($this->_cache_files['gtypes'], $globaltypes);

//         return $globaltypes;
//     }

//    /**
//     * Informationen von einem Seitentyp bekommen
//     *
//     * @param String $type - Seitentyp welche gesucht ist
//     * @param String $value - Welche Informationen gewollt ist, wenn nicht übergeben wird ein Array zurück gegeben mit allen Informationen
//     * @return unknown
//     * @deprecated use Plugins->getTypeName()
//     */
//     public function getType($type, $value=false)
//     {
//         if ($type == 'standard' || empty($type))
//         {
//             if ($value == 'name') {
//                 return 'standard';
//             }

//             if ($value == false)
//             {
//                 return array(
//                     'name' => 'Standard'
//                 );
//             }

//             return '';
//         }

//         // Falls kein Standardtyp
//         $all_types = $this->getTypes();
//         $type      = explode('/', $type);

//         if (isset($type[0]) &&
//             isset($type[1]) &&
//              isset($all_types[$type[0]]) &&
//              isset($all_types[$type[0]]['types'][$type[1]]))
//         {
//             switch($value)
//             {
//                 default:
//                     return $all_types[$type[0]]['types'][$type[1]];
//                 break;

//                 case 'name':
//                     $types = $all_types[$type[0]];
//                     return $types['name'] .' / '. $types['types'][$type[1]]['name'];
//                 break;
//             }
//         }

//         return $type;
//     }

    /**
     * Ids von bestimmten Seiten bekommen
     *
     * @param Array $params
     * @todo Muss mal echt überarbeitet werden, bad code
     * @return Array
     */
    function getSitesIds($params=array())
    {
        if (empty($params) || !is_array($params))
        {
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

        if ( isset( $params['where_or'] ) ) {
            $sql['where_or'] = $params['where_or'];
        }

        // Aktivflag abfragen
        if (isset($sql['where']) && is_array($sql['where']) && !isset($sql['where']['active']))
        {
            $sql['where']['active'] = 1;
        } elseif (isset($sql['where']['active']) && $sql['where']['active'] == -1)
        {
            unset($sql['where']['active']);
        } elseif (isset($sql['where']) && is_string($sql['where']))
        {
            $sql['where'] .= ' AND active = 1';
        } elseif (!isset($sql['where']['active']))
        {
            $sql['where']['active'] = 1;
        }

        // Deletedflag abfragen
        if (isset($sql['where']) && is_array($sql['where']) && !isset($sql['where']['deleted']))
        {
            $sql['where']['deleted'] = 0;
        } elseif (isset($sql['where']['deleted']) && $sql['where']['deleted'] == -1)
        {
            unset($sql['where']['deleted']);
        } elseif (is_string($sql['where']))
        {
            $sql['where'] .= ' AND deleted = 0';
        } elseif (!isset($sql['where']['deleted']))
        {
            $sql['where']['deleted'] = 0;
        }

        if (isset($params['count']))
        {
            $sql['count'] = array(
                'select' => 'id',
                'as'     => 'count'
            );

            unset($sql['select']);
        } else
        {
            $sql['select'] = 'id';
            unset($sql['count']);
        }

        if (isset($params['limit'])) {
            $sql['limit'] = $params['limit'];
        }

        if (isset($params['order']))
        {
            $sql['order'] = $params['order'];
        } else
        {
            $sql['order'] = 'order_field';
        }

        if (isset($params['debug']))
        {
            $sql['debug'] = true;

            QUI\System\Log::writeRecursive( $sql );
        }

        if (isset($params['where_relation'])) {
            $sql['where_relation'] = $params['where_relation'];
        }

        return QUI::getDataBase()->fetch($sql);
    }

    /**
     * Alle Seiten bekommen
     *
     * @param array|bool $params
     * @return array|integer - if count is given, return is an integer, otherwise an array
     */
    public function getSites($params=false)
    {
         // Falls kein Query dann alle Seiten hohlen
        // @notice - Kann performancefressend sein

        $s = $this->getSitesIds($params);

        if ( empty($s) || !is_array($s) ) {
            return array();
        }

        if ( isset($params['count']) )
        {
            if ( isset($s[0]) && isset($s[0]['count']) ) {
                return $s[0]['count'];
            }

            return 0;
        }

        $sites = array();

        foreach ( $s as $site_id ) {
            $sites[] = $this->get( (int)$site_id['id'] );
        }

        return $sites;
    }

    /**
     * Execute the project setup
     */
    public function setup()
    {
        $DataBase = QUI::getDataBase();
        $Table    = $DataBase->Table();
        $User     = QUI::getUserBySession();

        // multi lingual table
        $multiLingualTable = QUI_DB_PRFX . $this->_name .'_multilingual';

        $Table->appendFields($multiLingualTable, array(
            'id' => 'bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY'
        ));


        foreach ( $this->_langs as $lang )
        {
            $table = QUI_DB_PRFX . $this->_name .'_'. $lang .'_sites';

            $Table->appendFields($table, array(
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
                'ALTER TABLE `'. $table .'` CHANGE `name` `name` VARCHAR( 255 ) NOT NULL'
            );

            $DataBase->getPDO()->exec(
                'ALTER TABLE `'. $table .'` CHANGE `order_type` `order_type` VARCHAR( 255 ) NULL DEFAULT NULL'
            );

            $DataBase->getPDO()->exec(
                'ALTER TABLE `'. $table .'` CHANGE `type` `type` VARCHAR( 255 ) NULL DEFAULT NULL'
            );

            if ( !$Table->issetPrimaryKey( $table, 'id' ) ) {
                $Table->setPrimaryKey( $table, 'id' );
            }

            $Table->setIndex( $table, 'name' );
            $Table->setIndex( $table, 'active' );
            $Table->setIndex( $table, 'deleted' );
            $Table->setIndex( $table, 'order_field' );
            $Table->setIndex( $table, 'type' );
            $Table->setIndex( $table, 'c_date' );
            $Table->setIndex( $table, 'e_date' );


            // create first site -> id 1 if not exist
            $firstChildResult = $DataBase->fetch(array(
                'from'  => $table,
                'where' => array(
                    'id' => 1
                ),
                'limit' => 1
            ));

            if ( !isset( $firstChildResult[0] ) )
            {
                $DataBase->insert($table, array(
                    'id'     => 1,
                    'name'   => 'start',
                    'title'  => 'Start',
                    'type'   => 'standard',
                    'c_date' => date( 'Y-m-d H:i:s' ),
                    'c_user' => $User->getId(),
                    'c_user_ip' => QUI\Utils\System::getClientIP()
                ));
            }

            // Beziehungen
            $table = QUI_DB_PRFX . $this->_name .'_'. $lang .'_sites_relations';

            $Table->appendFields($table, array(
                'parent'  => 'bigint(20)',
                'child'   => 'bigint(20)',
                'oparent' => 'bigint(20)'
            ));

            $Table->setIndex( $table, 'parent' );
            $Table->setIndex( $table, 'child' );

            // multilingual field
            $Table->appendFields(
                $multiLingualTable,
                array( $lang => 'bigint(20)' )
            );

            // Translation Setup
            QUI\Translator::addLang( $lang );
        }

        // Media Setup
        $this->getMedia()->setup();


        // read xml files
        $dir = USR_DIR . $this->_name .'/';

        QUI\Update::importDatabase( $dir .'database.xml' );
        QUI\Update::importTemplateEngines( $dir .'engines.xml' );
        QUI\Update::importEditors( $dir .'wysiwyg.xml' );
        QUI\Update::importMenu( $dir .'menu.xml' );
        QUI\Update::importPermissions( $dir .'permissions.xml', 'project/'.$this->_name );
        QUI\Update::importEvents( $dir .'events.xml' );
        QUI\Update::importMenu( $dir .'menu.xml' );

        // settings
        if ( !file_exists( $dir .'settings.xml' ) ) {
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
     * @param Integer $date
     */
    public function setEditDate($date)
    {
        QUI\Cache\Manager::set(
            'projects/edate/'. md5( $this->getName() .'_'. $this->getLang() ),
            (int)$date
        );
    }

    /**
     * Return the last edit date in the project
     *
     * @return Integer
     */
    public function getLastEditDate()
    {

        try
        {
            return (int)QUI\Cache\Manager::get(
                'projects/edate/'. md5( $this->getName() .'_'. $this->getLang() )
            );

        } catch ( QUI\Exception $Exception )
        {

        }

        return 0;
    }

    /**
     * permissions
     */

    /**
     * Add an user to the project permission
     *
     * @param String $permission - name of the permission
     * @param User $User - User Object
     */
    public function addUserToPermission(User $User, $permission)
    {
        Permission::addUserToProjectPermission( $User, $this, $permission );
    }

    /**
     * Add an group to the project permission
     *
     * @param String $permission - name of the permission
     * @param Group $Group - Group Object
     */
    public function addGroupToPermission(Group $Group, $permission)
    {
        Permission::addGroupToProjectPermission( $Group, $this, $permission );
    }

    /**
     * Remove the user from the project permission
     *
     * @param String $permission - name of the permission
     * @param User $User - User Object
     */
    public function removeUserFromPermission(User $User, $permission)
    {
        Permission::removeUserFromProjectPermission( $User, $this, $permission );
    }
}
