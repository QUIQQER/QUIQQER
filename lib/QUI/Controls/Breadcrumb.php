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
    public function __construct($attributes = [])
    {
        // default options
        $this->setAttributes([
            'class'         => 'quiqqer-breadcrumb',
            'controlHeight' => 40,
            'layout'        => 'slider'
        ]);

        parent::__construct($attributes);
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Control::create()
     */
    public function getBody()
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        $Engine->assign([
            'this'    => $this,
            'Rewrite' => QUI::getRewrite()
        ]);

        $this->setAttribute(
            'height',
            (int)$this->getAttribute('controlHeight').'px'
        );

        $this->setStyle('height', $this->getAttribute('controlHeight'));

        $layout = strtolower($this->getAttribute('layout'));

        switch ($layout) {
            default:
            case 'slider':
                $template = '/Breadcrumb.Slider.html';
                $css      = '/Breadcrumb.Slider.css';
                $this->setAttribute(
                    'data-qui', 'package/quiqqer/quiqqer/bin/Controls/BreadcrumbSlider'
                );
                break;

            case 'dropdown':
                $template = '/Breadcrumb.DropDown.html';
                $css      = '/Breadcrumb.DropDown.css';
                $this->setAttribute(
                    'data-qui', 'package/quiqqer/quiqqer/bin/Controls/BreadcrumbDropDown'
                );
                break;
        }

        $this->addCSSFile(\dirname(__FILE__).$css);

        return $Engine->fetch(\dirname(__FILE__).$template);
    }
}
