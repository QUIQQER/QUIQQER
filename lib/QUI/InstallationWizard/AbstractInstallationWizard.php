<?php

namespace QUI\InstallationWizard;

use QUI;

use function array_map;
use function get_class;

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
     * @param $Locale
     * @return string
     */
    public function getFinishButtonText($Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/quiqqer', 'set.up.execute.button.text');
    }

    /**
     * @param null $Locale
     * @return array
     */
    public function toArray($Locale = null): array
    {
        $steps = array_map(function ($Step) {
            return $Step->toArray();
        }, $this->getSteps());

        return [
            'title'        => $this->getTitle($Locale),
            'description'  => $this->getDescription($Locale),
            'status'       => $this->getStatus(),
            'steps'        => $steps,
            'class'        => get_class($this),
            'finishButton' => $this->getFinishButtonText($Locale)
        ];
    }

    public function onListInit(&$list)
    {
    }

    /**
     * @return false|string
     */
    public function finish()
    {
        return false;
    }

    //region execution methods

    /**
     * Returns the text for the finish display
     *
     * @return string
     */
    public function getExecuteContent(): string
    {
        return '';
    }

    /**
     * Returns the step which are shown during the execute() step (installation)
     *
     * @return \string[][]
     */
    public function getExecuteSteps(): array
    {
        return [];
    }

    /**
     * Writes an output during the ->execute() installation / setup
     *
     * @param string $line
     * @return void
     */
    public function write(string $line)
    {
        echo $line . '<br />';
        echo '<script>
        (function() {
            const Process = document.querySelector(".wizard-process");
            Process.scrollTop = Process.scrollHeight;
        })();
        </script>';

        if (function_exists('flushIt')) {
            flushIt();
        }
    }

    //endregion
}
