<?php

/**
 * Saves the data of a media file
 *
 * @param String $project - Name of the project
 * @param String|Integer - File-ID
 * @param String $attributes - JSON Array, new file attributes
 * @return String
 */
function ajax_media_file_save($project, $fileid, $attributes)
{
    $Project = \QUI\Projects\Manager::getProject( $project );
    $Media   = $Project->getMedia();
    $File    = $Media->get( $fileid );

    $attributes = json_decode($attributes, true);

    $File->setAttributes($attributes);
    $File->save();

    return $File->getAttributes();
}

\QUI::$Ajax->register(
    'ajax_media_file_save',
    array('project', 'fileid', 'attributes'),
    'Permission::checkAdminUser'
);
