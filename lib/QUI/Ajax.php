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
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 * @package com.pcsg.qui.utils.request
 */
class Ajax extends QUI\QDOM
{
    /**
     * Available ajax functions
     *
     * @var array
     */
    protected static $functions = array();

    /**
     * Available ajax lambda functions
     *
     * @var array
     */
    protected static $callables = array();

    /**
     * javascript functions to be executed by after a request
     * This functions are registered via Ajax.registerCallback('functionName', callable);
     *
     * @var array
     */
    protected $jsCallbacks = array();

    /**
     * registered permissions from available ajax functions
     *
     * @var array
     */
    protected static $permissions = array();

    /**
     * constructor
     *
     * @param array $params
     */
    public function __construct($params = array())
    {
        self::setAttributes($params);

        // Shutdown Handling
        $ErrorHandler = QUI::getErrorHandler();
        $ErrorHandler->registerShutdown(array($this, 'onShutdown'));
    }

    /**
     * Registered a function which is available via ajax
     *
     * @param string $reg_function - Function which is callable via ajax
     * @param array|boolean $reg_vars - Variables of the function
     * @param bool|string $user_perm - rights, optional
     *
     * @return bool
     */
    public static function register(
        $reg_function,
        $reg_vars = array(),
        $user_perm = false
    ) {
        if (!is_string($reg_function)) {
            return false;
        }

        if (!is_array($reg_vars)) {
            $reg_vars = array();
        }

        self::$functions[$reg_function] = $reg_vars;

        if ($user_perm) {
            self::$permissions[$reg_function] = $user_perm;
        }

        return true;
    }

    /**
     * Registered a lambda function which is available via ajax
     *
     * @param string $name - Name of the function
     * @param callable $function - Function
     * @param array $reg_vars - Variables of the function
     * @param bool|false $user_perm - (optional) permissions / rights
     *
     * @return bool
     */
    public static function registerFunction(
        $name,
        $function,
        $reg_vars = array(),
        $user_perm = false
    ) {
        if (!is_callable($function)) {
            return false;
        }

        if (!is_string($name)) {
            return false;
        }

        if (!is_array($reg_vars)) {
            $reg_vars = array();
        }

        self::$callables[$name] = array(
            'callable' => $function,
            'params'   => $reg_vars
        );

        if ($user_perm) {
            self::$permissions[$name] = $user_perm;
        }

        return true;
    }

    /**
     * Checks the rights if a function has a checkPermissions routine
     *
     * @param string|callback $reg_function
     *
     * @throws \QUI\Exception
     */
    public static function checkPermissions($reg_function)
    {
        if (!isset(self::$permissions[$reg_function])) {
            return;
        }

        $function = self::$permissions[$reg_function];

        if (is_object($function) && get_class($function) === 'Closure') {
            $function();
            return;
        }


        if (is_string($function)) {
            $function = array($function);
        }

        foreach ($function as $func) {
            // if it is a real permission
            if (strpos($func, '::') === false) {
                Permissions\Permission::checkPermission($func);

                return;
            }

            if (strpos($func, 'Permission') === 0) {
                $func = '\\QUI\\Rights\\' . $func;
            }

            if (!is_callable($func)) {
                throw new QUI\Exception('Permission denied', 503);
            }

            call_user_func($func);
        }
    }

    /**
     * ajax processing
     *
     * @return string - quiqqer XML
     * @throws QUI\Exception
     */
    public function call()
    {
        if (!isset($_REQUEST['_rf'])
            || !is_string($_REQUEST['_rf']) && count($_REQUEST['_rf']) > 1
        ) {
            return $this->writeException(
                new QUI\Exception('Bad Request', 400)
            );
        }

        $_rfs   = json_decode($_REQUEST['_rf'], true);
        $result = array();

        if (!is_array($_rfs)) {
            $_rfs = array($_rfs);
        }

        foreach ($_rfs as $_rf) {
            $result[$_rf] = $this->callRequestFunction($_rf);
        }

        QUI::getSession()->getSymfonySession()->save();

        if (QUI::getMessagesHandler()) {
            $result['message_handler'] = QUI::getMessagesHandler()->getMessagesAsArray(
                QUI::getUserBySession()
            );
        }

        // maintenance flag
        $result['maintenance'] = QUI::conf('globals', 'maintenance') ? 1 : 0;
        $result['jsCallbacks'] = $this->jsCallbacks;

        return '<quiqqer>' . json_encode($result) . '</quiqqer>';
    }

    /**
     * Internal call of an ajax function
     *
     * @param string $_rf
     * @param array|boolean|mixed $values
     *
     * @return array - the result
     */
    public function callRequestFunction($_rf, $values = false)
    {
        if (!isset(self::$functions[$_rf]) && !isset(self::$callables[$_rf])) {
            if (defined('DEVELOPMENT') && DEVELOPMENT) {
                System\Log::addDebug('Funktion ' . $_rf . ' nicht gefunden');
            }

            return $this->writeException(
                new QUI\Exception('Bad Request', 400)
            );
        }

        // Rechte prüfung
        try {
            $this->checkPermissions($_rf);
        } catch (QUI\Exception $Exception) {
            return $this->writeException($Exception);
        }


        // Request vars
        if (isset($_REQUEST['pcsg_uri'])) {
            $_SERVER['REQUEST_URI'] = $_REQUEST['pcsg_uri'];
        }

        // Params
        $params = array();

        if (isset(self::$callables[$_rf])) {
            $functionParams = self::$callables[$_rf]['params'];
        } else {
            $functionParams = self::$functions[$_rf];
        }

        foreach ($functionParams as $var) {
            if (!isset($_REQUEST[$var]) && !$values) {
                $params[$var] = '';
                continue;
            }

            if ($values && isset($values[$var])) {
                $value = $values[$var];
            } else {
                $value = $_REQUEST[$var];
            }

            if (is_object($value)) {
                $params[$var] = $value;
                continue;
            }

            $params[$var] = $value;
        }

        QUI::getEvents()->fireEvent('ajaxCallBefore', array(
            'function' => $_rf,
            'params'   => $params
        ));

        try {
            if (isset(self::$callables[$_rf])) {
                $return = array(
                    'result' => call_user_func_array(
                        self::$callables[$_rf]['callable'],
                        $params
                    )
                );
            } else {
                $return = array(
                    'result' => call_user_func_array($_rf, $params)
                );
            }
        } catch (QUI\Exception $Exception) {
            return $this->writeException($Exception);
        } catch (\PDOException $Exception) {
            return $this->writeException($Exception);
        }


        QUI::getEvents()->fireEvent('ajaxCall', array(
            'function' => $_rf,
            'result'   => $return,
            'params'   => $params
        ));


        // json errors bekommen
        if (function_exists('json_last_error')) {
            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    // alles ok
                    break;

                case JSON_ERROR_DEPTH:
                case JSON_ERROR_STATE_MISMATCH:
                case JSON_ERROR_CTRL_CHAR:
                case JSON_ERROR_SYNTAX:
                case JSON_ERROR_UTF8:
                default:
                    QUI\System\Log::addError(
                        'JSON Error: ' .
                        json_last_error() . ' :: ' .
                        print_r($return, true)
                    );
                    break;
            }
        }

        return $return;
    }

    /**
     * Add a JavaScript callback function to the request
     *
     * @param string $javascriptFunctionName - name of the javascript callback function
     * @param array $params - optional, params for the javascript callback function
     */
    public function triggerGlobalJavaScriptCallback($javascriptFunctionName, $params = array())
    {
        if (is_string($javascriptFunctionName)) {
            $this->jsCallbacks[$javascriptFunctionName] = $params;
        }
    }

    /**
     * Exceptions xml / json return
     *
     * @param \QUI\Exception|\PDOException $Exception
     *
     * @return array
     */
    public function writeException($Exception)
    {
        $return = array();
        $class  = get_class($Exception);

        switch ($class) {
            case 'PDOException':
            case 'QUI\\Database\\Exception':
                // DB Fehler immer loggen
                System\Log::writeException($Exception);

                if ($this->getAttribute('db_errors')) {
                    $return['ExceptionDBError']['message'] = $Exception->getMessage();
                    $return['ExceptionDBError']['code']    = $Exception->getCode();
                    $return['ExceptionDBError']['type']    = get_class($Exception);
                } else {
                    // Standardfehler rausbringen
                    $return['Exception']['message'] = 'Internal Server Error';
                    $return['Exception']['code']    = 500;
                    $return['Exception']['type']    = get_class($Exception);
                }

                if ((DEVELOPMENT || DEBUG_MODE) && $class != 'PDOException') {
                    $return['Exception']['context'] = $Exception->getContext();
                }

                break;

            case 'QUI\\ExceptionStack':
                /* @var $Exception \QUI\ExceptionStack */
                $list = $Exception->getExceptionList();

                if (isset($list[0])) {
                    /* @var $FirstException \QUI\Exception */
                    $FirstException = $list[0];
                    // method nicht mit ausgeben
                    $message = $FirstException->getMessage();
                    $message = mb_substr($message, 0, mb_strripos($message, ' :: '));

                    $return['Exception']['message'] = $message;
                    $return['Exception']['code']    = $FirstException->getCode();
                    $return['Exception']['type']    = $FirstException->getType();

                    if (DEVELOPMENT || DEBUG_MODE) {
                        $return['Exception']['context'] = $FirstException->getContext();
                    }
                }

                break;

            case 'QUI\\Exception':
                $return['Exception']['message'] = $Exception->getMessage();
                $return['Exception']['code']    = $Exception->getCode();
                $return['Exception']['type']    = $Exception->getType();

                if (DEVELOPMENT || DEBUG_MODE) {
                    $return['Exception']['context'] = $Exception->getContext();
                }

                break;

            default:
                $return['Exception']['message'] = $Exception->getMessage();
                $return['Exception']['code']    = $Exception->getCode();
                $return['Exception']['type']    = get_class($Exception);
                break;
        }

        return $return;
    }

    /**
     * Ajax Timeout handling
     */
    public function onShutdown()
    {
        switch (connection_status()) {
            case 2: // timeout #locale
                $return = array(
                    'Exception' => array(
                        'message' => 'Zeitüberschreitung der Anfrage.' .
                                     'Bitte versuchen Sie es erneut oder zu einem späteren Zeitpunkt.',
                        'code'    => 504
                    )
                );

                echo '<quiqqer>' . json_encode($return) . '</quiqqer>';
                break;
        }
    }
}
