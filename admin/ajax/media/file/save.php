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
        $Project    = QUI\Projects\Manager::getProject($project);
        $Media      = $Project->getMedia();
        $File       = $Media->get($fileid);
        $attributes = \json_decode($attributes, true);

        // rename check
        if (isset($attributes['name']) && $File->getAttribute('name') != $attributes['name']) {
            $File->rename($attributes['name']);

            unset($attributes['name']);
        }

        if (isset($attributes['file'])) {
            unset($attributes['file']);
        }

        $File->setAttributes($attributes);
        $File->save();

        //QUI::getMessagesHandler()->clear();

        if (QUI\Projects\Media\Utils::isFolder($File)) {
            QUI::getMessagesHandler()->addSuccess(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'projects.project.site.media.folderPanel.message.save.success'
                )
            );
        } else {
            QUI::getMessagesHandler()->addSuccess(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'projects.project.site.media.filePanel.message.save.success'
                )
            );
        }

        return $File->getAttributes();
    },
    ['project', 'fileid', 'attributes'],
    'Permission::checkAdminUser'
);
