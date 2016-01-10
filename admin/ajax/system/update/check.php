<?php

/**
 * Check for updates
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_system_update_check',
    function () {
        $updates = QUI::getPackageManager()->checkUpdates();

        if (!count($updates)) {
            QUI::getMessagesHandler()->addInformation(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'message.packages.no.updates.available'
                )
            );
        }

        return $updates;
    },
    false,
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
