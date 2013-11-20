<?php

/**
 * This file contains the Controls_Contextmenu_Bar
 */

/**
 * ContextBar
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.php.controls.contextmenu
 */

class Controls_Contextmenu_Bar extends \QUI\QDOM
{
    /**
     * subitems
     * @var array
     */
    private $_items = array();

    /**
     * Konstruktor
     *
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        $this->setAttributes( $settings );
        $this->setAttribute( 'type', 'Controls_Contextmenu_Bar' );
    }

    /**
     * Ein ContextBarItem in die ContextBar hinzufügen
     *
     * @param Controls_Contextmenu_Baritem $Itm
     */
    public function appendChild(Controls_Contextmenu_Baritem $Itm)
    {
        $this->_items[] = $Itm;
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
     * Gibt ein Kind per Namen zurück
     *
     * @param String $name - Name des Menüeintrages
     * @return Bool | ContextBarItem
     */
    public function getElementByName($name)
    {
        foreach ( $this->_items as $Item )
        {
            if ( $name == $Item->getName() ) {
                return $Item;
            }
        }

        return false;
    }

    /**
     * Alle Kinder bekommen
     *
     * @return Array
     */
    public function getChildren()
    {
        return $this->_items;
    }

    /**
     * Menü als Array bekommen
     *
     * @return Array
     */
    public function toArray()
    {
        $result = array();

        foreach ( $this->_items as $Itm )
        {
            $Itm->addParent($this);
            $result[] = $Itm->toArray();
        }

        return $result;
    }
}

?>