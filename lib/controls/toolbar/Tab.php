<?php

/**
 * This file contains the Controls_Toolbar_Tab
 */

/**
 * A Toolbar Tab
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.controls.toolbar
 */

class Controls_Toolbar_Tab extends \QUI\QDOM
{
    /**
     * The Parent object
     * @var Controls_Control
     */
    private $_parent;

    /**
     * Constructor
     *
     * @param array $attributes
     */
    public function __construct(array $attributes)
    {
        $this->setAttributes($attributes);
    }

    /**
     * Set the Parent
     *
     * @param Controls_Toolbar_Bar $Parent
     */
    public function addParent(Controls_Toolbar_Bar $Parent)
    {
        $this->_parent = $Parent;
    }

    /**
     * Get the name attribute from the control
     *
     * @return String
     */
    public function getName()
    {
        return $this->getAttribute( 'name' );
    }

    /**
     * JavaScript onclick event
     *
     * @return String
     */
    public function onclick()
    {
        return $this->getName() .'.onclick();';
    }

    /**
     * Gibt den JavaScript Code mit create() des Tabs zurück
     *
     * @return String
     */
    public function create()
    {
        $jsString  = 'var '. $this->getName() .' = '. $this->jsObject();
        $jsString .= $this->_parent->getName() .'.appendChild( '. $this->getName() .' );';

        return $jsString;
    }

    /**
     * Gibt den JavaScript Code des Tabs zurück
     *
     * @return String
     */
    public function jsObject()
    {
        $jsString   = 'new QUI.controls.toolbar.Tab({';
        $attributes = $this->getAllAttributes();

        foreach ( $attributes as $s => $value )
        {
            if ( $s != 'name' ) {
                $jsString .= $s .' : "'. $value .'",';
            }
        }

        $jsString .= 'name: "'. $this->getName() .'"';
        $jsString .= '});';

        return $jsString;
    }
}

?>