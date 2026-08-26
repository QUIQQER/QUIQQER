<?php

QUI::getAjax()->registerFunction(
    'ajax_editor_toolbar_search',
    static function ($search): array {
        return QUI\Editor\Manager::search($search);
    },
    ['search']
);
