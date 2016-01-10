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

if (!defined('QUIQQER_SYSTEM')) {
    exit;
}

if (!defined('ETC_DIR')) {
    require_once 'quiqqer.php';
    exit;
}

require_once dirname(__FILE__).'/lib/autoload.php';
require_once dirname(__FILE__).'/lib/header.php';
