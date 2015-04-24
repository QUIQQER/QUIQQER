<?php

/**
 * Add a toolbar
 *
 * @param String $toolbar - name of the toolbar
 */
function ajax_editor_toolbar_add($toolbar)
{
    QUI\Editor\Manager::addToolbar($toolbar);
}

QUI::$Ajax->register( 'ajax_editor_toolbar_add', array('toolbar') );
