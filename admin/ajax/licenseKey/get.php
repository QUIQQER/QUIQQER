<?php

use QUI\Security\Encryption;
use QUI\Utils\System\File;
use QUI\Config;

/**
 * Get license key information
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_licenseKey_get',
    function () {
        $licenseConfigFile = CMS_DIR . 'etc/license.ini.php';
        $default           = array(
            'id'         => '-',
            'created'    => '-',
            'validUntil' => '-',
            'name'       => '-',
        );

        if (!file_exists($licenseConfigFile)) {
            return $default;
        }

        $LicenseConfig = new Config($licenseConfigFile);
        $data          = array_merge($default, $LicenseConfig->getSection('license'));

        if (isset($data['created'])) {
            $data['created'] = date('Y-m-d', (int)$data['created']);
        }

        if (isset($data['validUntil'])) {
            if ($data['validUntil'] === 'forever') {
                $data['validUntil'] = QUI::getLocale()->get('quiqqer/system', 'quiqqer.licenseKey.unlimited');
            } else {
                $data['validUntil'] = date('Y-m-d', (int)$data['validUntil']);
            }
        }

        unset($data['keyHash']);

        return $data;
    },
    array(),
    'Permission::checkAdminUser'
);
