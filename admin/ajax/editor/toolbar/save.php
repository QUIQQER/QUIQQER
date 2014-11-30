<?php

/**
 * Save toolbar
 *
 * @param String $toolbar
 * @param String $xml
 */
function ajax_editor_toolbar_save($toolbar, $xml)
{
    \QUI\Editor\Manager::saveToolbar( $toolbar, $xml );

    \QUI::getMessagesHandler()->addSuccess(
        \QUI::getLocale()->get(
            'quiqqer/system',
            'message.editor.toolbar.save.success'
        )
    );
}

\QUI::$Ajax->register(
    'ajax_editor_toolbar_save',
    array('toolbar', 'xml')
);
