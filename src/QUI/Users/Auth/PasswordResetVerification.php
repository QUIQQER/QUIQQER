<?php

namespace QUI\Users\Auth;

use QUI;
use QUI\Exception;
use QUI\Verification\AbstractLinkVerificationHandler;
use QUI\Verification\Entity\LinkVerification;
use QUI\Verification\Enum\VerificationErrorReason;
use QUI\Verification\Entity\AbstractVerification;

use function current;

class PasswordResetVerification extends AbstractLinkVerificationHandler
{
     /**
     * Execute this method on successful verification
     *
     * @param LinkVerification $verification
     * @return void
     */
    public function onSuccess(LinkVerification $verification): void
    {
        $Users = QUI::getUsers();
        $SystemUser = $Users->getSystemUser();

        try {
            $User = $Users->get($verification->getCustomDataEntry('uuid'));
            $newPassword = QUI\Security\Password::generateRandom();

            // check if user has to set new password
            if (QUI::conf('auth_settings', 'forceNewPasswordOnReset')) {
                $User->setAttribute('quiqqer.set.new.password', true);
            }

            $User->setPassword($newPassword, $SystemUser);
            $User->save($SystemUser);

            $this->sendNewUserPasswordMail($User, $newPassword);
        } catch (\Exception $Exception) {
            QUI\System\Log::addError(
                self::class . ' :: onSuccess -> Error while setting temporary new user password'
            );

            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * Send mail with temporary password to user
     *
     * @throws Exception
     * @throws \PHPMailer\PHPMailer\Exception
     */
    protected function sendNewUserPasswordMail(QUI\Interfaces\Users\User $User, string $newPass): void
    {
        $email = $User->getAttribute('email');

        if (empty($email)) {
            return;
        }

        $L = QUI::getLocale();
        $lg = 'quiqqer/core';
        $tplDir = QUI::getPackage('quiqqer/core')->getDir() . 'src/templates/mail/auth/';

        $Mailer = new QUI\Mail\Mailer();
        $Engine = QUI::getTemplateManager()->getEngine();

        $Engine->assign([
            'body' => $L->get($lg, 'mail.auth.password_reset_newpassword.body', [
                'username' => $User->getUsername(),
                'newPassword' => $newPass
            ])
        ]);

        $template = $Engine->fetch($tplDir . 'password_reset_newpassword.html');

        $Mailer->addRecipient($email);
        $Mailer->setSubject(
            $L->get($lg, 'mail.auth.password_reset_newpassword.subject')
        );

        $Mailer->setBody($template);
        $Mailer->send();
    }

  /**
     * Get the duration of a Verification (minutes)
     *
   * @param AbstractVerification $verification
   * @return int|null - duration in minutes;
     * on NULL use the module setting default value
     */
    public function getValidDuration(AbstractVerification $verification): ?int
    {
        return (int)QUI::conf('auth_settings', 'passwordResetLinkValidTime');
    }

    /**
     * Execute this method on unsuccessful verification
     * @param LinkVerification $verification
     * @param VerificationErrorReason $reason
     */
    public function onError(LinkVerification $verification, VerificationErrorReason $reason): void
    {
        // nothing
    }

    /**
     * This message is displayed to the user on successful verification
     * @param LinkVerification $verification
     */
    public function getSuccessMessage(LinkVerification $verification): string
    {
        return QUI::getLocale()->get(
            'quiqqer/core',
            'users.auth.passwordresetverification.success'
        );
    }

    /**
     * This message is displayed to the user on unsuccessful verification
     *
     * @param LinkVerification $verification
     * @param VerificationErrorReason $reason
     * @return string
     */
    public function getErrorMessage(LinkVerification $verification, VerificationErrorReason $reason): string
    {
        return QUI::getLocale()->get(
            'quiqqer/core',
            'users.auth.passwordresetverification.error'
        );
    }

    /**
     * Automatically redirect the user to this URL on successful verification
     *
     * @param LinkVerification $verification
     * @return string|null - If this method returns false, no redirection takes place
     * @throws QUI\Database\Exception|Exception
     */
    public function getOnSuccessRedirectUrl(LinkVerification $verification): ?string
    {
        $result = QUI::getRewrite()->getProject()->getSites([
            'where' => [
                'type' => 'quiqqer/frontend-users:types/login'
            ],
            'limit' => 1
        ]);

        if (empty($result)) {
            return false;
        }

        $LoginSite = current($result);

        return $LoginSite->getUrlRewritten([], [
            'password_reset' => '1'
        ]);
    }
}
