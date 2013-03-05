<?php

/**
 * Login Template
 *
 * @return String
 */
function ajax_login_template($lang)
{
    $Engine = QUI_Template::getEngine(true);

    return $Engine->fetch(
        CMS_DIR .'admin/template/login/login.html'
    );
}
QUI::$Ajax->register('ajax_login_template', array('lang'));

?>