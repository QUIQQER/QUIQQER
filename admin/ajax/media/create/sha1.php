<?php

/**
 * Generate sha1 hashes of the media files
 *
 * @param string $project - JSON project data
 */

QUI::$Ajax->registerFunction(
    'ajax_media_create_sha1',
    static function ($project): void {
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

                if (method_exists($Item, 'generateSHA1')) {
                    $Item->generateSHA1();
                }
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
