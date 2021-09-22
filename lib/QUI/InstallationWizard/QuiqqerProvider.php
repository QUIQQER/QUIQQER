<?php

namespace QUI\InstallationWizard;

use QUI;

/**
 * Class QuiqqerProvider
 */
class QuiqqerProvider extends AbstractInstallationWizard
{
    /**
     * @param null $Locale
     * @return string
     */
    public function getTitle($Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/quiqqer', 'set.up.title');
    }

    /**
     * @param null $Locale
     * @return string
     */
    public function getDescription($Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/quiqqer', 'set.up.description');
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return 1;
    }

    /**
     * @return QuiqqerSteps\Welcome[]
     */
    public function getSteps(): array
    {
        return [
            new QuiqqerSteps\Welcome(),
            new QuiqqerSteps\Country(),
            new QuiqqerSteps\Mail(),
            new QuiqqerSteps\MailSMTP(),
            new QuiqqerSteps\Cron()
        ];
    }

    /**
     * @param array $data
     */
    public function execute($data = []): void
    {
        // check if all data are available what we needed

    }
}
