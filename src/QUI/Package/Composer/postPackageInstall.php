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
    $Output->writeLn('Install package: ' . $packageName);

    \QUI\System\Log::addError('Install package: ' . $packageName);

    $Package = QUI::getPackage($packageName);
    $Package->install();

    \QUI\System\Log::addError('Install package DONE: ' . $packageName);

} catch (Exception $Exception) {
    \QUI\System\Log::addError('Install package exception: ' . $packageName);

    QUI\System\Log::addError(
        $Exception->getMessage(),
        [
            'method' => 'QUI\Package\Composer\PackageEvents::postPackageInstall',
            'package' => $packageName,
            'errorCode' => $Exception->getCode()
        ]
    );

    \QUI\System\Log::addError('Install package exception DONE: ' . $packageName);
}

QUI\Cache\Manager::clearPackagesCache();
QUI\Cache\Manager::clearSettingsCache();
QUI\Cache\Manager::clearCompleteQuiqqerCache();

QUI\Cache\Manager::clear('quiqqer');
QUI\Cache\LongTermCache::clear('quiqqer');
