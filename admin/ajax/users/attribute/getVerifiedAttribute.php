<?php

QUI::$Ajax->registerFunction(
    'ajax_users_attribute_getVerifiedAttribute',
    static function ($userUuid, $type, $value) {
        $User = QUI::getUsers()->get($userUuid);

        if (method_exists($User, 'getVerifiedAttributes')) {
            $verifiedAttributes = $User->getVerifiedAttributes();

            foreach ($verifiedAttributes as $verifiedAttribute) {
                if ($verifiedAttribute->getValue() === $value) {
                    return $verifiedAttribute->toArray();
                }
            }
        }

        return null;
    },
    ['userUuid', 'type', 'value'],
    'Permission::checkAdminUser'
);
