<?php

/**
 * System logs
 *
 * @return Array
 */
function ajax_system_logs_file($file)
{
    $log = VAR_DIR .'log/'. $file;

    if ( !file_exists( $log ) ) {
        return '';
    }

    return file_get_contents( $log );
}

\QUI::$Ajax->register(
    'ajax_system_logs_file',
    array( 'file' ),
    'Permission::checkSU'
);
