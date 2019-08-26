<?php

/**
 * Gruppe aktivieren
 *
 * @param Int $gid - Gruppen-ID
 * @return Bool
 */
QUI::$Ajax->registerFunction(
    'ajax_groups_activate',
    function ($gid) {
        $gid = \json_decode($gid, true);

        if (!\is_array($gid)) {
            $gid = [$gid];
        }

        $Groups = QUI::getGroups();
        $result = [];

        foreach ($gid as $_gid) {
            try {
                $Group = $Groups->get($_gid);
                $Group->activate();

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
    ['gid'],
    'Permission::checkSU'
);
