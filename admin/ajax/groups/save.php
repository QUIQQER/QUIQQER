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
    function ($gid, $attributes, $rights) {
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

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get('quiqqer/quiqqer', 'message.group.saved', array(
                'groupname' => $Group->getName(),
                'id'        => $Group->getId()
            ))
        );
    },
    array('gid', 'attributes', 'rights'),
    'Permission::checkSU'
);
