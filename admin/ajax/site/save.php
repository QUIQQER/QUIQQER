<?php

/**
 * Seite speichern
 *
 * @param String $project
 * @param String $lang
 * @param Integer $id
 * @param JSON Array $attributes
 *
 * @return Array
 */
function ajax_site_save($project, $lang, $id, $attributes)
{
    $Project = \QUI::getProject( $project, $lang );
    $Site    = new Projects_Site_Edit( $Project, (int)$id );

    $attributes = json_decode( $attributes, true );

    $Site->setAttributes( $attributes );
    $Site->save();
    $Site->refresh();

    require_once 'get.php';

    return ajax_site_get( $project, $lang, $id );
}

\QUI::$Ajax->register(
    'ajax_site_save',
    array( 'project', 'lang', 'id', 'attributes' ),
    'Permission::checkAdminUser'
);

?>