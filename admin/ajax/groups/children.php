<?php

/**
 * Gruppen unter der Gruppe bekommen
 *
 * @param integer $gid
 * @return array
 */
function ajax_groups_children($gid)
{
    $Groups   = QUI::getGroups();
    $Group    = $Groups->get($gid);
    $children = $Group->getChildren();

    return $children;
}

QUI::$Ajax->register(
    'ajax_groups_children',
    array('gid'),
    'Permission::checkSU'
);
