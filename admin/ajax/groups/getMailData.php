<?php

QUI::$Ajax->registerFunction(
    'ajax_groups_getMailData',
    static function ($groupId) {
        $Group = QUI::getGroups()->get($groupId);
        $users = $Group->getUsers([
            'select' => 'email'
        ]);

        $emails = [];

        foreach ($users as $user) {
            if (!isset($user['email'])) {
                continue;
            }

            $userEmails = explode(',', $user['email']);

            foreach ($userEmails as $email) {
                $email = trim($email);

                if ($email === '') {
                    continue;
                }

                $emails[strtolower($email)] = $email;
            }
        }

        return [
            'name' => $Group->getName(),
            'uniqueEmailCount' => count($emails)
        ];
    },
    ['groupId'],
    'Permission::checkAdminUser'
);
