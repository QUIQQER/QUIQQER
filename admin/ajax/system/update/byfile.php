<?php

/**
 * Update plugin or system by a file
 *
 * @params QDOM $File
 */
function ajax_system_update_byfile($File)
{
    $filepath = $File->getAttribute( 'filepath' );

    if ( !file_exists( $filepath ) && !is_dir( $filepath ) )
    {
        throw new \QException(
            \QUI::getLocale()->get(
                'quiqqer/system',
                'exception.no.quiqqer.update.archive'
            )
        );
    }

    \QUI::getPackageManager()->updatePackage(
        $File->getAttribute( 'filepath' )
    );
}

QUI::$Ajax->register(
	'ajax_system_update_byfile',
    array( 'File' ),
    array(
    	'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);

?>