<?php

/**
 * Add a toolbar
 *
 * @param string $toolbar - name of the toolbar
 */
QUI::$Ajax->registerFunction(
    'ajax_editor_toolbar_add',
    function ($toolbar) {
        QUI\Editor\Manager::addToolbar($toolbar);
    },
    array('toolbar')
);
