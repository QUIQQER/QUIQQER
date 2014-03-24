<?php

/**
 * Toolbar löschen
 *
 * @param String $toolbar
 */
function ajax_editor_toolbar_add($toolbar)
{
    return \QUI\Editor\Manager::addToolbar( $toolbar );
}

\QUI::$Ajax->register(
    'ajax_editor_toolbar_add',
    array('toolbar')
);
