<?php

use QUI\Utils\Security\Orthos;
use QUI\Controls\Navigating\Pagination;

/**
 * Get pagination control for navigating folder files in the project media panel
 *
 * @param array $attributes
 * @return string - HTML of pagination control
 */
QUI::$Ajax->registerFunction(
    'ajax_media_folder_getPagination',
    function ($attributes) {
        $attributes = Orthos::clearArray(\json_decode($attributes, true));
        $Pagination = new Pagination($attributes);

        $Output  = new QUI\Output();
        $control = $Pagination->create();
        $css = QUI\Control\Manager::getCSS();

        return $Output->parse($css.$control);
    },
    ['attributes'],
    'Permission::checkAdminUser'
);
