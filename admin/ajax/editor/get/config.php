<?php

/**
 * Return the main editor config
 *
 * @return array
 */
function ajax_editor_get_config()
{
    return QUI\Editor\Manager::getConfig();
}

QUI::$Ajax->register('ajax_editor_get_config');
