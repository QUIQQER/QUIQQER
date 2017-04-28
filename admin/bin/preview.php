<?php

/**
 * Einfache Vorschau
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('QUIQQER_SYSTEM', true);
require_once '../header.php';

if (!QUI::getUserBySession()->canUseBackend()) {
    header("HTTP/1.1 404 Not Found");
    exit;
}

if (!isset($_POST['project']) ||
    !isset($_POST['lang']) &&
    !isset($_POST['id'])
) {
    header("HTTP/1.1 404 Not Found");
    echo "Site not found";
    exit;
}

$Response = QUI::getGlobalResponse();
$Project  = QUI::getProject($_POST['project'], $_POST['lang']);
$Site     = new QUI\Projects\Site\Edit($Project, $_POST['id']);

if (isset($_POST['siteData']['type'])) {
    $Site->setAttribute('type', $_POST['siteData']['type']);
}

$Site->load();

if (!isset($_POST['siteData'])) {
    $_POST['siteData'] = array();
}

if (!isset($_POST['siteDataJSON'])) {
    $_POST['siteDataJSON'] = array();
}

// site data
foreach ($_POST['siteData'] as $key => $value) {
    $Site->setAttribute($key, $value);
}

foreach ($_POST['siteDataJSON'] as $key => $value) {
    $Site->setAttribute($key, json_decode($value, true));
}

$Template = QUI::getTemplateManager();
$content  = $Template->fetchTemplate($Site);

$Output  = new QUI\Output();
$content = $Output->parse($content);

$Response->headers->set("X-XSS-Protection", "0"); // <<<--- BAD
$Response->setContent($content);
$Response->send();
exit;
