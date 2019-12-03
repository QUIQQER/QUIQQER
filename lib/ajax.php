<?php

/**
 * Main Ajax Handling
 * Is used in backend and frontend ajax
 */

if (isset($_REQUEST['beacon'])) {
    $input = \file_get_contents('php://input');
    \parse_str($input, $_REQUEST);
}

if (!isset($_REQUEST['_rf'])) {
    exit;
}

use QUI\Utils\Security\Orthos;

// if user is loged in, use his language
$User = QUI::getUserBySession();

if ($User->getId() && $User->getLang()) {
    QUI::getLocale()->setCurrent($User->getLang());
}

// @todo dies muss wirklich getestet werden
// @todo falls sprachen chaos erscheint
if (\defined('QUIQQER_FRONTEND')
    && isset($_REQUEST['lang'])
    && (\strlen($_REQUEST['lang']) === 2 || \strlen($_REQUEST['lang']) === 5)) {
    QUI::getLocale()->setCurrent($_REQUEST['lang']);
}

// required ajax files
$_rf_files = \json_decode($_REQUEST['_rf'], true);

if (!\is_array($_rf_files)) {
    $_rf_files = [$_rf_files];
}

QUI::getAjax();

// ajax package loader
if (isset($_REQUEST['package'])) {
    $package = $_REQUEST['package'];
    $dir     = OPT_DIR;

    foreach ($_rf_files as $key => $file) {
        $firstPart = 'package_'.\str_replace('/', '_', $package);
        $ending    = \str_replace($firstPart, '', $file);

        $_rf_file = $dir.$package.\str_replace('_', '/', $ending).'.php';
        $_rf_file = Orthos::clearPath($_rf_file);
        $_rf_file = \realpath($_rf_file);

        if (\strpos($_rf_file, $dir) !== false && \file_exists($_rf_file)) {
            require_once $_rf_file;
        }
    }
}

// admin ajax
foreach ($_rf_files as $key => $file) {
    $_rf_file = OPT_DIR.'quiqqer/quiqqer/admin/'.\str_replace('_', '/', $file).'.php';
    $_rf_file = Orthos::clearPath($_rf_file);
    $_rf_file = \realpath($_rf_file);

    $dir = OPT_DIR.'quiqqer/quiqqer/admin/';

    if (\strpos($_rf_file, $dir) !== false && \file_exists($_rf_file)) {
        require_once $_rf_file;
    }
}

// ajax project loader
if (isset($_REQUEST['project'])) {
    try {
        $Project = QUI::getProjectManager()->decode($_REQUEST['project']);
    } catch (QUI\Exception $Exception) {
        try {
            $Project = QUI::getProjectManager()->getProject(
                $_REQUEST['project']
            );
        } catch (QUI\Exception $Exception) {
            $Project = QUI::getProjectManager()->getStandard();
        }
    }

    $projectDir = USR_DIR.$Project->getName();
    $firstPart  = 'project_'.$Project->getName().'_';

    foreach ($_rf_files as $key => $file) {
        $file = \str_replace($firstPart, '', $file);
        $file = $projectDir.'/lib/'.\str_replace('_', '/', $file).'.php';
        $file = Orthos::clearPath($file);
        $file = \realpath($file);

        $dir = $projectDir.'/lib/';

        if (\strpos($file, $dir) !== false && \file_exists($file)) {
            require_once $file;
        }
    }
}

$result = QUI::getAjax()->call();

// destroy current ob output, so ajax will be no longer destroyed
\ob_clean();
echo $result;
exit;
