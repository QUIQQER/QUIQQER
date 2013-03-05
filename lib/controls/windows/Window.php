<?php

/**
 * This file contains Controls_Window_Setting
 */

/**
 * Window control class
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.controls.windows
 */

class Controls_Windows_Window extends QDOM
{
    /**
     * control type
     * @var String
     */
	protected $_TYPE = 'Controls_Windows_Window';

	/**
	 * categories - Controls_Buttons_Button
	 * @var array
	 */
	protected $_categories = array();


	/**
	 * buttons - Controls_Buttons_Button
	 * @var array
	 */
	protected $_buttons = array();

	/**
	 * constructor
	 *
	 * @param array $settings
	 */
	public function __construct($settings=array())
	{
		$this->setAttributes( $settings );
	}

	/**
	 * Add a category
	 *
	 * @param Controls_Buttons_Button $Btn
	 */
	public function appendCategory(Controls_Buttons_Button $Btn)
	{
		$this->_categories[] = $Btn;
	}

	/**
	 * Add a button
	 *
	 * @param Controls_Buttons_Button $Btn
	 */
    public function appendButton(Controls_Buttons_Button $Btn)
	{
		$this->_buttons[] = $Btn;
	}

	/**
	 * Return the window as an array
	 *
	 * @return Array
	 */
	public function toArray()
	{
        $result = $this->getAllAttributes();
        $result['categories'] = array();
        $result['buttons']    = array();

	    foreach ( $this->_categories as $Itm )
		{
			$Itm->addParent( $this );
			$result['categories'][] = $Itm->toArray();
		}

	    foreach ( $this->_buttons as $Itm )
		{
			$Itm->addParent( $this );
			$result['buttons'][] = $Itm->toArray();
		}

        return $result;
	}
}
?>