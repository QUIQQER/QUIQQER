<?php

/**
 * Delete current license key
 *
 * @return bool - success
 */

QUI::$Ajax->registerFunction(
    'ajax_licenseKey_delete',
    static function (): bool {
        try {
            \QUI\System\License::deleteLicense();
        } catch (\Exception $exception) {
            QUI::getMessagesHandler()->addError(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'message.ajax.licenseKey.delete.error'
                )
            );

            return false;
        }

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/core',
                'message.ajax.licenseKey.delete.success'
            )
        );

        return true;
    },
    [],
    'Permission::checkAdminUser'
);
