<?php

QUI::$Ajax->registerFunction(
    'ajax_session_destroy',
    static function (): void {
        QUI::getSession()->destroy();
    }
);
