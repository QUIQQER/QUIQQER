<?php

/**
 * Return all available templates
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_template_getlist',
    static function (): array {
        return QUI::getPackageManager()->searchInstalledPackages([
            'type' => "quiqqer-template"
        ]);
    },
    false,
    'Permission::checkAdminUser'
);
