<?php

/**
 * PHP Ajax Schnittstelle
 */

\define('QUIQQER_AJAX', true);

if (isset($_REQUEST['_FRONTEND']) && !(int)$_REQUEST['_FRONTEND']) {
    \define('QUIQQER_BACKEND', true);
    \define('QUIQQER_FRONTEND', false);
} else {
    \define('QUIQQER_BACKEND', false);
    \define('QUIQQER_FRONTEND', true);
}

require_once 'header.php';

$User = QUI::getUserBySession();

// Falls Benutzer eingeloggt ist, dann seine Sprache nehmen
if ($User->getId() && $User->getLang()) {
    QUI::getLocale()->setCurrent($User->getLang());
}

// language
if (isset($_REQUEST['lang']) && \strlen($_REQUEST['lang']) === 2) {
    QUI::getLocale()->setCurrent($_REQUEST['lang']);
} elseif (isset($_REQUEST['quiqqerBundle'])
          && isset($_REQUEST['quiqqerBundle'][0])
          && isset($_REQUEST['quiqqerBundle'][0]['params'])
          && isset($_REQUEST['quiqqerBundle'][0]['params']['lang'])
) {
    $language = \json_decode($_REQUEST['quiqqerBundle'][0]['params']['lang']);

    QUI::getLocale()->setCurrent($_REQUEST['quiqqerBundle'][0]['params']['lang']);
}

// request
$Bundler = new QUI\Request\Bundler();
echo $Bundler->response();
