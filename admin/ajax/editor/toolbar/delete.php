<?php

/**
 * Toolbar löschen
 *
 * @param String $toolbar
 */
function ajax_editor_toolbar_delete($toolbar)
{
    \QUI\Editor\Manager::deleteToolbar( $toolbar );
}

\QUI::$Ajax->register(
    'ajax_editor_toolbar_delete',
    array('toolbar')
);
