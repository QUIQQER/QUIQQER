<?php

/**
 * This file contains \QUI\Ajax
 */

namespace QUI;

use PDOException;
use QUI;

use function array_filter;
use function array_slice;
use function call_user_func;
use function call_user_func_array;
use function connection_status;
use function count;
use function defined;
use function explode;
use function function_exists;
use function is_array;
use function is_bool;
use function is_callable;
use function is_numeric;
use function is_object;
use function is_string;
use function json_decode;
use function json_encode;
use function json_last_error;
use function mb_strripos;
use function mb_substr;
use function md5;
use function method_exists;
use function strip_tags;
use function utf8_encode;

/**
 * QUIQQER Ajax
 * Communication between JavaScript and PHP
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Ajax extends QUI\QDOM
{
    /**
     * Available ajax functions
     */
    protected static array $functions = [];

    /**
     * Available ajax lambda functions
     */
    protected static array $callables = [];

    /**
     * registered permissions from available ajax functions
     */
    protected static array $permissions = [];

    /**
     * javascript functions to be executed by after a request
     * These functions are registered via Ajax.registerCallback('functionName', callable);
     */
    protected array $jsCallbacks = [];

    public function __construct(array $params = [])
    {
        self::setAttributes($params);

        // Shutdown Handling
        $ErrorHandler = QUI::getErrorHandler();

        try {
            $ErrorHandler->registerShutdown($this->onShutdown(...));
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * Registered a function which is available via ajax
     *
     * @param string $reg_function - Function which is callable via ajax
     * @param boolean|array $reg_vars - Variables of the function
     * @param bool|string $user_perm - rights, optional
     *
     * @return bool
     */
    public static function register(
        string $reg_function,
        bool|array $reg_vars = [],
        bool|string $user_perm = false
    ): bool {
        if (!is_array($reg_vars)) {
            $reg_vars = [];
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
     * @param bool|array $reg_vars - Variables of the function
     * @param bool|array|string $user_perm - (optional) permissions / rights
     *
     * @return bool
     */
    public static function registerFunction(
        string $name,
        callable $function,
        bool|array $reg_vars = [],
        bool|array|string $user_perm = false
    ): bool {
        if (!is_callable($function)) {
            return false;
        }

        if (!is_array($reg_vars)) {
            $reg_vars = [];
        }

        self::$callables[$name] = [
            'callable' => $function,
            'params' => $reg_vars
        ];

        if ($user_perm) {
            self::$permissions[$name] = $user_perm;
        }

        return true;
    }

    public static function getRegisteredFunctions(): array
    {
        return self::$functions;
    }

    public static function getRegisteredCallables(): array
    {
        return self::$callables;
    }

    /**
     * ajax processing
     *
     * @return string|array - quiqqer XML
     * @throws Exception
     */
    public function call(): array|string
    {
        if (
            !isset($_REQUEST['_rf'])
            || !is_string($_REQUEST['_rf']) && count($_REQUEST['_rf']) > 1
        ) {
            return $this->writeException(
                new Exception('Bad Request', 400)
            );
        }

        $_rfs = json_decode($_REQUEST['_rf'], true);
        $result = [];

        if (!is_array($_rfs)) {
            $_rfs = [$_rfs];
        }

        foreach ($_rfs as $_rf) {
            $result[$_rf] = $this->callRequestFunction($_rf);
        }

        QUI::getSession()->getSymfonySession()->save();

        $result['message_handler'] = QUI::getMessagesHandler()->getMessagesAsArray(
            QUI::getUserBySession()
        );

        // maintenance flag
        $result['maintenance'] = QUI::conf('globals', 'maintenance') ? 1 : 0;
        $result['jsCallbacks'] = $this->jsCallbacks;
        $result['vMd5'] = md5(QUI::version());

        QUI::getEvents()->fireEvent('ajaxResult', [&$result]);

        $encoded = json_encode($result);

        $utf8ize = static function ($mixed) use (&$utf8ize) {
            if (is_string($mixed)) {
                return utf8_encode($mixed);
            }

            if (is_array($mixed)) {
                foreach ($mixed as $key => $value) {
                    $mixed[$key] = $utf8ize($value);
                }
            }

            return $mixed;
        };

        // json errors bekommen
        if (function_exists('json_last_error')) {
            if (json_last_error() == JSON_ERROR_UTF8) {
                $encoded = json_encode($utf8ize($result));
            }

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
                        print_r($encoded, true)
                    );
                    break;
            }
        }

        return '<quiqqer>' . $encoded . '</quiqqer>';
    }

    /**
     * Exceptions xml / json return
     */
    public function writeException(PDOException|\Exception|Exception $Exception): array
    {
        $return = [];
        $class = $Exception::class;

        $data = [];

        if (method_exists($Exception, 'toArray')) {
            $data = $Exception->toArray();
        }

        $attributes = array_filter($data, static function ($v, $k): bool {
            return match ($k) {
                'message', 'code', 'type', 'context' => false,
                default => is_string($v) || is_array($v) || is_numeric($v) || is_bool($v),
            };
        }, ARRAY_FILTER_USE_BOTH);

        switch ($class) {
            case 'PDOException':
            case \QUI\Database\Exception::class:
                // DB Fehler immer loggen
                if ($this->getAttribute('db_errors')) {
                    $return['ExceptionDBError']['message'] = $Exception->getMessage();
                    $return['ExceptionDBError']['code'] = $Exception->getCode();
                    $return['ExceptionDBError']['type'] = $Exception::class;
                } else {
                    // Standardfehler rausbringen
                    $return['Exception']['message'] = 'Internal Server Error';
                    $return['Exception']['code'] = 500;
                    $return['Exception']['type'] = $Exception::class;
                }

                if (
                    (DEVELOPMENT || DEBUG_MODE)
                    && $class !== 'PDOException'
                    && method_exists($Exception, 'getContext')
                ) {
                    $return['Exception']['context'] = $Exception->getContext();
                }

                break;

            case ExceptionStack::class:
                $list = [];

                if (method_exists($Exception, 'getExceptionList')) {
                    $list = $Exception->getExceptionList();
                }

                if (isset($list[0]) && $list[0] instanceof Exception) {
                    $FirstException = $list[0];
                    $message = $FirstException->getMessage();
                    $end = mb_strripos($message, ' :: ');

                    if ($end) {
                        $message = mb_substr($message, 0, $end);
                    }


                    $return['Exception']['message'] = $message;
                    $return['Exception']['code'] = $FirstException->getCode();
                    $return['Exception']['type'] = $FirstException->getType();

                    if (DEVELOPMENT || DEBUG_MODE) {
                        $return['Exception']['context'] = $FirstException->getContext();
                    }
                }

                break;

            case Exception::class:
            case QUI\Users\Exception::class:
                $return['Exception']['message'] = $Exception->getMessage();
                $return['Exception']['code'] = $Exception->getCode();
                $return['Exception']['type'] = get_class($Exception);

                if ((DEVELOPMENT || DEBUG_MODE) && method_exists($Exception, 'getContext')) {
                    $return['Exception']['context'] = $Exception->getContext();
                }

                break;

            default:
                $return['Exception']['message'] = $Exception->getMessage();
                $return['Exception']['code'] = $Exception->getCode();
                $return['Exception']['type'] = $Exception::class;
                break;
        }

        if ($Exception instanceof QUI\Users\UserAuthException) {
            // do nothing
            // UserAuthException writes its own log (auth.log)
        } elseif ($class === \QUI\Permissions\Exception::class) {
            QUI\System\Log::addInfo($Exception->getMessage());
        } else {
            QUI\System\Log::writeDebugException($Exception);
        }

        $return['Exception']['attributes'] = $attributes;

        // strip tags
        $return['Exception']['message'] = strip_tags(
            $return['Exception']['message'],
            '<div><span><p><br><hr><ul><ol><li><strong><em><b><i><u>'
        );

        return $return;
    }

    /**
     * Internal call of an ajax function
     */
    public function callRequestFunction(string $_rf, mixed $values = false): array
    {
        if (!isset(self::$functions[$_rf]) && !isset(self::$callables[$_rf])) {
            if (defined('DEVELOPMENT') && DEVELOPMENT) {
                System\Log::addDebug('Funktion ' . $_rf . ' nicht gefunden');
            }

            return $this->writeException(
                new Exception('Bad Request', 400)
            );
        }

        // Rechte prüfung
        try {
            self::checkPermissions($_rf);
        } catch (\Exception $Exception) {
            return $this->writeException($Exception);
        }


        // Request vars
        if (isset($_REQUEST['pcsg_uri'])) {
            $_SERVER['REQUEST_URI'] = $_REQUEST['pcsg_uri'];
        }

        // Params
        $params = [];

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

            $value = '';

            if ($values && isset($values[$var])) {
                $value = $values[$var];
            } elseif (isset($_REQUEST[$var])) {
                $value = $_REQUEST[$var];
            }

            if (is_object($value)) {
                $params[$var] = $value;
                continue;
            }

            $params[$var] = $value;
        }

        try {
            QUI::getEvents()->fireEvent('ajaxCallBefore', [
                'function' => $_rf,
                'params' => $params
            ]);
        } catch (\Exception $Exception) {
            return $this->writeException($Exception);
        }

        try {
            if (isset(self::$callables[$_rf])) {
                $return = [
                    'result' => call_user_func_array(
                        self::$callables[$_rf]['callable'],
                        $params
                    )
                ];
            } else {
                $return = [
                    'result' => call_user_func_array($_rf, $params)
                ];
            }
        } catch (\Exception $Exception) {
            return $this->writeException($Exception);
        }

        try {
            QUI::getEvents()->fireEvent('ajaxCall', [
                'function' => $_rf,
                'result' => $return,
                'params' => $params
            ]);
        } catch (\Exception $Exception) {
            return $this->writeException($Exception);
        }

        return $return;
    }

    /**
     * Checks the rights if a function has a checkPermissions routine
     *
     * @throws Exception
     * @throws \QUI\Permissions\Exception
     */
    public static function checkPermissions(callable|string $reg_function): void
    {
        if (!isset(self::$permissions[$reg_function])) {
            return;
        }

        $function = self::$permissions[$reg_function];

        if (is_object($function) && $function::class === 'Closure') {
            $function();

            return;
        }

        if (QUI::isBackend()) {
            $parts = explode('_', $reg_function);
            $pluginParts = array_slice($parts, 1, 2);

            if (isset($pluginParts[0]) && isset($pluginParts[1])) {
                try {
                    $Package = null;
                    $Package = QUI::getPackage($pluginParts[0] . '/' . $pluginParts[1]);
                } catch (Exception) {
                }

                $Package?->hasPermission();
            }
        }

        if (is_string($function)) {
            $function = [$function];
        }

        foreach ($function as $func) {
            // if it is a real permission
            if (!str_contains($func, '::')) {
                Permissions\Permission::checkPermission($func);

                return;
            }

            if (str_starts_with($func, 'Permission')) {
                $func = '\\QUI\Permissions\\' . $func;
            }

            if (!is_callable($func)) {
                throw new QUI\Permissions\Exception('Permission denied', 503);
            }

            call_user_func($func);
        }
    }

    /**
     * Add a JavaScript callback function to the request
     *
     * @param string $javascriptFunctionName - name of the javascript callback function
     * @param array $params - optional, params for the javascript callback function
     */
    public function triggerGlobalJavaScriptCallback(string $javascriptFunctionName, array $params = []): void
    {
        $this->jsCallbacks[$javascriptFunctionName] = $params;
    }

    /**
     * Ajax Timeout handling
     */
    public function onShutdown(): void
    {
        if (connection_status() == 2) {
            $return = [
                'Exception' => [
                    'message' => QUI::getLocale()->get('quiqqer/core', 'exception.timeout'),
                    'code' => 504
                ]
            ];

            echo '<quiqqer>' . json_encode($return) . '</quiqqer>';
        }
    }

    public function getJsCallbacks(): array
    {
        return $this->jsCallbacks;
    }
}
