<?php

/**
 * Returns the template for the file panel
 * @return String
 */
function ajax_media_file_getParentId($project, $fileid)
{
    $Project = \QUI\Projects\Manager::getProject( $project );
    $Media   = $Project->getMedia();

    try
    {
        $File = $Media->get( $fileid );

    } catch ( \QUI\Exception $Exception )
    {
        return $Media->firstChild()->getId();
    }

    if ( \QUI\Projects\Media\Utils::isFolder( $File ) ) {
        return $File->getId();
    }

    return $File->getParent()->getId();
}

\QUI::$Ajax->register(
    'ajax_media_file_getParentId',
    array( 'project', 'fileid' ),
    'Permission::checkAdminUser'
);
