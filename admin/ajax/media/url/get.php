<?php

/**
 * Return the rewrited url of a file
 *
 * @param string $project - Name of the project
 * @param string|integer $fileid - File-ID
 *
 * @return string
 * @throws \QUI\Exception
 */
function ajax_media_url_get($project, $fileid)
{
    $Project = QUI\Projects\Manager::getProject($project);
    $Media   = $Project->getMedia();

    return $Media->get($fileid)->getUrl(true);
}

QUI::$Ajax->register(
    'ajax_media_url_get',
    array('project', 'fileid'),
    'Permission::checkAdminUser'
);
