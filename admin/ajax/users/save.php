<?php

/**
 * save user
 *
 * @param integer|string $uid - user id or user hash
 * @param string $attributes - JSON String of Attributes
 *
 * @return boolean
 */

QUI::$Ajax->registerFunction(
    'ajax_users_save',
    static function ($uid, $attributes): array {
        $User = QUI::getUsers()->get($uid);
        $attributes = json_decode($attributes, true);
        $language = $User->getAttribute('lang');
        $extraAttributes = [];

        if (method_exists($User, 'getListOfExtraAttributes')) {
            $extraAttributes = $User->getListOfExtraAttributes();
        }
        
        $noAutoSave = array_filter($extraAttributes, static function (array $attribute): bool {
            if (!isset($attribute['no-auto-save'])) {
                return false;
            }
            if (!$attribute['no-auto-save']) {
                return false;
            }
            return true;
        });

        $noAutoSave = array_map(static fn($attribute) => $attribute['name'], $noAutoSave);

        foreach ($attributes as $key => $value) {
            if (!in_array($key, $noAutoSave)) {
                $User->setAttribute($key, $value);
            }
        }

        // activation / deactivation
        if (isset($attributes['active'])) {
            if ((int)$attributes['active'] === 1) {
                if (!$User->isActive()) {
                    $User->activate();
                }
            } else {
                $User->deactivate();
            }
        }

        $User->save();

        // if language changed
        if ($User->getAttribute('lang') !== $language) {
            QUI\Cache\Manager::clear();

            if ($User->getUUID() === QUI::getUserBySession()->getUUID()) {
                QUI::getSession()->set('quiqqer-user-language', false);
            }
        }

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get('quiqqer/core', 'message.user.saved', [
                'username' => $User->getName(),
                'id' => $User->getUUID()
            ])
        );

        return $User->getAttributes();
    },
    ['uid', 'attributes'],
    'Permission::checkAdminUser'
);
