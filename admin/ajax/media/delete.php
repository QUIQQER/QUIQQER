<?php

/**
 * Return the file(s)
 *
 * @param string $project - Name of the project
 * @param string|integer $fileid - File-ID or list of file ids (JSON array)
 *
 * @return string
 */
QUI::$Ajax->registerFunction(
    'ajax_media_delete',
    function ($project, $fileid) {
        $fileid  = json_decode($fileid, true);
        $Project = QUI\Projects\Manager::getProject($project);
        $Media   = $Project->getMedia();

        if (is_array($fileid)) {
            foreach ($fileid as $id) {
                try {
                    $Media->get($id)->delete();
                } catch (QUI\Exception $Exception) {
                    QUI::getMessagesHandler()->addError($Exception->getMessage());
                }
            }

            return;
        }

        $Media->get($fileid)->delete();
    },
    array('project', 'fileid'),
    'Permission::checkAdminUser'
);
