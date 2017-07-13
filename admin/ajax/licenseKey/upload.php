<?php

use QUI\Security\Encryption;
use QUI\Utils\System\File;
use QUI\Config;

/**
 * Upload a license key file
 *
 * @param \QUI\QDOM $File
 *
 * @return void
 */
QUI::$Ajax->registerFunction(
    'ajax_licenseKey_upload',
    function ($File) {
        try {
            $content = file_get_contents($File->getAttribute('filepath'));
            $content = json_decode(hex2bin($content), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new QUI\Exception('JSON Error in license data: ' . json_last_error_msg());
            }

            $keys = array(
                'id',
                'created',
                'licenseHash',
                'licenseServer',
                'validUntil',
                'name'
            );

            foreach ($keys as $key) {
                if (!isset($content[$key])) {
                    throw new QUI\Exception('Missing key "' . $key . '" in license data.');
                }

                if (empty($content[$key])) {
                    throw new QUI\Exception('Empty key "' . $key . '" in license data.');
                }

                if (!is_string($content[$key])) {
                    throw new QUI\Exception('Non-string key "' . $key . '" in license data.');
                }
            }

            // put license data in config
            $licenseConfigFile = CMS_DIR . 'etc/license.ini.php';
            File::mkfile($licenseConfigFile);

            if (!file_exists($licenseConfigFile)) {
                throw new QUI\Exception('Could not create license config file "' . $licenseConfigFile . '"');
            }

            $LicenseConfig = new Config($licenseConfigFile);

            $LicenseConfig->set('license', 'id', $content['id']);
            $LicenseConfig->set('license', 'created', $content['created']);
            $LicenseConfig->set('license', 'name', $content['name']);
            $LicenseConfig->set('license', 'validUntil', $content['validUntil']);
            $LicenseConfig->set(
                'license',
                'licenseHash',
                bin2hex(Encryption::encrypt(hex2bin($content['licenseHash'])))
            );

            $LicenseConfig->save($licenseConfigFile);

            // set license server
            $Config = new QUI\Config(ETC_DIR . 'conf.ini.php');
            $Config->set('license', 'url', $content['licenseServer']);
            $Config->save();

            // re-create composer.json
            QUI::getPackageManager()->refreshServerList();
        } catch (\Exception $Exception) {
            QUI\System\Log::addError('AJAX :: ajax_licenseKey_upload');
            QUI\System\Log::writeException($Exception);

            QUI::getMessagesHandler()->addError(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'message.ajax.licenseKey.upload.error'
                )
            );

            return;
        }

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/system',
                'message.ajax.licenseKey.upload.success'
            )
        );
    },
    array('File'),
    'Permission::checkAdminUser'
);
