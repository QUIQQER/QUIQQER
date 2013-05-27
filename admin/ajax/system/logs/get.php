<?php

/**
 * System logs
 *
 * @return Array
 */
function ajax_system_logs_get($page, $limit)
{
    $dir = VAR_DIR .'log/';

    $list  = array();
    $files = \Utils_System_File::readDir( $dir );

    rsort( $files );

    foreach ( $files as $file )
    {
        $mtime = filemtime( $dir . $file );

        $list[] = array(
            'file'  => $file,
            'mtime' => $mtime,
            'mdate' => date( 'Y-m-d H:i:s', $mtime )
        );
    }

    return \Utils_Grid::getResult( $list, $page, $limit );
}

\QUI::$Ajax->register(
    'ajax_system_logs_get',
    array( 'page', 'limit' ),
    'Permission::checkSU'
);
