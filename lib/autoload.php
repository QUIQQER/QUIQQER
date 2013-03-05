<?php

/**
 * This file contains the autoloader and exception_error_handler
 */

/**
 * Autoloader for the QUIQQER CMS
 *
 * @param String $classname
 * @return Bool
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 */

require LIB_DIR .'QUI/Autoloader.php';

if ( function_exists( 'spl_autoload_register' ) )
{
	if ( function_exists( '__autoload' ) ) {
    	spl_autoload_register( '__autoload' );
    }

    spl_autoload_register( '__quiqqer_autoload' );
} else
{
    /**
     * PHP Autoloader
     * Call the QUIQQER Autoloader function
     *
     * @param String $classname
     * @package com.pcsg.qui
     */
    function __autoload( $classname ) {
        __quiqqer_autoload( $classname );
    }
}

/**
 * Main QUIQQER Autoload function
 *
 * @param String $classname
 * @package com.pcsg.qui
 */
function __quiqqer_autoload($classname)
{
	return QUI_Autoloader::load($classname);
}


/**
 * Exception Handler
 *
 * @param Integer $errno
 * @param String $errstr
 * @param String $errfile
 * @param String $errline
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 */
function exception_error_handler($errno, $errstr, $errfile, $errline)
{
    if ( $errstr == 'json_encode(): Invalid UTF-8 sequence in argument' )
    {
        QUI::getErrorHandler()->setAttribute('show_request', true);
        QUI::getErrorHandler()->writeErrorToLog( $errno, $errstr, $errfile, $errline );
        QUI::getErrorHandler()->setAttribute('show_request', false);

        return true;
    }

	QUI::getErrorHandler()->writeErrorToLog( $errno, $errstr, $errfile, $errline );
	return true;
}

?>