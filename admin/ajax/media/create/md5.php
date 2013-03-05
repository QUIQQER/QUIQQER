<?php

/**
 * Activate the file / files
 *
 * @throws QException
 */
function ajax_media_create_md5($project, $params)
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
            $Item->generateMD5();

        } catch ( QException $Exception )
        {
            QUI::getMessagesHandler()->addException( $Exception );
        }
    }
}
QUI::$Ajax->register('ajax_media_create_md5', array('project', 'params'), 'Permission::checkAdminUser');

?>