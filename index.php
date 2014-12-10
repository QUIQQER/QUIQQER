<?php

use QUI;
use QUI\Utils\System\Debug;
use QUI\Utils\Security\Orthos;
use QUI\System\Log;

//$start_test = microtime();
// xdebug_start_trace();

/**
 * @author www.pcsg.com (Henning Leutz)
 */

// Mailto
if ( isset( $_REQUEST['_url'] ) && strpos( $_REQUEST['_url'], '[mailto]' ) !== false )
{
    $addr = str_replace('[mailto]', '', $_REQUEST['_url']);
    list($user, $host) = explode("[at]", $addr);

    if (isset($user) && isset($host))
    {
        header("Location: mailto:".$user."@".$host);
        exit;
    }
}

// ZLIB
if ( function_exists( 'gzcompress' ) ) {
    ob_start( 'ob_gzhandler' );
}

require_once 'bootstrap.php';

$Engine = QUI::getTemplateManager()->getEngine();

// UTF 8 Prüfung für umlaute in url
if ( isset( $_REQUEST['_url'] ) ) {
    $_REQUEST['_url'] = QUI\Utils\String::toUTF8( $_REQUEST['_url'] );
}

//\QUI\Utils\System\Debug::$run = true;
Debug::marker( 'index start' );

// check if one projects exists
if ( !QUI::getProjectManager()->count() )
{
    header( "HTTP/1.0 404 Not Found" );

    // no project exist
    echo '<div style="text-align: center; margin-top: 100px;">
                <img src="'. URL_BIN_DIR .'quiqqer_logo.png" style="max-width: 100%;" />
          </div>';
    exit;
}


// start
$Rewrite = QUI::getRewrite();
$Rewrite->exec();

QUI::getLocale()->setCurrent(
    $Rewrite->getProject()->getLang()
);


// sprache ausschalten
if ( isset( $_REQUEST['lang'] ) && $_REQUEST['lang'] == 'false' )
{
    header("X-Robots-Tag: noindex, nofollow", true);
    QUI::getLocale()->no_translation = true;
}

$Project = $Rewrite->getProject();
$Site    = $Rewrite->getSite();

$Site->load();

if ( isset( $Locale ) )
{
    unset( $Locale );
    $Locale = QUI::getLocale();
}

/**
 * Referal System
 */

if ( isset( $_REQUEST['ref'] ) ) {
    $Session->set( 'ref', Orthos::clear( $_REQUEST['ref'] ) );
}

/**
 * Wartungsarbeiten
 */
if (
    QUI::conf('globals','maintenance') &&
    !(QUI::getUserBySession()->getId() && QUI::getUserBySession()->isSu())
)
{
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    header('Status: 503 Service Temporarily Unavailable');
    header('Retry-After: 3600');
    header('X-Powered-By:');

    $Smarty = QUI::getTemplateManager()->getEngine();

    $Smarty->assign(array(
        'Project' => $Project,
        'URL_DIR'     => URL_DIR,
        'URL_BIN_DIR' => URL_BIN_DIR,
        'URL_LIB_DIR' => URL_LIB_DIR,
        'URL_VAR_DIR' => URL_VAR_DIR,
        'URL_OPT_DIR' => URL_OPT_DIR,
        'URL_USR_DIR' => URL_USR_DIR,
        'URL_TPL_DIR' => URL_USR_DIR . $Project->getName() .'/',
        'TPL_DIR'     => OPT_DIR . $Project->getName() .'/',
    ));

    $file  = SYS_DIR .'template/maintenance.html';
    $pfile = USR_DIR . $Project->getName() .'/lib/maintenance.html';

    if ( file_exists( $pfile ) ) {
        $file = $pfile;
    }

    echo $Smarty->fetch( $file );
    exit;
}


// Prüfen ob es ein Cachefile gibt damit alles andere übersprungen werden kann
$site_cache_dir    = VAR_DIR .'cache/sites/';
$project_cache_dir = $site_cache_dir . $Project->getAttribute('name') .'/';
$site_cache_file   = $project_cache_dir . $Site->getId() .'_'. $Project->getAttribute('name') .'_'. $Project->getAttribute('lang');

$suffix = '.html';

/* @todo Suffixe müssen variabel erweiterbar sein */

if ($Rewrite->getSuffix() == '.print')
{
    $suffix = '.print';
    $site_cache_file .= $suffix;
} else
{
    $suffix = $Rewrite->getSuffix();
}

// Event onstart
QUI::getEvents()->fireEvent( 'start' );

Debug::marker('objekte initialisiert');

// Wenn es ein Cache gibt und die Seite auch gecached werden soll
if ( CACHE && file_exists( $site_cache_file ) && $Site->getAttribute('nocache') != true )
{
    $cache_content = file_get_contents( $site_cache_file );
    $_content      = $Rewrite->outputFilter( $cache_content );
    //$_content      = QUI::getTemplateManager()->setAdminMenu( $_content );

    // Content Ausgabe
    echo $_content;
    exit;
}

/**
 * Template Content generieren
 */
try
{
    $Template = new QUI\Template();
    $content = $Template->fetchTemplate($Site);

    Debug::marker('fetch Template');

    // cachefile erstellen
    if ( $Site->getAttribute('nocache') != true )
    {
        QUI\Utils\System\File::mkdir($site_cache_dir . $Project->getAttribute('name') . '/');

        file_put_contents($site_cache_file, $content);
    }

    $content = $Rewrite->outputFilter($content);
    // $content = $Template->setAdminMenu( $content );
    $content = QUI\Control\Manager::setCSSToHead($content);

    Debug::marker('output Filter');

    // Suffix Verarbeitung
    $suffix_class_file = USR_DIR . 'lib/' . $Project->getAttribute('name') . '/Suffix.php';
    $suffix_class_name = 'Suffix' . ucfirst( strtolower( $Project->getAttribute('name') ) );

    if ( file_exists( $suffix_class_file ) )
    {
        require $suffix_class_file;

        if ( class_exists( $suffix_class_name ) )
        {
            $class = new $suffix_class_name();

            if ( method_exists( $class, 'suffix' ) ) {
                $class->suffix( $content );
            }
        }
    }

    echo $content;

    Debug::marker('content');

    if ( Debug::$run ) {
        Log( Debug::output() );
    }

} catch ( \QUI\Exception $Exception )
{
    header("HTTP/1.0 404 Not Found");

    if ( !defined( 'ERROR_HEADER') ) {
        define( 'ERROR_HEADER', 404 );
    }

    Log::addError( $Exception->getMessage() );
}