<?php

/**
 * Saves the data of a media file
 *
 * @param string $project - Name of the project
 * @param string|integer - File-ID
 * @param string $attributes - JSON Array, new file attributes
 *
 * @return string
 */
QUI::$Ajax->registerFunction(
    'ajax_media_file_save',
    function ($project, $fileid, $attributes) {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media   = $Project->getMedia();
        $File    = $Media->get($fileid);

        $attributes = json_decode($attributes, true);

        $File->setAttributes($attributes);
        $File->save();

        return $File->getAttributes();
    },
    array('project', 'fileid', 'attributes'),
    'Permission::checkAdminUser'
);
