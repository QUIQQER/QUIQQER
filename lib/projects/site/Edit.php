<?php

/**
 * This file contains the Projects_Site_Edit
 */

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
 * @version    $Revision: 4787 $
 * @since      Class available since Release P.MS 0.1
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
 * @qui-event onSiteActivate [Projects_Site_Edit]
 * @qui-event onSiteDeactivate [Projects_Site_Edit]
 * @qui-event onSiteSave [Projects_Site_Edit]
 *
 * @event onActivate [Projects_Site_Edit]
 * @event onDeactivate [Projects_Site_Edit]
 * @event onSave [Projects_Site_Edit]
 */

class Projects_Site_Edit extends Projects_Site
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
     * @param Projects_Project $Project
     * @param unknown_type $id
     */
    public function __construct(Projects_Project $Project, $id)
    {
        parent::__construct( $Project, $id );

        $this->refresh();

        $id       = $this->getId();
        $DataBase = QUI::getDataBase();

        $this->_marcatefile = VAR_DIR .'marcate/'.
                              $Project->getAttribute( 'name' ) .'_'.
                              $id .'_'. $Project->getAttribute( 'lang' );

        // Temp Dir abfragen ob existiert
        Utils_System_File::mkdir( VAR_DIR .'admin/' );

        // Erste Rechteprüfung
        $User = QUI::getUserBySession();

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
    }

    /**
     * Plugins laden
     * @todo anschauen wegen admin zeugs
     */
    protected function _load_plugins()
    {
        // Globale requireds
        $Project = $this->getProject();
        $Plugin  = QUI::getPlugins();

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
        $result = QUI::getDataBase()->fetch(array(
            'from'  => $this->_TABLE,
            'where' => array(
                'id' => $this->getId()
            ),
            'limit' => '1'
        ));

        // Verknüpfung hohlen
        if ( $this->getId() != 1 )
        {
            $relresult = QUI::getDataBase()->fetch(array(
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
            throw new QException(
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

                $this->_extra = json_decode( $a_val, true );
                continue;
            }

            $this->setAttribute( $a_key, $a_val );
        }
    }

    /**
     * Activate a site
     *
     * @throws QException
     */
    public function activate()
    {
        try
        {
            $this->checkPermission( 'quiqqer.projects.site.edit' );

        } catch ( \QException $Exception )
        {
            throw new QException(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.permissions.edit'
                )
            );
        }

        $this->Events->fireEvent( 'activate', array( $this ) );

        QUI::getEvents()->fireEvent( 'siteActivate', array( $this ) );


        /*
        $release_from = strtotime(
            $this->getAttribute('pcsg.base.release_from')
        );

        $release_until = strtotime(
            $this->getAttribute('pcsg.base.release_until')
        );

        if ( $release_from && $release_from > time() )
        {
            throw new QException(
                'Die Seite kann nicht aktiviert werden, da das Datum "Veröffentlichen von" in der Zukunft liegt'
            );
        }

        if ( $release_until && $release_until < time() )
        {
            throw new QException(
                'Die Seite kann nicht aktiviert werden, da das Datum "Veröffentlichen bis" in der Vergangenheit liegt'
            );
        }
        */

        QUI::getDataBase()->exec(array(
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
     * @throws QException
     */
    public function deactivate()
    {
        try
        {
            // Prüfen ob der Benutzer die Seite bearbeiten darf
           $this->checkPermission( 'quiqqer.projects.site.edit' );

        } catch ( \QException $Exception )
        {
            throw new QException(
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
     * Vom TempFile aktualisieren
     */
    /*
    public function updateFromTemp()
    {
        // User Prüfung
        $User = QUI::getUserBySession();

        // Rechte Prüfung
        if (!$this->getRights()->hasRights($User, $this, 'view'))
        {
            $result = QUI::getDataBase()->fetch(array(
                'select' => 'name',
                'from'   => $this->_TABLE,
                'where'  => array(
                    'id'  => $this->getId()
                ),
                'limit' => '1'
            ));

            if (isset($result[0]) && isset($result[0]['name'])) {
                $this->setAttribute('name', $result[0]['name']);
            }

            return false;
        }

        // Eigenschaften aus Temp hohlen
        $att = json_decode($this->_getTempFileContent(), true);

        if (!isset($att['field']) || !is_array($att['field'])) {
            return false;
        }

        foreach ($att['field'] as $key => $value)
        {
            switch ($key)
            {
                case "project":
                    // project wird nicht gesetzt
                break;

                case "site_name":
                case "name":
                    $this->setAttribute('name', $value);
                break;

                default:
                    $this->setAttribute($key, $value);
                break;
            }
        }
    }
    */
    /**
     * Saves the site
     *
     * @throws QException
     */
    public function save()
    {
        try
        {
            // Prüfen ob der Benutzer die Seite bearbeiten darf
            $this->checkPermission( 'quiqqer.project.site.edit' );

        } catch ( \QException $Exception )
        {
            throw new QException(
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
                $User = QUI::getUsers()->get( $mid );

            } catch ( QException $Exception )
            {

            }

            if ( isset( $User ) )
            {
                throw new QException(
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

            throw new QException(
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


        /* @var $Project Projects_Project */
        $Project = $this->getProject();

        // check if a name in the same level exists
        // observed linked sites
        if ( $this->getId() != 1 )
        {
            $parent_ids = $this->getParentIds();

            foreach ( $parent_ids as $pid )
            {
                $Parent = new Projects_Site_Edit( $Project, $pid );

                if ( $Parent->existNameInChildren( $name ) > 1 )
                {
                    throw new QException(
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

        // @todo onSave for Plugins

        $this->Events->fireEvent( 'save', array($this) );

        \QUI::getEvents()->fireEvent( 'siteSave', array($this) );

        /**
         * Speicher Routine der Plugins aufrufen
         */
        $Plugins  = $this->_getLoadedPlugins();
        $DataBase = QUI::getDataBase();
        /*
        for ($i = 0, $len = count($Plugins); $i < $len; $i++)
        {
            if (method_exists($Plugins[$i], 'onSave')) {
                $Plugins[$i]->onSave($this, $Project, $DataBase);
            }
        }
        */

        // Globale Tabs
        /*
        if (isset($GLOBALS['admin_plugins']))
        {
            foreach ($GLOBALS['admin_plugins'] as $plugins)
            {
                if (method_exists($plugins, 'onSave')) {
                    $plugins->onSave($this, $Project, $DataBase);
                }
            }
        }
        */

        // Haupttabelle speichern
        $update = $DataBase->update(
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
                'order_type'  => $this->getAttribute( 'order_type' ),
                'order_field' => $this->getAttribute( 'order_field' ),

                // Extra-Feld
                'extra' => json_encode( $this->_extra )
            ),
            array(
                'id' => $this->getId()
            )
        );

        //$this->deleteTemp($User);
        $Project->clearCache();

        // Cache löschen
        $this->deleteCache();

        // Objektcache anlegen
        $this->refresh();
        $this->createCache();

        // Letztes Speichern
        $Project->setEditDate( time() );

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

        throw new \QException(
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
            System_Log::write('WIRD NICHT verwendet'. $params['where'], 'error');
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

        $result = QUI::getDataBase()->fetch(array(
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
        $result = QUI::getDataBase()->fetch(array(
            'count'  => array(
                'select' => $this->_TABLE .'.id',
                'as'     => 'id'
            ),
            'from' 	 => array(
                $this->_RELTABLE,
                $this->_TABLE
            ),
            'where'  =>	array(
                $this->_RELTABLE .'.parent' => $this->getId(),
                $this->_RELTABLE .'.child'  => $this->_TABLE .'.id',
                $this->_TABLE .'.name'      => $name,
                $this->_TABLE.'.deleted'    => 0
            )
        ));

        if ( !isset( $result[0] ) ) {
            return false;
        }

        return (int)$result[0]['id'] ? (int)$result[0]['id'] : false;
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
        $Plugins = $this->_getLoadedPlugins();
        $Project = $this->getProject();

        foreach ( $Plugins as $Plugin )
        {
            if ( method_exists( $Plugin, 'onGetChildren' ) ) {
               $params = $Plugin->onGetChildren( $this, $params );
            }
        }

        /*
        $this->Events->fireEvent( 'getChildren', array( $this, $params ) );

        QUI::getEvents()->fireEvent( 'getChildren', array( $this, $params ) );
        */

        // if active = '0&1', project -> getchildren returns all children
        $params['active'] = '0&1';

        $children = array();
        $result   = $this->getChildrenIds( $params );

        if ( isset( $result[ 0 ] ) )
        {
            foreach ( $result as $id )
            {
                $child      = new Projects_Site_Edit( $Project, (int)$id );
                $children[] = $child;
            }
        }

        return $children;
    }

    /**
     * Beim Kind erstellen werden die Plugins aufgerufen
     *
     * @param Integer $childid
     */
    protected function _createChild($childid)
    {
        $User = QUI::getUserBySession();

        // Tabs der Plugins hohlen
        $Plugins = $this->_getLoadedPlugins();

        foreach ( $Plugins as $plg )
        {
            if ( method_exists( $plg, 'onChildCreate' ) ) {
                $plg->onChildCreate( $childid, $this );
            }
        }
    }

    /**
     * Gibt Archiveeinträge zurück
     *
     * @return Array
     */
    public function getArchive()
    {
        $Project = $this->getProject();
        $table   = $Project->getAttribute('name') .'_'.
                   $Project->getAttribute('lang') .'_archive';

        $result = QUI::getDataBase()->fetch(array(
            'from' 	=> $table,
            'where' => array(
                'id' => $this->getId()
            ),
            'order' => 'date DESC'
        ));

        return $result;
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
        Projects_Sites::checkRights( $this );

        $Project = $this->getProject();
        $p_lang  = $Project->getAttribute( 'lang' );

        $result = QUI::getDataBase()->fetch(array(
            'from' 	=> $this->_RELLANGTABLE,
            'where' => array(
                $p_lang => $this->getId()
            ),
            'limit' => '1'
        ));

        if ( isset( $result[0] ) )
        {
             return QUI::getDataBase()->exec(array(
                'update' => $this->_RELLANGTABLE,
                'set'    => array(
                     $lang => $id
                ),
                'where'  => array(
                    $p_lang => $this->getId()
                )
            ));
        }

        return QUI::getDataBase()->exec(array(
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
        // Edit Rechte prüfen
        Projects_Sites::checkRights($this);

        $Project = $this->getProject();

        return QUI::getDataBase()->exec(array(
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
     * @param Bool|Users_User|Users_SystemUser $User - the user which create the site, optional
     *
     * @return Int
     * @throws QException
     */
    public function createChild($params=array(), $User=false)
    {
        if ( $User == false ) {
            $User = QUI::getUserBySession();
        }

        // @todo Prüfen ob der Benutzer Kinder anlegen darf



        $Project = $this->getProject();

        //$newid    = $Project->getNewId();
        $new_name = 'Neue Seite';
        $old      = $new_name;

        // Namen vergeben falls existiert
        $i = 1;

        \System_Log::writeRecursive($params['name']);

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
            throw new QException( 'Name exist', 401 );
        }

        // Prüfung des Namens - Länge
        if ( strlen( $new_name ) <= 2 ) {
            throw new QException( 'Error Name: 2 signs or lower', 701 );
        }

        if ( strlen( $new_name ) > 200 ) {
            throw new QException( 'Error Name: 200 signs or higher', 704 );
        }

        // Prüfung des Namens - Sonderzeichen
        if ( preg_match( "@[-.,:;#`!§$%&/?<>\=\'\"]@", $new_name ) ) {
            throw new QException( 'Error Name: Not supported signs in Name', 702 );
        }

        $_params = array(
            'name'   => $new_name,
            'title'  => $new_name,
            'c_date' => date( 'Y-m-d G:i:s' ),
            'e_user' => $User->getId(),
            'c_user' => $User->getId(),

            'c_user_ip' => Utils_System::getClientIP()
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

        $DataBase = QUI::getDB();

        $DataBase->addData(
            $this->_TABLE,
            $_params
        );

        $new_id = $DataBase->getPDO()->lastInsertId();

        $DataBase->addData($this->_RELTABLE, array(
            'parent' => $this->getId(),
            'child'  => $new_id
        ));

        // Aufruf der createChild Methode im TempSite - für den Adminbereich
        // needled ?
        if ( method_exists( $this, '_createChild' ) ) {
            $this->_createChild( $new_id );
        }

        return $new_id;
    }

    /**
     * load archiv
     * @todo muss in history plugin rein
     *
     * @param unknown_type $date
     * @deprecated
     */
    public function loadArchive($date)
    {
        // Edit Rechte prüfen
        Projects_Sites::checkRights($this);

        $Project = $this->getProject();
        $table   = $Project->getAttribute('name') .'_'.
                   $Project->getAttribute('lang') .'_archive';

        $result = QUI::getDB()->select(array(
            'from' 	=> $table,
            'where' => array(
                'date' => date('Y-m-d G:i:s', $date)
            ),
            'order' => 'date DESC'
        ));

        if (!isset($result[0]) || !isset($result[0]['fields'])) {
            throw new QException('Archive not found', 404);
        }

        $fields = json_decode($result[0]['fields'], true);

        if (!is_array($fields)) {
            return;
        }

        if (isset($fields['project'])) {
            unset($fields['project']);
        }

        foreach ($fields as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    /**
     * create archiv
     * @todo muss in history plugin rein
     *
     * @return unknown
     * @deprecated
     */
    public function createArchive()
    {
        // Edit Rechte prüfen
        Projects_Sites::checkRights($this);

        $Project       = $this->getProject();
        $table_archive = $Project->getAttribute('name') .'_'.
                         $Project->getAttribute('lang') .'_archive';

        // [begin] Archive
        $allparams    = $this->getAllAttributes();
        $archiveitems = $Project->getConfig('archive');

        // anzahl der Einträge überprüfen
        $archives = QUI::getDB()->select(array(
            'from'  => $table_archive,
            'where' => array(
                'id' => $this->getId()
            ),
            'order' => 'date ASC'
        ));

        if (isset($archives[0]) && count($archives) >= $archiveitems)
        {
            QUI::getDB()->deleteData(
                $table_archive,
                array('date' => $archives[0]['date'])
            );
        }

        $User = QUI::getUsers()->getUserBySession();

        return QUI::getDB()->addData($table_archive, array(
            'id'     => $this->getId(),
            'date'   => date('Y-m-d G:i:s'),
            'user'   => $User->getId(),
            'fields' => json_encode( $allparams )
        ));
    }

    /**
     * Spielt ein Archiv wieder ein
     *
     * @param unknown_type $date
     */
    public function restoreArchive($date)
    {
        // Edit Rechte prüfen
        Projects_Sites::checkRights($this);

        // jetziger Stand sichern
        $this->createArchive();

        // Tempfile löschen
        //$this->deleteTemp();

        // Archiv laden
        $this->loadArchive($date);

        // Attribute speichern
        $this->save(false);
    }

    /**
     * Verschiebt die Seite in ein anderes Parent
     *
     * @param Integer $pid - Parent ID
     * @return Bool
     */
    public function move($pid)
    {
        // Edit Rechte prüfen
        Projects_Sites::checkRights($this);

        $Project = $this->getProject();
        $Parent  = $Project->get( (int)$pid );// Prüfen ob das Parent existiert

        $children = $this->getChildrenIds( $this->getId(), array(), true );

        if (!in_array($pid, $children) &&
            $pid != $this->getId())
        {
            $DataBase = QUI::getDB();

            $DataBase->updateData(
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
        Projects_Sites::checkRights($this);

        $Project   = $this->getProject();
        $Parent    = new Projects_Site_Edit( $Project, (int)$pid );
        $attribues = $this->getAllAttributes();

        // [begin] Prüfen ob es eine Seite mit dem gleichen Namen im Parent schon gibt
        try
        {
            $Child = $Parent->getChildIdByName(
                $this->getAttribute('name')
            );
        } catch (QException $e)
        {
            // es wurde kein Kind gefunden
            $Child  = false;
        }

            if ($Child)
        {
            $parents   = $Parent->getParents();
            $parents[] = $Parent;

            $path    = '';

            foreach ($parents as $Prt) {
                $path .= '/'. $Prt->getAttribute('name');
            }

            // Es wurde ein Kind gefunde
            throw new QException(
                'Eine Seite mit dem Namen '. $this->getAttribute('name') .' befindet sich schon unter '. $path
            );
        }
        // [end] Prüfen ob es eine Seite mit dem gleichen Namen im Parent schon gibt

        // kopiervorgang beginnen
        $site_id = $Parent->createChild(array(
            'name' => 'copypage'
        ));

        // Erstmal Seitentyp setzn
        $Site = new Projects_Site_Edit($Project, (int)$site_id);
        $Site->setAttribute('type', $this->getAttribute('type'));
        $Site->save(false);

        // Alle Attribute setzen
        $Site = new Projects_Site_Edit($Project, (int)$site_id);

        foreach ($attribues as $key => $value) {
            $Site->setAttribute($key, $value);
        }

        return $Site->save(false);
    }

    /**
     * Erstellt eine Verknüpfung
     *
     * @param Int $pid
     */
    public function linked($pid)
    {
        $Project   = $this->getProject();

        $table     = $Project->getAttribute('name') .'_'.
                     $Project->getAttribute('lang') .'_sites_relations';

        $NewParent = new Projects_Site_Edit($Project, (int)$pid);
        $Parent    = $this->getParent();

        // Prüfen ob die Seite schon in dem Parent ist
        if ($Parent->getId() == $pid)
        {
            throw new QException(
                'Es kann keine Verknüpfung in dieser Ebene erstellt werden,
                da eine Verknüpfung oder die original Seite bereits in dieser Ebene existiert', 400
            );
        }

        $links = QUI::getDB()->select(array(
            'from'  => $table,
            'where' => array(
                'child' => $this->getId()
            )
        ));

        foreach ($links as $entry)
        {
            if ($entry['parent'] == $pid)
            {
                throw new QException(
                    'Es kann keine Verknüpfung in dieser Ebene erstellt werden,
                    da eine Verknüpfung oder die original Seite bereits in dieser Ebene existiert', 400
                );
            }
        }

        return QUI::getDB()->addData($table, array(
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
     */
    public function deleteLinked($pid, $all=false, $orig=false)
    {
        $Project = $this->getProject();
        $Parent  = $this->getParent();
        $db      = QUI::getDB(); /* @var $db MyDB */

        $table   = $Project->getAttribute('name') .'_'.
                   $Project->getAttribute('lang') .'_sites_relations';

        if (PT_Bool::JSBool($all))
        {
            // Seite löschen
            $this->delete();

            $qry  = 'DELETE FROM `'. $table.'` ';
            $qry .= 'WHERE child ='. $this->getId() .' AND parent != '. $Parent->getId();

            // Alle Verknüpfungen
            return $db->query($qry);
        }

        // Einzelne Verknüpfung löschen
        if ($pid && $orig == false)
        {
            $qry  = 'DELETE FROM `'. $table.'` ';
            $qry .= 'WHERE child ='. $this->getId() .' AND parent = '. (int)$pid;

            return $db->query($qry);
        }

        $qry  = 'DELETE FROM `'. $table.'` ';
        $qry .= 'WHERE child ='. $this->getId() .' AND parent = '. (int)$pid .' AND oparent = '. (int)$orig;

        return $db->query($qry);
    }

    /**
     * Löscht den Site Cache
     */
    public function deleteCache()
    {
        // Seiten Cache löschen
        parent::deleteCache();

        // Link Cache löschen
        $Project = $this->getProject();

        $link_cache_dir  = VAR_DIR .'cache/links/'. $Project->getAttribute('name') .'/';
        $link_cache_file = $link_cache_dir . $this->getId() .'_'. $Project->getAttribute('name') .'_'. $Project->getAttribute('lang');

        if (file_exists($link_cache_file)) {
            unlink($link_cache_file);
        }
    }

    /**
     * Erstellt den Site Cache
     */
    public function createCache()
    {
        // Objekt Cache
        parent::createCache();

        // Link Cache
        $Project = $this->getProject();

        $link_cache_dir  = VAR_DIR .'cache/links/'. $Project->getAttribute('name') .'/';
        $link_cache_file = $link_cache_dir . $this->getId() .'_'. $Project->getAttribute('name') .'_'. $Project->getAttribute('lang');

        Utils_System_File::mkdir($link_cache_dir);

        file_put_contents($link_cache_file, $this->getUrlRewrited());
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $name
     * @param unknown_type $value
     */
    public function setExtra($name, $value)
    {
        $this->_extra[$name] = $value;
    }

    /**
     * Extra Feld löschen
     *
     * @param unknown_type $name
     */
    public function removeExtra($name)
    {
        if (isset($this->_extra[$name])) {
            unset($this->_extra[$name]);
        }
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
        if (!file_exists($this->_marcatefile)) {
            return false;
        }

        $uid = file_get_contents($this->_marcatefile);

        if (QUI::getUserBySession()->getId() == $uid) {
            return false;
        }

        $time = time() - filemtime($this->_marcatefile);
        $max_life_time = QUI::conf('session', 'max_life_time');

        if ($time > $max_life_time)
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
        if ($this->isMarcate()) {
            return;
        }

        file_put_contents($this->_marcatefile, QUI::getUserBySession()->getId());
    }

    /**
     * Demarkiert die Seite, die Seite wird nicht mehr bearbeitet
     */
    public function demarcate()
    {
        if (!file_exists($this->_marcatefile)) {
            return;
        }

        unlink($this->_marcatefile);
    }

    /**
     * Ein SuperUser kann eine Seite trotzdem demakieren wenn er möchte
     */
    public function demarcateWithRights()
    {
        if (!QUI::getUserBySession()->isSU()) {
            return;
        }

           if (file_exists($this->_marcatefile)) {
            unlink($this->_marcatefile);
        }
    }

    /**
     * Säubert eine URL macht sie schön
     *
     * @param String $url
     * @param Projects_Project $Project - Project clear extension
     * @return String
     */
    static function clearUrl($url, Projects_Project $Project)
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

        if (file_exists($filter))
        {
            require_once $filter;

            if (function_exists($func)) {
                $url = $func($url);
            }
        }

        return $url;
    }

    /**
     * Prüft ob der Name erlaubt ist
     *
     * @param unknown_type $name
     * @throws QException
     */
    static function checkName($name)
    {
        if (!isset($name)) {
            throw new QException('Bitte gebe einen Titel ein');
        }

        if (strlen($name) <= 2) {
            throw new QException('Die URL muss mehr als 2 Zeichen lang sein', 701);
        }

        if (strlen($name) > 200) {
            throw new QException('Die URL darf nicht länger als 200 Zeichen lang sein', 704);
        }

        // Prüfung des Namens - Sonderzeichen
        if (preg_match("@[-.,:;#`!§$%&/?<>\=\'\"\@\_\]\[\+]@", $name)) {
            throw new QException('In der URL "'. $name .'" dürfen folgende Zeichen nicht verwendet werden: _-.,:;#@`!§$%&/?<>=\'"[]+', 702);
        }

        return true;
    }
}
?>