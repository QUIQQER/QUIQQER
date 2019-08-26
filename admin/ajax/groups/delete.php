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
        $gids   = \json_decode($gids, true);
        $Groups = QUI::getGroups();

        if (!\is_array($gids)) {
            $gids = [$gids];
        }

        $result = [];
        $names  = [];

        $ExceptionStack = new QUI\ExceptionStack();

        foreach ($gids as $gid) {
            try {
                $groupName = $Groups->get($gid)->getName();
                $groupId   = $Groups->get($gid)->getId();

                $Groups->get($gid)->delete();

                $result[] = $groupId;
                $names[]  = $groupName;
            } catch (QUI\Exception $Exception) {
                $ExceptionStack->addException($Exception);
            }
        }

        if (!empty($result)) {
            if (\count($result) === 1) {
                QUI::getMessagesHandler()->addSuccess(
                    QUI::getLocale()->get('quiqqer/quiqqer', 'message.group.deleted', [
                        'groupname' => $names[0],
                        'id'        => $result[0]
                    ])
                );
            } else {
                QUI::getMessagesHandler()->addSuccess(
                    QUI::getLocale()->get('quiqqer/quiqqer', 'message.groups.deleted', [
                        'groups' => \implode(', ', $result)
                    ])
                );
            }
        }

        if (!$ExceptionStack->isEmpty()) {
            $message = \array_map(function ($Exception) {
                /* @var $Exception QUI\Exception */
                return $Exception->getMessage();
            }, $ExceptionStack->getExceptionList());

            QUI::getMessagesHandler()->addAttention(
                \implode("<br>", $message)
            );
        }

        return $result;
    },
    ['gids'],
    'Permission::checkSU'
);
