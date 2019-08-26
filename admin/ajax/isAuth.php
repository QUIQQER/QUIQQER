<?php

QUI::getAjax()->registerFunction('ajax_isAuth', function () {
    $SessionUser = QUI::getUserBySession();

    return [
        'id'   => $SessionUser->getId(),
        'name' => $SessionUser->getName(),
        'lang' => $SessionUser->getLang()
    ];
});
