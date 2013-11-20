<?php

/**
 * Konfiguration bekommen welche zur Verfügung stehen
 *
 * @return Array
 */
function ajax_editor_get_config()
{
    return \QUI\Editor\Manager::getConfig();
}

\QUI::$Ajax->register(
    'ajax_editor_get_config',
    false,
    'Permission::checkSU'
);
