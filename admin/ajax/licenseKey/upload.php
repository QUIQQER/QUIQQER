<?php

/**
 * Upload a license key file
 *
 * @param \QUI\QDOM $File
 *
 * @return void
 */

use QUI\System\License;

QUI::$Ajax->registerFunction(
    'ajax_licenseKey_upload',
    static function ($File): void {
        try {
            License::registerLicenseFile($File);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            QUI::getMessagesHandler()->addError(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'message.ajax.licenseKey.upload.error'
                )
            );

            return;
        }

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/core',
                'message.ajax.licenseKey.upload.success'
            )
        );
    },
    ['File'],
    'Permission::checkAdminUser'
);
