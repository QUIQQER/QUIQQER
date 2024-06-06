<?php

QUI::getAjax()->registerFunction('ajax_isAuth', static function () {
    $SessionUser = QUI::getUserBySession();

    return [
        'id'   => $SessionUser->getUUID(),
        'name' => $SessionUser->getName(),
        'lang' => $SessionUser->getLang()
    ];
});
