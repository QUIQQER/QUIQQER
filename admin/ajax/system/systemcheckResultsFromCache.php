<?php

/**
 * Return the system check
 * Only for SuperUsers
 *
 * @return String
 */
QUI::$Ajax->registerFunction(
    'ajax_system_systemcheckResultsFromCache',
    function () {

        if (!isset($_REQUEST['lang'])) {
            $_REQUEST['lang'] = 'en';
        }

        $lang = substr($_REQUEST['lang'], 0, 2);

        $Requirements = new \QUI\Requirements\Requirements($lang);

        $allTests = $Requirements->getTests();

        return \QUI\Requirements\Utils::htmlFormatTestResults($allTests);
    },
    false,
    'Permission::checkSU'
);
