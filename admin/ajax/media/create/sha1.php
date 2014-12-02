<?php

/**
 * Generate sha1 hashes of the media files
 *
 * @param String $project - name of the project
 * @param String $params - JSON Array
 */
function ajax_media_create_sha1($project, $params)
{
    $params  = json_decode( $params, true );
    $Project = \QUI\Projects\Manager::getProject( $project );
    $Media   = $Project->getMedia();

    $ids = $Media->getChildrenIds(array(
        'where' => array(
            'type' => array(
                'type'  => 'NOT',
                'value' => 'folder'
            )
        )
    ));

    foreach ( $ids as $id )
    {
        try
        {
            $Item = $Media->get( $id );
            $Item->generateSHA1();

        } catch ( \QUI\Exception $Exception )
        {
            \QUI::getMessagesHandler()->addException( $Exception );
        }
    }
}

\QUI::$Ajax->register(
    'ajax_media_create_sha1',
    array('project', 'params'),
    'Permission::checkAdminUser'
);
