<?php

/**
 * Return the user data
 *
 * @param string / Integer $uid
 *
 * @return array
 */

function ajax_users_get($uid)
{
    try {
        return QUI::getUsers()->get((int)$uid)->getAttributes();

    } catch (QUI\Exception $Exception) {
        return QUI::getUsers()->getNobody()->getAttributes();
    }
}

QUI::$Ajax->register(
    'ajax_users_get',
    array('uid'),
    'Permission::checkUser'
);
