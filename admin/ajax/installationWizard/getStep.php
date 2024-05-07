<?php

/**
 * Return list of packages which needs a setup
 */

use QUI\InstallationWizard\InstallationWizardInterface;

QUI::$Ajax->registerFunction(
    'ajax_installationWizard_getStep',
    function ($provider, $step) {
        if (!class_exists($provider)) {
            return '';
        }

        $interfaces = class_implements($provider);

        if (!isset($interfaces[InstallationWizardInterface::class])) {
            return '';
        }

        /* @var $Provider QUI\InstallationWizard\InstallationWizardInterface */
        $Provider = new $provider();
        $Step = $Provider->getStep($step);

        $control = $Step->create();
        $control .= QUI\Control\Manager::getCSS();

        return $control;
    },
    ['provider', 'step'],
    'Permission::checkSU'
);
