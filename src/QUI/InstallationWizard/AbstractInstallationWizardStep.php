<?php

namespace QUI\InstallationWizard;

use QUI;
use QUI\Locale;

/**
 * Class AbstractInstallationWizard
 */
abstract class AbstractInstallationWizardStep extends QUI\Control implements InstallationWizardStepInterface
{
    public function execute(): void
    {
    }

    public function toArray(?Locale $Locale = null): array
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
