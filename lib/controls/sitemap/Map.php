<?php

/**
 * This file contains the Controls_Sitemap_Map
 */

/**
 * Sitemap
 * Baut eine Sitemap
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.controls.sitemap
 */
class Controls_Sitemap_Map extends QDOM
{
    /**
     * Sub Items
     * @var array
     */
	private $_items = array();

	/**
	 * Konstruktor
	 *
	 * @param array $settings
	 *  parent = id vom Parent HTML Element in welches die ContextBar eingefügt werden soll
	 *  id = id des DivElements der ContextBar
	 *  name = Objektname der Contextbar
	 */
	public function __construct(array $settings)
	{
		$this->setAttributes($settings);
	}

	/**
	 * Ein ContextBarItem in die ContextBar hinzufügen
	 *
	 * @param Controls_Sitemap_Item $itm
	 */
	public function appendChild(Controls_Sitemap_Item $itm)
	{
		$this->_items[] = $itm;
	}

	/**
	 * Namen vom Objekt bekommen
	 *
	 * @return String
	 * @deprecated Es sollte getAttribute('name') verwendet werden
	 */
	public function getName()
	{
		return $this->getAttribute('name');
	}

	/**
	 * Erstellt das JavaScript für eine Sitemap
	 *
	 * @return String
	 */
	public function create()
	{
		$jsString = 'var '.$this->getAttribute('name').' = new _ptools.Sitemap();';

		foreach($this->_items as $itm)
		{
			$itm->addParent($this);
			$jsString .= $itm->create();
		}

		$jsString .= 'document.getElementById("'.$this->getAttribute('parent').'").appendChild('.$this->getAttribute('name').'.create());';
		return $jsString;
	}

}

?>