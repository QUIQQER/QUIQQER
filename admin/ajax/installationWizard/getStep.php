<?php

/**
 * Return list of packages which needs a setup
 */

use QUI\InstallationWizard\InstallationWizardInterface;

QUI::$Ajax->registerFunction(
    'ajax_installationWizard_getStep',
    static function ($provider, $step): string {
        if (
            !is_string($provider)
            || !class_exists($provider)
        ) {
            return '';
        }

        $interfaces = class_implements($provider);

        if (!isset($interfaces[InstallationWizardInterface::class])) {
            return '';
        }

        $Provider = new $provider();

        if (!$Provider instanceof InstallationWizardInterface) {
            return '';
        }

        if (is_string($step) && ctype_digit($step)) {
            $step = (int)$step;
        }

        if (!is_int($step) || $step < 0) {
            return '';
        }

        $Step = $Provider->getStep($step);

        $control = $Step->create();
        $control .= QUI\Control\Manager::getCSS();

        return $control;
    },
    ['provider', 'step'],
    'Permission::checkSU'
);
