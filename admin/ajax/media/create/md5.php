<?php

/**
 * generate md hashes of the media files
 *
 * @param String $project - name of the project
 * @param String $params - JSON Array
 */
function ajax_media_create_md5($project, $params)
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
            $Item->generateMD5();

        } catch ( \QUI\Exception $Exception )
        {
            \QUI::getMessagesHandler()->addException( $Exception );
        }
    }
}

\QUI::$Ajax->register(
    'ajax_media_create_md5',
    array('project', 'params'),
    'Permission::checkAdminUser'
);
