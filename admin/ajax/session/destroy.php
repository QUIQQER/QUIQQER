<?php

QUI::getAjax()->registerFunction(
    'ajax_session_destroy',
    static function (): void {
        QUI::getSession()->destroy();
    }
);
