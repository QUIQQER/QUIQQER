<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * @author www.pcsg.com (Henning Leutz)
 */

// Mailto
if (isset($_REQUEST['_url'])
    && strpos($_REQUEST['_url'], '[mailto]') !== false
) {
    $addr = str_replace('[mailto]', '', $_REQUEST['_url']);
    list($user, $host) = explode("[at]", $addr);

    if (isset($user) && isset($host)) {
        header("Location: mailto:".$user."@".$host);
        exit;
    }
}

require_once 'bootstrap.php';

use \Symfony\Component\HttpFoundation\Response;

use QUI\Utils\System\Debug;
use QUI\Utils\Security\Orthos;
use QUI\System\Log;

$Response = QUI::getGlobalResponse();
$Engine = QUI::getTemplateManager()->getEngine();

// UTF 8 Prüfung für umlaute in url
if (isset($_REQUEST['_url'])) {
    $_REQUEST['_url'] = QUI\Utils\String::toUTF8($_REQUEST['_url']);
}

//\QUI\Utils\System\Debug::$run = true;
Debug::marker('index start');

// check if one projects exists
if (!QUI::getProjectManager()->count()) {

    $Response->setStatusCode(Response::HTTP_NOT_FOUND);

    $Response->setContent(
        '<div style="text-align: center; margin-top: 100px;">
            <img src="'.URL_BIN_DIR.'quiqqer_logo.png" style="max-width: 100%;" />
        </div>'
    );

    $Response->send();
    exit;
}

// start
$Rewrite = QUI::getRewrite();
$Rewrite->exec();

QUI::getLocale()->setCurrent(
    $Rewrite->getProject()->getLang()
);


// sprache ausschalten
if (isset($_REQUEST['lang']) && $_REQUEST['lang'] == 'false') {
    $Response->headers->set('X-Robots-Tag', 'noindex, nofollow');
    QUI::getLocale()->no_translation = true;
}

$Project = $Rewrite->getProject();
$Site = $Rewrite->getSite();

$Site->load();

if (isset($Locale)) {
    unset($Locale);
    $Locale = QUI::getLocale();
}

/**
 * Referal System
 */

if (isset($_REQUEST['ref'])) {
    QUI::getSession()->set('ref', Orthos::clear($_REQUEST['ref']));
}

/**
 * Wartungsarbeiten
 */
if (QUI::conf('globals', 'maintenance')
    && !(QUI::getUserBySession()->getId()
        && QUI::getUserBySession()->isSu())
) {
    $Response->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
    $Response->headers->set('X-Powered-By', '');
    $Response->headers->set('Retry-After', 3600);

    $Smarty = QUI::getTemplateManager()->getEngine();

    $Smarty->assign(array(
        'Project'     => $Project,
        'URL_DIR'     => URL_DIR,
        'URL_BIN_DIR' => URL_BIN_DIR,
        'URL_LIB_DIR' => URL_LIB_DIR,
        'URL_VAR_DIR' => URL_VAR_DIR,
        'URL_OPT_DIR' => URL_OPT_DIR,
        'URL_USR_DIR' => URL_USR_DIR,
        'URL_TPL_DIR' => URL_USR_DIR.$Project->getName().'/',
        'TPL_DIR'     => OPT_DIR.$Project->getName().'/',
    ));

    $file = SYS_DIR.'template/maintenance.html';
    $pfile = USR_DIR.$Project->getName().'/lib/maintenance.html';

    if (file_exists($pfile)) {
        $file = $pfile;
    }

    $Response->setContent($Smarty->fetch($file));
    $Response->send();
    exit;
}


// Prüfen ob es ein Cachefile gibt damit alles andere übersprungen werden kann
$site_cache_dir = VAR_DIR.'cache/sites/';
$project_cache_dir = $site_cache_dir.$Project->getAttribute('name').'/';
$site_cache_file
    = $project_cache_dir.$Site->getId().'_'.$Project->getAttribute('name').'_'
    .$Project->getAttribute('lang');

// Event onstart
QUI::getEvents()->fireEvent('start');

Debug::marker('objekte initialisiert');

// Wenn es ein Cache gibt und die Seite auch gecached werden soll
if (CACHE && file_exists($site_cache_file)
    && $Site->getAttribute('nocache') != true
) {
    $cache_content = file_get_contents($site_cache_file);
    $_content = $Rewrite->outputFilter($cache_content);

    $Response->setContent($content);
    $Response->send();
    exit;
}

/**
 * Template Content generieren
 */
try {
    $Template = new QUI\Template();
    $content = $Template->fetchTemplate($Site);

    Debug::marker('fetch Template');

    // cachefile erstellen
    if ($Site->getAttribute('nocache') != true) {
        QUI\Utils\System\File::mkdir($site_cache_dir
            .$Project->getAttribute('name').'/');

        file_put_contents($site_cache_file, $content);
    }

    $content = $Rewrite->outputFilter($content);
    $content = QUI\Control\Manager::setCSSToHead($content);
    Debug::marker('output done');

    $Response->setContent($content);
    Debug::marker('content done');

    if (Debug::$run) {
        Log(Debug::output());
    }


} catch (QUI\Exception $Exception) {

    if ($Exception->getCode() == 404) {
        $Response->setStatusCode(Response::HTTP_NOT_FOUND);
    } else {
        $Response->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
    }


    Log::addError($Exception->getMessage());

    $Template = new QUI\Template();

    try {
        $content = $Template->fetchTemplate($Rewrite->getErrorSite());

    } catch (\QUI\Exception $Exception) {
        $content = $Template->fetchTemplate($Project->firstChild());
    }


    $content = $Rewrite->outputFilter($content);
    $content = QUI\Control\Manager::setCSSToHead($content);

    $Response->setContent($content);
}

$Response->prepare(QUI::getRequest());
$Response->send();
exit;
