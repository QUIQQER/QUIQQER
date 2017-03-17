<?php

/**
 * Get search config
 *
 * @param string $section - settings section
 * @param string $var (optional) - settings var; if omitted get whole section
 * @return mixed - settings value
 */
QUI::$Ajax->registerFunction(
    'ajax_workspace_search_getSetting',
    function ($section, $var = null) {
        $Conf = QUI::getConfig('etc/search.ini.php');

        if (empty($var)) {
            $var = null;
        }

        return $Conf->get($section, $var);
    },
    array('section', 'var')
);
