<?php

namespace QUI\InstallationWizard;

use QUI\Locale;

/**
 * Interface InstallationWizardStepInterface
 */
interface InstallationWizardStepInterface
{
    public function getTitle(?Locale $Locale = null): string;

    public function getDescription(?Locale $Locale = null): string;

    public function getJavaScriptControl(): string;

    /**
     * Method is called when the setup will be executed
     *
     * @throws Exception
     */
    public function execute(): void;

    /**
     * @return string
     */
    public function create(): string;
}
