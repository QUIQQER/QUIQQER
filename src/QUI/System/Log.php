<?php

/**
 * This file contains \QUI\System\Log
 */

namespace QUI\System;

use Exception;
use QUI;

use const DEBUG_MODE;

/**
 * Writes Logs into the log dir
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 * @need quiqqer/log
 */
class Log
{
    const LEVEL_DEBUG = 100;
    const LEVEL_DEPRECATED = 150;
    const LEVEL_INFO = 200;
    const LEVEL_NOTICE = 250;
    const LEVEL_WARNING = 300;
    const LEVEL_ERROR = 400;
    const LEVEL_CRITICAL = 500;
    const LEVEL_ALERT = 550;
    const LEVEL_EMERGENCY = 600;

    /**
     * Writes with print_r the object into a log file
     *
     * @param object|array|integer|string $object
     * @param integer $logLevel - Log-Level ( \QUI\System\Log::LEVEL_ERROR ... )
     * @param array $context - context data
     * @param boolean|string $filename - [optional] name of the log eq: messages, database
     * @param boolean $force - [optional] if true: log in any case, no matter which settings
     */
    public static function writeRecursive(
        object|array|int|string $object,
        int $logLevel = self::LEVEL_INFO,
        array $context = [],
        bool|string $filename = false,
        bool $force = false
    ): void {
        self::write(print_r($object, true), $logLevel, $context, $filename, $force);
    }

    /**
     * Writes a string to a log file
     *
     * @param string $message - string to write
     * @param integer $logLevel - loglevel ( \QUI\System\Log::LEVEL_ERROR ... )
     * @param array $context - context data
     * @param boolean|string $filename - [optional] name of the log eq: messages, database,
     * @param boolean $force - [optional] if true: log in any case, no matter which settings
     *
     * @example \QUI\System\Log::write( 'My Error', \QUI\System\Log::LEVEL_ERROR );
     */
    public static function write(
        string $message,
        int $logLevel = self::LEVEL_INFO,
        array $context = [],
        bool|string $filename = false,
        bool $force = false
    ): void {
        $Logger = QUI\Log\Logger::getLogger();
        $levels = QUI\Log\Logger::$logLevels;

        $logLevelName = self::levelToLogName($logLevel);

        if (
            $force === false
            && isset($levels[$logLevelName])
            && (int)$levels[$logLevelName] === 0
        ) {
            return;
        }

        if (!empty($_SERVER['REQUEST_URI'])) {
            $context['request'] = HOST . $_SERVER['REQUEST_URI'];
        }

        if (isset($_REQUEST['quiqqerBundle'])) {
            $context['ajaxBundler'] = $_REQUEST['quiqqerBundle'];
        }

        $context['errorFilename'] = $filename;
        $context['IP'] = QUI\Utils\System::getClientIP();

        if (defined('QUIQQER_SESSION_STARTED')) {
            $User = QUI::getUserBySession();
            $context['userId'] = $User->getUUID();
            $context['username'] = $User->getUsername();
        }

        if ($filename) {
            $context['filename'] = $filename;
        }

        if ($logLevel === self::LEVEL_DEPRECATED) {
            $context['filename'] = 'deprecated';
        }

        match ($logLevelName) {
            'debug' => $Logger->debug($message, $context),
            'info' => $Logger->info($message, $context),
            'notice' => $Logger->notice($message, $context),
            'deprecated', 'warning' => $Logger->warning($message, $context),
            'critical' => $Logger->critical($message, $context),
            'alert' => $Logger->alert($message, $context),
            'emergency' => $Logger->emergency($message, $context),
            default => $Logger->error($message, $context),
        };
    }

    /**
     * Return the log name by a log level
     *
     * @param integer $LogLevel - Log Level
     *
     * @return string
     */
    public static function levelToLogName(int $LogLevel): string
    {
        return match ($LogLevel) {
            self::LEVEL_DEBUG => 'debug',
            self::LEVEL_DEPRECATED => 'deprecated',
            self::LEVEL_INFO => 'info',
            self::LEVEL_NOTICE => 'notice',
            self::LEVEL_WARNING => 'warning',
            self::LEVEL_ERROR => 'error',
            self::LEVEL_CRITICAL => 'critical',
            self::LEVEL_ALERT => 'alert',
            self::LEVEL_EMERGENCY => 'emergency',
            default => 'error',
        };
    }

    /**
     * Writes an Exception to a log file
     *
     * @param Exception|QUI\Exception $Exception
     * @param integer $logLevel - loglevel ( \QUI\System\Log::LEVEL_ERROR ... )
     * @param array $context - context data
     * @param boolean|string $filename - [optional] name of the log eq: messages, database
     * @param boolean $force - [optional] if true: log in any case, no matter which settings
     */
    public static function writeException(
        Exception|QUI\Exception $Exception,
        int $logLevel = self::LEVEL_ERROR,
        array $context = [],
        bool|string $filename = false,
        bool $force = false
    ): void {
        $message = $Exception->getCode() . " :: \n\n";

        if (method_exists($Exception, 'getContext')) {
            $message .= print_r($Exception->getContext(), true) . "\n\n";
        }

        $message .= $Exception->getMessage() . "\n";
        $message .= $Exception->getTraceAsString();

        self::write($message, $logLevel, $context, $filename, $force);
    }

    /**
     * Writes an Exception to a log file
     *
     * @param Exception|QUI\Exception $Exception
     * @param integer $logLevel - loglevel ( \QUI\System\Log::LEVEL_ERROR ... )
     * @param array $context - context data
     * @param boolean|string $filename - [optional] name of the log eq: messages, database
     * @param boolean $force - [optional] if true: log in any case, no matter which settings
     */
    public static function writeDebugException(
        Exception|QUI\Exception $Exception,
        int $logLevel = self::LEVEL_DEBUG,
        array $context = [],
        bool|string $filename = false,
        bool $force = false
    ): void {
        if (!defined('DEBUG_MODE') || DEBUG_MODE === false) {
            return;
        }

        $message = $Exception->getCode() . " :: \n\n";

        if (method_exists($Exception, 'getContext')) {
            $message .= print_r($Exception->getContext(), true) . "\n\n";
        }

        $message .= $Exception->getMessage() . "\n";
        $message .= $Exception->getTraceAsString();

        self::write($message, $logLevel, $context, $filename, $force);
    }

    /**
     * Adds a log record at the DEBUG level.
     *
     * @param string $message The log message
     * @param array $context - context data
     * @param boolean|string $filename - [optional] name of the log eq: messages, database (default = error)
     */
    public static function addDebug(string $message, array $context = [], bool|string $filename = false): void
    {
        self::write($message, self::LEVEL_DEBUG, $context, $filename);
    }

    /**
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function addDeprecated(string $message, array $context = []): void
    {
        $trace = (new QUI\Exception())->getTraceAsString();
        $trace = explode("\n", $trace);

        $context['trace'] = $trace;

        self::write($message, self::LEVEL_DEPRECATED, $context);
    }

    /**
     * Adds a log record at the INFO level.
     *
     * @param string $message The log message
     * @param array $context - context data
     * @param boolean|string $filename - [optional] name of the log eq: messages, database (default = error)
     */
    public static function addInfo(string $message, array $context = [], bool|string $filename = false): void
    {
        self::write($message, self::LEVEL_INFO, $context, $filename);
    }

    /**
     * Adds a log record at the NOTICE level.
     *
     * @param string $message The log message
     * @param array $context - context data
     * @param boolean|string $filename - [optional] name of the log eq: messages, database (default = error)
     */
    public static function addNotice(string $message, array $context = [], bool|string $filename = false): void
    {
        self::write($message, self::LEVEL_NOTICE, $context, $filename);
    }

    /**
     * Adds a log record at the WARNING level.
     *
     * @param string $message The log message
     * @param array $context - context data
     * @param boolean|string $filename - [optional] name of the log eq: messages, database (default = error)
     */
    public static function addWarning(string $message, array $context = [], bool|string $filename = false): void
    {
        self::write($message, self::LEVEL_WARNING, $context, $filename);
    }

    /**
     * Adds a log record at the ERROR level.
     *
     * @param string $message The log message
     * @param array $context - context data
     * @param boolean|string $filename - [optional] name of the log eq: messages, database (default = error)
     */
    public static function addError(string $message, array $context = [], bool|string $filename = false): void
    {
        self::write($message, self::LEVEL_ERROR, $context, $filename);
    }

    /**
     * Adds a log record at the CRITICAL level.
     *
     * @param string $message The log message
     * @param array $context - context data
     * @param boolean|string $filename - [optional] name of the log eq: messages, database (default = error)
     */
    public static function addCritical(string $message, array $context = [], bool|string $filename = false): void
    {
        self::write($message, self::LEVEL_CRITICAL, $context, $filename);
    }

    /**
     * Adds a log record at the ALERT level.
     *
     * @param string $message The log message
     * @param array $context - context data
     * @param boolean|string $filename - [optional] name of the log eq: messages, database (default = error)
     */
    public static function addAlert(string $message, array $context = [], bool|string $filename = false): void
    {
        self::write($message, self::LEVEL_ALERT, $context, $filename);
    }

    /**
     * Adds a log record at the EMERGENCY level.
     *
     * @param string $message The log message
     * @param array $context - context data
     * @param boolean|string $filename - [optional] name of the log eq: messages, database (default = error)
     */
    public static function addEmergency(string $message, array $context = [], bool|string $filename = false): void
    {
        self::write($message, self::LEVEL_EMERGENCY, $context, $filename);
    }
}
