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
 * @licence For copyright and license information, please view the /README.md
 */
class Breadcrumb extends QUI\Control
{
    /**
     * constructor
     * @param array $attributes
     */
    public function __construct($attributes = array())
    {
        parent::__construct($attributes);

        $this->addCSSFile(
            dirname(__FILE__) . '/Breadcrumb.css'
        );

        $this->setAttribute('qui-class', "Controls/Breadcrumb");
        $this->setAttribute('class', 'quiqqer-breadcrumb');
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

        return $Engine->fetch(dirname(__FILE__) . '/Breadcrumb.html');
    }
}
