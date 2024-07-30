<?php

global $Output;
require "header.php";

// quiqqer install
$Output->writeLn('> Execute Package uninstallations');

if ($argc > 1) {
    $packages = $argv[1];
    $packages = explode(',', $packages);
} else {
    $Output->writeLn('No package name', 'red');
    exit;
}

foreach ($packages as $package) {
    try {
        $Output->writeLn('>> uninstallation of '. $package);

        $Package = QUI::getPackage($package);
        $Package->uninstall();
    } catch (QUI\Exception $Exception) {
        QUI\System\Log::addError(
            $Exception->getMessage(),
            [
                'type' => 'uninstall',
                'package' => $package,
                'errorCode' => $Exception->getCode()
            ]
        );
    }
}
