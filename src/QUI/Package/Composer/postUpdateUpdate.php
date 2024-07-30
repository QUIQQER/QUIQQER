<?php

global $Output;
require "header.php";

// quiqqer install
$Output->writeLn('> Execute Package update');

if ($argc > 1) {
    $packages = $argv[1];
    $packages = explode(',', $packages);
} else {
    $Output->writeLn('No package name', 'red');
    exit;
}

foreach ($packages as $package) {
    try {
        $Output->writeLn('>> update of '. $package);

        $Package = QUI::getPackage($package);
        $Package->onUpdate();
    } catch (QUI\Exception $Exception) {
        QUI\System\Log::addError(
            $Exception->getMessage(),
            [
                'type' => 'update',
                'package' => $package,
                'errorCode' => $Exception->getCode()
            ]
        );
    }
}
