<?php

/**
 * Check for updates
 *
 * @return Array
 */
function ajax_system_update_check()
{
    $updates = QUI::getPackageManager()->checkUpdates();

    if ( !count( $updates ) )
    {
        \QUI::getMessagesHandler()->addInformation(
            \QUI::getLocale()->get(
                'quiqqer/system',
                'message.packages.no.updates.available'
            )
        );
    }

    return $updates;
}

QUI::$Ajax->register(
	'ajax_system_update_check',
    false,
    array(
    	'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);

?>