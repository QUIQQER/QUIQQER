<?php

/**
 * Replace a file with another file
 *
 * @param string $project - name of the project
 * @param integer $fileid
 * @param \QUI\QDOM $File
 */
QUI::$Ajax->registerFunction(
    'ajax_media_replace',
    function ($project, $fileid, $File) {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media   = $Project->getMedia();

        /* @var $File \QUI\QDOM */
        $file = $File->getAttribute('filepath');

        if (!file_exists($file)) {
            return;
        }

        $Media->replace($fileid, $file);
    },
    array('project', 'fileid', 'File'),
    'Permission::checkAdminUser'
);
