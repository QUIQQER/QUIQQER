<?php

/**
 * This file contains System_Cron_Manager
 */

/**
 * Cron Manager
 * Manage the cron and the execution of the crons
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.system.cron
 */
class System_Cron_Manager extends \QUI\QDOM
{
    /**
     * internal cron list
     * @var array
     */
    protected $_crons = array();

    /**
     * constructor
     */
    public function __construct()
    {
        // Plugins einlesen
        $Plugins = \QUI::getPlugins();
        $list    = $Plugins->get();

        foreach ( $list as $Plugin )
        {
            if ( !method_exists( $Plugin, 'registerCrons' ) ) {
                continue;
            }

            $Plugin->registerCrons($this);
        }

        // Projekte einlesen
        $projects = \QUI\Projects\Manager::getProjects();

        foreach ( $projects as $project )
        {
            $dir = USR_DIR .'lib/'. $project .'/cron/';

            if ( !is_dir( $dir ) ) {
                continue;
            }

            $crons = \QUI\Utils\System\File::readDir( $dir, true );

            foreach ( $crons as $cronfile )
            {
                if ( !file_exists( $dir . $cronfile ) ) {
                     continue;
                }

                require_once $dir . $cronfile;

                $class = 'Cron'. str_replace( array('cron.', '.php' ), '', $cronfile );

                if ( !class_exists( $class ) ) {
                    continue;
                }

                $Cron = new $class();

                try
                {
                    $this->register(
                        \QUI::get( $project ),
                        $class,
                        $Cron->getAttribute( 'desc' )
                    );
                } catch ( \QUI\Exception $e )
                {
                    \QUI\System\Log::write(
                        'Cron konnte nicht registriert werden: '. $e->getMessage().' '. $class,
                        'cron'
                    );
                }
            }
        }
    }

    /**
     * Return the cron tabe
     *
     * @return String
     */
    static function Table()
    {
        return QUI_DB_PRFX .'cron';
    }

    /**
     * Registered Crons to the Cron Manager
     *
     * @param Plugin|\QUI\Projects\Project $Plugin - Plugin or project which the cron provides
     * @param String $cronname - Name of the cron
     * @param String $desc - Description of the cron
     */
    public function register($Plugin, $cronname, $desc)
    {
        $this->_crons[] = array(
            'Plugin'   => $Plugin,
            'cronname' => $cronname,
            'desc'     => $desc,
            'project'  => get_class( $Plugin ) === 'Project' ? true : false
        );
    }

    /**
     * Returns all registered crons
     *
     * @return Array
     */
    public function getList()
    {
        return $this->_crons;
    }

    /**
     * Execute the cron manager
     * All crons that must be executed will be start
     *
     * @param \QUI\Users\User|Bool|\QUI\Users\SystemUser $User - The user who executes the cron
     */
    static function exec($User=false)
    {
        $crons   = self::get();
        $DatBase = \QUI::getDB();

        foreach ( $crons as $Cron )
        {
            try
            {
                $exec = $Cron->exec($User); /* @var $Cron Cron */

                if ( $exec )
                {
                    $attributes = print_r( $Cron->getAllAttributes(), true );
                    self::log("Cron '". $Cron->getAttribute( 'cronname' ) ."' executed.\nParameters: ". $attributes);
                }

            } catch ( \QUI\Exception $e )
            {
                self::log( 'ERROR '. $e->getMessage() );
            }
        }
    }

    /**
     * Add a cron to the execution list
     *
     * @param Plugin|\QUI\Projects\Project $Plugin
     * @param String $cronname - The name of the cron
     * @param Array $date   - When should the cron run
     * @param Array $params - Which parameters should be carried out of the cron
     *
     * @example System_Cron_Manager::add($Plugin, array(
     * 		'min'   => 0,
     * 		'hour'  => 1
     * ));
     */
    static function add($Plugin, $cronname, $date=array(), $params=array())
    {
        $name = $Plugin->getAttribute('name');

        if ( get_class( $Plugin ) === 'Project' ) {
            $name = 'project.'. $name;
        }

        $data = array(
            'min'      => '*',
            'hour'     => '*',
            'day'      => '*',
            'month'    => '*',
            'plugin'   => $name,
            'cronname' => $cronname,
            'params'   => json_encode( $params )
        );

        if ( isset( $date['min'] ) ) {
            $data['min'] = $date['min'];
        }

        if ( isset( $date['hour'] ) ) {
            $data['hour'] = $date['hour'];
        }

        if ( isset( $date['day'] ) ) {
            $data['day'] = $date['day'];
        }

        if ( isset( $date['month'] ) ) {
            $data['month'] = $date['month'];
        }

        \QUI::getDB()->addData( self::Table(), $data );
    }

    /**
     * Returns the specific crons
     *
     * @param Array|Int $params - DB search parameter
     * @return Array
     *
     * @example System_Cron_Manager::get(array(
     * 		'where' => array(
     * 			'plugin' => 'plugin'
     * 		)
     * ));
     *
     * System_Cron_Manager::get();
     */
    static function get($params=array())
    {
        $params['from'] = self::Table();

        $result = \QUI::getDB()->select( $params );
        $crons  = array();

        foreach ( $result as $entry )
        {
            if ( strpos( $entry['plugin'], 'project.' ) !== false )
            {
                $cronfile = USR_DIR .'lib/'. str_replace( 'project.', '', $entry['plugin'] ) .'/cron/cron.'. str_replace( 'Cron', '', $entry['cronname'] ) .'.php';
                $class    = $entry['cronname'];
            } else
            {
                $cronfile = OPT_DIR . $entry['plugin'] .'/cron/cron.'. $entry['cronname'] .'.php';
                $class    = 'Cron'. ucwords( strtolower( $entry['cronname'] ) );
            }

            // Prüfungen ob Cron existiert
            if ( !file_exists( $cronfile ) ) {
                continue;
            }

            require_once $cronfile;

            if ( !class_exists( $class ) ) {
                continue;
            }

            $crons[] = new $class( $entry );
        }

        return $crons;
    }

    /**
     * Return a cron
     *
     * @param Integer $cid - Cron ID
     * @return System_Cron_Cron
     */
    static function getCronByCid($cid)
    {
        $result = self::get(array(
            'where' => array(
                'id' => (int)$cid
            ),
            'limit' => '1'
        ));

        if ( !isset( $result[0] ) ) {
            throw new \QUI\Exception( 'Cron was not found', 404 );
        }

        return $result[0];
    }

    /**
     * Edit a cron
     *
     * @param Integer $id   - The Cron-ID
     * @param Array $params - Parameters which are to be updated
     *
     * @return Resource
     */
    static function edit($id, $params)
    {
        $result = self::get(array(
            'where' => array(
                'id' => (int)$id
            ),
            'limit' => '1'
        ));

        if ( !isset($result[0]) ) {
            return;
        }

        $data = array();

        if ( isset($params['min']) ) {
            $data['min'] = $params['min'];
        }

        if ( isset($params['hour']) ) {
            $data['hour'] = $params['hour'];
        }

        if ( isset($params['day']) ) {
            $data['day'] = $params['day'];
        }

        if ( isset($params['month']) ) {
            $data['month'] = $params['month'];
        }

        if ( isset($params['plugin']) ) {
            $data['plugin'] = $params['plugin'];
        }

        if ( isset($params['params']) ) {
            $data['params'] = json_encode($params['params']);
        }

        // Daten aktualisieren
        return \QUI::getDB()->updateData(
            self::Table(),
            $data,
            array(
                'id' => $id
            )
        );
    }

    /**
     * Delete a cron from the execution list
     *
     * @param System_Cron_Cron $Cron
     * @return Bool
     */
    static function delete(System_Cron_Cron $Cron)
    {
        return \QUI::getDB()->deleteData(
            self::Table(),
            array( 'id' => $Cron->getAttribute( 'id' ) )
        );
    }

    /**
     * Print a message to the log
     *
     * @param String $message - Message
     */
    static function log($message)
    {
        $User = \QUI::getUsers()->getUserBySession();
        $dir  = VAR_DIR . 'log/';
        $file = $dir . 'cron_'. date('Y-m-d') .'.log';

        $str = '['. date('Y-m-d H:i:s') .' :: '. $User->getName() .'] '. $message;

        \QUI\Utils\System\File::mkdir( $dir );
        \QUI\Utils\System\File::putLineToFile( $file, $str );
    }

    /**
     * Setup for the crons
     * Create the cron database tables
     */
    static function setup()
    {
        $DataBase = \QUI::getDataBase();
        $PDO      = $DataBase->getPDO();
        $table    = self::Table();

        $DataBase->Table()->appendFields(
            $table,
            array(
                'id'       => 'INT( 3 ) NOT NULL',
                'min'      => 'VARCHAR( 2 ) NOT NULL',
                'hour'     => 'VARCHAR( 2 ) NOT NULL',
                'day'      => 'VARCHAR( 2 ) NOT NULL',
                'month'    => 'VARCHAR( 2 ) NOT NULL',
                'plugin'   => 'VARCHAR( 60 ) NOT NULL',
                'cronname' => 'VARCHAR( 60 ) NOT NULL',
                'params'   => 'TEXT NOT NULL',
                'lastexec' => 'datetime NULL'
            )
        );

        $PDO->exec( 'ALTER TABLE `'. $table .'` CHANGE `min` `min` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL' );
        $PDO->exec( 'ALTER TABLE `'. $table .'` CHANGE `hour` `hour` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL' );
        $PDO->exec( 'ALTER TABLE `'. $table .'` CHANGE `day` `day` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL' );
        $PDO->exec( 'ALTER TABLE `'. $table .'` CHANGE `month` `month` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL' );


        // Primary Key setzen
        $DataBase->Table()->setPrimaryKey( $table, 'id' );
        $PDO->exec( 'ALTER TABLE `'. $table .'` CHANGE `id` `id` INT( 3 ) NOT NULL AUTO_INCREMENT' );
    }
}

?>