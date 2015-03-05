<?php

/**
 * Get the available versions of quiqqer
 *
 * @return String
 */
function ajax_system_getAvailableLanguages()
{
    $langs    = array();
    $projects = QUI::getProjectManager()->getProjects( true );

    foreach ( $projects as $Project ) {
        $langs = array_merge( $langs, $Project->getAttribute('langs') );
    }

    $langs = array_unique( $langs );
    $langs = array_values( $langs );

    return $langs;
}

\QUI::$Ajax->register(
    'ajax_system_getAvailableLanguages',
    false,
    'Permission::checkUser'
);
