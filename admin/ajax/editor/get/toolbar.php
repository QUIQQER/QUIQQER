<?php

/**
 * Toolbars bekommen welche zur Verfügung stehen
 *
 * @param String / Integer $uid
 * @return Array
 */
function ajax_editor_get_toolbar($toolbar)
{
    if ( isset( $toolbar ) && !empty( $toolbar ) )
    {
        return \QUI\Editor\Manager::parseXmlFileToArray(
            \QUI\Editor\Manager::getToolbarsPath() . $toolbar
        );
    }

    return \QUI\Editor\Manager::getToolbarButtonsFromUser();
}

\QUI::$Ajax->register( 'ajax_editor_get_toolbar', array('toolbar') );