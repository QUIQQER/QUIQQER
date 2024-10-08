<?php

/**
 * Hide a file
 *
 * @param string $project - Name of the project
 * @param string $ids - ids which copied
 *
 * @throws \QUI\Exception
 */

QUI::$Ajax->registerFunction(
    'ajax_media_hide',
    static function ($project, $ids): void {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();
        $ids = json_decode($ids, true);

        foreach ($ids as $id) {
            try {
                $Item = $Media->get((int)$id);

                if (method_exists($Item, 'setHidden')) {
                    $Item->setHidden();
                }

                $Item->save();
            } catch (QUI\Exception $Exception) {
                QUI::getMessagesHandler()->addError(
                    $Exception->getMessage()
                );
            }
        }
    },
    ['project', 'ids'],
    'Permission::checkAdminUser'
);
