<?php

/**
 * Deactivate the file / files
 *
 * @param String $project - Name of the project
 * @param String|Integer $fileid - File-ID or JSON Array list of file IDs
 * @throws \QUI\Exception
 */
function ajax_media_deactivate($project, $fileid)
{
    $fileid = json_decode($fileid, true);

    $Project = \QUI\Projects\Manager::getProject( $project );
    $Media   = $Project->getMedia();

    if ( is_array( $fileid ) )
    {
        foreach ( $fileid as $id )
        {
            try
            {
                $Media->get($id)->deactivate();

            } catch ( \QUI\Exception $Exception )
            {
                \QUI::getMessagesHandler()->addError( $Exception->getMessage() );
            }
        }

        return;
    }

    $Media->get( $fileid )->deactivate();
}

\QUI::$Ajax->register(
    'ajax_media_deactivate',
    array('project', 'fileid'),
    'Permission::checkAdminUser'
);
