<?php

/**
 * This file contains Controls_Select_Select
 */

/**
 * Select Element
 * Erstellt eine Selectbox
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.controls.select
 *
 * @todo we need that?
 */

class Controls_Select_Select extends \QUI\QDOM
{
    /**
     * Parent Object
     * @var Controls_Control
     */
    private $_parent;

    /**
     * Sub items
     * @var array
     */
    private $_items = array();

    /**
     * constructor
     *
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        $this->setAttributes($settings);
    }

    /**
     * Ein Optionfield hinzufügen
     *
     * @param Controls_Select_Option $option
     */
    public function appendChild( Controls_Select_Option $option)
    {
        $this->_items[] = $option;
    }

    /**
     * Parent setzen
     *
     * @param Controls_Toolbar_Bar $parent
     */
    public function addParent($parent)
    {
        $this->_parent = $parent;
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
     * Enter description here...
     *
     * @return Array
     */
    public function getChildren()
    {
         return $this->_items;
    }

    /**
     * create the jsobject and the create
     * @return String
     */
    public function create()
    {
        $jsString  = 'var '.$this->getAttribute('name') .' = '. $this->jsObject() .';';
         $jsString .= $this->_parent->getName().'.appendChild( '.$this->getAttribute('name').' );';

        return $jsString;
    }

    /**
     * create only the jsobject
     * @return String
     */
    public function jsObject()
    {
        $allattributes = $this->getAllAttributes();

        $jsString = 'new _ptools.Select({'.
            'name: "'.$this->getAttribute('name').'",';

        foreach ($allattributes as $key => $setting)
        {
            if($key != 'name' && $key != 'text') {
                $jsString  .= $key.': '. json_encode($setting) .',';
            }
        }

        if( $this->getAttribute('text') )
        {
            $jsString .= 'text: "'.$this->getAttribute('text').'"';
        } else
        {
            $jsString .= 'text: ""';
        }

        $jsString .= '})';


        if(count( $this->_items ) > 0)
        {
            foreach ($this->_items as $itm)
            {
                $itm->addParent( $this );
                $jsString .= '.appendChild('. $itm->jsObject() .')';
            }
        }

        return $jsString;
    }
}

?>