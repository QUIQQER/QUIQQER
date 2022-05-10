<?php

namespace QUI\InstallationWizard;

use QUI;

use function dirname;
use function file_get_contents;

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
        return 2;
    }

    /**
     * @return QuiqqerSteps\Welcome[]
     */
    public function getSteps(): array
    {
        return [
            new QuiqqerSteps\Welcome(),
            new QuiqqerSteps\Country(),
            new QuiqqerSteps\Groups(),
            new QuiqqerSteps\Mail(),
            new QuiqqerSteps\MailSMTP(),
            new QuiqqerSteps\Cron(),
            new QuiqqerSteps\Workspace(),
            new QuiqqerSteps\Finish(),
        ];
    }

    /**
     * @param array $data
     * @throws QUI\Exception
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

        if (!empty($data['add-quiqqer-groups'])) {
            $this->setupForGroupsAndToolbars();
        }

        // workspace
        if (isset($data['workspace-columns'])) {
            switch ($data['workspace-columns']) {
                case '2-columns':
                    QUI\Workspace\Manager::setStandardWorkspace(
                        QUI::getUserBySession(),
                        1
                    );
                    break;

                case '3-columns':
                    QUI\Workspace\Manager::setStandardWorkspace(
                        QUI::getUserBySession(),
                        2
                    );
                    break;
            }
        }

        $Config->save();
    }

    /**
     * @return void
     */
    protected function setupForGroupsAndToolbars()
    {
        try {
            $Root   = QUI::getGroups()->get(QUI::conf('globals', 'root'));
            $Config = QUI::getConfig('etc/installationWizard.ini.php');
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
            return;
        }

        // Redakteur / Editor
        $table        = QUI\Permissions\Manager::table();
        $table2groups = $table . '2groups';

        try {
            if (!$Config->getValue('installationWizard', 'editorId')) {
                $Editor = $Root->createChild('Editor');
                $Editor->activate();
                $Config->setValue('installationWizard', 'editorId', $Editor->getId());

                $permissions = file_get_contents(dirname(__FILE__) . '/permissions.editor.json');

                QUI::getDataBase()->update(
                    $table2groups,
                    ['permissions' => $permissions],
                    ['group_id' => $Editor->getId()]
                );
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }

        // sys admin
        try {
            if (!$Config->getValue('installationWizard', 'sysAdminId')) {
                $sysAdmin = $Root->createChild('System administrator');
                $sysAdmin->activate();
                $Config->setValue('installationWizard', 'sysAdminId', $sysAdmin->getId());

                $permissions = file_get_contents(dirname(__FILE__) . '/permissions.sysadmin.json');

                QUI::getDataBase()->update(
                    $table2groups,
                    ['permissions' => $permissions],
                    ['group_id' => $sysAdmin->getId()]
                );
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }


        try {
            $Config->save();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
            return;
        }
    }
}
