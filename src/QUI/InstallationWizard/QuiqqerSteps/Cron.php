<?php

namespace QUI\InstallationWizard\QuiqqerSteps;

use QUI;
use QUI\Locale;

/**
 * Class Welcome
 */
class Cron extends QUI\InstallationWizard\AbstractInstallationWizardStep
{
    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setJavaScriptControl('controls/installation/Cron');
    }

    public function getTitle(?Locale $Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/core', 'quiqqer.setup.cron.title');
    }

    public function getDescription(?Locale $Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/core', 'quiqqer.setup.cron.description');
    }

    public function create(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        $Engine->assign([
            'urlImageDir' => URL_OPT_DIR . 'quiqqer/core/bin/images/installation/'
        ]);

        return $Engine->fetch(__DIR__ . '/Cron.html');
    }
}
