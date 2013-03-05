<?php

/**
 * Deactivate the file / files
 *
 * @throws QException
 */
function ajax_media_deactivate($project, $fileid)
{
$fileid = json_decode($fileid, true);

    $Project = Projects_Manager::getProject( $project );
    $Media   = $Project->getMedia();

    if ( is_array( $fileid ) )
    {
        foreach ( $fileid as $id ) {
            $Media->get( $id )->deactivate();
        }

        return;
    }

    $Media->get( $fileid )->deactivate();
}
QUI::$Ajax->register('ajax_media_deactivate', array('project', 'fileid'), 'Permission::checkAdminUser');

?>