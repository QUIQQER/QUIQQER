<?php

/**
 * This file contains the Controls_Contextmenu_Baritem
 */

/**
 * ContextBarItem
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.controls.contextmenu
 */

Class Controls_Contextmenu_Baritem extends QDOM
{
    /**
     * subitems
     * @var array
     */
	private $_items = array();

	/**
	 * Parent Object
	 * @var Controls_Control
	 */
	private $_parent = null;

	/**
	 * Disable status
	 * @var Bool
	 */
	private $_disabled = false;

	/**
	 * Constructor
	 *
	 * @param array $settings
	 * $settings['text'] = Text vom Button
	 * $settings['name'] = Name vom JavaScript Objekt
	 */
	public function __construct(array $settings)
	{
		$this->setAttributes( $settings );
	    $this->setAttribute( 'type', 'Controls_Contextmenu_Baritem' );
	}

	/**
	 * Parent setzen
	 *
	 * @param Controls_Contextmenu_Bar $parent
	 */
	public function addParent(Controls_Contextmenu_Bar $parent)
	{
		$this->_parent = $parent;
	}

	/**
	 * Namen vom Objekt bekommen
	 *
	 * @return String
	 */
	public function getName()
	{
		return $this->getAttribute('name');
	}

	/**
	 * Ein ContextMenuItem hinzufügen
	 *
	 * @param Controls_Contextmenu_Menuitem $itm
	 */
	public function appendChild($itm)
	{
		$this->_items[] = $itm;
		return $this;
	}

	/**
	 * Setzt den Menüpunkt inaktiv
	 */
	public function setDisable()
	{
		$this->_disabled = true;
	}

	/**
	 * Macht einen inaktiven Menüpunkt wieder verfügbar
	 */
	public function setEnable()
	{
		$this->_disabled = false;
	}

	/**
	 * Gibt ein Kind per Namen zurück
	 *
	 * @param String $name - Name des Menüeintrages
	 * @return Bool | ContextMenuItem
	 */
	public function getElementByName($name)
	{
		foreach ($this->_items as $itm)
		{
			if ($name == $itm->getName()) {
				return $itm;
			}
		}

		return false;
	}

	/**
	 * Item als Array bekommen
	 *
	 * @return Array
	 */
	public function toArray()
	{
        $result = $this->getAllAttributes();
        $result['items'] = array();

	    foreach ($this->_items as $Itm)
		{
			$Itm->addParent($this);
			$result['items'][] = $Itm->toArray();
		}

        return $result;
	}
}

?>