<?php

/**
 * Delete current license key
 *
 * @return bool - success
 */
QUI::$Ajax->registerFunction(
    'ajax_licenseKey_delete',
    function () {
        $licenseConfigFile = CMS_DIR.'etc/license.ini.php';

        if (!\file_exists($licenseConfigFile)) {
            QUI::getMessagesHandler()->addError(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'message.ajax.licenseKey.delete.error'
                )
            );

            return false;
        }

        \unlink($licenseConfigFile);

        // re-create composer.json
        QUI::getPackageManager()->refreshServerList();

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/system',
                'message.ajax.licenseKey.delete.success'
            )
        );

        return true;
    },
    [],
    'Permission::checkAdminUser'
);
