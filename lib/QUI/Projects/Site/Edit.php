<?php

/**
 * This file contains the QUI\Projects\Site\Edit
 */

namespace QUI\Projects\Site;

use \QUI\Utils\String as StringUtils;

/**
 * Site Objekt für den Admibereich
 *
 * Stellt Methoden für den Admibereich zur Verfügung welche auf der Seite nicht benötig werden
 * Hauptänderung ist das Cacheing von Änderungen und das Speichern aus der Tempdatei
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.project
 *
 * @copyright  2008 PCSG
 * @since      Class available since Release QUIQQER 0.1
 *
 * @errorcodes
 * <ul>
 * <li>701 - Error Name: 2 signs or lower</li>
 * <li>702 - Error Name: Not supported signs in Name</li>
 * <li>703 - Error Name: Duplicate Entry in Parent; Child width the same Name exist</li>
 * <li>704 - Error Name: 200 signs or higher</li>
 * </ul>
 *
 * @todo Sortierung als eigene Methoden
 * @todo Rechte Prüfung
 * @todo site plugin erweiterungen
 * @todo translation der quelltext doku
 *
 * @qui-event onSiteActivate [ \QUI\Projects\Site\Edit ]
 * @qui-event onSiteDeactivate [ \QUI\Projects\Site\Edit ]
 * @qui-event onSiteSave [ \QUI\Projects\Site\Edit ]
 *
 * @event onSiteCreateChild [ Integer $newId, \QUI\Projects\Site\Edit ]
 * @event onActivate [ \QUI\Projects\Site\Edit ]
 * @event onDeactivate [ \QUI\Projects\Site\Edit ]
 * @event onSave [ \QUI\Projects\Site\Edit ]
 */

class Edit extends \QUI\Projects\Site
{
    const ESAVE  = 3;
    const EACCES = 4;

    /**
     * Project conf <<------ ??? why here
     * @var array
     */
    public $conf = array();

    /**
     * Konstruktor
     *
     * @param \QUI\Projects\Project $Project
     * @param unknown_type $id
     */
    public function __construct(\QUI\Projects\Project $Project, $id)
    {
        parent::__construct( $Project, $id );

        $this->refresh();

        $id       = $this->getId();
        $DataBase = \QUI::getDataBase();

        $this->_marcatefile = VAR_DIR .'marcate/'.
                              $Project->getAttribute( 'name' ) .'_'.
                              $id .'_'. $Project->getAttribute( 'lang' );

        // Temp Dir abfragen ob existiert
        \QUI\Utils\System\File::mkdir( VAR_DIR .'admin/' );

        // Erste Rechteprüfung
        $User = \QUI::getUserBySession();

        if ( !$User->getId() ) {
            return false;
        }

        $this->load();


        // Onload der Plugins ausführen
        $Plugins = $this->_getLoadedPlugins();

        foreach ( $Plugins as $plg )
        {
            if ( method_exists( $plg, 'onLoad' ) ) {
                $plg->onLoad( $this );
            }
        }

        // onInit event
        $this->Events->fireEvent( 'init', array( $this ) );
        \QUI::getEvents()->fireEvent( 'siteInit', array( $this ) );
    }

    /**
     * Plugins laden
     * @todo anschauen wegen admin zeugs
     */
    protected function _load_plugins()
    {
        // Globale requireds
        $Project = $this->getProject();
        $Plugin  = \QUI::getPluginManager();

        // Plugins laden
        parent::_load_plugins();

        // zusätzlich noch globale Sachen
        // @todo muss noch in die Plugin Klasse
        $global_scripts = $Project->getGlobalTypes();

        if ( !isset( $global_scripts['admin'] ) ) {
            return;
        }

        foreach ( $global_scripts['admin'] as $plug => $p )
        {
            $class = 'Global_'.$plug;

            if ( !class_exists( $class ) )
            {
                if ( !is_array( $p ) ) {
                    continue;
                }

                foreach ( $p as $p_file )
                {
                    if ( file_exists( $p_file ) ) {
                        require_once $p_file;
                    }
                }
            }

            if ( class_exists( $class ) ) {
                $this->_plugins[] = new $class();
            }
        }
    }

    /**
     * Hohlt frisch die Daten aus der DB
     */
    public function refresh()
    {
        $result = \QUI::getDataBase()->fetch(array(
            'from'  => $this->_TABLE,
            'where' => array(
                'id' => $this->getId()
            ),
            'limit' => '1'
        ));

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

        foreach ( $result[0] as $a_key => $a_val )
        {
            // Extra-Feld behandeln
            if ( $a_key == 'extra' )
            {
                if ( empty( $a_val ) ) {
                    continue;
                }

                // @todo get extra attribute list

                $extra = json_decode( $a_val, true );

                foreach ( $extra as $key => $value ) {
                    $this->setAttribute( $key, $value );
                }

                continue;
            }

            $this->setAttribute( $a_key, $a_val );
        }
    }

    /**
     * Activate a site
     *
     * @throws \QUI\Exception
     */
    public function activate()
    {
        try
        {
            $this->checkPermission( 'quiqqer.projects.site.edit' );

        } catch ( \QUI\Exception $Exception )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.permissions.edit'
                )
            );
        }

        $this->Events->fireEvent( 'activate', array( $this ) );

        \QUI::getEvents()->fireEvent( 'siteActivate', array( $this ) );


        /*
        $release_from = strtotime(
            $this->getAttribute('pcsg.base.release_from')
        );

        $release_until = strtotime(
            $this->getAttribute('pcsg.base.release_until')
        );

        if ( $release_from && $release_from > time() )
        {
            throw new \QUI\Exception(
                'Die Seite kann nicht aktiviert werden, da das Datum "Veröffentlichen von" in der Zukunft liegt'
            );
        }

        if ( $release_until && $release_until < time() )
        {
            throw new \QUI\Exception(
                'Die Seite kann nicht aktiviert werden, da das Datum "Veröffentlichen bis" in der Vergangenheit liegt'
            );
        }
        */

        \QUI::getDataBase()->exec(array(
            'update' => $this->_TABLE,
            'set'    => array(
                'active' => 1
            ),
            'where'  => array(
                'id' => $this->getId()
            )
        ));

        $this->deleteCache();
        $this->getProject()->clearCache();
    }

    /**
     * Deactivate a site
     *
     * @throws \QUI\Exception
     */
    public function deactivate()
    {
        try
        {
            // Prüfen ob der Benutzer die Seite bearbeiten darf
           $this->checkPermission( 'quiqqer.projects.site.edit' );

        } catch ( \QUI\Exception $Exception )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.permissions.edit'
                )
            );
        }

        // fire events
        $this->Events->fireEvent( 'deactivate', array( $this ) );

        \QUI::getEvents()->fireEvent( 'siteDeactivate', array( $this ) );


        // deactivate
        \QUI::getDataBase()->exec(array(
            'update' => $this->_TABLE,
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
         * package destroy
         */
        $Project  = $this->getProject();
        $packages = \QUI::getPackageManager()->getPackageDatabaseXmlList();
        $name     = $Project->getName();
        $lang     = $Project->getLang();
        $siteType = $this->getAttribute( 'type' );

        // @todo fields and table list must cached -> performance
        foreach ( $packages as $package )
        {
            $file = OPT_DIR . $package .'/database.xml';

            $Dom  = \QUI\Utils\XML::getDomFromXml( $file );
            $Path = new \DOMXPath( $Dom );

            $tableList = $Path->query( "//database/projects/table" );

            for ( $i = 0, $len = $tableList->length; $i < $len; $i++ )
            {
                $Table = $tableList->item( $i );

                if ( $Table->getAttribute( 'no-auto-update' ) ) {
                    continue;
                }

                // types check
                $types = $Table->getAttribute( 'site-types' );

                if ( $types ) {
                    $types = explode( ',', $types );
                }

                if ( !empty( $types ) )
                {
                    foreach ( $types as $allowedType )
                    {
                        if ( !StringUtils::match( $allowedType, $siteType ) ) {
                            continue 2;
                        }
                    }
                }

                // destroy package sites
                $suffix = $Table->getAttribute( 'name' );
                $fields = $Table->getElementsByTagName( 'field' );

                $table = \QUI::getDBTableName( $name .'_'. $lang .'_'. $suffix );

                $result = \QUI::getDataBase()->fetch(array(
                    'from' => $table,
                    'where' => array(
                        'id' => $this->getId()
                    )
                ));

                if ( isset( $result[0] ) )
                {
                    \QUI::getDataBase()->delete($table, array(
                        'id' => $this->getId()
                    ));
                }
            }
        }

        // on destroy event
        $this->Events->fireEvent( 'destroy', array($this) );

        \QUI::getEvents()->fireEvent( 'siteDestroy', array($this) );


        /**
         * Site destroy
         */

        // Daten löschen
        \QUI::getDataBase()->delete($this->_TABLE, array(
            'id' => $this->getId()
        ));

        // sich als Kind löschen
        \QUI::getDataBase()->delete($this->_RELTABLE, array(
            'child' => $this->getId()
        ));

        // sich als parent löschen
        \QUI::getDataBase()->delete($this->_RELTABLE, array(
            'parent' => $this->getId()
        ));

        // Rechte löschen
        $Manager = \QUI::getPermissionManager();
        $Manager->removeSitePermissions( $this );

        // Cache löschen
        $this->deleteCache();
    }

    /**
     * Saves the site
     *
     * @throws \QUI\Exception
     */
    public function save()
    {
        try
        {
            // Prüfen ob der Benutzer die Seite bearbeiten darf
            $this->checkPermission( 'quiqqer.project.site.edit' );

        } catch ( \QUI\Exception $Exception )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.permissions.edit'
                )
            );
        }

        $mid = $this->isMarcate();

        if ( $mid )
        {
            try
            {
                $User = \QUI::getUsers()->get( $mid );

            } catch ( \QUI\Exception $Exception )
            {

            }

            if ( isset( $User ) )
            {
                throw new \QUI\Exception(
                    \QUI::getLocale()->get(
                        'quiqqer/system',
                        'exception.site.is.being.edited.user',
                        array(
                            'username' => $User->getName()
                        )
                    ),
                    703
                );
            }

            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.site.is.being.edited'
                ),
                703
            );
        }

        // check the name, unallowed signs?
        $name = $this->getAttribute( 'name' );

        self::checkName( $name );


        /* @var $Project \QUI\Projects\Project */
        $Project = $this->getProject();

        // check if a name in the same level exists
        // observed linked sites
        if ( $this->getId() != 1 )
        {
            $parent_ids = $this->getParentIds();

            foreach ( $parent_ids as $pid )
            {
                $Parent = new \QUI\Projects\Site\Edit( $Project, $pid );

                if ( $Parent->existNameInChildren( $name ) > 1 )
                {
                    throw new \QUI\Exception(
                        \QUI::getLocale()->get(
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

        switch ( $this->getAttribute( 'order_type' ) )
        {
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
                $order_type = $this->getAttribute( 'order_type' );
            break;
        }


        // release dates
        $release_from = '';
        $release_to   = '';

        if ( $this->getAttribute( 'release_from' ) )
        {
            $rf = strtotime( $this->getAttribute( 'release_from' ) );

            if ( $rf ) {
                $release_from = date('Y-m-d H:i:s', $rf);
            }
        }

        if ( $this->getAttribute( 'release_to' ) )
        {
            $rf = strtotime( $this->getAttribute( 'release_to' ) );

            if ( $rf ) {
                $release_to = date('Y-m-d H:i:s', $rf);
            }
        }

        // save extra package attributes (site.xml)
        $extraAttributes = Utils::getExtraAttributeListForSite( $this );
        $siteExtra       = array();

        foreach ( $extraAttributes as $attribute ) {
            $siteExtra[ $attribute ] = $this->getAttribute( $attribute );
        }

        // save main data
        $update = \QUI::getDataBase()->update(
            $this->_TABLE,
            array(
                'name'     => $this->getAttribute( 'name' ),
                'title'    => $this->getAttribute( 'title' ),
                'short'    => $this->getAttribute( 'short' ),
                'content'  => $this->getAttribute( 'content' ),
                'type' 	   => $this->getAttribute( 'type' ),
                'nav_hide' => $this->getAttribute( 'nav_hide' ) ? 1 : 0,
                'e_user'   => \QUI::getUserBySession()->getId(),

                // ORDER
                'order_type'  => $order_type,
                'order_field' => $this->getAttribute( 'order_field' ),

                // images
                'image_emotion' => $this->getAttribute( 'image_emotion' ),
                'image_site'    => $this->getAttribute( 'image_site' ),

                // release
                'release_from' => $release_from,
                'release_to'   => $release_to,

                // Extra-Feld
                'extra' => json_encode( $siteExtra )
            ),
            array(
                'id' => $this->getId()
            )
        );

        // save package automatic site data (database.xml)
        $dataList = Utils::getDataListForSite( $this );

        foreach ( $dataList as $dataEntry )
        {
            $data = array();

            $table     = $dataEntry[ 'table' ];
            $fieldList = $dataEntry[ 'data' ];
            $package   = $dataEntry[ 'package' ];
            $suffix    = $dataEntry[ 'suffix' ];

            $attributeSuffix = $package .'.'. $suffix .'.';
            $attributeSuffix = str_replace( '/', '.', $attributeSuffix );

            foreach ( $fieldList as $siteAttribute ) {
                 $data[ $siteAttribute ] = $this->getAttribute( $attributeSuffix . $siteAttribute );
            }

            $result = \QUI::getDataBase()->fetch(array(
                'from'  => $table,
                'where' => array(
                    'id' => $this->getId()
                ),
                'limit' => 1
            ));

            if ( !isset( $result[ 0 ] ) )
            {
                \QUI::getDataBase()->insert($table, array(
                    'id' => $this->getId()
                ));
            }

            \QUI::getDataBase()->update($table, $data, array(
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
        $Project->setEditDate( time() );

        // on save event
        $this->Events->fireEvent( 'save', array($this) );
        \QUI::getEvents()->fireEvent( 'siteSave', array($this) );


        if ( $update )
        {
            \QUI::getMessagesHandler()->addSuccess(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'message.site.save.success',
                    array(
                        'id'    => $this->getId(),
                        'title' => $this->getAttribute( 'title' ),
                        'name'  => $this->getAttribute( 'name' )
                    )
                )
            );

            return true;
        }

        throw new \QUI\Exception(
            \QUI::getLocale()->get(
                'quiqqer/system',
                'exception.site.save.error'
            ),
            self::ESAVE
        );
    }

    /**
     * (non-PHPdoc)
     * @see Site::getChildrenIdsFromParentId()
     *
     * @param Integer $pid - Parent - ID
     * @param array $params
     */
    public function getChildrenIdsFromParentId($pid, $params=array())
    {
        $where_1 = array(
            $this->_RELTABLE .'.parent' => (int)$pid,
            $this->_TABLE .'.deleted'   => 0,
            $this->_RELTABLE .'.child'  => '`'. $this->_TABLE .'.id`'
        );

        if (isset($params['where']) && is_array($params['where']))
        {
            $where = array_merge($where_1, $params['where']);

        } elseif (isset($params['where']) && is_string($params['where']))
        {
            // @todo where als param String
            \QUI\System\Log::write('WIRD NICHT verwendet'. $params['where'], 'error');
            $where = $where_1;
        } else
        {
            $where = $where_1;
        }

        $order = $this->_TABLE .'.order_field';

        if (isset($params['order']))
        {
            if (strpos($params['order'], '.') !== false)
            {
                $order = $this->_TABLE.'.'.$params['order'];
            } else
            {
                $order = $params['order'];
            }
        }

        $result = \QUI::getDataBase()->fetch(array(
            'select' => $this->_TABLE .'.id',
            'count'  => isset($params['count']) ? 'count' : false,
            'from' 	 => array(
                $this->_RELTABLE,
                $this->_TABLE
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
     * @param String $name
     * @return Bool
     */
    public function existNameInChildren($name)
    {
        $query = "
            SELECT COUNT({$this->_TABLE}.id) AS count
            FROM `{$this->_RELTABLE}`,`{$this->_TABLE}`
            WHERE `{$this->_RELTABLE}`.`parent` = {$this->getId()} AND
                  `{$this->_RELTABLE}`.`child` = `{$this->_TABLE}`.`id` AND
                  `{$this->_TABLE}`.`name` = :name AND
                  `{$this->_TABLE}`.`deleted` = 0
        ";

        $PDO   = \QUI::getDataBase()->getPDO();
        $Stmnt = $PDO->prepare( $query );

        $Stmnt->bindValue( ':name', $name, \PDO::PARAM_STR );
        $Stmnt->execute();

        $result = $Stmnt->fetchAll( \PDO::FETCH_ASSOC );

        if ( !isset( $result[0] ) ) {
            return false;
        }

        return (int)$result[0]['count'] ? (int)$result[0]['count'] : false;
    }

    /**
     * Return the children
     *
     * @param Array $params Parameter für die Childrenausgabe
     * 	$params['where']
     * 	$params['limit']
     * @param Bool $recursiv Rekursiv alle Kinder IDs bekommen
     * @return Array;
     */
    public function getChildren($params=array(), $recursiv=false)
    {
        if ( !isset($params['order']) )
        // Falls kein Ordner übergeben wird das eingestellte Site Ordner
        {
            switch ( $this->getAttribute('order_type') )
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

                default:
                    $params['order'] = 'order_field';
                break;
            }
        }

        // Tabs der Plugins hohlen
//         $Plugins = $this->_getLoadedPlugins();
        $Project = $this->getProject();

//         foreach ( $Plugins as $Plugin )
//         {
//             if ( method_exists( $Plugin, 'onGetChildren' ) ) {
//                $params = $Plugin->onGetChildren( $this, $params );
//             }
//         }

        /*
        $this->Events->fireEvent( 'getChildren', array( $this, $params ) );

        \QUI::getEvents()->fireEvent( 'getChildren', array( $this, $params ) );
        */

        // if active = '0&1', project -> getchildren returns all children
        $params['active'] = '0&1';

        $children = array();
        $result   = $this->getChildrenIds( $params );

        if ( isset( $result[ 0 ] ) )
        {
            foreach ( $result as $id )
            {
                $child      = new \QUI\Projects\Site\Edit( $Project, (int)$id );
                $children[] = $child;
            }
        }

        return $children;
    }

    /**
     * Fügt eine Verknüpfung zu einer anderen Sprache ein
     *
     * @param String $lang - Sprache zu welcher verknüpft werden soll
     * @param String $id - ID zu welcher verknüpft werden soll
     * @return Bool
     */
    public function addLanguageLink($lang, $id)
    {
        $this->checkPermission( 'quiqqer.projects.site.edit' );


        $Project = $this->getProject();
        $p_lang  = $Project->getAttribute( 'lang' );

        $id = (int)$id;

        $result = \QUI::getDataBase()->fetch(array(
            'from' 	=> $this->_RELLANGTABLE,
            'where' => array(
                $p_lang => $this->getId()
            ),
            'limit' => '1'
        ));

        if ( isset( $result[0] ) )
        {
             return \QUI::getDataBase()->exec(array(
                'update' => $this->_RELLANGTABLE,
                'set'    => array(
                     $lang => $id
                ),
                'where'  => array(
                    $p_lang => $this->getId()
                )
            ));
        }

        return \QUI::getDataBase()->exec(array(
            'insert' => $this->_RELLANGTABLE,
            'set'    => array(
                $p_lang => $this->getId(),
                $lang   => $id
            )
        ));
    }

    /**
     * Entfernt eine Verknüpfung zu einer Sprache
     *
     * @param String $lang
     * @return Bool
     */
    public function removeLanguageLink($lang)
    {
        $this->checkPermission( 'quiqqer.projects.site.edit' );

        $Project = $this->getProject();

        return \QUI::getDataBase()->exec(array(
            'update' => $this->_RELLANGTABLE,
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
     * @param Bool|\QUI\Users\User|\QUI\Users\SystemUser $User - the user which create the site, optional
     *
     * @return Int
     * @throws \QUI\Exception
     */
    public function createChild($params=array(), $User=false)
    {
        if ( $User == false ) {
            $User = \QUI::getUserBySession();
        }

        // @todo Prüfen ob der Benutzer Kinder anlegen darf



        $Project = $this->getProject();

        //$newid    = $Project->getNewId();
        $new_name = 'Neue Seite';
        $old      = $new_name;

        // Namen vergeben falls existiert
        $i = 1;

        if ( !isset( $params['name'] ) || empty( $params['name'] ) )
        {
            while ( $this->existNameInChildren( $new_name ) )
            {
                $new_name = $old.' ('.$i.')';
                $i++;
            }

        } else
        {
            $new_name = $params['name'];
        }

        if ( $this->existNameInChildren( $new_name ) ) {
            throw new \QUI\Exception( 'Name exist', 401 );
        }

        // can we use this name?
        self::checkName( $new_name );



        $childCount = $this->hasChildren( true );

        $_params = array(
            'name'   => $new_name,
            'title'  => $new_name,
            'c_date' => date( 'Y-m-d G:i:s' ),
            'e_user' => $User->getId(),
            'c_user' => $User->getId(),

            'c_user_ip'   => \QUI\Utils\System::getClientIP(),
            'order_field' => $childCount + 1
        );

        if ( isset( $params['title'] ) ) {
            $_params['title'] = $params['title'];
        }

        if ( isset( $params['short'] ) ) {
            $_params['short'] = $params['short'];
        }

        if (isset($params['content'])) {
            $_params['content'] = $params['content'];
        }

        $DataBase = \QUI::getDataBase();

        $DataBase->insert( $this->_TABLE , $_params );

        $newId = $DataBase->getPDO()->lastInsertId();

        $DataBase->insert($this->_RELTABLE, array(
            'parent' => $this->getId(),
            'child'  => $newId
        ));

        // Aufruf der createChild Methode im TempSite - für den Adminbereich
        $this->Events->fireEvent('createChild', array($newId, $this));
        \QUI::getEvents()->fireEvent( 'siteCreateChild', array($newId, $this) );

        return $newId;
    }

    /**
     * Move the site to another parent
     *
     * @param Integer $pid - Parent ID
     * @return Bool
     */
    public function move($pid)
    {
        $this->checkPermission( 'quiqqer.projects.site.edit' );

        $Project = $this->getProject();
        $Parent  = $Project->get( (int)$pid );// Prüfen ob das Parent existiert

        $children = $this->getChildrenIds( $this->getId(), array(), true );

        if ( !in_array($pid, $children) && $pid != $this->getId() )
        {
            \QUI::getDataBase()->update(
                $this->_RELTABLE,
                array('parent' => $Parent->getId()),
                'child = '. $this->getId() .' AND oparent IS NULL'
            );

            //$this->deleteTemp();
            $this->deleteCache();

            return true;
        }

        return false;
    }

    /**
     * Kopiert die Seite
     *
     * @param Integer $pid - ID des Parents unter welches die Kopie eingehängt werden soll
     *
     * @return Bool
     *
     * @todo Rekursiv kopieren
     */
    public function copy($pid)
    {
        // Edit Rechte prüfen
        $this->checkPermission( 'quiqqer.projects.site.edit' );

        $Project   = $this->getProject();
        $Parent    = new \QUI\Projects\Site\Edit( $Project, (int)$pid );
        $attribues = $this->getAllAttributes();

        // Prüfen ob es eine Seite mit dem gleichen Namen im Parent schon gibt
        try
        {
            $Child = $Parent->getChildIdByName(
                $this->getAttribute('name')
            );
        } catch ( \QUI\Exception $Exception )
        {
            // es wurde kein Kind gefunden
            $Child  = false;
        }

        if ( $Child )
        {
            $parents   = $Parent->getParents();
            $parents[] = $Parent;

            $path    = '';

            foreach ( $parents as $Prt ) {
                $path .= '/'. $Prt->getAttribute('name');
            }

            // Es wurde ein Kind gefunde
            throw new \QUI\Exception(
                'Eine Seite mit dem Namen '. $this->getAttribute('name') .' befindet sich schon unter '. $path
            );
        }


        // kopiervorgang beginnen
        $site_id = $Parent->createChild(array(
            'name' => 'copypage'
        ));

        // Erstmal Seitentyp setzn
        $Site = new \QUI\Projects\Site\Edit( $Project, (int)$site_id );
        $Site->setAttribute( 'type', $this->getAttribute('type') );
        $Site->save( false );

        // Alle Attribute setzen
        $Site = new \QUI\Projects\Site\Edit( $Project, (int)$site_id );

        foreach ( $attribues as $key => $value ) {
            $Site->setAttribute( $key, $value );
        }

        $Site->save( false );

        return $Site;
    }

    /**
     * Erstellt eine Verknüpfung
     *
     * @param Int $pid
     */
    public function linked($pid)
    {
        $Project = $this->getProject();

        $table = $Project->getAttribute('name') .'_'.
                 $Project->getAttribute('lang') .'_sites_relations';

        $NewParent = new \QUI\Projects\Site\Edit($Project, (int)$pid);
        $Parent    = $this->getParent();

        // Prüfen ob die Seite schon in dem Parent ist
        if ($Parent->getId() == $pid)
        {
            throw new \QUI\Exception(
                'Es kann keine Verknüpfung in dieser Ebene erstellt werden,
                da eine Verknüpfung oder die original Seite bereits in dieser Ebene existiert', 400
            );
        }

        $links = \QUI::getDataBase()->fetch(array(
            'from'  => $table,
            'where' => array(
                'child' => $this->getId()
            )
        ));

        foreach ( $links as $entry )
        {
            if ( $entry['parent'] == $pid )
            {
                throw new \QUI\Exception(
                    'Es kann keine Verknüpfung in dieser Ebene erstellt werden,
                    da eine Verknüpfung oder die original Seite bereits in dieser Ebene existiert', 400
                );
            }
        }

        return \QUI::getDataBase()->insert($table, array(
            'parent'  => $pid,
            'child'   => $this->getId(),
            'oparent' => $Parent->getId()
        ));
    }

    /**
     * Löscht eine Verknüpfung
     *
     * @param Integer $pid - Parent ID
     * @param Integer $all - Alle Verknüpfungen und Original Seite löschen
     * @param Bool $orig   - Delete the original site, too
     *
     * @todo refactor -> use PDO
     */
    public function deleteLinked($pid, $all=false, $orig=false)
    {
        $this->checkPermission( 'quiqqer.projects.site.edit' );

        $Project  = $this->getProject();
        $Parent   = $this->getParent();
        $DataBase = \QUI::getDataBase();

        $table = $Project->getAttribute('name') .'_'.
                 $Project->getAttribute('lang') .'_sites_relations';

        if ( \QUI\Utils\Bool::JSBool( $value ) )
        {
            // Seite löschen
            $this->delete();

            $qry  = 'DELETE FROM `'. $table .'` ';
            $qry .= 'WHERE child ='. $this->getId() .' AND parent != '. $Parent->getId();

            // Alle Verknüpfungen
            return $DataBase->fetchSQL( $qry );
        }

        // Einzelne Verknüpfung löschen
        if ( $pid && $orig == false )
        {
            $qry  = 'DELETE FROM `'. $table.'` ';
            $qry .= 'WHERE child ='. $this->getId() .' AND parent = '. (int)$pid;

            return $DataBase->fetchSQL( $qry );
        }

        $qry  = 'DELETE FROM `'. $table.'` ';
        $qry .= 'WHERE child ='. $this->getId() .' AND parent = '. (int)$pid .' AND oparent = '. (int)$orig;

        return $DataBase->fetchSQL( $qry );
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

        $link_cache_dir  = VAR_DIR .'cache/links/'. $Project->getAttribute('name') .'/';
        $link_cache_file = $link_cache_dir . $this->getId() .'_'. $Project->getAttribute('name') .'_'. $Project->getAttribute('lang');

        if ( file_exists( $link_cache_file ) ) {
            unlink( $link_cache_file );
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

        $link_cache_dir  = VAR_DIR .'cache/links/'. $Project->getAttribute('name') .'/';
        $link_cache_file = $link_cache_dir . $this->getId() .'_'. $Project->getAttribute('name') .'_'. $Project->getAttribute('lang');

        \QUI\Utils\System\File::mkdir( $link_cache_dir );

        file_put_contents( $link_cache_file, URL_DIR . $this->getUrlRewrited() );
    }

    /**
     * Prüft ob die Seite gerade bearbeitet wird
     * Wenn die Seite von einem selbst bearbeitet wird, kommt auch false zurück.
     *
     * Falls die Seite von jemand anderes bearbeitet wird, wird die ID des Benutzers zurück gegeben
     *
     * @return false || Integer
     */
    public function isMarcate()
    {
        if ( !file_exists( $this->_marcatefile ) ) {
            return false;
        }

        $uid = file_get_contents( $this->_marcatefile );

        if ( \QUI::getUserBySession()->getId() == $uid ) {
            return false;
        }

        $time = time() - filemtime( $this->_marcatefile );
        $max_life_time = \QUI::conf('session', 'max_life_time');

        if ( $time > $max_life_time )
        {
            $this->demarcate();
            return false;
        }

        return (int)$uid;
    }

    /**
     * Markiert die Seite -> die Seite wird gerade bearbeitet
     * Markiert nur wenn die Seite nicht markiert ist
     */
    public function marcate()
    {
        if ( $this->isMarcate() ) {
            return;
        }

        file_put_contents( $this->_marcatefile, \QUI::getUserBySession()->getId() );
    }

    /**
     * Demarkiert die Seite, die Seite wird nicht mehr bearbeitet
     */
    public function demarcate()
    {
        if ( !file_exists( $this->_marcatefile ) ) {
            return;
        }

        unlink( $this->_marcatefile );
    }

    /**
     * Ein SuperUser kann eine Seite trotzdem demakieren wenn er möchte
     */
    public function demarcateWithRights()
    {
        if ( !\QUI::getUserBySession()->isSU() ) {
            return;
        }

        if ( file_exists( $this->_marcatefile ) ) {
            unlink( $this->_marcatefile );
        }
    }

    /**
     * Säubert eine URL macht sie schön
     *
     * @param String $url
     * @param \QUI\Projects\Project $Project - Project clear extension
     * @return String
     */
    static function clearUrl($url, \QUI\Projects\Project $Project)
    {
        $signs = array(
            '-', '.', ',', ':', ';',
            '#', '`', '!', '§', '$',
            '%', '&', '?', '<', '>',
            '=', '\'', '"', '@', '_',
            ']', '[', '+', '/'
        );

        $url = str_replace($signs, '', $url);
        //$url = preg_replace('[-.,:;#`!§$%&/?<>\=\'\"\@\_\]\[\+]', '', $url);

        // doppelte leerzeichen löschen
        $url = preg_replace('/([ ]){2,}/', "$1", $url);

        // URL Filter
        $name   = $Project->getAttribute('name');
        $filter = USR_DIR .'lib/'. $name .'/url.filter.php';
        $func   = 'url_filter_'. $name;

        if ( file_exists( $filter ) )
        {
            require_once $filter;

            if ( function_exists( $func ) ) {
                $url = $func( $url );
            }
        }

        return $url;
    }

    /**
     * Prüft ob der Name erlaubt ist
     *
     * @param unknown_type $name
     * @throws \QUI\Exception
     * @return Bool
     */
    static function checkName($name)
    {
        if ( !isset( $name ) )
        {
            throw new \QUI\Exception(
                'Bitte gebe einen Titel ein'
            );
        }

        if ( strlen( $name ) <= 2 )
        {
            throw new \QUI\Exception(
                'Die URL muss mehr als 2 Zeichen lang sein',
                701
            );
        }

        if ( strlen( $name ) > 200 )
        {
            throw new \QUI\Exception(
                'Die URL darf nicht länger als 200 Zeichen lang sein',
                704
            );
        }

        // Prüfung des Namens - Sonderzeichen
        if ( preg_match("@[-.,:;#`!§$%&/?<>\=\'\"\@\_\]\[\+]@", $name ))
        {
            throw new \QUI\Exception(
                'In der URL "'. $name .'" dürfen folgende Zeichen nicht verwendet werden: _-.,:;#@`!§$%&/?<>=\'"[]+',
                702
            );
        }

        return true;
    }
}
