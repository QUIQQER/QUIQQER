<?php

/**
 * Update plugin or system by a file
 *
 * @params \QUI\QDOM $File
 */
QUI::$Ajax->registerFunction(
    'ajax_system_update_byfile',
    function ($File) {
        /* @var $File \QUI\QDOM */
        $filepath = $File->getAttribute('filepath');

        if (!file_exists($filepath) && !is_dir($filepath)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
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
