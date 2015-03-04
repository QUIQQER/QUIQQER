<?php

/**
 * Return the children of a media folder
 *
 * @param String $project - Name of the project
 * @param String|Integer $folderid - Folder-ID
 * @param String $params - JSON Order Params
 * @return Array
 */
function ajax_media_folder_children($project, $folderid, $params)
{
    $Project = \QUI\Projects\Manager::getProject( $project );
    $Media   = $Project->getMedia();
    $File    = $Media->get( $folderid ); /* @var $File \QUI\Projects\Media\Folder */
    $params  = json_decode( $params, true );

    $children = array();

    if ( isset( $params['order'] ) )
    {
        $_children = $File->getChildrenIds( $params['order'] );
    } else
    {
        $_children = $File->getChildrenIds();
    }


    // create children data
    foreach ( $_children as $id )
    {
        try
        {
            $Child      = $Media->get( $id );
            $children[] = \QUI\Projects\Media\Utils::parseForMediaCenter( $Child );

        } catch ( \QUI\Exception $Exception )
        {
            $params = array(
                'id'    => $id,
                'name'  => $Exception->getAttribute('name'),
                'title' => $Exception->getAttribute('title'),
                'error' => true
            );

            $children[] = $params;
        }
    }

    return $children;
}

\QUI::$Ajax->register(
    'ajax_media_folder_children',
    array('project', 'folderid', 'params'),
    'Permission::checkAdminUser'
);
