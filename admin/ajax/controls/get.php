<?php

/**
 * Get a PHP control
 *
 * @param string $control - Control Name
 * @param string|bool $params - JSON Array
 * @return string
 * @throws \QUI\Exception
 */

use QUI\Control;

QUI::$Ajax->registerFunction(
    'ajax_controls_get',
    static function ($control, $params = false): string {
        if (
            !is_string($control)
            || !is_subclass_of($control, Control::class)
        ) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/core', 'control.not.found'),
                404
            );
        }

        try {
            $Control = new $control();

            if (is_string($params)) {
                $params = json_decode($params, true);
            }

            if (is_array($params)) {
                $Control->setAttributes($params);
            }
        } catch (QUI\Exception) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/core', 'control.not.found'),
                404
            );
        }

        $Output = new QUI\Output();
        $control = $Control->create();
        $css = QUI\Control\Manager::getCSS();

        return $Output->parse($css . $control);
    },
    ['control', 'params']
);
