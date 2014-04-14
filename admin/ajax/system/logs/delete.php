<?php

/**
 * System logs löschen
 *
 * @return Array
 */
function ajax_system_logs_delete($file)
{
    $log = VAR_DIR .'log/'. $file;
    $log = \QUI\Utils\Security\Orthos::clearPath( $log );

    \QUI\Utils\System\File::unlink( $log );
}

\QUI::$Ajax->register(
    'ajax_system_logs_delete',
    array( 'file' ),
    'Permission::checkSU'
);
