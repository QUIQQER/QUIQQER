<?php

/**
 * Return all addresses from an user
 *
 * @param integer|string $uid - id of the user
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_users_address_list',
    function ($uid) {
        $User      = QUI::getUsers()->get((int)$uid);
        $addresses = $User->getAddressList();
        $result    = array();

        foreach ($addresses as $Address) {
            /* @var $Address \QUI\Users\Address */
            $entry         = $Address->getAttributes();
            $entry['id']   = $Address->getId();
            $entry['text'] = $Address->getText();
            $entry['uid']  = $User->getId();
            
            $result[] = $entry;
        }

        return $result;
    },
    array('uid'),
    'Permission::checkSU'
);
