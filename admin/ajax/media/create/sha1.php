<?php

/**
 * Generate sha1 hashes of the media files
 *
 * @param String $project - JSON project data
 */
function ajax_media_create_sha1($project)
{
    $Project = QUI\Projects\Manager::decode($project);
    $Media = $Project->getMedia();

    $ids = $Media->getChildrenIds(array(
        'where' => array(
            'type' => array(
                'type'  => 'NOT',
                'value' => 'folder'
            )
        )
    ));

    foreach ($ids as $id) {

        try {
            $Item = $Media->get($id);
            $Item->generateSHA1();

        } catch (QUI\Exception $Exception) {

            QUI::getMessagesHandler()->addError(
                $Exception->getMessage()
            );
        }
    }
}

QUI::$Ajax->register(
    'ajax_media_create_sha1',
    array('project'),
    'Permission::checkAdminUser'
);
