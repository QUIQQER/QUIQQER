<?php

/**
 * Seitetyp Title bekommen
 *
 * @param String $id
 * @param String $lang
 * @param String $project
 *
 * @return String
 */
function ajax_project_types_get_title($sitetype)
{
    return \QUI::getPluginManager()->getTypeName( $sitetype );
}

\QUI::$Ajax->register(
    'ajax_project_types_get_title',
    array('sitetype'),
    'Permission::checkAdminUser'
);
