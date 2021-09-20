<?php

namespace QUI\InstallationWizard;

use QUI;

/**
 * Class AbstractInstallationWizard
 */
abstract class AbstractInstallationWizardStep extends QUI\Control implements \QUI\InstallationWizard\InstallationWizardStepInterface
{
    /**
     * can be overwritten
     *
     * @return void
     */
    public function execute(): void
    {
    }

    /**
     * @param null $Locale
     * @return array
     */
    public function toArray($Locale = null): array
    {
        return [
            'title'       => $this->getTitle($Locale),
            'description' => $this->getDescription($Locale)
        ];
    }
}
