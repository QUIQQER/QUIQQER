<?php

/**
 * This file contains \QUI\System\Log
 */

namespace QUI\System;

use QUI;

/**
 * Writes Logs into the logdir
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 * @package QUI\System
 * @need quiqqr/log
 */
class Log
{
    const LEVEL_DEBUG = 100;
    const LEVEL_INFO = 200;
    const LEVEL_NOTICE = 250;
    const LEVEL_WARNING = 300;
    const LEVEL_ERROR = 400;
    const LEVEL_CRITICAL = 500;
    const LEVEL_ALERT = 550;
    const LEVEL_EMERGENCY = 600;

    /**
     * Return the log name by a log level
     *
     * @param integer $LogLevel - Log Level
     *
     * @return string
     */
    static function levelToLogName($LogLevel)
    {
        switch ($LogLevel) {
            case self::LEVEL_DEBUG     :
                return 'debug';
            case self::LEVEL_INFO      :
                return 'info';
            case self::LEVEL_NOTICE    :
                return 'notice';
            case self::LEVEL_WARNING   :
                return 'warning';
            case self::LEVEL_ERROR     :
                return 'error';
            case self::LEVEL_CRITICAL  :
                return 'critical';
            case self::LEVEL_ALERT     :
                return 'alert';
            case self::LEVEL_EMERGENCY :
                return 'emergency';
        }

        return 'error';
    }

    /**
     * Writes a string to a log file
     *
     * @param string $message - string to write
     * @param integer $loglevel - loglevel ( \QUI\System\Log::LEVEL_ERROR ... )
     * @param array $context - context data
     * @param string|boolean $filename - [optional] name of the log eq: messages, database,
     *
     * @example \QUI\System\Log::write( 'My Error', \QUI\System\Log::LEVEL_ERROR );
     */
    static function write(
        $message,
        $loglevel = self::LEVEL_INFO,
        $context = array(),
        $filename = false
    ) {
        $Logger = QUI\Log\Logger::getLogger();
        $User   = QUI::getUserBySession();

        if (isset($_SERVER['REQUEST_URI'])
            && !empty($_SERVER['REQUEST_URI'])
        ) {
            $context['request'] = HOST . $_SERVER['REQUEST_URI'];
        }

        $context['errorFilename'] = $filename;
        $context['userId']        = $User->getId();
        $context['username']      = $User->getUsername();


        switch (self::levelToLogName($loglevel)) {
            case 'debug':
                $Logger->addDebug($message, $context);
                break;

            case 'info':
                $Logger->addInfo($message, $context);
                break;

            case 'notice':
                $Logger->addNotice($message, $context);
                break;

            case 'warning':
                $Logger->addWarning($message, $context);
                break;

            case 'critical':
                $Logger->addCritical($message, $context);
                break;

            case 'alert':
                $Logger->addAlert($message, $context);
                break;

            case 'emergency':
                $Logger->addEmergency($message, $context);
                break;

            case 'error':
            default:
                $Logger->addError($message, $context);
        }
    }

    /**
     * Writes with print_r the object into a log file
     *
     * @param object|string|integer|array $object
     * @param integer $loglevel - loglevel ( \QUI\System\Log::LEVEL_ERROR ... )
     * @param array $context - context data
     * @param string|boolean $filename - [optional] name of the log eq: messages, database,
     */
    static function writeRecursive(
        $object,
        $loglevel = self::LEVEL_INFO,
        $context = array(),
        $filename = false
    ) {
        self::write(print_r($object, true), $loglevel, $context, $filename);
    }

    /**
     * Writes an Exception to a log file
     *
     * @param \Exception $Exception
     * @param integer $loglevel - loglevel ( \QUI\System\Log::LEVEL_ERROR ... )
     * @param array $context - context data
     * @param string|boolean $filename - [optional] name of the log eq: messages, database,
     */
    static function writeException(
        $Exception,
        $loglevel = self::LEVEL_ERROR,
        $context = array(),
        $filename = false
    ) {
        $message = $Exception->getCode() . " :: \n\n";
        $message .= $Exception->getMessage();
        $message .= $Exception->getTraceAsString();

        self::write($message, $loglevel, $context, $filename);
    }

    /**
     * Adds a log record at the DEBUG level.
     *
     * @param string $message The log message
     * @param array $context - context data
     * @param string|boolean $filename - [optional] name of the log eq: messages, database (default = error)
     */
    static function addDebug($message, $context = array(), $filename = false)
    {
        self::write($message, self::LEVEL_DEBUG, $context, $filename);
    }

    /**
     * Adds a log record at the INFO level.
     *
     * @param string $message The log message
     * @param array $context - context data
     * @param string|boolean $filename - [optional] name of the log eq: messages, database (default = error)
     */
    static function addInfo($message, $context = array(), $filename = false)
    {
        self::write($message, self::LEVEL_INFO, $context, $filename);
    }

    /**
     * Adds a log record at the NOTICE level.
     *
     * @param string $message The log message
     * @param array $context - context data
     * @param string|boolean $filename - [optional] name of the log eq: messages, database (default = error)
     */
    static function addNotice($message, $context = array(), $filename = false)
    {
        self::write($message, self::LEVEL_NOTICE, $context, $filename);
    }

    /**
     * Adds a log record at the WARNING level.
     *
     * @param string $message The log message
     * @param array $context - context data
     * @param string|boolean $filename - [optional] name of the log eq: messages, database (default = error)
     */
    static function addWarning($message, $context = array(), $filename = false)
    {
        self::write($message, self::LEVEL_WARNING, $context, $filename);
    }

    /**
     * Adds a log record at the ERROR level.
     *
     * @param string $message The log message
     * @param array $context - context data
     * @param string|boolean $filename - [optional] name of the log eq: messages, database (default = error)
     */
    static function addError($message, $context = array(), $filename = false)
    {
        self::write($message, self::LEVEL_ERROR, $context, $filename);
    }

    /**
     * Adds a log record at the CRITICAL level.
     *
     * @param string $message The log message
     * @param array $context - context data
     * @param string|boolean $filename - [optional] name of the log eq: messages, database (default = error)
     */
    static function addCritical($message, $context = array(), $filename = false)
    {
        self::write($message, self::LEVEL_CRITICAL, $context, $filename);
    }

    /**
     * Adds a log record at the ALERT level.
     *
     * @param string $message The log message
     * @param array $context - context data
     * @param string|boolean $filename - [optional] name of the log eq: messages, database (default = error)
     */
    static function addAlert($message, $context = array(), $filename = false)
    {
        self::write($message, self::LEVEL_ALERT, $context, $filename);
    }

    /**
     * Adds a log record at the EMERGENCY level.
     *
     * @param string $message The log message
     * @param array $context - context data
     * @param string|boolean $filename - [optional] name of the log eq: messages, database (default = error)
     */
    static function addEmergency($message, $context = array(), $filename = false)
    {
        self::write($message, self::LEVEL_EMERGENCY, $context, $filename);
    }
}
