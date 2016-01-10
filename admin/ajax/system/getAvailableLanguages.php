<?php

/**
 * Get the available versions of quiqqer
 *
 * @return String
 */
QUI::$Ajax->registerFunction(
    'ajax_system_getAvailableLanguages',
    function () {
        $langs    = array();
        $projects = QUI::getProjectManager()->getProjects(true);

        /* @var $Project QUI\Projects\Project */
        foreach ($projects as $Project) {
            $langs = array_merge($langs, $Project->getAttribute('langs'));
        }

        $langs = array_unique($langs);
        $langs = array_values($langs);

        return $langs;
    },
    false,
    'Permission::checkUser'
);
