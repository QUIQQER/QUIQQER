<?php

/**
 * This file contains \QUI\Controls\Contact
 */

namespace QUI\Controls;

use QUI;
use QUI\Utils\Security\Orthos;

/**
 * Mini contact control
 * {control control="\QUI\Controls\Contact" labels=false}
 *
 * @author www.pcsg.de (Henning Leutz)
 */

class Contact extends QUI\Control
{
    /**
     * constructor
     * @param Array $attributes
     */
    public function __construct($attributes=array())
    {
        $this->setAttributes(array(
            'data-ajax'    => 1,
            'POST_NAME'    => '',
            'POST_EMAIL'   => '',
            'POST_MESSAGE' => ''
        ));

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
        $Engine = QUI::getTemplateManager()->getEngine();

        // filter POST vars if exist
        $this->setAttributes(array(
            'POST_NAME'    => Orthos::clearFormRequest( $this->getAttribute('POST_NAME') ),
            'POST_EMAIL'   => Orthos::clearFormRequest( $this->getAttribute('POST_EMAIL') ),
            'POST_MESSAGE' => Orthos::clearFormRequest( $this->getAttribute('POST_MESSAGE') ),
        ));

        $Engine->assign(array(
            'this' => $this
        ));

        return $Engine->fetch( dirname( __FILE__ ) .'/Contact.html' );
    }
}