<?php

/**
 * Repair CLI Mode
 */

// no console
if (php_sapi_name() !== 'cli') {
    exit;
}

// not loaded via ./console
if (!defined('CMS_DIR')) {
    exit;
}

echo '
   ____  _    _ _____ ____   ____  ______ _____  
  / __ \| |  | |_   _/ __ \ / __ \|  ____|  __ \ 
 | |  | | |  | | | || |  | | |  | | |__  | |__) |
 | |  | | |  | | | || |  | | |  | |  __| |  _  / 
 | |__| | |__| |_| || |__| | |__| | |____| | \ \ 
  \___\_\\____/|_____\___\_\\___\_\______|_|  \_\
     ____   ______ _____        _____ _____
    |  __ \|  ____|  __ \ /\   |_   _|  __ \     
    | |__) | |__  | |__) /  \    | | | |__) |    
    |  _  /|  __| |  ___/ /\ \   | | |  _  /     
    | | \ \| |____| |  / ____ \ _| |_| | \ \     
    |_|  \_\______|_| /_/    \_\_____|_|  \_\    
                                              
';

//start helper-functions

function getNewestFileFrom(string $folder, string $fileEnding): string
{
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder));
    $latestTime = 0;
    $latestFile = '';

    foreach ($iterator as $file) {
        if ($file->isFile() && pathinfo($file->getFilename(), PATHINFO_EXTENSION) === $fileEnding) {
            if ($file->getMTime() > $latestTime) {
                $latestTime = $file->getMTime();
                $latestFile = $file->getRealPath();
            }
        }
    }

    return $latestFile;
}

//end helper-functions


echo PHP_EOL;
echo '=========================' . PHP_EOL;
echo '|   Download Composer   |' . PHP_EOL;
echo '=========================' . PHP_EOL;
echo PHP_EOL;

$etcDir = CMS_DIR . 'etc/';
$iniFile = parse_ini_file($etcDir . 'conf.ini.php', true);

$varDir = $iniFile['globals']['var_dir'];
$phpExec = 'php';

if (!empty($iniFile['globals']['phpCommand'])) {
    $phpExec = $iniFile['globals']['phpCommand'];
}

$composerDir = $varDir . 'composer/';
$backupFolder = $varDir . 'backup';

if (!is_dir($composerDir)) {
    mkdir($composerDir);
}
$composerPhar = $composerDir . 'composer.phar';

system('wget https://getcomposer.org/download/latest-stable/composer.phar -O ' . $composerPhar);

$composerCommand = $phpExec . ' ' . $composerPhar . ' --working-dir="' . $composerDir . '"';

echo PHP_EOL;
echo '========================' . PHP_EOL;
echo '|   Composer version   |' . PHP_EOL;
echo '========================' . PHP_EOL;
echo PHP_EOL;

system($composerCommand . ' --version');

echo PHP_EOL;
echo '=================================' . PHP_EOL;
echo '|  Check needed composer files  |' . PHP_EOL;
echo '=================================' . PHP_EOL;
echo PHP_EOL;

if (file_exists($composerDir . 'composer.json')) {
    echo '- composer.json [ok]' . PHP_EOL;
} else {
    echo '- composer.json [error]' . PHP_EOL;
    echo '-> try to restore' . PHP_EOL;

    $latestComposerJson = getNewestFileFrom($backupFolder, 'json');
    copy($latestComposerJson, $composerDir . 'composer.json');

    if (file_exists($composerDir . 'composer.json')) {
        echo '- composer.json [ok]' . PHP_EOL;
    } else {
        echo '\e[31m';
        echo "Something went wrong, could not restore the composer.json file. Please restore the composer.json file.";
        echo '\e[0m';

        exit;
    }
}
/*
if (file_exists($composerDir . 'composer.lock')) {
    echo '- composer.lock [ok]' . PHP_EOL;
} else {
    echo '- composer.lock [error]' . PHP_EOL;
    echo '-> try to restore' . PHP_EOL;

    $latestComposerJson = getNewestFileFrom($backupFolder, 'lock');
    copy($latestComposerJson, $composerDir . 'composer.lock');

    if (file_exists($composerDir . 'composer.lock')) {
        echo '- composer.lock [ok]' . PHP_EOL;
    } else {
        echo "-> Something went wrong, could not restore the composer.lock file." . PHP_EOL;
        echo "-> Since the composer.json is available, I will try to repair it anyway." . PHP_EOL;
    }
}
*/

echo PHP_EOL;
echo '======================================' . PHP_EOL;
echo '|  Try to update and repair QUIQQER  |' . PHP_EOL;
echo '======================================' . PHP_EOL;
echo PHP_EOL;

system($composerCommand . ' clear-cache');

if (file_exists($composerDir . 'composer.lock')) {
    unlink($composerDir . 'composer.lock');
}

echo '- execute an update without scripts' . PHP_EOL;
system($composerCommand . ' update --no-scripts -v');

echo '- execute a regular update' . PHP_EOL;
system($composerCommand . ' update');

chdir(CMS_DIR);

echo PHP_EOL;
echo '===================' . PHP_EOL;
echo '|  Hello QUIQQER  |' . PHP_EOL;
echo '===================' . PHP_EOL;
echo PHP_EOL;

system('./console');
