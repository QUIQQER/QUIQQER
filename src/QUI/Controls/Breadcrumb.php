<?php

/**
 * This file contains \QUI\Controls\Breadcrumb
 */

namespace QUI\Controls;

use QUI;

/**
 * Alphabet sorting
 */
class Breadcrumb extends QUI\Control
{
    protected array $allowedSeparators = [
        'angle-right',
        'chevron-right',
        'angles-right',
        'caret-right',
        'slash',
        'bullet',
        'pipe'
    ];

    protected array $allowedLastItemStyles = [
        'none',
        'primary',
        'bold',
        'primary-bold'
    ];

    public function __construct(array $attributes = [])
    {
        // default options
        $this->setAttributes([
            'class' => 'quiqqer-breadcrumb',
            'controlHeight' => 40,
            'layout' => 'slider',
            'showTitle' => true,
            'titleText' => '',
            'fontSize' => '0.9em',
            'separator' => 'angle-right',
            'lastItemStyle' => 'primary'
        ]);

        parent::__construct($attributes);

        $this->setAttribute('cacheable', 0);
    }

    /**
     * @throws QUI\Exception
     */
    public function getBody(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();
        $separator = $this->normalizeSeparator(
            (string)$this->getAttribute('separator')
        );
        $lastItemStyle = $this->normalizeLastItemStyle(
            (string)$this->getAttribute('lastItemStyle')
        );
        $fontSize = $this->normalizeFontSize(
            (string)$this->getAttribute('fontSize')
        );

        $this->setAttribute('separator', $separator);
        $this->setAttribute('lastItemStyle', $lastItemStyle);
        $this->setAttribute('fontSize', $fontSize);
        $this->setAttribute(
            'showTitle',
            (int)(bool)$this->getAttribute('showTitle')
        );

        $separatorConfig = $this->getSeparatorConfig($separator);

        $Engine->assign([
            'this' => $this,
            'Rewrite' => QUI::getRewrite(),
            'separatorConfig' => $separatorConfig
        ]);

        $this->setAttribute(
            'height',
            (int)$this->getAttribute('controlHeight') . 'px'
        );

        $this->setStyle('height', $this->getAttribute('controlHeight'));
        $this->setStyle('font-size', $fontSize);
        $this->setAttribute('data-qui-breadcrumb-separator', $separator);
        $this->setAttribute('data-qui-breadcrumb-last-item-style', $lastItemStyle);
        $this->addCSSClass('quiqqer-breadcrumb--separator-' . $separator);
        $this->addCSSClass('quiqqer-breadcrumb--last-item-' . $lastItemStyle);

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

    protected function normalizeSeparator(string $separator): string
    {
        if (!in_array($separator, $this->allowedSeparators, true)) {
            return 'angle-right';
        }

        return $separator;
    }

    protected function normalizeLastItemStyle(string $lastItemStyle): string
    {
        if (!in_array($lastItemStyle, $this->allowedLastItemStyles, true)) {
            return 'none';
        }

        return $lastItemStyle;
    }

    protected function normalizeFontSize(string $fontSize): string
    {
        if (preg_match('/^\d+(?:\.\d+)?(?:em|rem|px|%)$/', $fontSize)) {
            return $fontSize;
        }

        return '0.9em';
    }

    protected function getSeparatorConfig(string $separator): array
    {
        switch ($separator) {
            case 'chevron-right':
                return [
                    'type' => 'icon',
                    'class' => 'fa-chevron-right',
                    'text' => ''
                ];

            case 'angles-right':
                return [
                    'type' => 'icon',
                    'class' => 'fa-angles-right',
                    'text' => ''
                ];

            case 'caret-right':
                return [
                    'type' => 'icon',
                    'class' => 'fa-caret-right',
                    'text' => ''
                ];

            case 'slash':
                return [
                    'type' => 'text',
                    'class' => '',
                    'text' => '/'
                ];

            case 'bullet':
                return [
                    'type' => 'text',
                    'class' => '',
                    'text' => '•'
                ];

            case 'pipe':
                return [
                    'type' => 'text',
                    'class' => '',
                    'text' => '|'
                ];

            case 'angle-right':
            default:
                return [
                    'type' => 'icon',
                    'class' => 'fa-angle-right',
                    'text' => ''
                ];
        }
    }
}
