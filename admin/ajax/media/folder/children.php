<?php

/**
 * Nur subfolders bekommen
 *
 * @param String $project
 * @param String $lang
 * @param String $fileid
 *
 * @return Array
 */
function ajax_media_folder_children($project, $folderid)
{
    $Project = \QUI\Projects\Manager::getProject( $project );
    $Media   = $Project->getMedia();
    $File    = $Media->get( $folderid ); /* @var $File \QUI\Projects\Media\Folder */

    $children  = array();
    $_children = $File->getChildrenIds();

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
QUI::$Ajax->register('ajax_media_folder_children', array('project', 'folderid'), 'Permission::checkAdminUser');

?>