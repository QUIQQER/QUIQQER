<?php

QUI::$Ajax->registerFunction(
    'ajax_users_attribute_setVerifiableAttribute',
    static function ($userUuid, $value, $type, $status) {
        $User = QUI::getUsers()->get($userUuid);
        $attribute = null;

        if (method_exists($User, 'setStatusToVerifiableAttribute')) {
            $User->setStatusToVerifiableAttribute($value, $type, $status);
            $User->save();

            if (method_exists($User, 'getVerifiedAttribute')) {
                $attribute = $User->getVerifiedAttribute($value);
            }
        }

        return $attribute?->toArray();
    },
    ['userUuid', 'type', 'value', 'status'],
    'Permission::checkAdminUser'
);
