<?php

/**
 * System Setup ausführen
 *
 * @return String
 */
function ajax_system_setup()
{
    \QUI\Setup::all();
}

\QUI::$Ajax->register(
    'ajax_system_setup',
    false,
    'Permission::checkSU'
);
