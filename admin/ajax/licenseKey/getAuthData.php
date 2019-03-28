<?php

use QUI\Security\Encryption;
use QUI\Utils\System\File;
use QUI\Config;

/**
 * Get license data used for authentication
 *
 * @return array|false - license data or false if no license set
 */
QUI::$Ajax->registerFunction(
    'ajax_licenseKey_getAuthData',
    function () {
        $licenseConfigFile = CMS_DIR.'etc/license.ini.php';

        if (!\file_exists($licenseConfigFile)) {
            return false;
        }

        $LicenseConfig = new Config($licenseConfigFile);
        $data          = $LicenseConfig->getSection('license');

        if (empty($data['id'])
            || empty($data['licenseHash'])
        ) {
            return false;
        }

        $hash = $data['licenseHash'];
        $hash = \hex2bin($hash);
        $hash = \bin2hex(Encryption::decrypt($hash));

        return [
            'licenseId'   => $data['id'],
            'licenseHash' => $hash
        ];
    },
    [],
    'Permission::checkAdminUser'
);
