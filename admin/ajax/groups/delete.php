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
        $names  = array();

        foreach ($gids as $gid) {
            try {
                $groupName = $Groups->get($gid)->getName();
                $groupId   = $Groups->get($gid)->getId();

                $Groups->get($gid)->delete();

                $result[] = $groupId;
                $names[]  = $groupName;
            } catch (QUI\Exception $Exception) {
            }
        }

        if (!empty($result)) {
            if (count($result) === 1) {
                QUI::getMessagesHandler()->addSuccess(
                    QUI::getLocale()->get('quiqqer/quiqqer', 'message.group.deleted', array(
                        'groupname' => $names[0],
                        'id'        => $result[0]
                    ))
                );
            } else {
                QUI::getMessagesHandler()->addSuccess(
                    QUI::getLocale()->get('quiqqer/quiqqer', 'message.groups.deleted', array(
                        'groups' => implode(', ', $result)
                    ))
                );
            }
        }

        return $result;
    },
    array('gids'),
    'Permission::checkSU'
);
