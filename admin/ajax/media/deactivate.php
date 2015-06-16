<?php

/**
 * Deactivate the file / files
 *
 * @param String         $project - Name of the project
 * @param String|Integer $fileid  - File-ID or JSON Array list of file IDs
 *
 * @return string|array
 * @throws \QUI\Exception
 */
function ajax_media_deactivate($project, $fileid)
{
    $fileid = json_decode($fileid, true);

    $Project = QUI\Projects\Manager::getProject($project);
    $Media = $Project->getMedia();

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
}

QUI::$Ajax->register(
    'ajax_media_deactivate',
    array('project', 'fileid'),
    'Permission::checkAdminUser'
);
