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
    function ($toolbar) {
        if (isset($toolbar) && !empty($toolbar)) {
            return QUI\Editor\Manager::parseXmlFileToArray(
                QUI\Editor\Manager::getToolbarsPath().$toolbar
            );
        }

        return QUI\Editor\Manager::getToolbarButtonsFromUser();
    },
    ['toolbar']
);
