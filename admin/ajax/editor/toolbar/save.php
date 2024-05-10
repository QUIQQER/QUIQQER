<?php

/**
 * Saves a toolbar
 *
 * @param string $toolbar - name of the toolbar
 * @param string $xml - xml content
 */

QUI::$Ajax->registerFunction(
    'ajax_editor_toolbar_save',
    static function ($toolbar, $xml) {
        QUI\Editor\Manager::saveToolbar($toolbar, $xml);

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/core',
                'message.editor.toolbar.save.success'
            )
        );
    },
    ['toolbar', 'xml']
);
