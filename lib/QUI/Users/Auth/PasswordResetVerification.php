<?php

namespace QUI\Users\Auth;

use QUI;
use QUI\Verification\AbstractVerification;

class PasswordResetVerification extends AbstractVerification
{
    /**
     * Project
     *
     * @var QUI\Projects\Project
     */
    protected $Project;

    /**
     * PasswordResetVerification constructor.
     *
     * @param string $identifier
     * @param array $additionalData
     *
     * @throws QUI\Exception
     */
    public function __construct($identifier, $additionalData = [])
    {
        parent::__construct($identifier, $additionalData);
        $this->Project = new QUI\Projects\Project($additionalData['project'], $additionalData['projectLang']);
    }

    /**
     * Execute this method on successful verification
     *
     * @return void
     */
    public function onSuccess()
    {
        $Users      = QUI::getUsers();
        $SystemUser = $Users->getSystemUser();

        try {
            $User        = $Users->get((int)$this->getIdentifier());
            $newPassword = QUI\Security\Password::generateRandom();

            // check if user has to set new password
            if (\boolval(QUI::conf('auth_settings', 'forceNewPasswordOnReset'))) {
                $User->setAttribute('quiqqer.set.new.password', true);
            }

            $User->setPassword($newPassword, $SystemUser);
            $User->save($SystemUser);

            $this->sendNewUserPasswordMail($User, $newPassword);
        } catch (\Exception $Exception) {
            QUI\System\Log::addError(
                self::class.' :: onSuccess -> Error while setting temporary new user password'
            );

            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * Get the duration of a Verification (minutes)
     *
     * @return int|false - duration in minutes;
     * if this method returns false use the module setting default value
     */
    public function getValidDuration()
    {
        return (int)QUI::conf('auth_settings', 'passwordResetLinkValidTime');
    }

    /**
     * Execute this method on unsuccessful verification
     *
     * @return void
     */
    public function onError()
    {
        // TODO: Implement onError() method.
    }

    /**
     * This message is displayed to the user on successful verification
     *
     * @return string
     */
    public function getSuccessMessage()
    {
        return QUI::getLocale()->get(
            'quiqqer/system',
            'users.auth.passwordresetverification.success'
        );
    }

    /**
     * This message is displayed to the user on unsuccessful verification
     *
     * @param string $reason - The reason for the error (see \QUI\Verification\Verifier::REASON_)
     * @return string
     */
    public function getErrorMessage($reason)
    {
        return QUI::getLocale()->get(
            'quiqqer/system',
            'users.auth.passwordresetverification.error'
        );
    }

    /**
     * Automatically redirect the user to this URL on successful verification
     *
     * @return string|false - If this method returns false, no redirection takes place
     */
    public function getOnSuccessRedirectUrl()
    {
        $result = $this->Project->getSites([
            'where' => [
                'type' => 'quiqqer/frontend-users:types/login'
            ],
            'limit' => 1
        ]);

        if (empty($result)) {
            return false;
        }

        $LoginSite = \current($result);

        return $LoginSite->getUrlRewritten([], [
            'password_reset' => '1'
        ]);
    }

    /**
     * Automatically redirect the user to this URL on unsuccessful verification
     *
     * @return string|false - If this method returns false, no redirection takes place
     */
    public function getOnErrorRedirectUrl()
    {
        return false;
    }

    /**
     * Send mail with temporary password to user
     *
     * @param QUI\Users\User $User
     * @param string $newPass
     * @return void
     */
    protected function sendNewUserPasswordMail($User, $newPass)
    {
        $email = $User->getAttribute('email');

        if (empty($email)) {
            return;
        }

        $L      = QUI::getLocale();
        $lg     = 'quiqqer/system';
        $tplDir = QUI::getPackage('quiqqer/quiqqer')->getDir().'lib/templates/mail/auth/';

        $Mailer = new QUI\Mail\Mailer();
        $Engine = QUI::getTemplateManager()->getEngine();

        $Engine->assign([
            'body' => $L->get($lg, 'mail.auth.password_reset_newpassword.body', [
                'username'    => $User->getUsername(),
                'newPassword' => $newPass
            ])
        ]);

        $template = $Engine->fetch($tplDir.'password_reset_newpassword.html');

        $Mailer->addRecipient($email);
        $Mailer->setSubject(
            $L->get($lg, 'mail.auth.password_reset_newpassword.subject')
        );

        $Mailer->setBody($template);
        $Mailer->send();
    }
}
