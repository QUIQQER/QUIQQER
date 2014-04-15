<?php

/**
 * Einfache Vorschau
 *
 * @author www.pcsg.de (Henning Leutz)
 */

require_once '../header.php';

if ( !QUI::getUserBySession()->isAdmin() )
{
    header( "HTTP/1.1 404 Not Found" );
    exit;
}

if ( !isset( $_POST['project'] ) ||
     !isset( $_POST['lang'] ) &&
     !isset( $_POST['id'] ) )
{
    header( "HTTP/1.1 404 Not Found" );
    echo "Site not found";
    exit;
}

$Project = \QUI::getProject( $_POST['project'], $_POST['lang'] );
$Site    = new \QUI\Projects\Site\Edit( $Project, $_POST['id'] );

$Site->load();

// site data
foreach ( $_POST['siteData'] as $key => $value ) {
    $Site->setAttribute( $key, $value );
}

$Template = new \QUI\Template();
$content  = $Template->fetchTemplate( $Site );

echo $content;

exit;
