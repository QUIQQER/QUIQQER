<?php

/**
 * This file contains \QUI\Controls\Breadcrumb
 */

namespace QUI\Controls;

use QUI;

/**
 * Alphabet sorting
 *
 * @author www.pcsg.de (Henning Leutz)
 */

class Breadcrumb extends QUI\Control
{
    /**
     * constructor
     * @param Array $attributes
     */
    public function __construct($attributes = array())
    {
        parent::setAttributes( $attributes );

        $this->addCSSFile(
            dirname(__FILE__) . '/Breadcrumb.css'
        );

        $this->setAttribute( 'class', 'quiqqer-breadcrumb grid-100 grid-parent' );
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        $Engine->assign(array(
            'Rewrite' => QUI::getRewrite()
        ));

        return $Engine->fetch( dirname( __FILE__ ) .'/Breadcrumb.html' );
    }
}