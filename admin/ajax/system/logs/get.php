<?php

/**
 * System logs
 *
 * @return Array
 */
function ajax_system_logs_get($page, $limit, $search='')
{
    $dir = VAR_DIR .'log/';

    $list  = array();
    $files = \QUI\Utils\System\File::readDir( $dir );

    rsort( $files );

    foreach ( $files as $file )
    {
        if ( $search && strpos( $file, $search ) === false ) {
            continue;
        }

        $mtime = filemtime( $dir . $file );

        $list[] = array(
            'file'  => $file,
            'mtime' => $mtime,
            'mdate' => date( 'Y-m-d H:i:s', $mtime )
        );
    }

    return \QUI\Utils\Grid::getResult( $list, $page, $limit );
}

\QUI::$Ajax->register(
    'ajax_system_logs_get',
    array( 'page', 'limit', 'search' ),
    'Permission::checkSU'
);
