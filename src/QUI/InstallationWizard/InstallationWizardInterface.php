<?php

namespace QUI\InstallationWizard;

/**
 * Interface InstallationWizardInterface
 */
interface InstallationWizardInterface
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
    public function getLogo(): string;

    /**
     * Return the priority of the installation wizard
     *
     * @return int
     */
    public function getPriority(): int;

    /**
     * @return int
     */
    public function getStatus(): int;

    /**
     * Return the installation steps
     *
     * @return InstallationWizardStepInterface[]
     */
    public function getSteps(): array;

    /**
     * Returns the step
     *
     * @param int $step
     * @return InstallationWizardStepInterface
     *
     * @throws Exception
     */
    public function getStep(int $step): InstallationWizardStepInterface;

    /**
     * @param null $Locale
     * @return array
     */
    public function toArray($Locale = null): array;

    /**
     * Setup will be executed
     *
     * @param array $data
     */
    public function execute(array $data = []);

    /**
     * Returns a finish text (optional)
     *
     * @return bool|string
     */
    public function finish();

    /**
     * output for the setup details
     * is usable via the execute method
     *
     * @param string $line
     */
    public function write(string $line);

    /**
     * @return array
     */
    public function getExecuteSteps(): array;

    /**
     * @return string
     */
    public function getExecuteContent(): string;

    /**
     * is called when all provider lists are called via ajax
     *
     * @param $list
     * @return mixed
     */
    public function onListInit(&$list);
}
