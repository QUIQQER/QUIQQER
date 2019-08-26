<?php

/**
 * Bootstrap for quiqqer tests
 */

$etcDir = getenv('ETC_DIR');

if (!is_dir($etcDir) || empty($etcDir)) {
    $etcDir = dirname(dirname(__FILE__));
    $etcDir = substr($etcDir, 0, strrpos($etcDir, 'quiqqer/quiqqer'));
    $etcDir = dirname($etcDir);
    $etcDir = $etcDir . '/etc/';
}

if (!is_dir($etcDir) || empty($etcDir)) {
    echo "ETC_DIR not found\n";
    exit;
}

define('ETC_DIR', $etcDir);
define('QUIQQER_SYSTEM', true);

require dirname(dirname(__FILE__)) . '/bootstrap.php';

/**
 * @param mixed $message - optional
 */
function writePhpUnitMessage($message = '')
{
    fwrite(STDERR, print_r($message, true) . "\n");
}


//
//// execute: DB_USER=* DB_PASS=* DB_DRIVER=* phpunit
//
//$quiqqerPackageDir = dirname(dirname(__FILE__));
//$packageDir        = dirname(dirname($quiqqerPackageDir));
//
//$autloaderFile = $packageDir . '/autoload.php';
//$headerFile    = $packageDir . '/header.php';
//
//define('QUIQQER_PHPUNIT_GROUP', 'phpunit');
//define('QUIQQER_PHPUNIT_USER', 'phpunit');
//define('QUIQQER_PHPUNIT_PROJECT', 'phpunit');
//
//// autoloader
//require $autloaderFile;
//
//
///**
// * setup QUIQQER environment
// */
//
//// create database
//use org\bovigo\vfs\vfsStream;
//use org\bovigo\vfs\vfsStreamDirectory;
//
//$dbName   = 'phpunit_' . (int)microtime(true);
//$dbUser   = getenv('DB_USER');
//$dbPass   = getenv('DB_PASS');
//$dbDriver = getenv('DB_DRIVER');
//$dbHost   = getenv('DB_HOST');
//
//$dsn     = $dbDriver . ':host=' . $dbHost;
//$options = array();
//
//switch ($dbDriver) {
//    case 'mysql':
//        $options = array(
//            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
//        );
//
//        break;
//}
//
//$PDO = new \PDO($dsn, $dbUser, $dbPass, $options);
//
//// mysql
//$Result = $PDO->exec(
//    "CREATE DATABASE `{$dbName}`;
//    CREATE USER '{$dbUser}'@'{$dbHost}' IDENTIFIED BY '{$dbPass}';
//    GRANT ALL ON `{$dbName}`.* TO '{$dbUser}'@'{$dbHost}';
//    FLUSH PRIVILEGES;"
//) or die(print_r($PDO->errorInfo(), true));
//
///**
// * create temp filesystem
// */
//vfsStream::setup('QUIQQER');
//
//vfsStream::create(array(
//    'etc'   => array(
//        'conf.ini.php' => '
//            [db]
//            driver="' . $dbDriver . '"
//            host="' . $dbHost . '"
//            database="' . $dbName . '"
//            user="' . $dbUser . '"
//            password="' . $dbPass . '"
//            prfx=""
//        '
//    ),
//    'var'   => array(),
//    'media' => array(),
//    'usr'   => array()
//), new vfsStreamDirectory('QUIQQER'));
//
//
//define('CMS_DIR', vfsStream::url('QUIQQER/'));
//define('SYS_DIR', vfsStream::url('QUIQQER/etc/'));
//define('VAR_DIR', vfsStream::url('QUIQQER/var/'));
//define('media_DIR', vfsStream::url('QUIQQER/media/'));
//define('USR_DIR', vfsStream::url('QUIQQER/usr/'));
//
//
//exit;


//if (!file_exists($headerFile)) {
//    throw new \Exception('QUIQQER must be installed.');
//}
//
//require $headerFile;
