<?php

use QUI\Workspace\Search\Builder;

/**
 * Get all available provider search groups
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_workspace_search_getFilterGroups',
    function () {
        return Builder::getInstance()->getFilterGroups();
    },
    array()
);
