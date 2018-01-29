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
        $User       = QUI::getUsers()->get($uid);
        $attributes = json_decode($attributes, true);
        $language   = $User->getAttribute('lang');

        foreach ($attributes as $key => $value) {
            $User->setAttribute($key, $value);
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
        }

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get('quiqqer/quiqqer', 'message.user.saved', array(
                'username' => $User->getName(),
                'id'       => $User->getId()
            ))
        );

        return true;
    },
    array('uid', 'attributes'),
    'Permission::checkAdminUser'
);
