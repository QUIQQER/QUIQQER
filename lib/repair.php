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
echo '======================================' . PHP_EOL;
echo '|  Try to update and repair QUIQQER  |' . PHP_EOL;
echo '======================================' . PHP_EOL;
echo PHP_EOL;

system($composerCommand . ' clear-cache');

if (file_exists($composerDir . 'composer.lock')) {
    unlink($composerDir . 'composer.lock');
}

system($composerCommand . ' update --no-scripts -v');
chdir(CMS_DIR);

echo PHP_EOL;
echo '===================' . PHP_EOL;
echo '|  Hello QUIQQER  |' . PHP_EOL;
echo '===================' . PHP_EOL;
echo PHP_EOL;

system('./console');
