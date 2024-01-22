<?php

/**
 * HEADER ACP
 *
 * @author www.pcsg.de (Henning Leutz)
 */

require_once dirname(__FILE__, 4) . '/header.php';

// wenn https vorhanden, dann dahin
if (
    (int)$_SERVER['SERVER_PORT'] !== 443
    && QUI::conf('globals', 'httpshost')
) {
    // auf https leiten
    header('Location: ' . QUI::conf('globals', 'httpshost') . $_SERVER['REQUEST_URI']);
    exit;
}

$Users = QUI::getUsers();
$User = $Users->getUserBySession();

if (strpos($_SERVER['SCRIPT_NAME'], 'index.php') !== false) {
    if (!$User->canUseBackend() || !$Users->isAuth($User)) {
        if ($User->getId() && !QUI\Permissions\Permission::isAdmin($User)) {
            $User->logout();
        }

        require_once __DIR__ . '/login.php';
        exit;
    }
}

//Adminbereich markieren
define('ADMIN', true);
