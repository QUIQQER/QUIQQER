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
    private const DEFAULT_FONT_SIZE = '0.9em';
    private const DEFAULT_PADDING = '1rem';

    protected array $allowedFontSizes = [
        'xs' => '0.75em',
        's' => '0.9em',
        'normal' => '1em',
        'lg' => '1.25em',
        'xl' => '1.5em'
    ];

    protected array $allowedPaddings = [
        'none' => '0px',
        'xs' => '0.25rem',
        's' => '0.5rem',
        'normal' => '1rem',
        'lg' => '1.25rem',
        'xl' => '2rem'
    ];

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
            'class' => 'quiqqer-core-controls-breadcrumb',
            'layout' => 'slider',
            'showTitle' => true,
            'titleText' => '',
            'firstItemText' => '',
            'fontSize' => 's',
            'paddingBlock' => 'normal',
            'separator' => 'angle-right',
            'lastItemStyle' => 'bold'
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
        $paddingBlock = $this->normalizePadding(
            (string)$this->getAttribute('paddingBlock')
        );

        $this->setAttribute('separator', $separator);
        $this->setAttribute('lastItemStyle', $lastItemStyle);
        $this->setAttribute('fontSize', $fontSize);
        $this->setAttribute('paddingBlock', $paddingBlock);
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

        if ($fontSize !== self::DEFAULT_FONT_SIZE) {
            $this->setCustomVariable('font-size', $fontSize);
        }

        if ($paddingBlock !== self::DEFAULT_PADDING) {
            $this->setCustomVariable('padding-y', $paddingBlock);
        }

        $this->setAttribute('data-qui-breadcrumb-separator', $separator);
        $this->setAttribute('data-qui-breadcrumb-last-item-style', $lastItemStyle);
        $this->addCSSClass('quiqqer-core-controls-breadcrumb--separator-' . $separator);
        $this->addCSSClass('quiqqer-core-controls-breadcrumb--last-item-' . $lastItemStyle);

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

    /**
     * Normalize the configured separator and apply the default fallback.
     *
     * @param string $separator
     * @return string
     */
    protected function normalizeSeparator(string $separator): string
    {
        if (!in_array($separator, $this->allowedSeparators, true)) {
            return 'angle-right';
        }

        return $separator;
    }

    /**
     * Normalize the configured last item style and apply the default fallback.
     *
     * @param string $lastItemStyle
     * @return string
     */
    protected function normalizeLastItemStyle(string $lastItemStyle): string
    {
        if (!in_array($lastItemStyle, $this->allowedLastItemStyles, true)) {
            return 'none';
        }

        return $lastItemStyle;
    }

    /**
     * Resolve the configured font size preset to its CSS value.
     *
     * @param string $fontSize
     * @return string
     */
    protected function normalizeFontSize(string $fontSize): string
    {
        if (isset($this->allowedFontSizes[$fontSize])) {
            return $this->allowedFontSizes[$fontSize];
        }

        return $this->allowedFontSizes['s'];
    }

    /**
     * Resolve the configured padding preset to its CSS value.
     *
     * @param string $padding
     * @return string
     */
    protected function normalizePadding(string $padding): string
    {
        if (isset($this->allowedPaddings[$padding])) {
            return $this->allowedPaddings[$padding];
        }

        if ($this->isAllowedCssSpacingValue($padding)) {
            return $padding;
        }

        return $this->allowedPaddings['normal'];
    }

    /**
     * Check whether a custom CSS spacing value is safe to use.
     *
     * @param string $value
     * @return bool
     */
    protected function isAllowedCssSpacingValue(string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        if (preg_match('/^0$/', $value)) {
            return true;
        }

        if (
            preg_match(
                '/^-?(?:\d+|\d*\.\d+)(?:px|rem|em|%|vh|vw|svh|svw|lvh|lvw|dvh|dvw|ch|ex|cm|mm|in|pt|pc)$/',
                $value
            )
        ) {
            return true;
        }

        return preg_match(
            '/^(?:var|calc|clamp|min|max)\([^;{}]+\)$/',
            $value
        ) === 1;
    }

    /**
     * Return the template config for the configured separator style.
     *
     * @param string $separator
     * @return array{type: string, class: string, text: string}
     */
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

    /**
     * Write an internal control CSS variable to the root element style.
     *
     * @param string $name
     * @param string $value
     * @return void
     */
    private function setCustomVariable(string $name, string $value): void
    {
        if ($name === '' || $value === '') {
            return;
        }

        $this->setStyle('--_q-controlConf-' . $name, $value);
    }
}
