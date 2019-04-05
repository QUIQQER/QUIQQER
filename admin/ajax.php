<?php

/**
 * QUIQQER Backend Ajax API
 */

\define('QUIQQER_AJAX', true);
\define('QUIQQER_SYSTEM', true);
\define('QUIQQER_BACKEND', true);

require_once 'header.php';

\header("Content-Type: text/plain");

// expire date in the past
\header("Cache-Control: no-cache, must-revalidate");
\header("Pragma: no-cache");
\header('Expires: '.\gmdate('D, d M Y H:i:s', \time() - 60).' GMT');

//require '../bootstrap.php';
require '../lib/ajax.php';
