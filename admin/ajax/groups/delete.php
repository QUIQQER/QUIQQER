<?php

/**
 * Delete groups
 *
 * @param string $gids - Group-IDs, json array
 * @return array - Group-IDs which have been deleted
 */
QUI::$Ajax->registerFunction(
    'ajax_groups_delete',
    function ($gids) {
        $gids   = json_decode($gids, true);
        $Groups = QUI::getGroups();

        if (!is_array($gids)) {
            $gids = array($gids);
        }

        $result = array();

        foreach ($gids as $gid) {
            try {
                $Groups->get($gid)->delete();

                $result[] = $gid;

            } catch (QUI\Exception $Exception) {
            }
        }

        // #locale
        QUI::getMessagesHandler()->addInformation(
            'Die Gruppe(n) ' . implode(', ', $gids) . ' wurde(n) erfolgreich gelöscht'
        );

        return $result;
    },
    array('gids'),
    'Permission::checkSU'
);
