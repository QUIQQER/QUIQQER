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

use QUI\Projects\Media\Folder;

QUI::$Ajax->registerFunction(
    'ajax_media_file_save',
    static function ($project, $fileid, $attributes): array {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();
        $File = $Media->get($fileid);
        $attributes = json_decode($attributes, true);
        $oldEffects = [];

        // rename check
        if (isset($attributes['name']) && $File->getAttribute('name') != $attributes['name']) {
            $File->rename($attributes['name']);

            unset($attributes['name']);
        }

        if (isset($attributes['file'])) {
            unset($attributes['file']);
        }

        if (method_exists($File, 'getEffects')) {
            $oldEffects = $File->getEffects();
            $File->setAttributes($attributes);

            if (isset($attributes['image_effects']) && method_exists($File, 'setEffects')) {
                $File->setEffects($attributes['image_effects']);
            }
        }

        $File->save();

        if ($File instanceof Folder) {
            $newEffects = $File->getEffects();

            if (json_encode($oldEffects) !== json_encode($newEffects)) {
                $File->deleteCache();
            }

            QUI::getMessagesHandler()->addSuccess(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'projects.project.site.media.folderPanel.message.save.success'
                )
            );
        } else {
            QUI::getMessagesHandler()->addSuccess(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'projects.project.site.media.filePanel.message.save.success'
                )
            );
        }

        return $File->getAttributes();
    },
    ['project', 'fileid', 'attributes'],
    'Permission::checkAdminUser'
);
