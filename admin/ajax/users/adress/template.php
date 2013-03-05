<?php

/**
 * Adresse zu einem Benutzer hinzufügen
 *
 * @param String $uid
 * @param String $params
 */
function ajax_users_adress_template()
{
    $Engine    = QUI_Template::getEngine(true);
    $Countries = QUI::getCountries();

    $Engine->assign(array(
        'countrys' => $Countries->getList()
    ));

    return $Engine->fetch(SYS_DIR .'template/users/adress/new.html');
}
$ajax->register('ajax_users_adress_template', array('uid', 'params'));


?>