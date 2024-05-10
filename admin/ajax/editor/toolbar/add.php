<?php

/**
 * Add a toolbar
 *
 * @param string $toolbar - name of the toolbar
 */

QUI::$Ajax->registerFunction(
    'ajax_editor_toolbar_add',
    static function ($toolbar) {
        QUI\Editor\Manager::addToolbar($toolbar);
    },
    ['toolbar']
);
