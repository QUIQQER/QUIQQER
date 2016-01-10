<?php

/**
 * Restore media files
 *
 * @param string $project - Name of the project
 * @param string $ids - JSON Array, File IDs
 * @param string|integer $parentid - Folder-ID
 *
 * @throws QUI\Exception
 */
QUI::$Ajax->registerFunction(
    'ajax_trash_media_restore',
    function ($project, $ids, $parentid) {
        $Project = QUI::getProjectManager()->decode($project);
        $Media   = $Project->getMedia();
        $Trash   = $Media->getTrash();
        $Folder  = $Media->get($parentid);

        if (!QUI\Projects\Media\Utils::isFolder($Folder)) {
            throw new QUI\Exception(
                'No Folder given' //#locale
            );
        }

        /* @var $Folder \QUI\Projects\Media\Folder */
        $ids = json_decode($ids, true);

        foreach ($ids as $id) {
            $Trash->restore($id, $Folder);
        }
    },
    array('project', 'ids', 'parentid'),
    'Permission::checkAdminUser'
);
