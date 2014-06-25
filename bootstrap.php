<?php

/**
 * This file is part of QUIQQER.
 *
 * (c) Henning Leutz <leutz@pcsg.de>
 * Moritz Scholz <scholz@pcsg.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This file contains the bootstrap for quiqqer
 * it includes the header file
 */

$etc_dir = __DIR__ .'/etc/';

if ( !file_exists( $etc_dir .'conf.ini.php' ))
{
    require 'quiqqer.php';
    exit;
}

$config = parse_ini_file( $etc_dir .'conf.ini.php', true );

if ( file_exists( $config['globals']['lib_dir'] .'autoload.php' ) ) {
    require_once $config['globals']['lib_dir'] .'autoload.php';
}

if ( file_exists( $config['globals']['lib_dir'] .'header.php' ) ) {
    require $config['globals']['lib_dir'] .'header.php';
}
