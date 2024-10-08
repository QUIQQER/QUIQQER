<?php

define('QUIQQER_UPDATE_IN_PROGRESS', true);

global $Output;
require "header.php";

// quiqqer install
$Output->writeLn('> Executing package uninstallations');

if ($argc > 1) {
    $packages = $argv[1];
    $packages = explode(',', $packages);
} else {
    $Output->writeLn('No package name', 'red');
    exit;
}

foreach ($packages as $package) {
    try {
        $Output->writeLn('>> ' . $package);

        $Package = QUI::getPackage($package);
        $Package->uninstall();
    } catch (\Exception $Exception) {
        $Output->writeLn('!! ' . $Exception->getMessage(), 'red');

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
