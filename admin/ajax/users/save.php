<?php

/**
 * Benutzer speichern
 *
 * @param integer $uid - Benutzer-ID
 * @param string $attributes - JSON String of Attributes
 *
 * @return boolean
 */
function ajax_users_save($uid, $attributes)
{
    $User       = QUI::getUsers()->get($uid);
    $attributes = json_decode($attributes, true);

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

    QUI::getMessagesHandler()->addInformation(
        'Der Benutzer ' . $User->getName() . ' (' . $User->getId()
        . ') wurde erfolgreich gespeichert'
    ); // #locale

    return true;
}

QUI::$Ajax->register(
    'ajax_users_save',
    array('uid', 'attributes'),
    'Permission::checkSU'
);
