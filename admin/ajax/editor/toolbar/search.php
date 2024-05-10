<?php

QUI::$Ajax->registerFunction(
    'ajax_editor_toolbar_search',
    static fn($search) => QUI\Editor\Manager::search($search),
    ['search']
);
