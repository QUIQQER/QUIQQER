<?php

/**
 * Benutzer speichern
 *
 * @param integer $uid - Benutzer-ID
 * @param string $attributes - JSON String of Attributes
 *
 * @return boolean
 */

QUI::$Ajax->registerFunction(
    'ajax_users_save',
    function ($uid, $attributes) {
        $User = QUI::getUsers()->get($uid);
        $attributes = json_decode($attributes, true);
        $language = $User->getAttribute('lang');

        $noAutoSave = array_filter($User->getListOfExtraAttributes(), function ($attribute) {
            if (isset($attribute['no-auto-save']) && $attribute['no-auto-save']) {
                return true;
            }

            return false;
        });

        $noAutoSave = array_map(function ($attribute) {
            return $attribute['name'];
        }, $noAutoSave);

        foreach ($attributes as $key => $value) {
            if (!in_array($key, $noAutoSave)) {
                $User->setAttribute($key, $value);
            }
        }

        // aktivieren / deaktivieren
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

            if ($User->getId() === QUI::getUserBySession()->getId()) {
                QUI::getSession()->set('quiqqer-user-language', false);
            }
        }

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get('quiqqer/quiqqer', 'message.user.saved', [
                'username' => $User->getName(),
                'id' => $User->getId()
            ])
        );

        return $User->getAttributes();
    },
    ['uid', 'attributes'],
    'Permission::checkAdminUser'
);
