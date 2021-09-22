<?php

namespace QUI\InstallationWizard\QuiqqerSteps;

use QUI;

/**
 * Class Welcome
 */
class Welcome extends QUI\InstallationWizard\AbstractInstallationWizardStep
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

        return $Locale->get('quiqqer/quiqqer', 'quiqqer.setup.welcome.title');
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

        return $Locale->get('quiqqer/quiqqer', 'quiqqer.setup.welcome.description');
    }

    /**
     * @return string
     */
    public function create(): string
    {
        try {
            $Engine = QUI::getTemplateManager()->getEngine();
        } catch (QUI\Exception $Exception) {
            return '';
        }

        $Engine->assign([
            'urlImageDir' => URL_OPT_DIR.'quiqqer/quiqqer/bin/images/installation/'
        ]);

        return $Engine->fetch(dirname(__FILE__).'/Welcome.html');
    }
}