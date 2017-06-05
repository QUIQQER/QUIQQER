<?php

/**
 * Gruppen unter der Gruppe bekommen
 *
 * @param integer $gid
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_groups_children',
    function ($gid) {
        $Groups   = QUI::getGroups();
        $Group    = $Groups->get($gid);
        $children = $Group->getChildren();

        return $children;
    },
    array('gid'),
    'Permission::checkAdminUser'
);
