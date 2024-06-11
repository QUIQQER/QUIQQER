<?php

global $Output;
require "header.php";

if ($argc > 1) {
    $packageName = $argv[1];
} else {
    $Output->writeLn('No package name', 'red');
    exit;
}

try {
    $Package = QUI::getPackage($packageName);
    $Package->onUpdate();
} catch (Exception $Exception) {
    QUI\System\Log::addError(
        $Exception->getMessage(),
        [
            'method' => 'QUI\Package\Composer\PackageEvents::postPackageUpdate',
            'package' => $packageName,
            'errorCode' => $Exception->getCode()
        ]
    );
}

QUI\Cache\Manager::clear('quiqqer');
QUI\Cache\Manager::clearPackagesCache();
QUI\Cache\Manager::clearSettingsCache();
QUI\Cache\Manager::clearCompleteQuiqqerCache();
QUI\Cache\LongTermCache::clear('quiqqer');
