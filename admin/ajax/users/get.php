<?php

/**
 * Return the user data
 *
 * @param string / Integer $uid
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_users_get',
    function ($uid) {
        try {
            return QUI::getUsers()->get((int)$uid)->getAttributes();
        } catch (QUI\Exception $Exception) {
            return QUI::getUsers()->getNobody()->getAttributes();
        }
    },
    array('uid'),
    'Permission::checkUser'
);
