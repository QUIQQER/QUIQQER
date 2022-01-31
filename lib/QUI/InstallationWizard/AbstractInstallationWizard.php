<?php

namespace QUI\InstallationWizard;

/**
 * Class AbstractInstallationWizard
 */
abstract class AbstractInstallationWizard implements InstallationWizardInterface
{
    /**
     * @return int
     */
    public function getPriority(): int
    {
        return 0;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return ProviderHandler::getProviderStatus($this);
    }

    /**
     * @param int $step
     * @return InstallationWizardStepInterface
     * @throws Exception
     */
    public function getStep(int $step): InstallationWizardStepInterface
    {
        $steps = $this->getSteps();

        if (isset($steps[$step])) {
            return $steps[$step];
        }

        throw new Exception('Step not found', 404);
    }

    /**
     * @param null $Locale
     * @return array
     */
    public function toArray($Locale = null): array
    {
        $steps = \array_map(function ($Step) {
            return $Step->toArray();
        }, $this->getSteps());

        return [
            'title'       => $this->getTitle($Locale),
            'description' => $this->getDescription($Locale),
            'status'      => $this->getStatus(),
            'steps'       => $steps,
            'class'       => \get_class($this)
        ];
    }

    public function onListInit(&$list)
    {
    }
}
