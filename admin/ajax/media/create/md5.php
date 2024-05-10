<?php

/**
 * generate md5 hashes of the media files
 *
 * @param string $project - JSON project data
 */

QUI::$Ajax->registerFunction(
    'ajax_media_create_md5',
    static function ($project) {
        $Project = QUI\Projects\Manager::decode($project);
        $Media = $Project->getMedia();

        $ids = $Media->getChildrenIds([
            'where' => [
                'type' => [
                    'type' => 'NOT',
                    'value' => 'folder'
                ]
            ]
        ]);

        foreach ($ids as $id) {
            try {
                $Item = $Media->get($id);
                $Item->generateMD5();
            } catch (QUI\Exception $Exception) {
                QUI::getMessagesHandler()->addError(
                    $Exception->getMessage()
                );
            }
        }
    },
    ['project'],
    'Permission::checkAdminUser'
);
