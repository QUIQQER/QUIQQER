<?php

/**
 * This file contains \QUI\Controls\Contact
 */

namespace QUI\Controls;

/**
 * Mini contact control
 * {control control="\QUI\Controls\Contact" labels=false}
 *
 * @author www.pcsg.de (Henning Leutz)
 */

class Contact extends \QUI\Control
{
    /**
     * constructor
     * @param Array $attributes
     */
    public function __construct($attributes=array())
    {
        parent::setAttributes( $attributes );

        $this->addCSSFile(
            dirname( __FILE__ ) .'/Contact.css'
        );

        $this->setAttribute( 'class', 'quiqqer-contact grid-100 grid-parent' );
        $this->setAttribute( 'qui-class', "Controls/Contact" );
        $this->setAttribute( 'labels', true );
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        $Engine = \QUI::getTemplateManager()->getEngine();

        $Engine->assign(array(
            'this' => $this
        ));

        return $Engine->fetch( dirname( __FILE__ ) .'/Contact.html' );
    }

}