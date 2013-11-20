<?php

/**
 * Copy files to a folder
 *
 * @param String $project
 * @param String $to	- folder id
 * @param String $ids	- ids which copied
 */
function ajax_media_move($project, $to, $ids)
{
    $Project = Projects_Manager::getProject( $project );
    $Media   = $Project->getMedia();
    $Folder  = $Media->get( $to );

    $ids = json_decode($ids, true);

    if ( !Projects_Media_Utils::isFolder( $Folder ) )
    {
        throw new \QUI\Exception(
        	'Bitte wählen Sie ein Ordner aus um die Dateie zu verschieben.'
        );
    }

    foreach ( $ids as $id )
    {
        try
        {
            $Item = $Media->get( (int)$id );
            $Item->moveTo( $Folder );

        } catch ( \QUI\Exception $e )
        {
            // @todo Fehler sammeln und an den handler weiter reichen
        }
    }
}
QUI::$Ajax->register('ajax_media_move', array('project', 'to', 'ids'), 'Permission::checkAdminUser');

?>