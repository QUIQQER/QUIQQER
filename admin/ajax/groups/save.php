<?php

/**
 * Save the group
 *
 * @param integer $gid - Group-ID
 * @param string $attributes - Attributes, json array
 * @param string $rights - Rights, json array
 */

QUI::$Ajax->registerFunction(
    'ajax_groups_save',
    static function ($gid, $attributes, $rights): void {
        $Groups = QUI::getGroups();
        $Group = $Groups->get($gid);

        $attributes = json_decode($attributes, true);
        $rights = json_decode($rights, true);

        $Group->setRights($rights);
        $Group->setAttributes($attributes);
        $Group->save();

        if (isset($attributes['parent'])) {
            $Group->setParent($attributes['parent']);
        }

        if (isset($attributes['active'])) {
            if ($attributes['active'] == 1) {
                $Group->activate();
            } else {
                $Group->deactivate();
            }
        }

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get('quiqqer/core', 'message.group.saved', [
                'groupname' => $Group->getName(),
                'id' => $Group->getUUID()
            ])
        );
    },
    ['gid', 'attributes', 'rights'],
    'Permission::checkSU'
);
