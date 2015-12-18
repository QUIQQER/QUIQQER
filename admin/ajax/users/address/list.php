<?php

/**
 * Return all addresses from an user
 *
 * @param integer|string $uid - id of the user
 *
 * @return array
 */
function ajax_users_address_list($uid)
{
    $User      = QUI::getUsers()->get((int)$uid);
    $addresses = $User->getAddressList();
    $result    = array();

    foreach ($addresses as $Address) {
        $entry        = $Address->getAllAttributes();
        $entry['id']  = $Address->getId();
        $entry['uid'] = $User->getId();

        $result[] = $entry;
    }

    return $result;
}

QUI::$Ajax->register(
    'ajax_users_address_list',
    array('uid'),
    'Permission::checkSU'
);
