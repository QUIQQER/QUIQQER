<?php

/**
 * Delete a toolbar
 *
 * @param string $toolbar - name of the toolbar
 */

QUI::$Ajax->registerFunction(
    'ajax_editor_toolbar_delete',
    static function ($toolbar) {
        QUI\Editor\Manager::deleteToolbar($toolbar);
    },
    ['toolbar']
);
