<?php

QUI::getAjax()->registerFunction('ajax_isAuth', function () {
    return QUI::getUserBySession()->getId();
});
