<?php

QUI::$Ajax->registerFunction('ajax_isAuth', function () {
    return QUI::getUserBySession()->getId();
});
