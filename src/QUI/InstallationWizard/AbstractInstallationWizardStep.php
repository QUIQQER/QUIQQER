<?php

namespace QUI\InstallationWizard;

use QUI;

/**
 * Class AbstractInstallationWizard
 */
abstract class AbstractInstallationWizardStep extends QUI\Control implements
    \QUI\InstallationWizard\InstallationWizardStepInterface
{
    public function execute(): void
    {
    }

    public function toArray($Locale = null): array
    {
        return [
            'title' => $this->getTitle($Locale),
            'description' => $this->getDescription($Locale),
            'jsControl' => $this->getJavaScriptControl()
        ];
    }

    public function getJavaScriptControl(): string
    {
        return $this->getAttribute('qui-class');
    }
}
