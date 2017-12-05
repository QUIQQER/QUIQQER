<?php

/**
 * Return the check for files
 * Only for SuperUsers
 *
 * @return String
 */
QUI::$Ajax->registerFunction(
    'ajax_system_systemcheckChecksum',
    function ($packageName) {

        $cacheFile = VAR_DIR."/tmp/requirements_checks_result_package";
        if(!file_exists($cacheFile)){
            return "Bitte neu laden";
        }

        $packages = json_decode(file_get_contents($cacheFile),true);

        if (isset($packages[$packageName])) {
            \QUI\System\Log::writeRecursive($packages[$packageName]);
            return $packages[$packageName];
        }



    },
    array('packageName'),
    'Permission::checkSU'
);
