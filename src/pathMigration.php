<?php

if (php_sapi_name() !== 'cli') {
    die('Error: This script can only be executed from the command line via ./console system-migration' . PHP_EOL);
}

$validScripts = ['quiqqer.php', './console'];

$scriptName = $_SERVER['argv'][0] ?? '';

if (!in_array($scriptName, $validScripts, true)) {
    die('Error: The path migration script can only be executed via "./console system-migration" from the main QUIQQER directory.' . PHP_EOL);
}

echo 'Download newest version of QUIQQER path migration script ...' . PHP_EOL;

$pathMigration = file_get_contents(
    'https://dev.quiqqer.com/quiqqerscripts/PathMigration/-/raw/master/pathmigration.php?ref_type=heads'
);

$file = getcwd() .'/pathMigration.php';
file_put_contents($file, $pathMigration);

system(PHP_BINARY .' '.$file);
unlink($pathMigration);
