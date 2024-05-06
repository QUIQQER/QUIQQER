<?php

/**
 * This file contains \QUI\Exceptions\Handler
 */

namespace QUI\Exceptions;

use QUI;

use function array_shift;
use function call_user_func_array;
use function class_exists;
use function date;
use function debug_print_backtrace;
use function defined;
use function error_log;
use function file_exists;
use function file_put_contents;
use function func_get_args;
use function gethostbyaddr;
use function is_callable;
use function ob_end_clean;
use function ob_get_contents;
use function ob_start;
use function print_r;
use function register_shutdown_function;

/**
 * Exception and Error Manager
 *
 * Exception manager capture php generated errors or an exception, that wasn't caught
 * You can define the error level, which error level would be logged
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
    protected array $shutDownCallbacks = [];

    /**
     * constructor
     *
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        // defaults
        $this->setAttribute('logdir', '');
        $this->setAttribute('show_request', false);
        $this->setAttribute('backtrace', false);

        // default error handling
        $this->setAttribute('ERROR_1', true); // 1          E_ERROR
        $this->setAttribute('ERROR_2', true); // 2          E_WARNING
        $this->setAttribute('ERROR_4', true); // 4          E_PARSE
        $this->setAttribute('ERROR_8', true); // 8          E_NOTICE
        $this->setAttribute('ERROR_16', true); // 16        E_CORE_ERROR
        $this->setAttribute('ERROR_32', true); // 32        E_CORE_WARNING
        $this->setAttribute('ERROR_64', true); // 64        E_COMPILE_ERROR
        $this->setAttribute('ERROR_128', true); // 128      E_COMPILE_WARNING
        $this->setAttribute('ERROR_256', true); // 256      E_USER_ERROR
        $this->setAttribute('ERROR_512', true); // 512      E_USER_WARNING
        $this->setAttribute('ERROR_1024', true); // 1024    E_USER_NOTICE
        $this->setAttribute('ERROR_2048', true); // 2048    E_STRICT

        $this->setAttribute('ERROR_6143', true); // 6143    E_ALL
        $this->setAttribute('ERROR_2048', true); // 2048    E_STRICT
        $this->setAttribute('ERROR_4096', true); // 4096    E_RECOVERABLE_ERROR
        $this->setAttribute('ERROR_8192', true); // 8192    E_DEPRECATED
        $this->setAttribute('ERROR_16384', true); // 16384  E_USER_DEPRECATED

        $this->setAttributes($params);

        register_shutdown_function([$this, "callShutdown"]);
    }

    /**
     * Register shutdown functions
     *
     * @return boolean
     * @throws QUI\Exception
     * @example QUI\ExceptionHandler->registerShutdown('function', 'param');
     * QUI\ExceptionHandler->registerShutdown(array($Object, 'dynamicMethod'));
     * QUI\ExceptionHandler->registerShutdown('class::staticMethod');
     *
     */
    public function registerShutdown(): bool
    {
        $callback = func_get_args();

        if (empty($callback)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.lib.qui.exceptions.handler.nocallback',
                    ['function' => __FUNCTION__]
                ),
                E_USER_ERROR
            );
        }

        if (!is_callable($callback[0])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'exception.lib.qui.exceptions.handler.invalid',
                    ['function' => __FUNCTION__]
                ),
                E_USER_ERROR
            );
        }

        $this->shutDownCallbacks[] = $callback;

        return true;
    }

    /**
     * Call all shutdown functions
     */
    public function callShutdown(): void
    {
        $callbacks = $this->shutDownCallbacks;

        foreach ($callbacks as $arguments) {
            $callback = array_shift($arguments);
            call_user_func_array($callback, $arguments);
        }
    }

    /**
     * Writes the error to the log
     *
     * @param integer $errno - error code
     * @param string $errStr - error
     * @param string $errFile - (optional) Datei in welcher der Fehler auftaucht
     * @param integer|string $errLine - (optional) Zeile in welcher der Fehler auftaucht
     */
    public function writeErrorToLog(
        int $errno,
        string $errStr,
        string $errFile = '',
        int|string $errLine = ''
    ): void {
        if (!$this->getAttribute('ERROR_' . $errno)) {
            return;
        }

        $log = false;

        if ($this->getAttribute('logdir')) {
            $log = $this->getAttribute('logdir') . 'error' . date('-Y-m-d') . '.log';

            // Log Verzeichnis erstellen
            QUI\Utils\System\File::mkdir($this->getAttribute('logdir'));
        }

        if ($log && !file_exists($log)) {
            file_put_contents($log, ' ');
        }

        $err_msg = "\n\n==== Date: " . date('Y-m-d H:i:s')
            . " ============================================\n";

        if ($this->getAttribute('show_request')) {
            if (isset($_SERVER['REQUEST_URI'])) {
                $err_msg .= 'REQUEST URI: ' . $_SERVER['REQUEST_URI'] . "\n";
            }

            if (isset($_SERVER['HTTP_HOST'])) {
                $err_msg .= 'HTTP_HOST: ' . $_SERVER['HTTP_HOST'] . "\n";
            }

            if (isset($_SERVER['REMOTE_ADDR'])) {
                $err_msg .= 'REMOTE_ADDR: ' . $_SERVER['REMOTE_ADDR'] . "\n";
            }

            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $err_msg .= 'HTTP_USER_AGENT: ' . $_SERVER['HTTP_USER_AGENT']
                    . "\n";
            }

            if (isset($_REQUEST['_url'])) {
                $err_msg .= '$_REQUEST[\'_url\']: ' . $_REQUEST['_url'] . "\n";
            }

            unset($_REQUEST['REQUEST_URI']);
            unset($_REQUEST['HTTP_HOST']);
            unset($_REQUEST['REMOTE_ADDR']);
            unset($_REQUEST['HTTP_USER_AGENT']);
            unset($_REQUEST['_url']);

            $err_msg .= '$_REQUEST: ' . print_r($_REQUEST, true) . "\n\n";
        }

        $err_msg .= "\nMessage:\n" . $errStr . "\n";

        if ($errno) {
            $err_msg .= "Error No: ERROR_" . $errno . "\n";
        }

        if ($errFile) {
            $err_msg .= "Error File:" . $errFile . "\n";
        }

        if ($errLine) {
            $err_msg .= "Error Line:" . $errLine . "\n";
        }

        // Nutzerdaten
        if (isset($_SERVER['SERVER_ADDR'])) {
            $err_msg .= "IP: " . $_SERVER['SERVER_ADDR'] . "\n";
            $err_msg .= "Host: " . gethostbyaddr($_SERVER['SERVER_ADDR']) . "\n";
        }

        // Backtrace
        if ($this->getAttribute('backtrace')) {
            ob_start();
            debug_print_backtrace();
            $buffer = ob_get_contents();
            ob_end_clean();

            $err_msg .= "\n BACKTRACE\n\n" . $buffer . "\n";
        }


        if (
            defined('ERROR_SEND')
            && defined('ERROR_MAIL')
            && class_exists('Mail')
            && ERROR_SEND
            && ERROR_MAIL
        ) {
            try {
                QUI::getMailManager()->send(
                    ERROR_MAIL,
                    QUI::getLocale()->get(
                        'quiqqer/quiqqer',
                        'lib.qui.exceptions.handler.mail.subject',
                        [
                            'host' => $_SERVER['HTTP_HOST'],
                            'url' => $_SERVER['REQUEST_URI']
                        ]
                    ),
                    $err_msg
                );
            } catch (QUI\Exception) {
            }
        }

        error_log($err_msg, 3, $log);
    }
}
