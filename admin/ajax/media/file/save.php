<?php

/**
 * Returns the template for the file panel
 * @return String
 */
function ajax_media_file_save($project, $fileid, $attributes)
{
    $Project = Projects_Manager::getProject( $project );
    $Media   = $Project->getMedia();
    $File    = $Media->get( $fileid );

    $attributes = json_decode($attributes, true);

    $File->setAttributes($attributes);
    $File->save();

    return $File->getAttributes();
}
QUI::$Ajax->register('ajax_media_file_save', array('project', 'fileid', 'attributes'), 'Permission::checkAdminUser');

?>