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
QUI::$Ajax->registerFunction(
    'ajax_media_url_get',
    function ($project, $fileid) {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media   = $Project->getMedia();

        return $Media->get($fileid)->getUrl(true);
    },
    array('project', 'fileid'),
    'Permission::checkAdminUser'
);
