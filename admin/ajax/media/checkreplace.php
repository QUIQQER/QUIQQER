<?php

/**
 * Replace  a file with another file
 *
 * @param String $project
 * @param Integer $fileid
 * @param $file
 */
function ajax_media_checkreplace($project, $fileid, $filename, $filetype)
{
    $Project = Projects_Manager::getProject( $project );
    $Media   = $Project->getMedia();

    // check before upload if a replacement is allowed
    Projects_Media_Utils::checkReplace($Media, $fileid, array(
        'name' => $filename,
        'type' => $filetype
    ));
}

QUI::$Ajax->register(
	'ajax_media_checkreplace',
    array('project', 'fileid', 'filename', 'filetype'),
    'Permission::checkAdminUser'
);

?>