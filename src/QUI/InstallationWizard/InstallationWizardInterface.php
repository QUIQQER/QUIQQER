<?php

namespace QUI\InstallationWizard;

use QUI\Locale;

/**
 * Interface InstallationWizardInterface
 */
interface InstallationWizardInterface
{
    public function getTitle(?Locale $Locale = null): string;

    public function getDescription(?Locale $Locale = null): string;

    public function getLogo(): string;

    public function getPriority(): int;

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
     * @throws Exception
     */
    public function getStep(int $step): InstallationWizardStepInterface;

    public function toArray(?Locale $Locale = null): array;

    public function execute(array $data = []);

    /**
     * Returns a finish text (optional)
     *
     * @return bool|string
     */
    public function finish(): bool|string;

    public function write(string $line);

    public function getExecuteSteps(): array;

    public function getExecuteContent(): string;

    /**
     * is called when all provider lists are called via ajax
     *
     * @param array $list
     */
    public function onListInit(array &$list): void;
}
