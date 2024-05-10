<?php

/**
 * Gruppen unter der Gruppe bekommen
 *
 * @param integer $gid
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_groups_children',
    static function ($gid) {
        $Groups = QUI::getGroups();
        $Group = $Groups->get($gid);

        return $Group->getChildren();
    },
    ['gid'],
    'Permission::checkAdminUser'
);
