<?php

/**
 * Login Template
 *
 * @return String
 */

QUI::getAjax()->registerFunction('ajax_login_template', static function (): string {
    $Engine = QUI::getTemplateManager()->getEngine(true);

    return $Engine->fetch(
        CMS_DIR . 'admin/template/login/login.html'
    );
});
