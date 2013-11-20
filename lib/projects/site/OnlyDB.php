<?php

/**
 * This file contains the Projects_Site_DB
 */

/**
 * This object is only used to get data purely from the DataBase
 * Without performing file system operations (cache etc.)
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.project
 */

class Projects_Site_OnlyDB extends Projects_Site
{
    /**
     * constructor
     *
     * @param Projects_Project $Project
     * @param Integer $id - Site ID
     */
    public function __construct(Projects_Project $Project, $id)
	{
        $this->_db    = \QUI::getDB();
		$this->_users = \QUI::getUsers();
		$this->_user  = $this->_users->getUserBySession();

		$this->_TABLE        = $Project->getAttribute('db_table');
		$this->_RELTABLE     = $this->_TABLE .'_relations';
		$this->_RELLANGTABLE = $Project->getAttribute('name') .'_multilingual';

		$id = (int)$id;

		if ( empty($id) ) {
			throw new \QUI\Exception( 'Site Error; No ID given:'. $id, 400 );
		}

		$this->_id = $id;

		// Daten aus der DB hohlen
		$this->refresh();
	}

    /**
	 * Hohlt sich die Daten frisch us der DB
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

		if ( !isset( $result[0] ) ) {
			throw new \QUI\Exception( 'Site not exist', 404 );
		}

		$params = $result[0];

		// Verknüpfung hohlen
		if ( $this->getId() != 1 )
		{
			$relresult = \QUI::getDataBase()->fetch(array(
				'from'  => $this->_RELTABLE,
				'where' => array(
					'child' => $this->getId()
				)
			));

			if ( isset($relresult[0]) )
			{
				foreach ( $relresult as $entry )
				{
					if ( !isset($entry['oparent']) ) {
						continue;
					}

					$this->_LINKED_PARENT = $entry['oparent'];
				}
			}
		}

		if ( isset($result[0]['extra']) )
		{
		    $this->_extra = json_decode( $result[0]['extra'], true );
		    unset($result[0]['extra']);
		}

		$this->setAttributes( $result[0] );
	}

	/**
	 * Clears the internal objects
	 */
	public function __destroy()
	{
        $this->_db    = null;
		$this->_users = null;
		$this->_user  = null;
	}
}

?>