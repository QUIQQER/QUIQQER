<?php

namespace QUI\InstallationWizard;

use QUI;

use function array_map;
use function get_class;
use function method_exists;

/**
 * Class AbstractInstallationWizard
 */
abstract class AbstractInstallationWizard implements InstallationWizardInterface
{
    public function getPriority(): int
    {
        return 0;
    }

    /**
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

    public function toArray($Locale = null): array
    {
        $steps = array_map(static function ($Step) {
            if (method_exists($Step, 'toArray')) {
                return $Step->toArray();
            }

            return [];
        }, $this->getSteps());

        return [
            'title' => $this->getTitle($Locale),
            'description' => $this->getDescription($Locale),
            'logo' => $this->getLogo(),
            'status' => $this->getStatus(),
            'steps' => $steps,
            'class' => $this::class,
            'finishButton' => $this->getFinishButtonText($Locale)
        ];
    }

    public function getLogo(): string
    {
        return URL_OPT_DIR . 'quiqqer/core/bin/quiqqer_logo.svg';
    }

    public function getStatus(): int
    {
        return ProviderHandler::getProviderStatus($this);
    }

    public function getFinishButtonText($Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/core', 'set.up.execute.button.text');
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
     */
    public function getExecuteContent(): string
    {
        return '';
    }

    /**
     * Returns the step which are shown during the execute() step (installation)
     *
     * @return array
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
