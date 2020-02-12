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
    function ($project, $ids) {
        $Project = QUI\Projects\Manager::getProject($project);
        $Media   = $Project->getMedia();
        $ids     = \json_decode($ids, true);

        foreach ($ids as $id) {
            try {
                $Item = $Media->get((int)$id);
                $Item->setHidden();
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
