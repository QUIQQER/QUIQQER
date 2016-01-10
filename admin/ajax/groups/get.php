<?php

/**
 * Gruppendaten
 *
 * @param string / Integer $uid
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_groups_get',
    function ($gid) {
        $Groups = QUI::getGroups();
        $Group  = $Groups->get((int)$gid);

        $attr                = $Group->getAttributes();
        $attr['hasChildren'] = $Group->hasChildren();
        $attr['rights']      = $Group->getRights();

        return $attr;
    },
    array('gid'),
    'Permission::checkSU'
);
