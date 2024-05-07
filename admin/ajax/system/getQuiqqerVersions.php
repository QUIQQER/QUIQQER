<?php

/**
 * Get the available versions of quiqqer
 *
 * @return String
 */

QUI::$Ajax->registerFunction(
    'ajax_system_getQuiqqerVersions',
    function () {
        $packages = @file_get_contents('https://update.quiqqer.com/packages.json');
        $packages = json_decode($packages, true);
        $versions = [];
        $highestMinors = [];

        if (isset($packages['packages']['quiqqer/core'])) {
            $quiqqer = $packages['packages']['quiqqer/core'];
            $versionList = array_keys($quiqqer);

            foreach ($versionList as $version) {
                [$major, $minor] = explode('.', $version) + [null, null];

                if ($major === null || $minor === null) {
                    continue;
                }

                if (!array_key_exists($major, $highestMinors) || $minor > $highestMinors[$major]) {
                    $highestMinors[$major] = $minor;
                }
            }
        }

        foreach ($highestMinors as $major => $minor) {
            $versions[] = $major . ".*";
            $versions[] = $major . "." . $minor . ".*";
        }

        $versions[] = "dev-master";
        $versions[] = "dev-dev";

        return $versions;
    },
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
