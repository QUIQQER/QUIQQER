<?php

/**
 * This file contains the Controls_Contextmenu_Menuitem
 */

/**
 * ContextMenuItem
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.controls.contextmenu
 */

Class Controls_Contextmenu_Menuitem extends \QUI\QDOM
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
     * $settings['image'] = Menubild
     */
    public function __construct(array $settings)
    {
        $this->setAttributes( $settings );
        $this->setAttribute( 'type', 'Controls_Contextmenu_Menuitem' );
    }

    /**
     * Parent setzen
     *
     * @param ContextBarItem || Button $parent
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
            'Argument 1 passed to ContextMenuItem::addParent()
             must be an instance of Controls_Buttons_Button or Controls_Contextmenu_Bar '.
            get_class( $parent ).' given'
        );
    }

    /**
     * Sortiert die Kinder
     */
    public function sortChildren()
    {
        $_children = array();
        $children  = $this->_items;

        foreach ( $children as $Itm ) {
            $_children[ $Itm->getAttribute( 'text' ) ] = $Itm;
        }

        ksort( $_children );

        $this->_items = $_children;
    }

    /**
     * Ein Kind hinzufügen
     *
     * @param Controls_Contextmenu_Menuitem|Controls_Contextmenu_Seperator $child
     */
    public function appendChild($child)
    {
        if ( get_class( $child ) == 'Controls_Contextmenu_Menuitem' ||
             get_class( $child ) == 'Controls_Contextmenu_Seperator' )
        {
            $this->_items[] = $child;
        }

        return $this;
    }

    /**
     * Kinder bekommen
     *
     * @return Array
     */
    public function getChildren()
    {
        return $this->_items;
    }

    /**
     * Namen vom Objekt bekommen
     *
     * @return String
     */
    public function getName()
    {
        return $this->getAttribute( 'name' );
    }

    /**
     * Setzt den Menüpunkt inaktiv
     */
    public function setDisable()
    {
        $this->_disabled = true;
    }

    /**
     * Setzt den Menüpunkt inaktiv
     */
    public function isDisable()
    {
        if ( $this->_disabled ) {
            return true;
        }

        return false;
    }

    /**
     * Macht einen inaktiven Menüpunkt wieder verfügbar
     */
    public function setEnable()
    {
        $this->_disabled = false;
    }

    /**
     * Setzt den Menüpunkt inaktiv
     */
    public function isEnable()
    {
        if ( $this->_disabled ) {
            return false;
        }

        return true;
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

        if ( $this->getAttribute( 'onClick' ) ) {
            $result['events']['onClick'] = $this->getAttribute( 'onClick' );
        }

        if ( $this->getAttribute( 'onMouseDown' ) ) {
            $result['events']['onMouseDown'] = $this->getAttribute( 'onMouseDown' );
        }

        if ( $this->getAttribute( 'onMouseUp' ) ) {
            $result['events']['onMouseUp'] = $this->getAttribute( 'onMouseUp' );
        }

        foreach ( $this->_items as $Itm )
        {
            $Itm->addParent( $this );
            $result['items'][] = $Itm->toArray();
        }

        return $result;
    }
}
?>