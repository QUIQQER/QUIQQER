<?php

/**
 * Returns the template for the file panel
 * @return String
 */
function ajax_media_delete($project, $fileid)
{
    $fileid  = json_decode($fileid, true);
    $Project = Projects_Manager::getProject( $project );
    $Media   = $Project->getMedia();

    if ( is_array($fileid) )
    {
        foreach ( $fileid as $id ) {
            $Media->get( $id )->delete();
        }

        return;
    }

    $Media->get( $fileid )->delete();
}
QUI::$Ajax->register('ajax_media_delete', array('project', 'fileid'), 'Permission::checkAdminUser');

?>