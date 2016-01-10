<?php

/**
 * Get a PHP control
 *
 * @param string $control - Control Name
 * @param string|bool $params - JSON Array
 * @return string
 * @throws \QUI\Exception
 */
QUI::$Ajax->registerFunction(
    'ajax_controls_get',
    function ($control, $params = false) {
        try {
            $Control = new $control();
            $params  = json_decode($params, true);

            /* @var $Control QUI\Control */
            if ($params) {
                $Control->setAttributes($params);
            }

        } catch (QUI\Exception $Exception) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/system', 'control.not.found'),
                404
            );
        }

        if (!is_subclass_of($Control, '\QUI\Control')) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/system', 'control.not.found'),
                404
            );
        }

        return $Control->create();
    },
    array('control', 'params')
);
