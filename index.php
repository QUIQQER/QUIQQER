<?php

//$start_test = microtime();

/**
 * @author PCSG - Henning
 * @package com.pcsg.pms
 *
 * @copyright  2008 PCSG
 * @version    $Revision: 4785 $
 * @since      available since Release P.MS 0.1
 */

// Mailto
if ( isset( $_REQUEST['_url'] ) && strpos( $_REQUEST['_url'], '[mailto]' ) !== false )
{
    /**
     * PCSG Redirect um Mailclient zu öffnen
     *
     * @author PCSG - Henning
     * @package com.pcsg.pms
     *
     * @copyright  2008 PCSG
     * @version    $Revision: 4785 $
     * @since      available since Release P.MS 0.11
     */
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

$Engine = \QUI\Template::getEngine();

// UTF 8 Prüfung für umlaute in url
if ( isset( $_REQUEST['_url'] ) ) {
    $_REQUEST['_url'] = \Utils_String::toUTF8( $_REQUEST['_url'] );
}

//\QUI\Utils\System\Debug::$run = true;
\QUI\Utils\System\Debug::marker( 'index start' );

$Rewrite = \QUI::getRewrite();
$Rewrite->exec();

// sprache ausschalten
if ( isset( $_REQUEST['lang'] ) && $_REQUEST['lang'] == 'false' )
{
    header("X-Robots-Tag: noindex, nofollow", true);
    \QUI::getLocale()->no_translation = true;
}

$Project = $Rewrite->getProject(); 		/* @var $Project \QUI\Projects\Project */
$Site    = $Rewrite->getSite()->load(); /* @var $Site \QUI\Projects\Site\Edit */

if ( isset( $Locale ) )
{
    unset( $Locale );
    $Locale = \QUI::getLocale();
}

/**
 * Referal System
 */

if ( isset( $_REQUEST['ref'] ) ) {
    $Session->set( 'ref', \Utils_Security_Orthos::clear( $_REQUEST['ref'] ) );
}

/**
 * Wartungsarbeiten
 */
if (
    \QUI::conf('globals','maintenance') &&
    !(\QUI::getUserBySession()->getId() && \QUI::getUserBySession()->isSu())
)
{
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    header('Status: 503 Service Temporarily Unavailable');
    header('Retry-After: 3600');
    header('X-Powered-By:');

    $Smarty = \QUI\Template::getEngine();

    $Smarty->assign(array(
        'Project' => $Project
    ));

    $file  = SYS_DIR .'template/maintenance.html';
    $pfile = USR_DIR .'lib/'. $Project->getAttribute('template') .'/maintenance.html';

    if (file_exists($pfile)) {
        $file = $pfile;
    }

    echo $Smarty->fetch($file);
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
\QUI::getEvents()->fireEvent( 'start' );

\QUI\Utils\System\Debug::marker('objekte initialisiert');

// Wenn es ein Cache gibt und die Seite auch gecached werden soll
if ( CACHE && file_exists( $site_cache_file ) && $Site->getAttribute('nocache') != true )
{
    $cache_content = file_get_contents( $site_cache_file );
    $_content      = $Rewrite->outputFilter( $cache_content );
    $_content      = \QUI\Template::setAdminMenu( $_content );

    // Content Ausgabe
    echo $_content;
    exit;
}

/**
 * Template Content generieren
 */
$Template = new \QUI\Template();
$content  = $Template->fetchTemplate( $Site );

\QUI\Utils\System\Debug::marker('fetch Template');

// cachefile erstellen
if ($Site->getAttribute('nocache') != true)
{
    Utils_System_File::mkdir($site_cache_dir . $Project->getAttribute('name') .'/');

    file_put_contents($site_cache_file, $content);
}

$content = $Rewrite->outputFilter( $content );
$content = \QUI\Template::setAdminMenu( $content );

\QUI\Utils\System\Debug::marker('output Filter');

// Suffix Verarbeitung
$suffix_class_file = USR_DIR .'lib/'. $Project->getAttribute('name') .'/Suffix.php';
$suffix_class_name = 'Suffix'. ucfirst(strtolower($Project->getAttribute('name')));

if (file_exists($suffix_class_file))
{
    require $suffix_class_file;

    if (class_exists($suffix_class_name))
    {
        $class = new $suffix_class_name();

        if (method_exists($class, 'suffix')) {
            $class->suffix($content);
        }
    }
}

echo $content;

\QUI\Utils\System\Debug::marker('content');

if ( \QUI\Utils\System\Debug::$run )
{
    \QUI\System\Log(
        \QUI\Utils\System\Debug::output()
    );
}
