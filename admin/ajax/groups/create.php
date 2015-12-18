<?php

/**
 * Create a new group
 *
 * @param string $groupname - Gruppennamen
 * @param integer $pid - Gruppen-ID des Parents
 * @return integer - the new group id
 */
function ajax_groups_create($groupname, $pid)
{
    $Groups = QUI::getGroups();
    $Parent = $Groups->get((int)$pid);
    $Group  = $Parent->createChild($groupname);

    return $Group->getId();
}

QUI::$Ajax->register(
    'ajax_groups_create',
    array('groupname', 'pid'),
    'Permission::checkUser'
);
