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
        $result    = [];
        $Standard  = null;

        try {
            $Standard = $User->getStandardAddress();
        } catch (QUI\Exception $Exception) {
            if (\count($addresses)) {
                $Standard = $addresses[0];
            }
        }

        foreach ($addresses as $Address) {
            /* @var $Address \QUI\Users\Address */
            $entry            = $Address->getAttributes();
            $entry['id']      = $Address->getId();
            $entry['text']    = $Address->getText();
            $entry['uid']     = $User->getId();
            $entry['default'] = 0;

            if ($Standard && $Standard->getId() === $Address->getId()) {
                $entry['default'] = 1;
            }

            $result[] = $entry;
        }

        return $result;
    },
    ['uid'],
    'Permission::checkAdminUser'
);
