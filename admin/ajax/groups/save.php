<?php

/**
 * Save the group
 *
 * @param integer $gid - Group-ID
 * @param string $attributes - Attributes, json array
 * @param string $rights - Rights, json array
 */
function ajax_groups_save($gid, $attributes, $rights)
{
    $Groups = QUI::getGroups();
    $Group  = $Groups->get((int)$gid);

    $attributes = json_decode($attributes, true);
    $rights     = json_decode($rights, true);

    $Group->setRights($rights);
    $Group->setAttributes($attributes);
    $Group->save();

    if (isset($attributes['active'])) {
        if ($attributes['active'] == 1) {
            $Group->activate();
        } else {
            $Group->deactivate();
        }
    }

    // #locale
    QUI::getMessagesHandler()->addSuccess(
        'Die Gruppe ' . $Group->getAttribute('name') . ' wurde erfolgreich gespeichert'
    );
}

QUI::$Ajax->register(
    'ajax_groups_save',
    array('gid', 'attributes', 'rights'),
    'Permission::checkSU'
);
