<?php

/**
 * Activate the file / files
 *
 * @param string $project - Name of the project
 * @param string|integer $fileid - File-ID
 *
 * @return array|boolean
 * @throws \QUI\Exception
 */

QUI::$Ajax->registerFunction(
    'ajax_media_activate',
    static function ($project, $fileid) {
        $fileid = json_decode($fileid, true);

        $Project = QUI\Projects\Manager::getProject($project);
        $Media = $Project->getMedia();

        if (is_array($fileid)) {
            $result = [];

            foreach ($fileid as $id) {
                try {
                    $File = $Media->get($id);
                    $File->activate();

                    $result[$File->getId()] = $File->isActive();
                } catch (QUI\Exception $Exception) {
                    QUI::getMessagesHandler()->addError($Exception->getMessage());
                }
            }

            return $result;
        }

        $File = $Media->get($fileid);
        $File->activate();

        return $File->isActive();
    },
    ['project', 'fileid'],
    'Permission::checkAdminUser'
);
