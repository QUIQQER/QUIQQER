<?php

/**
 * Verfügbare Templates bekommen
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_template_getlist',
    function () {
        return QUI::getPackageManager()->getInstalled(array(
            'type' => "quiqqer-template"
        ));
    },
    false,
    'Permission::checkAdminUser'
);
