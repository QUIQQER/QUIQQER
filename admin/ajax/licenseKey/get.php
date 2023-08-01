<?php

/**
 * Get license key information
 *
 * @return array
 */

use QUI\Config;
use QUI\System\License;

QUI::$Ajax->registerFunction(
    'ajax_licenseKey_get',
    function () {
        $licenseConfigFile = CMS_DIR . 'etc/license.ini.php';
        $systemId = License::getSystemId();
        $systemDataHash = License::getSystemDataHash();

        $default = [
            'systemId' => $systemId,
            'systemDataHash' => $systemDataHash,
            'id' => '-',
            'created' => '-',
            'validUntil' => '-',
            'name' => '-',
        ];

        if (!file_exists($licenseConfigFile)) {
            return $default;
        }

        $LicenseConfig = new Config($licenseConfigFile);
        $data = array_merge($default, $LicenseConfig->getSection('license'));

        if (isset($data['created'])) {
            $data['created'] = date('Y-m-d', (int)$data['created']);
        }

        if (isset($data['validUntil'])) {
            if ($data['validUntil'] === 'forever') {
                $data['validUntil'] = QUI::getLocale()->get('quiqqer/quiqqer', 'quiqqer.licenseKey.unlimited');
            } else {
                $data['validUntil'] = date('Y-m-d', (int)$data['validUntil']);
            }
        }

        unset($data['keyHash']);

        $data['systemId'] = $systemId;
        $data['systemDataHash'] = $systemDataHash;

        return $data;
    },
    [],
    'Permission::checkAdminUser'
);
