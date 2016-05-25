<?php

/**
 * Load the pthread polyfill
 */

if (!extension_loaded("pthreads")) {
    require_once dirname(__FILE__) . '/Polyfills/Collectable.php';
    require_once dirname(__FILE__) . '/Polyfills/Pool.php';
    require_once dirname(__FILE__) . '/Polyfills/Threaded.php';
    require_once dirname(__FILE__) . '/Polyfills/Thread.php';
    require_once dirname(__FILE__) . '/Polyfills/Volatile.php';
    require_once dirname(__FILE__) . '/Polyfills/Worker.php';
}
