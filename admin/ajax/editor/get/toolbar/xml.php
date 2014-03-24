<?php

/**
 * Toolbars bekommen welche zur Verfügung stehen
 *
 * @param String / Integer $uid
 * @return Array
 */
function ajax_editor_get_toolbar_xml($toolbar)
{
    $file = \QUI\Editor\Manager::getToolbarsPath() . $toolbar;
    $file = \QUI\Utils\Security\Orthos::clearPath( $file );

    if ( file_exists( $file ) ) {
        return file_get_contents( $file );
    }

    return '';
}

\QUI::$Ajax->register( 'ajax_editor_get_toolbar_xml', array('toolbar') );