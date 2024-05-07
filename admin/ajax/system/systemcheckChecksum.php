<?php

/**
 * Return the check for files
 * Only for SuperUsers
 *
 * @return String
 */

QUI::$Ajax->registerFunction(
    'ajax_system_systemcheckChecksum',
    function ($packageName) {
        $Package = QUI::getPackage('quiqqer/requirements');
        $dir = $Package->getVarDir();
        $cacheFile = $dir . "requirements_checks_result_package";

        if (!file_exists($cacheFile)) {
            QUI::getMessagesHandler()->addError(
                QUI::getLocale()->get('quiqqer/core', 'packages.panel.category.systemcheck.checksum.fileNotFound')
            );

            return false;
        }

        $packages = json_decode(file_get_contents($cacheFile), true);

        if (!isset($packages[$packageName])) {
            QUI::getMessagesHandler()->addError(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'packages.panel.category.systemcheck.checksum.cacheForThisPackageNotFound',
                    ['cacheForThisPackage' => $packageName]
                )
            );

            return false;
        }

        return $packages[$packageName];
    },
    ['packageName'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
