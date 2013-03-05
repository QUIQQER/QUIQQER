<?php

/**
 * This file contains the Controls_Toolbar_Bar
 */

/**
 * Toolbar Bar
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.controls.toolbar
 */

class Controls_Toolbar_Bar
{
    /**
     * all settings from the toolbar
     * @var array
     */
	private $_settings = array();

    /**
     * all subitems from the toolbar
     * @var array
     */
	private $_items = array();

	/**
	 * constructor
	 *
	 * @param array $settings
	 *  parent = id vom Parent HTML Element in welches die Toolbar eingefügt werden soll
	 *  name = Objektname der Toolbar
	 */
	public function __construct(array $settings)
	{
		$this->_settings = $settings;
	}

	/**
	 * Fügt ein Kind ein
	 *
	 * @param Controls_Sitemap_Item|Controls_Toolbar_Tab $itm
	 * @return this
	 */
	public function appendChild($itm)
	{
		$this->_items[] = $itm;
		return $this;
	}

	/**
	 * Namen vom Objekt bekommen
	 *
	 * @return String
	 */
	public function getName()
	{
		return $this->_settings['name'];
	}

	/**
	 * JavaScript Clear
	 *
	 * @return String
	 */
	public function clear()
	{
		return $this->_settings['name'] .'.clear();';
	}

	/**
	 * Gibt die Items zurück
	 *
	 * @return unknown
	 */
	public function getItems()
	{
		return $this->_items;
	}

	/**
	 * Erstellt den JavaScriptbereich um eine ContextBar mit seinen Kindern aufzubauen
	 *
	 * @return unknown
	 */
	public function create()
	{
		$jsString  = 'var '. $this->_settings['name'] .' = '. $this->jsObject();
		$jsString .= 'document.getElementById("'. $this->_settings['parent'] .'").appendChild('. $this->_settings['name'] .'.create());';

		return $jsString;
	}

	/**
	 * Gibt den JavaScript Code des Tabs zurück
	 *
	 * @return String
	 */
	public function jsObject()
	{
		$jsString = 'new QUI.controls.toolbar.Bar({';

		foreach ( $this->_settings as $s => $value )
		{
			if ( $s != 'name' ) {
				$jsString .= $s .' : "'. $value .'",';
			}
		}

		$jsString .= 'name : "'. $this->_settings['name'] .'"';
		$jsString .= '});'."\n";

		foreach ( $this->_items as $itm )
		{
			$itm->addParent( $this );
			$jsString .= $itm->create() ."\n";
		}

		return $jsString;
	}

	/**
	 * Sucht ein Item in der Toolbar nach dem Namen und gibt dieses zurück
	 *
	 * @param String $name
	 * @return Bool false || ToolbarItem
	 */
	public function getElementByName($name)
	{
		foreach ( $this->_items as $itm )
		{
			if ( $itm->getName() == $name )
			{
				return $itm;
				break;
			}
		}

		return false;
	}

	/**
	 * Löscht ein Kind aus der Toolbar
	 *
	 * @param Object $Child - Das Kind Objekt
	 */
	public function removeChild($Child)
	{
		foreach ( $this->_items as $key => $Itm )
		{
			if ( $Itm == $Child ) {
				unset( $this->_items[ $key ] );
			}
		}
	}

	/**
	 * Gibt das erste Kind zurück
	 *
	 * @return unknown
	 */
	public function firstChild()
	{
		if ( !isset($this->_items[0]) ) {
			return false;
		}

		return $this->_items[0];
	}

	/**
	 * Gibt alle Kinder zurück
	 *
	 * @return Array
	 */
	public function getChildren()
	{
		return $this->_items;
	}

	/**
	 * Nur die das JavaScript der Kinder bekommen
	 * appendChild der Toolbar wird hier nicht ausgegeben
	 *
	 * @return String
	 */
	public function createChildJs()
	{
		$jsString = '';

		foreach ( $this->_items as $itm )
		{
			$itm->addParent( $this );
			$jsString .= $itm->create();
		}

		return $jsString;
	}

	/**
	 * Alle Kinder als Array bekommen
	 *
	 * @return Array
	 */
	public function toArray()
	{
    	$result = array();

	    foreach ( $this->_items as $Itm ) {
			$result[] = $Itm->getAllAttributes();
		}

    	return $result;
	}
}

?>