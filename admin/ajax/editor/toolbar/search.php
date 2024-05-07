<?php

QUI::$Ajax->registerFunction(
    'ajax_editor_toolbar_search',
    fn($search) => QUI\Editor\Manager::search($search),
    ['search']
);
