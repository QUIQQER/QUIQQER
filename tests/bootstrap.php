<?php

$quiqqerPackageDir = dirname(dirname(__FILE__));
$packageDir        = dirname(dirname($quiqqerPackageDir));

$autloaderFile = $packageDir .'/autoload.php';
$headerFile = $packageDir .'/header.php';

// autoloader
require $autloaderFile;

if (!file_exists($headerFile)) {
    throw new \Exception('QUIQQER must be installed.');
}

require $headerFile;
