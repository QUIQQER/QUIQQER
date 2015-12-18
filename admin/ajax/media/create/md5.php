<?php

/**
 * generate md5 hashes of the media files
 *
 * @param string $project - JSON project data
 */
function ajax_media_create_md5($project)
{
    $Project = QUI\Projects\Manager::decode($project);
    $Media   = $Project->getMedia();

    $ids = $Media->getChildrenIds(array(
        'where' => array(
            'type' => array(
                'type' => 'NOT',
                'value' => 'folder'
            )
        )
    ));

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
}

QUI::$Ajax->register(
    'ajax_media_create_md5',
    array('project'),
    'Permission::checkAdminUser'
);
