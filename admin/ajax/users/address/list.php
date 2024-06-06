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
    static function ($uid): array {
        $User = QUI::getUsers()->get($uid);
        $addresses = $User->getAddressList();
        $result = [];
        $Standard = null;

        try {
            $Standard = $User->getStandardAddress();
        } catch (QUI\Exception) {
            if (\count($addresses)) {
                $Standard = \current($addresses);
            }
        }

        foreach ($addresses as $Address) {
            /* @var $Address \QUI\Users\Address */
            $entry = $Address->getAttributes();
            $entry['id'] = $Address->getUUID();
            $entry['uuid'] = $Address->getUUID();
            $entry['text'] = $Address->getText();
            $entry['uid'] = $User->getUUID();
            $entry['default'] = 0;

            if ($Standard && $Standard->getUUID() === $Address->getUUID()) {
                $entry['default'] = 1;
            }

            $result[] = $entry;
        }

        return $result;
    },
    ['uid'],
    'Permission::checkAdminUser'
);
