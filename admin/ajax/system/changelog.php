<?php

/**
 * Get the changelog from http://update.quiqqer.com/CHANGELOG
 *
 * @return String
 */

QUI::$Ajax->registerFunction(
    'ajax_system_changelog',
    static function (): string {
        $Package = QUI::getPackage('quiqqer/core');
        $changelog = $Package->getDir() . 'CHANGELOG';

        return htmlspecialchars(file_get_contents($changelog));
    },
    false,
    'Permission::checkUser'
);
