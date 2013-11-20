<?php

/**
 * Seiten wiederherstellen
 *
 * @param String $project
 * @param String $lang
 * @param JSON Array $ids
 */
function ajax_trash_restore($project, $lang, $ids, $parentid)
{
    $Project = \QUI::getProject($project, $lang);
    $ids     = json_decode($ids, true);
    $Trash   = $Project->getTrash();

    $Trash->restore($Project, $ids, $parentid);
}
QUI::$Ajax->register('ajax_trash_restore', array('project', 'lang', 'ids', 'parentid'), 'Permission::checkAdminUser');

?>