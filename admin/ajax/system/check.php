<?php

/**
 * Systemcheck
 *
 * @return String
 */
function ajax_system_check()
{
    $Engine = \QUI\Template::getEngine();
    $Engine->assign(array(
        'checks' => QUI_Systemcheck_Manager::standard()
    ));

    return $Engine->fetch( SYS_DIR .'ajax/system/check.html' );
}

\QUI::$Ajax->register('ajax_system_check', false, 'Permission::checkSU');
