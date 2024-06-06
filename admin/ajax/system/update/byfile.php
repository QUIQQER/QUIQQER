<?php

/**
 * Update plugin or system by a file
 *
 * @params \QUI\QDOM $File
 */

use QUI\QDOM;

QUI::$Ajax->registerFunction(
    'ajax_system_update_byfile',
    static function ($File): void {
        /* @var $File QDOM */
        $filepath = $File->getAttribute('filepath');

        if (!file_exists($filepath) && !is_dir($filepath)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.no.quiqqer.update.archive'
                )
            );
        }

        QUI::getPackageManager()->updatePackage(
            $File->getAttribute('filepath')
        );
    },
    ['File'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
