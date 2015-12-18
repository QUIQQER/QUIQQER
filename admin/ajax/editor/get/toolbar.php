<?php

/**
 * Return a wanted toolbar
 *
 * @param String / Integer $uid
 *
 * @return array
 */
function ajax_editor_get_toolbar($toolbar)
{
    if (isset($toolbar) && !empty($toolbar)) {
        return QUI\Editor\Manager::parseXmlFileToArray(
            QUI\Editor\Manager::getToolbarsPath().$toolbar
        );
    }

    return QUI\Editor\Manager::getToolbarButtonsFromUser();
}

QUI::$Ajax->register('ajax_editor_get_toolbar', array('toolbar'));
