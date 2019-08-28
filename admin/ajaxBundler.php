<?php

/**
 * PHP Ajax Schnittstelle
 */

require_once 'header.php';

$User = QUI::getUserBySession();

// Falls Benutzer eingeloggt ist, dann seine Sprache nehmen
if ($User->getId() && $User->getLang()) {
    QUI::getLocale()->setCurrent($User->getLang());
}

// language
if (isset($_REQUEST['lang']) && \strlen($_REQUEST['lang']) === 2) {
    QUI::getLocale()->setCurrent($_REQUEST['lang']);
}

// request
$Bundler = new QUI\Request\Bundler();
echo $Bundler->response();
