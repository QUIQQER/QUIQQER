<?php

namespace QUI\InstallationWizard;

/**
 * Interface InstallationWizardStepInterface
 */
interface InstallationWizardStepInterface
{
    /**
     * @param null $Locale
     * @return string
     */
    public function getTitle($Locale = null): string;

    /**
     * @param null $Locale
     * @return string
     */
    public function getDescription($Locale = null): string;

    /**
     * @return string
     */
    public function getJavaScriptControl(): string;

    /**
     * Method is called when the setup will be executed
     *
     * @return void
     *
     * @throws Exception
     */
    public function execute(): void;

    /**
     * @return string
     */
    public function create();
}
