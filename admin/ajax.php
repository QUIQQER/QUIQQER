<?php

/**
 * PHP Ajax Schnittstelle
 */

require_once 'header.php';

// use QUI;
use QUI\Utils\Security\Orthos;

header("Content-Type: text/plain");

// expire date in the past
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header('Expires: '.gmdate('D, d M Y H:i:s', time() - 60).' GMT');

$User = QUI::getUserBySession();

// Falls Benutzer eingeloggt ist, dann seine Sprache nehmen
if ($User->getId() && $User->getLang()) {
    QUI::getLocale()->setCurrent($User->getLang());
}

// language
if (isset($_REQUEST['lang']) && strlen($_REQUEST['lang']) === 2) {
    QUI::getLocale()->setCurrent($_REQUEST['lang']);
}

if (!isset($_REQUEST['_rf'])) {
    exit;
}

$_rf_files = json_decode($_REQUEST['_rf'], true);


if (!is_array($_rf_files)) {
    $_rf_files = array($_rf_files);
}


// ajax package loader
if (isset($_REQUEST['package'])) {
    $package = $_REQUEST['package'];
    $dir = OPT_DIR;

    foreach ($_rf_files as $key => $file) {
        $firstpart = 'package_'.str_replace('/', '_', $package);
        $ending = str_replace($firstpart, '', $file);

        $_rf_file = $dir.$package.str_replace('_', '/', $ending).'.php';
        $_rf_file = Orthos::clearPath($_rf_file);
        $_rf_file = realpath($_rf_file);

        if (strpos($_rf_file, $dir) !== false && file_exists($_rf_file)) {
            require_once $_rf_file;
        }
    }
}

// admin ajax
foreach ($_rf_files as $key => $file) {
    $_rf_file
        = OPT_DIR.'quiqqer/quiqqer/admin/'.str_replace('_', '/', $file).'.php';
    $_rf_file = Orthos::clearPath($_rf_file);
    $_rf_file = realpath($_rf_file);

    $dir = OPT_DIR.'quiqqer/quiqqer/admin/';

    if (strpos($_rf_file, $dir) !== false && file_exists($_rf_file)) {
        require_once $_rf_file;
    }
}

// ajax project loader
if (isset($_REQUEST['project'])) {
    try {
        $Project = QUI::getProjectManager()->decode($_REQUEST['project']);

    } catch (QUI\Exception $Exception) {
        $Project = QUI::getProjectManager()->getProject(
            $_REQUEST['project']
        );
    }

    $projectDir = USR_DIR.$Project->getName();
    $firstpart = 'project_'.$Project->getName().'_';

    foreach ($_rf_files as $key => $file) {
        $file = str_replace($firstpart, '', $file);
        $file = $projectDir.'/lib/'.str_replace('_', '/', $file).'.php';
        $file = Orthos::clearPath($file);
        $file = realpath($file);

        $dir = $projectDir.'/lib/';

        if (strpos($file, $dir) !== false && file_exists($file)) {
            require_once $file;
        }
    }
}


/**
 * Ajax Ausgabe
 */
echo QUI::$Ajax->call();
exit;
