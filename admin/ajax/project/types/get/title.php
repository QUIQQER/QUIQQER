<?php

/**
 * Return the site type title
 *
 * @param String $sitetype - name of the sitetype
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
