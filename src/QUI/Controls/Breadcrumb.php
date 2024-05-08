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
    public function __construct(array $attributes = [])
    {
        // default options
        $this->setAttributes([
            'class' => 'quiqqer-breadcrumb',
            'controlHeight' => 40,
            'layout' => 'slider'
        ]);

        parent::__construct($attributes);

        $this->setAttribute('cacheable', 0);
    }

    /**
     * @return string
     * @throws QUI\Exception
     */
    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        $Engine->assign([
            'this' => $this,
            'Rewrite' => QUI::getRewrite()
        ]);

        $this->setAttribute(
            'height',
            (int)$this->getAttribute('controlHeight') . 'px'
        );

        $this->setStyle('height', $this->getAttribute('controlHeight'));

        $layout = strtolower($this->getAttribute('layout'));

        switch ($layout) {
            default:
            case 'slider':
                $template = '/Breadcrumb.Slider.html';
                $css = '/Breadcrumb.Slider.css';

                $this->setAttribute(
                    'data-qui',
                    'package/quiqqer/core/bin/Controls/BreadcrumbSlider'
                );
                break;

            case 'dropdown':
                $template = '/Breadcrumb.DropDown.html';
                $css = '/Breadcrumb.DropDown.css';

                $this->setAttribute(
                    'data-qui',
                    'package/quiqqer/core/bin/Controls/BreadcrumbDropDown'
                );
                break;
        }

        $this->addCSSFile(__DIR__ . $css);

        return $Engine->fetch(__DIR__ . $template);
    }
}
