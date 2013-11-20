<?php

/**
 * Activate the file / files
 *
 * @throws \QUI\Exception
 */
function ajax_media_create_sha1($project, $params)
{
    $params  = json_decode( $params, true );
    $Project = Projects_Manager::getProject( $project );
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
            QUI::getMessagesHandler()->addException( $Exception );
        }
    }
}
QUI::$Ajax->register('ajax_media_create_sha1', array('project', 'params'), 'Permission::checkAdminUser');

?>