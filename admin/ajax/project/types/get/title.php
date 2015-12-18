<?php

/**
 * Return the site type title
 *
 * @param string $sitetype - name of the sitetype
 * @return string
 */
function ajax_project_types_get_title($sitetype)
{
    return QUI::getPluginManager()->getTypeName($sitetype);
}

QUI::$Ajax->register(
    'ajax_project_types_get_title',
    array('sitetype'),
    'Permission::checkAdminUser'
);
