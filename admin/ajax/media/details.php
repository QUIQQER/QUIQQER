<?php

/**
 * Return the data of the fileid
 *
 * @param String $project - Project name
 * @param String $fileid  - JSON String|Array
 *
 * @return Array
 */
function ajax_media_details($project, $fileid)
{
    $fileid  = json_decode( $fileid, true );
    $Project = \QUI\Projects\Manager::getProject( $project );
    $Media   = $Project->getMedia();

    if ( is_array( $fileid ) )
    {
        $list = array();

        foreach ( $fileid as $id )
        {
            $File   = $Media->get( $id );
            $list[] = $File->getAttributes();
        }

        return $list;
    }

    return $Media->get( $fileid )->getAttributes();
}

\QUI::$Ajax->register(
    'ajax_media_details',
    array('project', 'fileid'),
    'Permission::checkAdminUser'
);
