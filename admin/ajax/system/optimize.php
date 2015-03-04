<?php

/**
 * System Tabellen optimieren
 */
function ajax_system_optimize()
{
    $Table = \QUI::getDataBase()->Table();
    $Table->optimize( $Table->getTables() );
}

\QUI::$Ajax->register( 'ajax_system_optimize', false, 'Permission::checkSU' );
