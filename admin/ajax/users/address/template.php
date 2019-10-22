<?php

/**
 * Return edit address template
 *
 * @return String
 */
QUI::$Ajax->registerFunction('ajax_users_address_template', function () {
    $Engine    = QUI::getTemplateManager()->getEngine(true);
    $Countries = QUI::getCountries();

    $Engine->assign([
        'countrys' => $Countries->getList()
    ]);

    return $Engine->fetch(SYS_DIR.'template/users/address/edit.html');
});
