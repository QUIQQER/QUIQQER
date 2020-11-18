<?php

use QUI\System\License;

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
            License::registerLicenseFile($File);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            QUI::getMessagesHandler()->addError(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'message.ajax.licenseKey.upload.error'
                )
            );

            return;
        }

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/quiqqer',
                'message.ajax.licenseKey.upload.success'
            )
        );
    },
    ['File'],
    'Permission::checkAdminUser'
);
