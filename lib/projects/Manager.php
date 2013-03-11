<?php

/**
 * This file contains the Projects_Manager
 */

/**
 * The Project Manager
 * The main object to get a project
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.projects
 */
class Projects_Manager
{
    /**
     * Projects config
     * @var QConfig
     */
    static $Config = null;

    /**
     * laoded projects
     * @var array
     */
    static $projects = array();

    /**
     * standard project
     * @var Projects_Project
     */
    static $Standard = null;

    /**
     * projects.ini
     *
     * @return QConfig
     */
    static function getConfig()
    {
        return QUI::getConfig('etc/projects.ini');
    }

	/**
	 * Returns the current project
	 *
	 * @return Project
	 * @throws QException
	 */
	static function get()
	{
	    $Rewrite = QUI::getRewrite();

	    if ( $Rewrite->getParam( 'project' ) )
		{
		    return self::getProject(
			    $Rewrite->getParam( 'project' ),
			    $Rewrite->getParam( 'lang' ),
			    $Rewrite->getParam( 'template' )
		    );
		}

		$Standard = self::getStandard();

		// Falls andere Sprache gewünscht
		if ( $Rewrite->getParam( 'lang' ) &&
		     $Rewrite->getParam( 'lang' ) != $Standard->getAttribute( 'lang' ) )
		{
            return self::getProject(
			    $Standard->getAttribute( 'name' ),
			    $Rewrite->getParam( 'lang' )
		    );
		}

		return $Standard;
	}

	/**
	 * Returns a project
	 *
	 * @param String $project   - Project name
	 * @param String $lang		- Project lang, optional (if not set, the standard language used)
	 * @param String $template  - used templaed, optional (if not set, the standard templaed used)
	 *
	 * @return Projects_Project
	 */
	static function getProject($project, $lang=false, $template=false)
	{
	    if ( $lang == false &&
	         isset( self::$projects[ $project ] ) &&
             isset( self::$projects[ $project ][ '_standard' ] ) )
        {
	        return self::$projects[ $project ][ '_standard' ];
	    }

		if ( isset( self::$projects[ $project ] ) &&
			 isset( self::$projects[ $project ][ $lang ] ) )
		{
			return self::$projects[ $project ][ $lang ];
		}

		// Wenn der RAM zu voll wird, Objekte mal leeren
		if ( Utils_System::memUsageCheck() ) {
            self::$projects = array();
		}


		if ( $lang === false )
		{
		    self::$projects[ $project ][ '_standard' ] = new Projects_Project( $project );
            return self::$projects[ $project ][ '_standard' ];
		}

		self::$projects[ $project ][ $lang ] = new Projects_Project(
		    $project,
		    $lang,
		    $template
	    );

		return self::$projects[ $project ][ $lang ];
	}

	/**
	 * Gibt alle Projektnamen zurück
	 *
	 * @param Bool $asobject - Als Objekte bekommen, default = false
	 * @return Array
	 */
	static function getProjects($asobject=false)
	{
		$config = self::getConfig()->toArray();
        $list   = array();

		foreach ( $config as $project => $conf )
		{
			try
			{
			    $Project = self::getProject(
				    $project,
				    $conf['default_lang'],
				    $conf['template']
				);

    			if ( isset( $conf['standard'] ) && $conf['standard'] == 1 ) {
    				self::$Standard = $Project;
    			}

				if ( $asobject == true )
				{
                    $list[] = $Project;
				} else
				{
                    $list[] = $project;
				}

			} catch ( QException $e )
			{

			}
		}

		return $list;
	}

    /**
     * Standard Projekt bekommen
     * @return Projects_Project
     */
	static function getStandard()
	{
        if ( !is_null( self::$Standard ) ) {
            return self::$Standard;
        }

        $config = self::getConfig()->toArray();

		foreach ( $config as $project => $conf )
		{
			if ( isset( $conf['standard'] ) && $conf['standard'] == 1)
			{
			    self::$Standard = self::getProject(
			        $project,
				    $conf['default_lang'],
				    $conf['template']
				);
			}
		}

		if ( is_null( self::$Standard ) ) {
            throw new QException( 'Es wurde kein Projekt gefunden', 404 );
		}

		return self::$Standard;
	}

    /**
     * Create a new project
     *
     * @param String $name - Project name
     * @param String $lang - Project lang
     * @param String $template - template, optional
     * @throws QException
     *
     * @todo noch einmal anschauen und übersichtlicher schreiben
     */
	static function createProject($name, $lang, $template=false)
	{
        $db    = QUI::getDB();
		$Users = QUI::getUsers();
		$User  = $Users->getUserBySession();

		if ( !$User->isSU() || empty( $name ) || empty( $lang ) ) {
			throw new QException( 'Error: No Rights', 401 );
		}

		if ( strlen( $name ) <= 2 ) {
			throw new QException( 'Der Projektname muss länger als 2 Zeichen sein', 701 );
		}

		if ( preg_match( "@[-.,:;#`!§$%&/?<>\=\'\" ]@", $name ) ) {
			throw new QException( 'Der Projektname enthält nicht erlaubte Zeichen. Nicht erlaubte Zeichen: - . , : ; # ` ! § $ % & / ? < > = \' " ', 702 );
		}

		$projects = $this->getProjects();

		if ( isset( $projects[ $name ] ) ) {
			throw new QException( 'Projekt existiert bereits' );
		}

		$name = Utils_Security_Orthos::clear( $name );

		$table_site      = $name .'_'. $lang .'_sites';
		$table_site_rel  = $name .'_'. $lang .'_sites_relations';
		$table_media     = $name .'_'. $lang .'_media';
		$table_media_rel = $name .'_'. $lang .'_media_relations';
		$table_rights    = $name .'_'. $lang .'_rights';

		// Prüfen ob es die Tabellen schon gibt
		if ( $db->existTable( $table_site ) != false ||
			 $db->existTable( $table_site_rel ) != false ||
			 $db->existTable( $table_media ) != false ||
			 $db->existTable( $table_media_rel ) != false ||
			 $db->existTable( $table_rights ) != false )
		{
			throw new QException( 'Project exist' );
		}

		// Site Tabelle
		$db->query(
			'CREATE TABLE IF NOT EXISTS `'. $table_site .'` (
			  `id` bigint(20) NOT NULL,
			  `name` varchar(200) NOT NULL,
			  `title` tinytext,
			  `short` text,
			  `content` longtext,
			  `type` varchar(32) default NULL,
			  `active` tinyint(1) NOT NULL,
			  `deleted` tinyint(1) NOT NULL,
			  `c_date` timestamp NULL default NULL,
			  `e_date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			  `c_user` int(11) default NULL,
			  `e_user` int(11) default NULL,
			  `nav_hide` tinyint(1) NOT NULL,
			  `order_type` varchar(12) default NULL,
			  `order_field` bigint(20) default NULL,
			  `extra` text default NULL,
			  PRIMARY KEY  (`id`),
			  KEY `name` (`name`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;'
		);

		// Startseite anlegen
		$db->query(
			'INSERT INTO `'. $table_site .'` (
				`id` ,
				`name` ,
				`title` ,
				`short` ,
				`content` ,
				`type` ,
				`active` ,
				`deleted` ,
				`c_date` ,
				`e_date` ,
				`c_user` ,
				`e_user` ,
				`nav_hide` ,
				`order_type` ,
				`order_field`
			)
			VALUES (
				\'1\', \'Startseite\', \'Startseite\', \'Kurzbeschreibung\' , \'<p>Inhalt der Startseite</p>\' , \'standard\', \'0\', \'0\', NOW( ) ,
				CURRENT_TIMESTAMP , \'1\', NULL , \''. $User->getId() .'\', NULL , NULL
			);'
		);

		// Site Beziehungen
		$db->query(
			'CREATE TABLE IF NOT EXISTS `'. $table_site_rel .'` (
			  `parent` bigint(20) NOT NULL,
			  `child` bigint(20) NOT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;'
		);

		// Media Tabelle
		$db->query(
			'CREATE TABLE IF NOT EXISTS `'. $table_media .'` (
			  `id` bigint(20) NOT NULL,
			  `name` varchar(200) NOT NULL,
			  `title` tinytext,
			  `short` text,
			  `type` varchar(32) default NULL,
			  `active` tinyint(1) NOT NULL,
			  `deleted` tinyint(1) NOT NULL,
			  `c_date` timestamp NULL default NULL,
			  `e_date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			  `file` text,
			  `alt` text,
			  `mime_type` text,
			  `image_height` int(6) default NULL,
			  `image_width` int(6) default NULL,
			  PRIMARY KEY  (`id`),
			  KEY `name` (`name`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;'
		);

		// Erster Ordner anlegen
		$db->query(
			'INSERT INTO `'. $table_media .'` (
				`id` ,
				`name` ,
				`title` ,
				`short` ,
				`type` ,
				`active` ,
				`deleted` ,
				`c_date` ,
				`e_date` ,
				`file` ,
				`alt` ,
				`mime_type` ,
				`image_height` ,
				`image_width`
			)
			VALUES (
				\'1\', \'Media\', NULL , NULL , \'folder\', \'1\', \'0\', NOW( ) ,
				CURRENT_TIMESTAMP , NULL , NULL , \'folder\', NULL , NULL
			);'
		);

		// Media Beziehungen
		$db->query(
			'CREATE TABLE IF NOT EXISTS `'. $table_media_rel .'` (
			  `parent` bigint(20) NOT NULL,
			  `child` bigint(20) NOT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;'
		);

		// Medieordner
		Utils_System_File::mkdir(CMS_DIR .'media/sites/'. $name .'/');

		// Rechte Tabelle
		$db->query(
			'CREATE TABLE IF NOT EXISTS `'. $table_rights .'` (
			  `id` bigint(20) NOT NULL,
			  `edit` text,
			  `del` text,
			  `new` text,
			  `view` text,
			  PRIMARY KEY  (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;'
		);

		// Projekt Ordner
		Utils_System_File::mkdir( USR_DIR .'lib/'. $name .'/' );

		Utils_System_File::copy(
		    SYS_DIR .'template/project/bin/index.html',
		    USR_DIR .'lib/'. $name .'/index.html'
	    );

		// Templateordner erstellen
		if ($template) {
			Utils_System_File::mkdir( USR_DIR .'bin/'. $name .'/' );
		}

		// Config schreiben
		if (!file_exists(CMS_DIR .'etc/projects.ini')) {
			file_put_contents(CMS_DIR .'etc/projects.ini', '');
		}

		$Config = QUI::getConfig('etc/projects.ini');

		$Config->setSection($name, array(
			'default_lang' => $lang,
			'langs'        => $lang,
			'admin_mail'   => 'support@pcsg.de',
			'template'     => $name,
			'image_text'   => '0',
			'keywords'     => '',
			'description'  => '',
			'robots'       => 'index',
			'author'       => '',
			'publisher'    => '',
			'copyright'    => '',
			'standard'     => '0'
		));

		$Config->save();

		// Plugin Setups
		$Plugins  = QUI::getPlugins();
		$Project  = QUI::getProject($project);
        $Plugins->setup($Project);

		// Projekt setup
		$Project->setup();

		// Projekt Cache löschen
		System_Cache_Manager::clear('QUI::config');

		return $Project;
	}
}

?>