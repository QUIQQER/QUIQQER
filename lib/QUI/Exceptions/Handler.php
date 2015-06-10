<?php

/**
 * This file contains \QUI\Exceptions\Handler
 */

namespace QUI\Exceptions;

use QUI;

/**
 * Exception and Error Manager
 *
 * Exception manager capture php generated errors or an exception, that wasn't catched
 * You can define the error level, which error leben would be loged
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Handler extends QUI\QDOM
{
    /**
     * registered shutdown callback functions
     *
     * @var array
     */
    protected $_shutdowncallbacks = array();

    /**
     * constructor
     *
     * @param array $params
     */
    public function __construct($params = array())
    {
        // defaults
        $this->setAttribute('logdir', '');
        $this->setAttribute('show_request', false);
        $this->setAttribute('backtrace', false);

        // default error handling
        $this->setAttribute('ERROR_1', true); // 1			E_ERROR
        $this->setAttribute('ERROR_2', true); // 2 			E_WARNING
        $this->setAttribute('ERROR_4', true); // 4 			E_PARSE
        $this->setAttribute('ERROR_8', true); // 8 			E_NOTICE
        $this->setAttribute('ERROR_16', true); // 16 		E_CORE_ERROR
        $this->setAttribute('ERROR_32', true); // 32 		E_CORE_WARNING
        $this->setAttribute('ERROR_64', true); // 64 		E_COMPILE_ERROR
        $this->setAttribute('ERROR_128', true); // 128 		E_COMPILE_WARNING
        $this->setAttribute('ERROR_256', true); // 256 		E_USER_ERROR
        $this->setAttribute('ERROR_512', true); // 512 		E_USER_WARNING
        $this->setAttribute('ERROR_1024', true); // 1024 	E_USER_NOTICE
        $this->setAttribute('ERROR_2048', true); // 2048 	E_STRICT

        $this->setAttribute('ERROR_6143', true); // 6143 	E_ALL
        $this->setAttribute('ERROR_2048', true); // 2048 	E_STRICT
        $this->setAttribute('ERROR_4096', true); // 4096 	E_RECOVERABLE_ERROR
        $this->setAttribute('ERROR_8192', true); // 8192 	E_DEPRECATED
        $this->setAttribute('ERROR_16384', true); // 16384 	E_USER_DEPRECATED

        $this->setAttributes($params);

        register_shutdown_function(array($this, "callShutdown"));
    }

    /**
     * Register shutdown funktions
     *
     * @example QUI\ExceptionHandler->registerShutdown('function', 'param');
     * QUI\ExceptionHandler->registerShutdown(array($Object, 'dynamicMethod'));
     * QUI\ExceptionHandler->registerShutdown('class::staticMethod');
     *
     * @throws QUI\Exception
     * @return Bool
     */
    public function registerShutdown()
    {
        $callback = func_get_args();

        if (empty($callback)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.qui.exceptions.handler.nocallback',
                    array('function' => __FUNCTION__)
                ),
                E_USER_ERROR
            );
        }

        if (!is_callable($callback[0])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.qui.exceptions.handler.invalid',
                    array('function' => __FUNCTION__)
                ),
                E_USER_ERROR
            );
        }

        $this->_shutdowncallbacks[] = $callback;

        return true;
    }

    /**
     * Call all shutdown functions
     */
    public function callShutdown()
    {
        $callbacks = $this->_shutdowncallbacks;

        foreach ($callbacks as $arguments) {
            $callback = array_shift($arguments);
            call_user_func_array($callback, $arguments);
        }
    }

    /**
     * Writes the error to the log
     *
     * @param Integer        $errno   - Fehlercode
     * @param String         $errstr  - Fehler
     * @param String         $errfile - (optional) Datei in welcher der Fehler auftaucht
     * @param Integer|String $errline - (optional) Zeile in welcher der Fehler auftaucht
     */
    public function writeErrorToLog(
        $errno,
        $errstr,
        $errfile = '',
        $errline = ''
    ) {
        if ($this->getAttribute('ERROR_'.$errno) == false) {
            return;
        }

        $log = false;

        if ($this->getAttribute('logdir')) {
            $log = $this->getAttribute('logdir').'error'.date('-Y-m-d').'.log';

            // Log Verzeichnis erstellen
            QUI\Utils\System\File::mkdir($this->getAttribute('logdir'));
        }

        if ($log && !file_exists($log)) {
            file_put_contents($log, ' ');
        }

        $err_msg = "\n\n==== Date: ".date('Y-m-d H:i:s')
            ." ============================================\n";

        if ($this->getAttribute('show_request')) {
            if (isset($_SERVER['REQUEST_URI'])) {
                $err_msg .= 'REQUEST URI: '.$_SERVER['REQUEST_URI']."\n";
            }

            if (isset($_SERVER['HTTP_HOST'])) {
                $err_msg .= 'HTTP_HOST: '.$_SERVER['HTTP_HOST']."\n";
            }

            if (isset($_SERVER['REMOTE_ADDR'])) {
                $err_msg .= 'REMOTE_ADDR: '.$_SERVER['REMOTE_ADDR']."\n";
            }

            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $err_msg .= 'HTTP_USER_AGENT: '.$_SERVER['HTTP_USER_AGENT']
                    ."\n";
            }

            if (isset($_REQUEST['_url'])) {
                $err_msg .= '$_REQUEST[\'_url\']: '.$_REQUEST['_url']."\n";
            }

            unset($_REQUEST['REQUEST_URI']);
            unset($_REQUEST['HTTP_HOST']);
            unset($_REQUEST['REMOTE_ADDR']);
            unset($_REQUEST['HTTP_USER_AGENT']);
            unset($_REQUEST['_url']);

            $err_msg .= '$_REQUEST: '.print_r($_REQUEST, true)."\n\n";
        }

        $err_msg .= "\nMessage:\n".$errstr."\n";

        if ($errno) {
            $err_msg .= "Error No: ERROR_".$errno."\n";
        }

        if ($errfile) {
            $err_msg .= "Error File:".$errfile."\n";
        }

        if ($errline) {
            $err_msg .= "Error Line:".$errline."\n";
        }

        // Nutzerdaten
        if (isset($_SERVER['SERVER_ADDR'])) {
            $err_msg .= "IP: ".$_SERVER['SERVER_ADDR']."\n";
            $err_msg .= "Host: ".gethostbyaddr($_SERVER['SERVER_ADDR'])."\n";
        }

        // Backtrace
        if ($this->getAttribute('backtrace')) {
            ob_start();
            debug_print_backtrace();
            $buffer = ob_get_contents();
            ob_end_clean();

            $err_msg .= "\n BACKTRACE\n\n".$buffer."\n";
        }


        if (defined('ERROR_SEND') && ERROR_SEND && defined('ERROR_MAIL')
            && ERROR_MAIL
            && class_exists('Mail')
        ) {
            \QUI::getMailManager()->send(
                ERROR_MAIL,
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'lib.qui.exceptions.handler.mail.subject',
                    array(
                        'host' => $_SERVER['HTTP_HOST'],
                        'url'  => $_SERVER['REQUEST_URI']
                    )
                ),
                $err_msg
            );
        }

        error_log($err_msg, 3, $log);
    }
}
