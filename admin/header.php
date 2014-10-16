<?php

/**
 * HEADER ACP
 * @author www.pcsg.de (Henning Leutz)
 */

require_once __DIR__ .'/../bootstrap.php'; // <<-- hmm ... not realy nice

// wenn https vorhanden, dann dahin
if ( (int)$_SERVER['SERVER_PORT'] !== 443 && \QUI::conf( 'globals', 'httpshost' ) )
{
    // auf https leiten
    header( 'Location: '. \QUI::conf( 'globals', 'httpshost' ) . $_SERVER['REQUEST_URI'] );
    exit;
}

$Users = \QUI::getUsers();
$User  = $Users->getUserBySession();


if ( strpos( $_SERVER['SCRIPT_NAME'], 'index.php' ) !== false )
{
    if ( !$User->isAdmin() || !$Users->isAuth( $User ) )
    {
        require_once 'login.php';
        exit;
    }
}

//Adminbereich markieren
define( 'ADMIN', true );
