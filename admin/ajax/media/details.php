<?php

use QUI\Projects\Media\Utils;

/**
 * Return the data of the fileid
 *
 * @param String $project - Project name
 * @param String $fileid  - JSON String|Array
 *
 * @return Array
 */
function ajax_media_details($project, $fileid)
{
    $fileid  = json_decode( $fileid, true );
    $Project = \QUI\Projects\Manager::getProject( $project );
    $Media   = $Project->getMedia();

    if ( !is_array( $fileid ) )
    {
        $File = $Media->get( $fileid );
        $attr = $File->getAttributes();

        if ( !Utils::isImage( $File ) ) {
            return $attr;
        }


        if ( !$attr['image_width'] ) {
            $attr['image_width'] = $File->getWidth();
        }

        if ( !$attr['image_height'] ) {
            $attr['image_height'] = $File->getHeight();
        }

        return $attr;
    }


    $list = array();

    foreach ( $fileid as $id )
    {
        $File = $Media->get( $id );

        if ( !Utils::isImage( $File ) )
        {
            $list[] = $File->getAttributes();
            continue;
        }


        $attributes = $File->getAttributes();

        if ( !$attributes['image_width'] ) {
            $attributes['image_width'] = $File->getWidth();
        }

        if ( !$attributes['image_height'] ) {
            $attributes['image_height'] = $File->getHeight();
        }

        $list[] = $attributes;
    }

    return $list;
}

\QUI::$Ajax->register(
    'ajax_media_details',
    array('project', 'fileid'),
    'Permission::checkAdminUser'
);
