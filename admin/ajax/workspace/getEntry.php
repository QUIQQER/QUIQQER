<?php

/**
 * Search for the desktop
 *
 * @param string $search
 * @param string $params
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_workspace_getEntry',
    function ($id) {
        return QUI\Workspace\Search\Search::getInstance()->getEntry($id);
    },
    array('id')
);
