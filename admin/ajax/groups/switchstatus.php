<?php

/**
 * Switch the groups status
 *
 * @param string $gid - JSON Integer | JSON Array
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_groups_switchstatus',
    static function ($gid): array {
        $gid = json_decode($gid, true);

        if (!is_array($gid)) {
            $gid = [$gid];
        }

        $Groups = QUI::getGroups();
        $result = [];

        foreach ($gid as $_gid) {
            try {
                $Group = $Groups->get($_gid);

                if ($Group->isActive()) {
                    $Group->deactivate();
                } else {
                    $Group->activate();
                }

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
