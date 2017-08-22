<?php

/**
 * Get the changelog from http://update.quiqqer.com/CHANGELOG
 *
 * @return String
 */
QUI::$Ajax->registerFunction(
    'ajax_system_changelog',
    function () {
        $Package   = QUI::getPackage('quiqqer/quiqqer');
        $changelog = $Package->getDir().'CHANGELOG';

        return htmlspecialchars(file_get_contents($changelog));
    },
    false,
    'Permission::checkUser'
);
