<?php

/**
 * Deactivate the file / files
 *
 * @param string $project - Name of the project
 * @param string|integer $fileid - File-ID or JSON Array list of file IDs
 *
 * @return string|array
 * @throws \QUI\Exception
 */
QUI::$Ajax->registerFunction(
    'ajax_media_deactivate',
    function ($project, $fileid) {
        $fileid = json_decode($fileid, true);

        $Project = QUI\Projects\Manager::getProject($project);
        $Media   = $Project->getMedia();

        if (is_array($fileid)) {
            $result = array();

            foreach ($fileid as $id) {
                try {
                    $File = $Media->get($id);
                    $File->deactivate();

                    $result[$File->getId()] = $File->isActive();
                } catch (QUI\Exception $Exception) {
                    QUI::getMessagesHandler()->addError($Exception->getMessage());
                }
            }

            return $result;
        }

        $File = $Media->get($fileid);
        $File->deactivate();

        return $File->isActive();
    },
    array('project', 'fileid'),
    'Permission::checkAdminUser'
);
