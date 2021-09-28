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
            new QuiqqerSteps\Cron(),
            new QuiqqerSteps\Finish(),
        ];
    }

    /**
     * @param array $data
     */
    public function execute(array $data = []): void
    {
        $Config = QUI::$Conf;

        // check if all data are available what we needed
        if (isset($data['quiqqer-country'])) {
            $Config->set('general', 'standardLanguage', $data['quiqqer-country']);
        }

        if (!empty($data['mail.admin_mail'])) {
            $Config->set('mail', 'admin_mail', $data['mail.admin_mail']);
        }

        // smtp stuff
        if (isset($data['use-smtp']) && (int)$data['use-smtp']) {
            $Config->set('mail', 'SMTP', '1');
            $Config->set('mail', 'SMTPServer', $data['smtp-server']);

            $Config->set('mail', 'SMTPPort', $data['smtp-port']);
            $Config->set('mail', 'SMTPAuth', $data['smtp-server']);
            $Config->set('mail', 'SMTPUser', $data['smtp-user']);
            $Config->set('mail', 'SMTPPass', $data['smtp-password']);
            $Config->set('mail', 'SMTPSecure', (int)$data['smtp-secure']);
            $Config->set('mail', 'SMTPSecureSSL_verify_peer', $data['smtp-secure-verify_peer']);
            $Config->set('mail', 'SMTPSecureSSL_verify_peer_name', $data['smtp-secure-verify_peer_name']);
            $Config->set('mail', 'SMTPSecureSSL_allow_self_signed', $data['mail.settings.allow_self_signed']);
        }

        $Config->save();
    }
}
