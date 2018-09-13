<?php

/**
 * Login Template
 *
 * @return String
 */
QUI::$Ajax->registerFunction('ajax_login_template', function () {
    $Engine = QUI::getTemplateManager()->getEngine(true);

    return $Engine->fetch(
        CMS_DIR.'admin/template/login/login.html'
    );
});
