<?php

/**
 *  _______          _________ _______  _______  _______  _______
 * (  ___  )|\     /|\__   __/(  ___  )(  ___  )(  ____ \(  ____ )
 * | (   ) || )   ( |   ) (   | (   ) || (   ) || (    \/| (    )|
 * | |   | || |   | |   | |   | |   | || |   | || (__    | (____)|
 * | |   | || |   | |   | |   | |   | || |   | ||  __)   |     __)
 * | | /\| || |   | |   | |   | | /\| || | /\| || (      | (\ (
 * | (_\ \ || (___) |___) (___| (_\ \ || (_\ \ || (____/\| ) \ \__
 * (____\/_)(_______)\_______/(____\/_)(____\/_)(_______/|/   \__/
 *
 * @author www.pcsg.com (Henning Leutz)
 */


\error_reporting(E_ALL);

if (!\defined('QUIQQER_SYSTEM')) {
    \define('QUIQQER_SYSTEM', true);
}

// Mailto
if (isset($_REQUEST['_url'])
    && \strpos($_REQUEST['_url'], '[mailto]') !== false
) {
    $addr = \str_replace('[mailto]', '', $_REQUEST['_url']);
    list($user, $host) = \explode("[at]", $addr);

    if (isset($user) && isset($host)) {
        \header("Location: mailto:".$user."@".$host);
        exit;
    }
}

use \Symfony\Component\HttpFoundation\Response;

use QUI\Utils\System\Debug;
use QUI\Utils\Security\Orthos;
use QUI\System\Log;

try {
    require_once 'bootstrap.php';

    $Response = QUI::getGlobalResponse();

    // UTF 8 Prüfung für umlaute in url
    if (isset($_REQUEST['_url'])) {
        $_REQUEST['_url'] = QUI\Utils\StringHelper::toUTF8($_REQUEST['_url']);
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


    // switch off language
    if (isset($_REQUEST['lang']) && $_REQUEST['lang'] == 'false') {
        $Response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        QUI::getLocale()->no_translation = true;
    }

    $Project = $Rewrite->getProject();
    $Site    = $Rewrite->getSite();
    $Engine  = QUI::getTemplateManager()->getEngine();

    $Site->load();

    if (isset($Locale)) {
        unset($Locale);
        $Locale = QUI::getLocale();
    }

    if (\defined('LOGIN_FAILED')
        || isset($_POST['login'])
        || isset($_GET['logout'])
    ) {
        $Site->setAttribute('nocache', true);
    }

    /**
     * Referral System
     */
    if (isset($_REQUEST['ref'])) {
        QUI::getSession()->set('ref', Orthos::clear($_REQUEST['ref']));
    }

    /**
     * maintenance work
     */
    if (QUI::conf('globals', 'maintenance')
        && !(QUI::getUserBySession()->getId() && QUI::getUserBySession()->isSU())
    ) {
        $Response->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
        $Response->headers->set('X-Powered-By', '');
        $Response->headers->set('Retry-After', 3600);

        $Smarty = QUI::getTemplateManager()->getEngine();

        $Smarty->assign([
            'Project'     => $Project,
            'URL_DIR'     => URL_DIR,
            'URL_BIN_DIR' => URL_BIN_DIR,
            'URL_LIB_DIR' => URL_LIB_DIR,
            'URL_VAR_DIR' => URL_VAR_DIR,
            'URL_OPT_DIR' => URL_OPT_DIR,
            'URL_USR_DIR' => URL_USR_DIR,
            'URL_TPL_DIR' => URL_USR_DIR.$Project->getName().'/',
            'TPL_DIR'     => OPT_DIR.$Project->getName().'/',
        ]);

        $file  = LIB_DIR.'templates/maintenance.html';
        $pfile = USR_DIR.$Project->getName().'/lib/maintenance.html';

        if (\file_exists($pfile)) {
            $file = $pfile;
        }

        $Response->setContent($Smarty->fetch($file));
        $Response->send();
        exit;
    }

    // Event onstart
    QUI::getEvents()->fireEvent('start');
    Debug::marker('objekte initialisiert');

    $siteCachePath = $Site->getCachePath().'/'.\md5(QUI::getRequest()->getRequestUri());

    // Check if user is allowed to view Site and set appropriate error code if not
    if ($Site instanceof QUI\Projects\Site\PermissionDenied) {
        $statusCode = (int)QUI::conf('globals', 'sitePermissionDeniedErrorCode');
        $Response->setStatusCode($statusCode);
    }


    // if cache exists, and cache should also be used
    if (CACHE
        && $Site->getAttribute('nocache') != true
        && !QUI::getUsers()->isAuth(QUI::getUserBySession())
    ) {
        try {
            $cache_content = QUI\Cache\Manager::get($siteCachePath);
            $content       = $Rewrite->outputFilter($cache_content);
            $_content      = $content;

            QUI::getEvents()->fireEvent('requestOutput', [&$_content]);

            $Response->setContent($content);
            $Response->send();
            exit;
        } catch (\Exception $Exception) {
            Log::writeDebugException($Exception);
        }
    }

    // Template Content generating
    try {
        $Template = new QUI\Template();
        $content  = $Template->fetchSite($Site);

        Debug::marker('fetch Template');

        $content = $Rewrite->outputFilter($content);
        $content = QUI\Control\Manager::setCSSToHead($content);
        Debug::marker('output done');

        QUI::getEvents()->fireEvent('requestOutput', [&$content]);

        $Response->setContent($content);
        Debug::marker('content done');

        // cachefile erstellen
        if ($Site->getAttribute('nocache') != true
            && !QUI::getUsers()->isAuth(QUI::getUserBySession())
        ) {
            try {
                QUI\Cache\Manager::set($siteCachePath, $content);
            } catch (\Exception $Exception) {
                Log::writeDebugException($Exception);
            }
        }

        if (Debug::$run) {
            Log::writeRecursive(Debug::output());
        }

        QUI::getSession()->set(
            'CURRENT_LANG',
            QUI::getLocale()->getCurrent()
        );
    } catch (QUI\Exception $Exception) {
        if ($Exception->getCode() == 404) {
            $Response->setStatusCode(Response::HTTP_NOT_FOUND);
        } else {
            $Response->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
        }


        Log::writeException($Exception, Log::LEVEL_ERROR);

        $Template = new QUI\Template();

        try {
            $content = $Template->fetchSite($Rewrite->getErrorSite());
        } catch (QUI\Exception $Exception) {
            $content = $Template->fetchSite($Project->firstChild());
        }

        $content = $Rewrite->outputFilter($content);
        $content = QUI\Control\Manager::setCSSToHead($content);

        $Response->setContent($content);
    }

    $Response->prepare(QUI::getRequest());
    $Response->send();
    exit;
} catch (\Exception $Exception) {
    // error ??
    \header('HTTP/1.1 503 Service Temporarily Unavailable');
    \header('Status: 503 Service Temporarily Unavailable');

    \error_log($Exception->getTraceAsString());
    \error_log($Exception->getMessage());

    echo \file_get_contents(
        \dirname(__FILE__).'/lib/templates/error.html'
    );
}
