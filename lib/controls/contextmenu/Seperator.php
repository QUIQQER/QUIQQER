<?php

/**
 * This file contains the Controls_Contextmenu_Seperator
 */

/**
 * Controls_Contextmenu_Seperator
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.controls.contextmenu
 */

class Controls_Contextmenu_Seperator extends QDOM
{
	/**
	 * The Parent Object
	 * @var Controls_Control
	 */
    private $_parent = null;

	/**
	 * Constructor
	 *
	 * @param array $settings
	 * $settings['name'] = Name vom JavaScript Objekt
	 *
	 */
	public function __construct(array $settings)
	{
	    $this->setAttributes( $settings );
	    $this->setAttribute( 'type', 'Controls_Contextmenu_Seperator' );
	}

	/**
	 * Parent setzen
	 *
	 * @param Controls_Buttons_Button|Controls_Contextmenu_Baritem|Controls_Contextmenu_Menuitem $parent
	 */
	public function addParent($parent)
	{
		if ( get_class( $parent ) == 'Controls_Buttons_Button' ||
			 get_class( $parent ) == 'Controls_Contextmenu_Baritem' ||
			 get_class( $parent ) == 'Controls_Contextmenu_Menuitem' )
		{
			$this->_parent = $parent;
			return true;
		}

		throw new Exception(
			'Argument 1 passed to '. get_class( $this ) .'::addParent() must be an instance of Button or ContextBarItem '
	    );
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
	 * Enter description here...
	 *
	 * @return String
	 */
	public function create()
	{
		$jsString = 'var '. $this->getAttribute('name') .' = new _ptools.ContextMenuSeperator({'.
			'name: "'. $this->getAttribute('name') .'"';
		$jsString .= '});';
		$jsString .= $this->_parent->getName() .'.appendChild('. $this->getAttribute('name') .');';

		return $jsString;
	}

	/**
	 * Enter description here...
	 *
	 * @return unknown
	 */
	public function getHtml()
	{
		return '<li class="divider"></li>';
	}

	/**
	 * Enter description here...
	 *
	 * @return unknown
	 */
	public function toArray()
	{
		return $this->getAllAttributes();
	}
}

?>