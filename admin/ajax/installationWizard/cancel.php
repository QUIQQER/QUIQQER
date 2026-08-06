<?php

/**
 * Cancel the setup for the specific providers
 */

use QUI\InstallationWizard\InstallationWizardInterface;
use QUI\InstallationWizard\ProviderHandler;

QUI::$Ajax->registerFunction(
    'ajax_installationWizard_cancel',
    static function ($providers): void {
        if (is_string($providers)) {
            $providers = json_decode($providers, true);
        }

        if (!is_array($providers)) {
            $providers = [];
        }

        foreach ($providers as $provider) {
            if (
                !is_string($provider)
                || !class_exists($provider)
            ) {
                continue;
            }

            $interfaces = class_implements($provider);

            if (!isset($interfaces[InstallationWizardInterface::class])) {
                continue;
            }

            $Provider = new $provider();

            if (!$Provider instanceof InstallationWizardInterface) {
                continue;
            }

            ProviderHandler::setProviderStatus(
                $Provider,
                ProviderHandler::STATUS_SET_UP_DONE
            );
        }
    },
    ['providers'],
    'Permission::checkSU'
);
