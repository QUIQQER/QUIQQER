<?php

namespace QUI\InstallationWizard\QuiqqerSteps;

use QUI;

/**
 * Class Welcome
 */
class MailSMTP extends QUI\InstallationWizard\AbstractInstallationWizardStep
{
    /**
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
        $this->setJavaScriptControl('controls/installation/MailSMTP');
    }

    /**
     * @param null $Locale
     * @return string
     */
    public function getTitle($Locale = null): string
    {
        if ($Locale === null) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/quiqqer', 'quiqqer.setup.MailSMTP.title');
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

        return $Locale->get('quiqqer/quiqqer', 'quiqqer.setup.MailSMTP.description');
    }

    /**
     * @return string
     */
    public function create(): string
    {
        try {
            $Engine = QUI::getTemplateManager()->getEngine();
        } catch (QUI\Exception) {
            return '';
        }

        $Engine->assign([
            'urlImageDir' => URL_OPT_DIR . 'quiqqer/quiqqer/bin/images/installation/',
            'SMTPServer' => QUI::conf('mail', 'SMTPServer'),
            'SMTPPort' => QUI::conf('mail', 'SMTPPort'),
            'SMTPSecure' => QUI::conf('mail', 'SMTPSecure'),
            'SMTPUser' => QUI::conf('mail', 'SMTPUser'),
            'SMTPPass' => QUI::conf('mail', 'SMTPPass'),

            'SMTPSecureSSL_verify_peer' => QUI::conf('mail', 'SMTPSecureSSL_verify_peer'),
            'SMTPSecureSSL_verify_peer_name' => QUI::conf('mail', 'SMTPSecureSSL_verify_peer_name'),
            'SMTPSecureSSL_allow_self_signed' => QUI::conf('mail', 'SMTPSecureSSL_allow_self_signed'),
        ]);

        return $Engine->fetch(dirname(__FILE__) . '/MailSMTP.html');
    }
}
