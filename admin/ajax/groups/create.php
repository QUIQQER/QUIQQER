<?php

/**
 * Create a new group
 *
 * @param string $groupname - Gruppennamen
 * @param integer $pid - Gruppen-ID des Parents
 * @return integer - the new group id
 */
QUI::$Ajax->registerFunction(
    'ajax_groups_create',
    function ($groupname, $pid) {
        $Groups = QUI::getGroups();
        $Parent = $Groups->get((int)$pid);
        $Group  = $Parent->createChild($groupname);

        return $Group->getId();
    },
    array('groupname', 'pid'),
    'Permission::checkUser'
);
