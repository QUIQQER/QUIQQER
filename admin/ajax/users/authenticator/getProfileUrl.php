<?php

QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_getProfileUrl',
    static function ($project): string {
        $Project = QUI\Projects\Manager::decode($project);
        $sites = $Project->getSites([
            'where' => [
                'type' => 'quiqqer/frontend-users:types/profile'
            ],
            'limit' => 1
        ]);

        if (empty($sites)) {
            return '/';
        }

        try {
            return $sites[0]->getUrlRewritten();
        } catch (\Exception) {
            return '/';
        }
    },
    ['project']
);
