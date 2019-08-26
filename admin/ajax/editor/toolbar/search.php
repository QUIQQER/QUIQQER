<?php


QUI::$Ajax->registerFunction(
    'ajax_editor_toolbar_search',
    function ($search) {
        return QUI\Editor\Manager::search($search);
    },
    array('search')
);
