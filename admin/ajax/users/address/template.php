<?php

/**
 * Return edit address template
 *
 * @return String
 */
function ajax_users_address_template()
{
    $Engine = QUI::getTemplateManager()->getEngine(true);
    $Countries = QUI::getCountries();

    $Engine->assign(array(
        'countrys' => $Countries->getList()
    ));

    return $Engine->fetch(SYS_DIR.'template/users/address/edit.html');
}

QUI::$Ajax->register(
    'ajax_users_address_template',
    false
);
