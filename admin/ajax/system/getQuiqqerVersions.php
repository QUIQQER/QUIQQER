<?php

/**
 * Get the available versions of quiqqer
 *
 * @return String
 */

QUI::$Ajax->registerFunction(
    'ajax_system_getQuiqqerVersions',
    static function (): array {
        $packages = @file_get_contents('https://update.quiqqer.com/packages.json');
        $packages = json_decode($packages, true);

        $currentVersion = QUI::getPackageManager()->getVersion();
        $currentVersionParts = explode('.', $currentVersion);
        $currentMajorVersion = $currentVersionParts[0];

        $versions = [$currentVersion];
        $highestMinors = [];

        if (isset($packages['packages']['quiqqer/core'])) {
            $quiqqer = $packages['packages']['quiqqer/core'];
            $versionList = array_keys($quiqqer);

            foreach ($versionList as $version) {
                [$major, $minor] = explode('.', $version) + [null, null];

                if ($major === null) {
                    continue;
                }

                if ($minor === null) {
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

        $filteredVersions = array_filter($versions, function ($version) use ($currentMajorVersion) {
            $versionParts = explode('.', $version);
            return $versionParts[0] === $currentMajorVersion;
        });

        $filteredVersions = array_values($filteredVersions);
        usort($filteredVersions, 'version_compare');

        //$versions[] = "dev-main";

        return $filteredVersions;
    },
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
