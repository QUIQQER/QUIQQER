<?php

/**
 * This file contains the \QUI\Projects\Site
 */

namespace QUI\Projects;

use \QUI\Utils\String as StringUtils;

/**
 * Site Objekt - eine einzelne Seite
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.project
 *
 * @copyright  2008 PCSG
 * @version    $Revision: 4657 $
 *
 * @errorcodes
 * <ul>
 * <li>400	- __construct() Error; Bad Request; Aufruf ist falsch</li>
 * <li>404	- __construct() Error; Not Found; Seite wurde nicht gefunden. Seite ist entweder inaktiv oder die Rechte fehlen</li>
 * </ul>
 */

class Site extends \QUI\QDOM
{
    /**
     * $Events - manage and fires events
     * @var \QUI\Events\Event
     */
    public $Events;

    /**
     * The site id
     * @var Integer
     */
    protected $_id;

    /**
     * parents array
     * @var array
     */
    protected $_parents;

    /**
     * the main parent id
     * @var Integer
     */
    protected $_parent_id;

    /**
     * Parent ids
     * @var array
     */
    protected $_parents_id;

    /**
     * the children list
     * @var array
     */
    protected $_childs_container;

    /**
     * children
     * @var array
     */
    protected $_children = array();

    /**
     * aassigned plugins
     * @var array
     */
    protected $_plugins = array();

    /**
     * site extra fields
     * @var array
     */
    protected $_extra = array();

    /**
     * the site url
     * @var String
     */
    protected $_url = false;

    /**
     * the ids of the pages in other languages
     * @var array
     */
    protected $_lang_ids = array();

    /**
     * the database table
     * @var String
     */
    protected $_TABLE = '';

    /**
     * the database relation table
     * @var String
     */
    protected $_RELTABLE = '';

    /**
     * the database relation language table
     * @var String
     */
    protected $_RELLANGTABLE = '';

    /**
     * the cachename
     * @var String
     */
    protected $_CACHENAME;

    /**
     * Is the site loaded flag
     * @var Bool
     */
    protected $_loadflag = false;

    /**
     * is the site a link
     * @var Bool
     */
    protected $_LINKED_PARENT = false;

    /**
     * tmp data from tables from the plugins
     * @var array
     */
    protected $_database_data = array();

    /**
     * Extend class string
     * @var String
     */
    protected $_extend = '';

    /**
     * Extend class object
     */
    protected $_Extend = null;

    /**
     * Type string
     * @var String|false
     */
    protected $_type = false;

    /**
     * Package string
     * @var String|false
     */
    protected $_package = false;

    /**
     * Constructor
     *
     * @param \QUI\Projects\Project $Project
     * @param Integer $id
     */
    public function __construct(\QUI\Projects\Project $Project, $id)
    {
        $this->_id      = (int)$id;
        $this->_Project = $Project;
        $this->Events   = new \QUI\Events\Event();

        if ( empty( $this->_id ) ) {
            throw new \QUI\Exception( 'Site Error; No ID given:'. $id, 400 );
        }

        // DB Tables
        $this->_TABLE        = $Project->getAttribute('db_table');
        $this->_RELTABLE     = $this->_TABLE .'_relations';
        $this->_RELLANGTABLE = $Project->getAttribute('name') .'_multilingual';

        // Cachefiles
        $this->_CACHENAME =
            'site/'.
            $Project->getAttribute('name') .'/'.
            $Project->getAttribute('lang') .'/'.
            $this->getId();


        // view permission check
        $this->checkPermission( 'quiqqer.site.view' );

        // Im Adminbereich wird kein Cache verwendet
        if ( defined( 'ADMIN' ) && ADMIN == 1 ) {
            return true;
        }

        try
        {
            $this->decode(
                \QUI\Cache\Manager::get( $this->_CACHENAME )
            );

        } catch ( \QUI\Cache\Exception $Exception )
        {
            // Daten aus der DB hohlen
            $this->refresh();

            // Falls ein PHP Cache vorhanden ist
            // dann diesen nutzen anstatt das Filesystem
            \QUI\Cache\Manager::set( $this->_CACHENAME, $this->encode() );
        }

        // set the default type if no type is set
        if ( !$this->getAttribute( 'type' ) ) {
            $this->setAttribute( 'type', 'standard' );
        }
    }

    /**
     * Returns the Edit Site object from this Site
     *
     * @return \QUI\Projects\Site\Edit
     */
    public function getEdit()
    {
        return new \QUI\Projects\Site\Edit(
            $this->getProject(),
            $this->getId()
        );
    }

    /**
     * Return the project object of the site
     *
     * @return \QUI\Projects\Project
     */
    public function getProject()
    {
        return $this->_Project;
    }

    /**
     * Lädt die Plugins der Seite
     *
     * @param String $plugin - Plugin welches geladen werden soll, optional, ansonsten werden alle geladen
     * @return Site
     */
    public function load($plugin=false)
    {
        $this->_loadflag = true;

        $PackageManager = \QUI::getPackageManager();
        $packages       = $PackageManager->getInstalled();

        foreach ( $packages as $package )
        {
            if ( $plugin && $plugin != $package['name'] ) {
                continue;
            }

            $dir = OPT_DIR . $package['name'] .'/';

            $this->_loadDatabases( $dir, $package['name'] );
        }

        // onLoad event
        $this->Events->fireEvent( 'load', array( $this ) );
        \QUI::getEvents()->fireEvent( 'siteLoad', array( $this ) );


        // load type
        $type = $this->getAttribute('type');

        if ( strpos( $type, ':' ) === false ) {
            return $this;
        }

        // site.xml
        $explode = explode( ':', $type );
        $package = $explode[ 0 ];
        $type    = $explode[ 1 ];

        $this->_type    = $type;
        $this->_package = $package;

        $siteXml = OPT_DIR . $package .'/site.xml';

        $Dom   = \QUI\Utils\XML::getDomFromXml( $siteXml );
        $XPath = new \DOMXPath( $Dom );
        $Types = $XPath->query( '//type[@type="'. $type .'"]' );

        $Type   = $Types->item( 0 );
        $extend = false;

        if ( $Type ) {
            $extend = $Type->getAttribute( 'extend' );
        }

        if ( $extend )
        {
            $this->_extend  = $extend;
            $this->_Extends = new $extend();
        }


        return $this;


//         $Project = $this->getProject();

//         if ( $plugin )
//         {
//             $Plugins = \QUI::getPlugins();
//             $Plugins->get( $plugin );

//             $this->_loadPlugin( $Plugins->get( $plugin ) );

//             return $this;
//         }


//         $this->_load_plugins();

//         $Plugins = $this->_getLoadedPlugins();

//         // first the database data
//         foreach ( $Plugins as $Plugin ) {
//             $this->_loadDatabases( $Plugin );
//         }

//         // then the plugin attributes
//         foreach ( $Plugins as $Plugin ) {
//             $this->_loadPlugin( $Plugin );
//         }

//         return $this;
    }

    /**
     * Load the Plugin databases
     * database.xml
     *
     * @param String $dir - Path to the package
     * @param String $package - name of the package
     *
     * @rewrite it to events
     */
    protected function _loadDatabases($dir, $package)
    {
        $databaseXml = $dir .'database.xml';

        // database.xml
        if ( !file_exists( $databaseXml ) ) {
            return;
        }


        $DataBaseXML = \QUI\Utils\XML::getDomFromXml( $databaseXml );
        $projects    = $DataBaseXML->getElementsByTagName( 'projects' );

        if ( !$projects || !$projects->length ) {
            return;
        }

        $project_name = $this->getProject()->getName();
        $project_lang = $this->getProject()->getLang();
        $siteType     = $this->getAttribute( 'type' );

        $tables = $projects->item( 0 )->getElementsByTagName( 'table' );

        for ( $i = 0; $i < $tables->length; $i++ )
        {
            $Table = $tables->item( $i );

            if ( (int)$Table->getAttribute( 'no-site-reference' ) == 1 ) {
                continue;
            }

            // type check
            $types = $Table->getAttribute( 'site-types' );

            if ( $types ) {
                $types = explode( ',', $types );
            }

            if ( !empty( $types ) )
            {
                foreach ( $types as $allowedType )
                {
                    if ( !StringUtils::match( $allowedType, $siteType ) ) {
                        continue;
                    }
                }
            }

            // get database fields
            $fields = \QUI\Utils\DOM::dbTableDomToArray( $Table );

            if ( !isset( $fields['suffix'] ) ||
                 !isset( $fields['fields'] ) )
            {
                continue;
            }

            // get data
            $tbl       = $project_name .'_'. $project_lang .'_'. $fields['suffix'];
            $fieldList = array_keys( $fields['fields'] );

            $result = \QUI::getDataBase()->fetch(array(
                'select' => $fieldList,
                'from'   => $tbl,
                'where'  => array(
                    'id' => $this->getId()
                ),
                'limit' => 1
            ));

            // package.package.table.attribute
            $attributePrfx = str_replace('/', '.', $package .'.'. $fields['suffix'] );

            foreach ( $fieldList as $field )
            {
                if ( $field == 'id' ) {
                    continue;
                }

                if ( !isset( $result[0][ $field ] ) ) {
                    continue;
                }

                $this->setAttribute(
                    $attributePrfx .'.'. $field,
                    $result[0][ $field ]
                );
            }
        }
    }

    /**
     * Serialisierungsdaten
     *
     * @return String
     */
    public function encode()
    {
        $att = $this->getAttributes();

        $att['extra']         = $this->_extra;
        $att['linked_parent'] = $this->_LINKED_PARENT;
        $att['_type']         = $this->_type;
        $att['_extend']       = $this->_extend;

        unset( $att['project'] );

        return json_encode($att);
    }

    /**
     * Setzt JSON Parameter
     *
     * @param String $params - JSON encoded string
     */
    public function decode($params)
    {
        $Project = $this->getProject();
        $decode  = json_decode( $params, true );

        if ( $decode['active'] != 1 ) {
            throw new \QUI\Exception( 'Site not exist', 404 );
        }

        if ( $decode['deleted'] == 1 ) {
            throw new \QUI\Exception( 'Site not exist', 404 );
        }

        if ( isset( $decode['extra'] ) )
        {
            $this->_extra = $decode['extra'];
            unset($decode['extra']);
        }

        if ( isset( $decode['linked_parent'] ) )
        {
            $this->_LINKED_PARENT = $decode['linked_parent'];
            unset($decode['linked_parent']);
        }

        $this->_attributes = $decode;
    }

    /**
     * Hohlt frisch die Daten aus der DB
     */
    public function refresh()
    {
        $this->_loadflag = false;

        $result = \QUI::getDataBase()->fetch(array(
            'from'  => $this->_TABLE,
            'where' => array(
                'id' => $this->getId()
            ),
            'limit' => '1'
        ));

        if ( !isset( $result[0] ) )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.site.not.found'
                ),
                404
            );
        }

        $params = $result[0];

        if ( $params['active'] != 1 )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.site.not.found'
                ),
                404
            );
        }

        if ( $params['deleted'] == 1 )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.site.not.found'
                ),
                404
            );
        }


        // Verknüpfung hohlen
        if ( $this->getId() != 1 )
        {
            $relresult = \QUI::getDataBase()->fetch(array(
                'from'  => $this->_RELTABLE,
                'where' => array(
                    'child' => $this->getId()
                )
            ));

            if ( isset( $relresult[0] ) )
            {
                foreach ( $relresult as $entry )
                {
                    if ( !isset( $entry['oparent'] ) ) {
                        continue;
                    }

                    $this->_LINKED_PARENT = $entry['oparent'];
                }
            }
        }

        if ( isset( $result[0]['extra'] ) )
        {
            $this->_extra = json_decode( $result[0]['extra'], true );
            unset( $result[0]['extra'] );
        }

        $this->setAttributes( $result[0] );
    }

    /**
     * Prüft ob es eine Verknüpfung ist
     *
     * @return unknown
     */
    public function isLinked()
    {
        if ( $this->_LINKED_PARENT == false ) {
            return false;
        }

        return $this->_LINKED_PARENT;
    }

    /**
     * Prüft ob es die Seite auch in einer anderen Sprache gibt
     *
     * @param String $lang
     * @param Bool $check_only_active - check only active pages
     * @return Bool
     */
    public function existLang($lang, $check_only_active=true)
    {
        if ( empty( $lang ) ) {
            return false;
        }

        $Project = $this->getProject();

        if ( $lang == $Project->getAttribute('lang') ) {
            return true;
        }

        // Sprachen ID rausbekommen
        $lang_id = $this->getId($lang);

        // Wenn keine ID dann gleich raus
        if ( $lang_id == false ) {
            return false;
        }

        // Wenn es egal ist ob aktive oder inaktive
        if ( $check_only_active == false && $lang_id == true ) {
            return true;
        }

        try
        {
            $_Project = \QUI::getProject(
                $Project->getAttribute('name'),
                $lang
            );

            $_Site = $_Project->get( $lang_id );

            return true;

        } catch ( \QUI\Exception $e )
        {
            // nothing
        }

        return false;
    }

    /**
     * Gibt die IDs von Sprachverknüpfungen zurück
     *
     * @return Array
     */
    public function getLangIds()
    {
        $result = array();

        try
        {
            $Project = $this->getProject();

            $dbResult = \QUI::getDataBase()->fetch(array(
                'from'  => $Project->getAttribute('name') .'_multilingual',
                'where' => array(
                    $Project->getAttribute('lang') => $this->getId()
                )
            ));

            $langs = $Project->getAttribute('langs');

            foreach ( $langs as $lang )
            {
                if ( isset( $dbResult[0][ $lang ] ) )
                {
                    $result[ $lang ] = $dbResult[0][ $lang ];
                    continue;
                }

                $result[ $lang ] = false;
            }

        } catch ( \QUI\Exception $Exception )
        {

        }

        return $result;
    }

    /**
     * Return the ID of the site,
     * or the ID of the sibling (linked) site of another languager
     *
     * @param String $lang - optional, if it is set, then the language of the wanted linked sibling site
     * @return Integer
     */
    public function getId($lang=false)
    {
        if ( $lang === false ) {
            return $this->_id;
        }

        // other languages
        $Project = $this->getProject();

        if ( $lang === $Project->getAttribute( 'lang' ) ) {
            return $this->_id;
        }

        // ???
        if ( isset( $this->_lang_ids[ $lang ] ) ) {
            return $this->_lang_ids[ $lang ];
        }

        $pname = $Project->getAttribute( 'name' );
        $plang = $Project->getAttribute( 'lang' );

        $site_table = $pname .'_'. $plang .'_sites';
        $lang_table = $pname .'_'. $lang.'_sites';
        $rel_table  = $pname .'_multilingual';

        $PDO = \QUI::getPDO();

        $Statment = $PDO->prepare('
            SELECT `'. $rel_table .'`.`'. $lang .'`
            FROM `'. $site_table .'`, `'. $lang_table .'`, `'. $rel_table .'`
            WHERE
                `'. $rel_table .'`.`'. $lang .'`  = `'. $lang_table.'`.`id` AND
                `'. $rel_table .'`.`'. $plang .'` = `'. $site_table.'`.`id` AND
                `'. $site_table .'`.`id`          = :id
               LIMIT 1;
        ');

        $Statment->bindValue( ':id', $this->getId(), \PDO::PARAM_INT );
        $Statment->execute();

        $result = $Statment->fetchAll( \PDO::FETCH_ASSOC );

        if ( isset( $result[0] ) && isset( $result[0][$lang] ) )
        {
            $this->_lang_ids[ $lang ] = (int)$result[0][ $lang ];

            return $this->_lang_ids[ $lang ];
        }

        return false;
    }

    /**
     * Ladet die benötigten Plugins für das Site Objekt
     */
    protected function _load_plugins()
    {
        $this->_plugins = \QUI\Projects\Sites::getPlugins( $this );
    }

    /**
     * Gibt die geladenen Plugins zurück
     *
     * @return unknown
     */
    protected function _getLoadedPlugins()
    {
        return $this->_plugins;
    }

    /**
     * Gibt alle Kinder zurück
     *
     * @param array $params - Parameter für die Childrenausgabe
     * 				$params['where']
     * 				$params['limit']
     * @param Bool $load - Legt fest ob die Kinder die Plugins laden sollen
     *
     * @return Array;
     */
    public function getChildren($params=array(), $load=false)
    {
        if ( !is_array( $params ) ) {
            $params = array();
        }

        // Falls kein Order übergeben wird das eingestellte Site Order
        if ( !isset( $params['order'] ) )
        {
            switch ( $this->getAttribute( 'order_type' ) )
            {
                case 'name_down':
                    $params['order'] = 'name DESC';
                break;

                case 'name_up':
                    $params['order'] = 'name ASC';
                break;

                case 'date_down':
                    $params['order'] = 'c_date DESC, name ASC';
                break;

                case 'date_up':
                    $params['order'] = 'c_date ASC, name ASC';
                break;
            }
        }

        // Tabs der Plugins hohlen
        $Plugins = $this->_getLoadedPlugins();

        foreach ( $Plugins as $Plugin )
        {
            if ( method_exists( $Plugin, 'onGetChildren' ) ) {
                $params = $Plugin->onGetChildren( $this, $params );
            }
        }

        if ( !isset( $params['order'] ) || empty( $params['order'] ) ) {
            $params['order'] = 'order_field';
        }

        $result = $this->getChildrenIds( $params );

        if ( isset( $params['count'] ) ) {
            return (int)$result;
        }


        $children = array();

        foreach ( $result as $id )
        {
            try
            {
                $Child = $this->getChild( (int)$id );

                if ( $load ) {
                    $Child->load();
                }

                $Child->setAttribute( 'parent', $this );

                $children[] = $Child;

            } catch ( \QUI\Exception $e )
            {
                if ( DEBUG_MODE ) {
                    \QUI\System\Log::writeException( $e );
                }
            }
        }

        return $children;
    }

    /**
     * Zur Verfügung stehende Sortierungen der Kinder
     *
     * @return Array
     */
    public function getSorts()
    {
        // Tabs der Plugins hohlen
        $Plugins = $this->_getLoadedPlugins();
        $sorts   = array(
            'manuel'    => 'Manuelle Sortierung',
            'name_down' => 'Namen absteigend',
            'name_up'   => 'Namen aufsteigend',
            'date_down' => 'Erstellungsdatum absteigend',
            'date_up'   => 'Erstellungsdatum aufsteigend'
        );

        foreach ($Plugins as $plg)
        {
            if (method_exists($plg, 'onGetSorts')) {
                $sorts = array_merge($sorts, $plg->onGetSorts($this));
            }
        }

        return $sorts;
    }

    /**
     * Liefert die nächstfolgende Seite
     *
     * @return \QUI\Projects\Site
     * @throws \QUI\Exception
     */
    public function nextSibling()
    {
        $Parent  = $this->getParent();
        $Project = $this->getProject();
        $list    = $Parent->getChildrenIds();

        foreach ($list as $key => $id)
        {
            if ($id == $this->getId() && isset($list[ $key+1 ])) {
                return $Project->get( (int)$list[ $key+1 ] );
            }
        }

        throw new \QUI\Exception('Die Seite besitzt keine nächstfolgende Seite');
    }

    /**
     * Die nächsten x Kinder
     *
     * @param Integer $no
     * @return Array
     */
    public function nextSiblings($no)
    {
        $no     = (int)$no;
        $result = array();

        $Parent  = $this->getParent();
        $Project = $this->getProject();
        $list    = $Parent->getChildrenIds();

        foreach ($list as $key => $id)
        {
            if ($id != $this->getId()) {
                continue;
            }

            // die nächsten x Kinder
            for ($i = 1; $i < $no; $i++)
            {
                if (isset($list[ $key + $i ]))
                {
                    try
                    {
                        $result[] = $Project->get( (int)$list[ $key + $i ] );
                    } catch (\QUI\Exception $e)
                    {

                    }
                }
            }
        }

        return $result;
    }

    /**
     * Liefert die vorhergehenden Seite
     *
     * @return \QUI\Projects\Site
     * @throws \QUI\Exception
     */
    public function previousSibling()
    {
        $Parent  = $this->getParent();
        $Project = $this->getProject();
        $list    = $Parent->getChildrenIds();

        foreach ($list as $key => $id)
        {
            if ($id == $this->getId() && isset($list[ $key-1 ])) {
                return $Project->get( (int)$list[ $key-1 ] );
            }
        }

        throw new \QUI\Exception('Die Seite besitzt keine vorhergehenden Seite');
    }

    /**
     * Die x vorhergehenden Geschwister
     *
     * @param Integer $no
     * @return Array
     */
    public function previousSiblings($no)
    {
        $no     = (int)$no;
        $result = array();

        $Parent  = $this->getParent();
        $Project = $this->getProject();
        $list    = $Parent->getChildrenIds();

        foreach ($list as $key => $id)
        {
            if ($id != $this->getId()) {
                continue;
            }

            // die nächsten x Kinder
            for ($i = 1; $i < $no; $i++)
            {
                if (isset($list[ $key - $i ]))
                {
                    try
                    {
                        $result[] = $Project->get( (int)$list[ $key - $i ] );
                    } catch (\QUI\Exception $e)
                    {

                    }
                }
            }
        }

        return $result;
    }

    /**
     * Gibt das erste Kind der Seite zurück
     *
     * @param Array $params
     * @return \QUI\Projects\Site | false
     */
    public function firstChild($params=array())
    {
        if ( !is_array($params) ) {
            $params = array();
        }

        $params['limit'] = '1';

        $children = $this->getChildren($params);

        if ( isset($children[0]) ) {
            return $children[0];
        }

        return false;
    }

    /**
     * Gibt die Kinder zurück achtet aber auf "Nicht in Navigation anzeigen" und Rechte
     *
     * @param array $params
     */
    public function getNavigation($params=array())
    {
        if ( !is_array( $params ) ) {
            $params = array();
        }

        $params['where']['nav_hide'] = 0;

        return $this->getChildren( $params );
    }

    /**
     * Gibt ein Kind zurück welches den Namen hat
     *
     * @param unknown_type $name
     */
    public function getChildIdByName($name)
    {
         $result = \QUI::getDataBase()->fetch(array(
            'from' 	=> array(
                $this->_RELTABLE,
                $this->_TABLE
            ),
            'where' => array(
                $this->_RELTABLE .'.parent' => $this->getId(),
                $this->_TABLE .'.deleted'   => 0,
                $this->_RELTABLE .'.child'  => '`'. $this->_TABLE .'.id`',

                // LIKE muss bleiben wegen _,
                // sonst werden keine Seiten mehr gefunden
                $this->_TABLE .'.name' => array(
                    'type'  => 'LIKE',
                    'value' => str_replace('-', '_', $name)
                )
            ),
            'limit' => 1
        ));

        if ( isset( $result[0] ) && isset( $result[0]["id"] ) ) {
            return $result[0]["id"];
        }

        throw new \QUI\Exception(
            'No Child found with name '. $name, 404
        );
    }

    /**
     * Return a children by id
     *
     * @param Integer $id
     * @return \QUI\Projects\Site
     */
    public function getChild($id)
    {
        $id = (int)$id;

        if ( $id == $this->getId() ) {
            throw new \QUI\Exception( 'Page can not be a child of itself' );
        }

        if ( isset( $this->_children[ $id ] ) ) {
            return $this->_children[ $id ];
        }

        $result = \QUI::getDataBase()->fetch(array(
            'from' 	=> $this->_RELTABLE,
            'where' => array(
                'parent' => $this->getId(),
                'child'  => $id
            ),
            'limit' => 1
        ));

        if ( !isset( $result[0] ) ) {
            throw new \QUI\Exception( 'Child not found', 404 );
        }

        $this->_children[ $id ] = $this->getProject()->get( $id );

        return $this->_children[ $id ];
    }

    /**
     * Gibt die ID's der Kinder zurück
     * Wenn nur die ID's verwendet werden sollte dies vor getChildren verwendet werden
     *
     * @param $params Parameter für die Childrenausgabe
     * 			$params['where']
     * 			$params['limit']
     *
     * @return Array
     */
    public function getChildrenIds($params=array())
    {
        return $this->getProject()->getChildrenIdsFrom(
            $this->getId(),
            $params
        );
    }

    /**
     * Return ALL children ids under the site
     *
     * @param array $params - db parameter
     * @return array
     */
    public function getChildrenIdsRecursive($params=array())
    {
        $this->_childs_container = array();
        $this->_recursivHelper($this->getId(), $params);

        return $this->_childs_container;
    }

    /**
     * Bereitet die ID's der Kinder vor -> getChildrenIds()
     * Funktion welche rekursiv aufgerufen wird
     *
     * @param unknown_type $pid
     * @param unknown_type $params
     */
    protected function _recursivHelper($pid, $params=array())
    {
        $ids = $this->getProject()->getChildrenIdsFrom($pid, $params);

        if (empty($ids)) {
            return;
        }

        foreach ($ids as $id)
        {
            $this->_childs_container[] = $id;
            $this->_recursivHelper($id, $params);
        }
    }

    /**
     * Gibt zurück ob Site Kinder besitzt
     *
     * @param Bool $navhide - if navhide == false, navhide must be 0
     * @return Integer - Anzahl der Kinder
     */
    public function hasChildren($navhide=false)
    {
        // where
        $where =
            '`'. $this->_RELTABLE .'`.`parent` = :pid AND '.
            '`'. $this->_TABLE .'`.`deleted` = :deleted AND '.
            '`'. $this->_RELTABLE .'`.`child` = `'. $this->_TABLE .'`.`id`';

        if ($navhide === false) {
            $where .= ' AND `' . $this->_TABLE .'`.`nav_hide` = :nav_hide';
        }

        // prepared
        $prepared = array(
            ':pid'      => $this->getId(),
            ':deleted'  => 0
        );

        if ($navhide === false) {
            $prepared[':nav_hide'] = 0;
        }

        // statement
        $Statement = \QUI::getPDO()->prepare(
            'SELECT COUNT(id) AS idc
            FROM `'. $this->_RELTABLE .'`, `'. $this->_TABLE .'`
            WHERE '. $where .'
            LIMIT 1'
        );

        $Statement->execute($prepared);
        $result = $Statement->fetchAll( \PDO::FETCH_ASSOC );

        return $result[0]['idc'];
    }

    /**
     * Setzt das delete Flag
     */
    public function delete()
    {
        if ( $this->getAttribute( 'id' ) == 1 ) {
            return false;
        }

        // Rekursiv alle Kinder bekommen
        $children = $this->getChildrenIdsRecursive();

        \QUI::getDataBase()->update(
            $this->_TABLE,
            array( 'deleted' => 1 ),
            array( 'id' => $this->getId() )
        );

        foreach ( $children as $child )
        {
            \QUI::getDataBase()->update(
                $this->_TABLE,
                array( 'deleted' => 1 ),
                array( 'id' => $child )
            );
        }

        return true;
    }

    /**
     * Überprüft ob es den Namen bei den Kindern schon gibt
     *
     * @param String $name
     * @return Bool
     */
    protected function _existNameInChildren($name)
    {
        $result = \QUI::getDataBase()->fetch(array(
            'from' 	=> array(
                $this->_RELTABLE,
                $this->_TABLE
            ),
            'where' => 	array(
                $this->_RELTABLE .'.parent' => $this->getId(),
                $this->_TABLE .'.deleted'   => 0,
                $this->_RELTABLE .'.child'  => $this->_TABLE .'.id',
                $this->_TABLE .'.name'      => $name
            ),
            'limit' => '1'
        ));

        return count($result) ? true : false;
    }

    /**
     * Gibt die URL der Seite zurück
     *
     * @param $params
     * @param $rewrited
     *
     * @return String
     */
    public function getUrl($params=array(), $rewrited=false)
    {
        $Rewrite = \QUI::getRewrite();

        if ( !is_array( $params ) ) {
            $params = array();
        }

        if ( $rewrited )
        {
            $params['site'] = $this;
            return $Rewrite->getUrlFromSite( $params );
        }

        $str = 'index.php?id='. $this->getId() .
               '&project='. $this->getProject()->getAttribute( 'name' ) .
               '&lang='. $this->getProject()->getAttribute( 'lang' );

        foreach ( $params as $param => $value )
        {
            if ( empty( $value ) ) {
                continue;
            }

            $str .= '&'. $param .'='. $value;
        }

        return $str;
    }

    /**
     * Gibt eine sprechenden URL zurück
     * DB Abfragen werden gemacht - Hier auf Performance achten
     *
     * @param Array $params - Parameter welche an die URL angehängt werden
     * @return String
     */
    public function getUrlRewrited($params=array())
    {
        if ( $this->getId() == 1 )
        {
            if ( empty( $params ) ) {
                return '';
            }

            $url = '';

            foreach ( $params as $param => $value )
            {
                if ( $param != 'suffix' && !empty( $value )  )
                {
                    $url .= \QUI\Rewrite::URL_PARAM_SEPERATOR . $param . \QUI\Rewrite::URL_PARAM_SEPERATOR . $value;
                    continue;
                }

                $url .= \QUI\Rewrite::URL_PARAM_SEPERATOR . $param;
            }

            if ( isset( $params['suffix'] ) ) {
                return $url .'.'. $params['suffix'];
            }

            return $url .'.html';
        }

        $url     = '';
        $Project = $this->getProject();
        $lang    = $Project->getAttribute('lang');

        $default_lang = $Project->getAttribute('default_lang');

        // Plugins können hier eingreifen
        // @todo event
        if ( \QUI::conf('system', 'geturl') )
        {
            $plugins = explode(',', \QUI::conf('system', 'geturl'));
            $Plugins = \QUI::getPluginManager();

            foreach ( $plugins as $plugin )
            {
                $Plugin = $Plugins->get( $plugin );

                if ( method_exists($Plugin, 'getUrl') )
                {
                    $_url = $Plugin->getUrl( $this );

                    if ( $_url ) {
                        return $_url;
                    }
                }
            }
        }


        // Url zusammen bauen
        if ( $this->_url != false )
        {
            $url = $this->_url;
        } else
        {
            if ( $this->getParentId() && $this->getParentId() != 1 )
            // Wenn parent nicht die Startseite ist, dann muss der Pfad generiert werden
            {
                $this->_getUrl($this->getId());

                foreach ( array_reverse($this->_parents) as $parent ) {
                    $url .= \QUI\Rewrite::replaceUrlSigns( $parent, true ).'/'; // URL auch Slash ersetzen
                }
            }

            $this->_url = $url;
        }

        $url .= \QUI\Rewrite::replaceUrlSigns( $this->getAttribute('name'), true );

        foreach ( $params as $param => $value )
        {
            if ( $param != 'suffix' && !empty( $value ) )
            {
                $url .= \QUI\Rewrite::URL_PARAM_SEPERATOR . $param . \QUI\Rewrite::URL_PARAM_SEPERATOR . $value;
                continue;
            }

            $url .= \QUI\Rewrite::URL_PARAM_SEPERATOR . $param;
        }

        if ( isset( $params['suffix'] ) ) {
            return $url .'.'. $params['suffix'];
        }

        return $url .'.html';
    }

    /**
     * rekursiver Aufruf getUrl
     *
     * @param Integer $id - Site ID
     */
    protected function _getUrl($id)
    {
        if ( $id != $this->getId() ) {
            $this->_parents[] = $this->getProject()->getNameById( $id );
        }

        $pid = $this->getProject()->getParentIdFrom( $id );

        if ( $pid && $pid != 1 ) {
            $this->_getUrl( $pid );
        }
    }

    /**
     * Return the Parent id from the site object
     *
     * @return Integer
     */
    public function getParentId()
    {
        if ( $this->getId() == 1 ) {
            return 0;
        }

        if ( $this->_parent_id ) {
            return $this->_parent_id;
        }

        $this->_parent_id = $this->getProject()->getParentIdFrom(
            $this->getId()
        );

        return $this->_parent_id;
    }

    /**
     * Gibt alle Eltern Ids zurück
     *
     * Site
     * ->Parent
     * ->Parent
     * ->Parent
     *
     * @return Array
     */
    public function getParentIds()
    {
        if ( $this->getId() == 1 ) {
            return 0;
        }

        if ( is_array( $this->_parents_id ) ) {
            return $this->_parents_id;
        }

        $result = \QUI::getDataBase()->fetch(array(
            'select' => 'parent',
            'from' 	 => $this->_RELTABLE,
            'where'  => array(
                'child' => $this->getId()
            ),
            'order'  => 'oparent ASC'
        ));

        $pids = array();

        if ( !isset( $result[0] ) ) {
            return $pids;
        }

        foreach ( $result as $entry ) {
            $pids[] = $entry['parent'];
        }

        $this->_parents_id = $pids;

        return $pids;
    }

    /**
     * Gibt das Parent Objekt zurück
     *
     * @return Site
     */
    public function getParent()
    {
        if ( !$this->getParentId() ) {
            return false;
        }

        return $this->getProject()->get(
            $this->getParentId()
        );
    }

    /**
     * Gibt alle rekursive Parents als Objekte zurück
     * Site->Parent->ParentParent->ParentParentParent
     *
     * @return Array
     */
    public function getParents()
    {
        $Project = $this->getProject();
        $parents = array();

        $search  = true;
        $id      = $this->getParentId();

        while ( $search )
        {
            try
            {
                $Parent    = $Project->get( $id );
                $parents[] = $Parent;

                $id = $Parent->getParentId();

                if ( $id == 0 ) {
                    $search = false;
                }

            } catch ( \QUI\Exception $e )
            {
                $search = false;
            }
        }

        return array_reverse( $parents );
    }

    /**
     * Stellt die Seite wieder her
     *
     * ??? wieso hier? und nicht im trash? O.o
     */
    public function restore()
    {
        \QUI::getDataBase()->update(
            $this->_TABLE,
            array( 'deleted' => 0 ),
            array( 'id' => $this->getId() )
        );
    }

    /**
     * Zerstört die Seite
     * Die Seite wird komplett aus der DB gelöscht und auch alle Beziehungen
     * Funktioniert nur wenn die Seite gelöscht ist
     */
    public function destroy()
    {
        if ( $this->getAttribute( 'deleted' ) != 1 ) {
            return;
        }

        /**
         *  Plugins Tabellen löschen
         */
        $Plugins = $this->_getLoadedPlugins();
        $Project = $this->getProject();

        $DataBase = \QUI::getDataBase();

        foreach ( $Plugins as $Plugin )
        {
            if ( method_exists( $Plugin, 'onDestroy' ) )
            {
                try
                {
                    $Plugin->onDestroy( $this, $Project, $DataBase );
                } catch ( \QUI\Exception $e )
                {
                    \QUI\System\Log::writeException( $e );
                }
            }

            // Falls im AdminPlugin noch was gesetzt wurde
            $AdminPlugin = $Plugin->getAdminPlugin();

            if ( method_exists( $AdminPlugin, 'onDestroy' ) ) {
                $plugins->onDestroy( $this, $Project, $DataBase );
            }
        }

        // Daten löschen
        $DataBase->exec(array(
            'delete' => true,
            'from'   => $this->_TABLE,
            'where'  => array(
                'id' => $this->getId()
            )
        ));

        // sich als Kind löschen
        $DataBase->exec(array(
            'delete' => true,
            'from'   => $this->_RELTABLE,
            'where'  => array(
                'child' => $this->getId()
            )
        ));

        // sich als parent löschen
        $DataBase->exec(array(
            'delete' => true,
            'from'   => $this->_RELTABLE,
            'where'  => array(
                'parent' => $this->getId()
            )
        ));

        // @todo Rechte löschen

        // Cache löschen
        $this->deleteCache();
    }

    /**
     * Gibt eine Extra Eigenschaft zurück
     *
     * @param String $name
     * @return unknown
     */
    public function getExtra($name)
    {
        if ( isset( $this->_extra[ $name ] ) ) {
            return $this->_extra[ $name ];
        }

        return false;
    }

    /**
     * Canonical URL - Um doppelte Inhalt zu vermeiden
     *
     * @return String
     */
    public function getCanonical()
    {
        if ( $this->getAttribute( 'canonical' ) ) {
            return $this->getAttribute( 'canonical' );
        }

        $this->setAttribute(
            'canonical',
            $this->getProject()->getHost() . URL_DIR . $this->getUrlRewrited()
        );

        return $this->getAttribute( 'canonical' );
    }

    /**
     * Löscht den Seitencache
     */
    public function deleteCache()
    {
        \QUI\Cache\Manager::clear( $this->_CACHENAME );
    }

    /**
     * Löscht den Seitencache
     */
    public function createCache()
    {
        \QUI\Cache\Manager::set( $this->_CACHENAME, $this->encode() );
    }

    /**
     * Shortcut for \QUI\Rights\Permission::hasSitePermission
     *
     * @param string $permission - name of the permission
     * @param \QUI\Users\User $User - optional
     * @return Bool|Integer
     */
    public function hasPermission($permission, $User=false)
    {
        return \QUI\Rights\Permission::hasSitePermission(
            $permission,
            $this,
            $User
        );
    }

    /**
     * Shortcut for \QUI\Rights\Permission::checkSitePermission
     *
     * @param string $permission - name of the permission
     * @param \QUI\Users\User $User - optional
     * @throws \QUI\Exception
     */
    public function checkPermission($permission, $User=false)
    {
        \QUI\Rights\Permission::checkSitePermission(
            $permission,
            $this,
            $User
        );
    }
}
