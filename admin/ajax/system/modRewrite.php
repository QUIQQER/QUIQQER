<?php

/**
 * Works ModRewrite?
 *
 * @return integer
 */

QUI::$Ajax->registerFunction(
    'ajax_system_modRewrite',
    static function (): int {
        // quiqqer check
        if (\array_key_exists('HTTP_MOD_REWRITE', $_SERVER)) {
            return 1;
        }

        if (\getenv('HTTP_MOD_REWRITE') == 'On') {
            return 1;
        }

        // test with apache modules
        if (\function_exists('apache_get_modules') && \in_array('mod_rewrite', \apache_get_modules())) {
            return 1;
        }

        // phpinfo test
        \ob_start();
        \phpinfo();
        $phpinfo = \ob_get_contents();
        \ob_end_clean();

        if (str_contains('mod_rewrite', $phpinfo)) {
            return 1;
        }

        return 0;
    },
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
