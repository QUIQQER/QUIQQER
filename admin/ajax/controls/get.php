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
        try {
            $Control = new $control();
            $params = json_decode($params, true);

            /* @var $Control QUI\Control */
            if ($params) {
                $Control->setAttributes($params);
            }
        } catch (QUI\Exception) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/core', 'control.not.found'),
                404
            );
        }

        if (!is_subclass_of($Control, Control::class)) {
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
