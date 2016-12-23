<?php

/**
 * Gruppe deaktivieren
 *
 * @param integer $gid - Gruppen-ID
 * @return bool
 */
QUI::$Ajax->registerFunction(
    'ajax_groups_deactivate',
    function ($gid) {
        $gid = json_decode($gid, true);

        if (!is_array($gid)) {
            $gid = array($gid);
        }

        $Groups = QUI::getGroups();
        $result = array();

        foreach ($gid as $_gid) {
            try {
                $Group = $Groups->get($_gid);
                $Group->deactivate();

                $result[$_gid] = $Group->isActive() ? 1 : 0;
            } catch (QUI\Exception $Exception) {
                QUI::getMessagesHandler()->addError(
                    $Exception->getMessage()
                );

                continue;
            }
        }

        return $result;
    },
    array('gid'),
    'Permission::checkSU'
);
