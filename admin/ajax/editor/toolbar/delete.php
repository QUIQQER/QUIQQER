<?php

/**
 * Delete a toolbar
 *
 * @param String $toolbar - name of the toolbar
 */
function ajax_editor_toolbar_delete($toolbar)
{
    QUI\Editor\Manager::deleteToolbar($toolbar);
}

QUI::$Ajax->register('ajax_editor_toolbar_delete', array('toolbar'));
