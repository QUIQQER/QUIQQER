<?php

/**
 * Return the main editor config
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_editor_get_config',
    static function (): array {
        return QUI\Editor\Manager::getConfig();
    }
);
