<?php

/**
 * Return a wanted toolbar
 *
 * @param string / Integer $uid
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_editor_get_toolbar',
    static function ($toolbar): array {
        if (isset($toolbar) && !empty($toolbar)) {
            return QUI\Editor\Manager::getToolbarData($toolbar);
        }

        return QUI\Editor\Manager::getToolbarButtonsFromUser();
    },
    ['toolbar']
);
