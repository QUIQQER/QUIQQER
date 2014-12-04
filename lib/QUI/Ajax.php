<?php

/**
 * This file contains \QUI\Ajax
 */

namespace QUI;

use QUI;

/**
 * QUIQQER Ajax
 * Communication between JavaScript and PHP
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils.request
 */
class Ajax extends QUI\QDOM
{
    /**
     * Available ajax functions
     * @var array
     */
    static $_functions = array();

    /**
     * registered permissions from available ajax functions
     * @var array
     */
    static $_permissions = array();

    /**
     * constructor
     *
     * @param array $params
     */
    public function __construct($params=array())
    {
        self::setAttributes( $params );

        // Shutdown Handling
        $ErrorHandler = QUI::getErrorHandler();
        $ErrorHandler->registerShutdown( array($this, 'onShutdown') );
    }

    /**
     * Registered functions which are available via Ajax
     *
     * @param string $reg_function - Function which exists in Ajax
     * @param array|bool $reg_vars     - Variables which has the function of
     * @param bool|string $user_perm    - rights, optional
     * @return bool
     */
    static function register($reg_function, $reg_vars=array(), $user_perm=false)
    {
        if ( !is_string( $reg_function ) ) {
            return false;
        }

        if ( !is_array( $reg_vars ) ) {
            $reg_vars = array();
        }

        self::$_functions[ $reg_function ] = $reg_vars;

        if ( $user_perm ) {
            self::$_permissions[ $reg_function ] = $user_perm;
        }

        return true;
    }

    /**
     * Checks the rights if a function has a checkPermissions routine
     *
     * @param String|callback $reg_function
     * @throws \QUI\Exception
     */
    static function checkPermissions($reg_function)
    {
        if ( !isset( self::$_permissions[ $reg_function ] ) ) {
            return;
        }

        $function = self::$_permissions[ $reg_function ];

        if ( is_object( $function ) && get_class( $function ) === 'Closure' )
        {
            $function();
            return;
        }


        if ( is_string( $function ) ) {
            $function = array($function);
        }

        foreach ( $function as $func )
        {
            // if it is a real permission
            if ( strpos( $func, '::' ) === false )
            {
                Rights\Permission::checkPermission( $func );
                return;
            }

            if ( strpos( $func, 'Permission' ) === 0 ) {
                   $func = '\\QUI\\Rights\\'. $func;
            }

            if ( !is_callable( $func ) ) {
                throw new QUI\Exception( 'Permission denied', 503 );
            }

            call_user_func( $func );
        }
    }

    /**
     * ajax processing
     *
     * @return String - quiqqer XML
     * @throws QUI\Exception
     */
    public function call()
    {
        if ( !isset( $_REQUEST['_rf'] ) ||
             !is_string( $_REQUEST['_rf'] ) &&
             count( $_REQUEST['_rf'] ) > 1 )
        {
            return $this->writeException(
                new QUI\Exception( 'Bad Request', 400 )
            );
        }

        $_rfs   = json_decode( $_REQUEST['_rf'], true );
        $result = array();

        if ( !is_array( $_rfs ) ) {
            $_rfs = array( $_rfs );
        }

        foreach ( $_rfs as $_rf ) {
            $result[ $_rf ] = $this->_call_rf( $_rf );
        }

        if ( QUI::getMessagesHandler() )
        {
            $result['message_handler'] = \QUI::getMessagesHandler()->getMessagesAsArray(
                QUI::getUserBySession()
            );
        }

        return '<quiqqer>'. json_encode( $result ) .'</quiqqer>';
    }

    /**
     * Internal call of an ajax function
     *
     * @param String $_rf
     * @return Array - the result
     */
    protected function _call_rf($_rf)
    {
        if ( !isset( self::$_functions[ $_rf ] ) )
        {
            if ( defined( 'DEVELOPMENT' ) && DEVELOPMENT ) {
                System\Log::write( 'Funktion '. $_rf .' nicht gefunden' );
            }

            return $this->writeException(
                new QUI\Exception( 'Bad Request', 400 )
            );
        }

        // Rechte prüfung
        try
        {
            $this->checkPermissions( $_rf );

        } catch ( QUI\Exception $Exception )
        {
            return $this->writeException( $Exception );
        }


        // Request vars
        if ( isset( $_REQUEST['pcsg_uri'] ) ) {
            $_SERVER['REQUEST_URI'] = $_REQUEST['pcsg_uri'];
        }

        $params = array();

        // Params
        foreach ( self::$_functions[ $_rf ] as $var )
        {
            if ( !isset($_REQUEST[ $var ]) )
            {
                $params[ $var ] = '';
                continue;
            }

            $value = $_REQUEST[ $var ];

            if ( is_object( $value ) )
            {
                $params[ $var ] = $value;
                continue;
            }

            $value = urldecode( $value );

            if ( get_magic_quotes_gpc() )
            {
                $params[ $var ] = stripslashes( $value );
            } else
            {
                $params[ $var ] = $value;
            }
        }

        try
        {
            $return = array(
                'result' => call_user_func_array( $_rf, $params )
            );

        } catch ( QUI\Exception $Exception )
        {
            return $this->writeException( $Exception );

        } catch ( \PDOException $Exception )
        {
            return $this->writeException( $Exception );
        }


        // json errors bekommen
        if ( function_exists( 'json_last_error' ) )
        {
            switch ( json_last_error() )
            {
                case JSON_ERROR_NONE:
                    // alles ok
                break;

                case JSON_ERROR_DEPTH:
                case JSON_ERROR_STATE_MISMATCH:
                case JSON_ERROR_CTRL_CHAR:
                case JSON_ERROR_SYNTAX:
                case JSON_ERROR_UTF8:
                default:
                    System\Log::write(
                        'JSON Error: '. json_last_error() . ' :: '. print_r( $return, true ),
                        'error'
                    );
                break;
            }
        }

        return $return;
    }

    /**
     * Exceptions xml / json return
     *
     * @param \QUI\Exception|\PDOException $Exception
     * @return Array
     */
    public function writeException($Exception)
    {
        $return = array();

        switch ( get_class( $Exception ) )
        {
            default:
                $return['Exception']['message'] = $Exception->getMessage();
                $return['Exception']['code']    = $Exception->getCode();
                $return['Exception']['type']    = $Exception->getType();
            break;

            case 'PDOException':
            case 'QUI\\Database\\Exception':
                // DB Fehler immer loggen
                System\Log::writeException( $Exception );

                if ( $this->getAttribute('db_errors') )
                {
                    $return['ExceptionDBError']['message'] = $Exception->getMessage();
                    $return['ExceptionDBError']['code']    = $Exception->getCode();
                } else
                {
                    // Standardfehler rausbringen
                    $return['Exception']['message'] = 'Internal Server Error';
                    $return['Exception']['code']    = 500;
                }
            break;
        }

        return $return;
    }

    /**
     * Ajax Timeout handling
     */
    public function onShutdown()
    {
        switch ( connection_status() )
        {
            case 2: // timeout

                $return = array(
                    'Exception' => array(
                        'message' => 'Zeitüberschreitung der Anfrage. Bitte versuchen Sie es erneut oder zu einem späteren Zeitpunkt.',
                        'code'    => 504
                    )
                );

                echo '<quiqqer>'. json_encode($return) .'</quiqqer>';
            break;
        }
    }
}
