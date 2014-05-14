<?php

/**
 * Template for create an address
 *
 * @param String $uid
 * @param String $params
 *
 */
function ajax_users_address_template()
{
    $Engine    = \QUI\Template::getEngine( true );
    $Countries = \QUI::getCountries();

    $Engine->assign(array(
        'countrys' => $Countries->getList()
    ));

    return $Engine->fetch(SYS_DIR .'template/users/address/new.html');
}

\QUI::$Ajax->register(
    'ajax_users_address_template',
    array('uid', 'params')
);
