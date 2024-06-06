<?php

namespace QUI\InstallationWizard\QuiqqerSteps;

use QUI;
use QUI\Locale;

/**
 * Class Welcome
 */
class Welcome extends QUI\InstallationWizard\AbstractInstallationWizardStep
{
    public function getTitle(?Locale $Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/core', 'quiqqer.setup.welcome.title');
    }

    public function getDescription(?Locale $Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/core', 'quiqqer.setup.welcome.description');
    }

    public function create(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        $Engine->assign([
            'urlImageDir' => URL_OPT_DIR . 'quiqqer/core/bin/images/installation/'
        ]);

        return $Engine->fetch(__DIR__ . '/Welcome.html');
    }
}
