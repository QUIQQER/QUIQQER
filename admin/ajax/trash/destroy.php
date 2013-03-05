<?php

/**
 * Seiten zerstören
 *
 * @param String $project
 * @param String $lang
 * @param JSON Array $ids
 */
function ajax_trash_destroy($project, $lang, $ids)
{
    $Project = QUI::getProject($project, $lang);
    $ids     = json_decode($ids, true);
    $Trash   = $Project->getTrash();

    $Trash->destroy($Project, $ids);
}
QUI::$Ajax->register('ajax_trash_destroy', array('project', 'lang', 'ids'), 'Permission::checkAdminUser');

?>