<?php

namespace QUI\InstallationWizard\QuiqqerSteps;

use QUI;
use QUI\Locale;

/**
 * Class Welcome
 */
class Mail extends QUI\InstallationWizard\AbstractInstallationWizardStep
{
    public function getTitle(?Locale $Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/core', 'quiqqer.setup.mail.title');
    }

    public function getDescription(?Locale $Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/core', 'quiqqer.setup.mail.description');
    }

    public function create(): string
    {
        $Engine = QUI::getTemplateManager()->getEngine();

        $Engine->assign([
            'urlImageDir' => URL_OPT_DIR . 'quiqqer/core/bin/images/installation/',
            'mail' => QUI::conf('mail', 'admin_mail')
        ]);

        return $Engine->fetch(__DIR__ . '/Mail.html');
    }
}
