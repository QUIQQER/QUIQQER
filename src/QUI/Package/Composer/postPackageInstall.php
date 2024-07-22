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

    $Package = QUI::getPackage($packageName);
    $Package->install();
} catch (Exception $Exception) {
    QUI\System\Log::addError(
        $Exception->getMessage(),
        [
            'method' => 'QUI\Package\Composer\PackageEvents::postPackageInstall',
            'package' => $packageName,
            'errorCode' => $Exception->getCode()
        ]
    );
}

QUI\Cache\Manager::clearPackagesCache();
QUI\Cache\Manager::clearSettingsCache();
QUI\Cache\Manager::clearCompleteQuiqqerCache();