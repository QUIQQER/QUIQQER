<?php

namespace QUI\Users\Auth;

use QUI;
use QUI\Interfaces\Users\User;
use QUI\Locale;
use QUI\Users\AbstractAuthenticator;
use QUI\Users\Attribute\AttributeVerificationStatus;
use QUI\Users\Attribute\Verifiable\MailAttribute;
use QUI\Users\Exception;
use QUI\Utils\Security\Orthos;
use Random\RandomException;

/**
 * Class Mail2FA
 * - only as second authentication
 */
class VerifiedMail2FA extends AbstractAuthenticator
{
    public const USER_CODE_ATTRIBUTE = 'quiqqer.verified.2fa.mail.code';
    public const USER_CODE_VERIFYING_ATTRIBUTE = 'quiqqer.verifying.2fa.mail.code';

    protected ?User $User = null;
    protected mixed $user = null;
    protected bool $authenticated = false;

    public function __construct(null | array | int | string | User $user = null)
    {
        if (empty($user)) {
            return;
        }

        if ($user instanceof User) {
            $this->User = $user;
            return;
        }

        $this->user = Orthos::clear($user);
    }

    public static function getLoginControl(): QUI\Control
    {
        return new Controls\VerifiedMail2FA();
    }

    public function getSettingsControl(): ?QUI\Control
    {
        $user = null;

        try {
            $user = $this->getUser();
        } catch (QUI\Exception) {
        }

        return new Controls\Settings\VerifiedMail2FA([
            'user' => $user
        ]);
    }

    public static function isCLICompatible(): bool
    {
        return true;
    }

    public function getTitle(null | Locale $Locale = null): string
    {
        if (is_null($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/core', 'quiqqer.mail2fa.title');
    }

    public function getDescription(null | Locale $Locale = null): string
    {
        if (is_null($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/core', 'quiqqer.mail2fa.description');
    }

    public function getIcon(): string
    {
        return 'fa fa-envelope';
    }

    public function getFrontendTitle(null | Locale $Locale = null): string
    {
        if (is_null($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/core', 'quiqqer.mail2fa.frontend.title');
    }

    public function getFrontendDescription(null | Locale $Locale = null): string
    {
        if (is_null($Locale)) {
            $Locale = QUI::getLocale();
        }

        return $Locale->get('quiqqer/core', 'quiqqer.mail2fa.frontend.description');
    }

    /**
     * @throws Exception
     */
    public function getUser(): User
    {
        if (!is_null($this->User)) {
            return $this->User;
        }

        if (empty($this->user)) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.login.fail.user.not.found'],
                404
            );
        }

        try {
            $this->User = QUI::getUsers()->get($this->user);
            return $this->User;
        } catch (QUI\Exception) {
        }

        try {
            $this->User = QUI::getUsers()->getUserByName($this->user);
            return $this->User;
        } catch (QUI\Exception) {
        }

        try {
            $this->User = QUI::getUsers()->getUserByMail($this->user);
            return $this->User;
        } catch (QUI\Exception) {
        }

        throw new QUI\Users\Exception(
            ['quiqqer/core', 'exception.login.fail.user.not.found'],
            404
        );
    }

    public function auth(string | int | array $authParams): bool
    {
        $uid = QUI::getSession()->get('uid');
        $hasChar = false;

        for ($i = 1; $i <= 6; $i++) {
            if (isset($authParams["char-$i"]) && $authParams["char-$i"] !== '') {
                $hasChar = true;
                break;
            }
        }

        if (empty($uid) || !$hasChar) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.login.fail.user.not.found'],
                401
            );
        }

        try {
            $User = QUI::getUsers()->get($uid);
        } catch (QUI\Exception) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.login.fail.user.not.found'],
            );
        }

        if (!($User instanceof QUI\Users\User)) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.login.fail.user.not.found'],
            );
        }

        $email = $User->getAttribute('email');
        $isVerified = $User->isAttributeVerified($email, MailAttribute::class);

        if (!$isVerified) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.user.mail.not.verified'],
            );
        }

        $code = $authParams['char-1'] . $authParams['char-2'] . $authParams['char-3'] .
            $authParams['char-4'] . $authParams['char-5'] . $authParams['char-6'];

        $userCode = QUI::getSession()->get(self::USER_CODE_ATTRIBUTE);

        if ($code === $userCode) {
            // reset code if all is ok
            QUI::getSession()->set(self::USER_CODE_ATTRIBUTE, '');

            return true;
        }

        throw new QUI\Users\Exception(
            ['quiqqer/core', 'exception.login.fail'],
            401
        );
    }

    /**
     * Sends a mail to the user with a 6-figure code
     *
     * @return void
     * @throws QUI\Permissions\Exception
     * @throws RandomException
     */
    public static function sendAuthMailToSessionUser(): void
    {
        // get user
        $uid = QUI::getSession()->get('uid');

        if (empty($uid)) {
            throw new QUI\Permissions\Exception(
                ['quiqqer/core', 'exception.login.fail.user.not.found'],
            );
        }

        try {
            $User = QUI::getUsers()->get($uid);
        } catch (QUI\Exception) {
            throw new QUI\Permissions\Exception(
                ['quiqqer/core', 'exception.login.fail.user.not.found'],
            );
        }

        if (!($User instanceof QUI\Users\User)) {
            throw new QUI\Permissions\Exception(
                ['quiqqer/core', 'exception.login.fail.user.not.found'],
            );
        }

        $email = $User->getAttribute('email');
        $isVerified = $User->isAttributeVerified($email, MailAttribute::class);

        if (!$isVerified) {
            throw new QUI\Permissions\Exception(
                ['quiqqer/core', 'exception.user.mail.not.verified'],
            );
        }

        // send mail
        $digitCode = '';
        for ($i = 0; $i < 6; $i++) {
            $digitCode .= random_int(0, 9);
        }

        try {
            QUI::getSession()->set(self::USER_CODE_ATTRIBUTE, $digitCode);

            QUI::getMailManager()->send(
                $email,
                QUI::getLocale()->get('quiqqer/core', 'quiqqer.verified.mail2fa.mail.subject', [
                    'host' => $_SERVER['HTTP_HOST']
                ]),
                QUI::getLocale()->get('quiqqer/core', 'quiqqer.verified.mail2fa.mail.content', [
                    'code' => $digitCode,
                    'host' => $_SERVER['HTTP_HOST']
                ])
            );
        } catch (\Exception $exception) {
            QUI\System\Log::addError($exception->getMessage(), [
                'source' => self::class . '::sendAuthMailToSessionUser'
            ]);
        }
    }

    //region enable

    /**
     * @throws QUI\Permissions\Exception
     * @throws RandomException
     */
    public static function sendEnableMailToSessionUser(): void
    {
        // get user
        $uid = QUI::getSession()->get('uid');

        if (empty($uid)) {
            return;
        }

        try {
            $User = QUI::getUsers()->get($uid);
        } catch (QUI\Exception) {
            throw new QUI\Permissions\Exception(
                ['quiqqer/core', 'exception.login.fail.user.not.found'],
            );
        }

        if (!($User instanceof QUI\Users\User)) {
            throw new QUI\Permissions\Exception(
                ['quiqqer/core', 'exception.login.fail.user.not.found'],
            );
        }

        $email = $User->getAttribute('email');

        if (empty($email)) {
            throw new QUI\Permissions\Exception(
                ['quiqqer/core', 'exception.login.fail.user.need.email'],
            );
        }

        // send mail
        $digitCode = '';

        for ($i = 0; $i < 6; $i++) {
            $digitCode .= random_int(0, 9);
        }

        try {
            QUI::getSession()->set(self::USER_CODE_VERIFYING_ATTRIBUTE, $digitCode);

            QUI::getMailManager()->send(
                $email,
                QUI::getLocale()->get('quiqqer/core', 'quiqqer.enable.mail2fa.mail.subject', [
                    'host' => $_SERVER['HTTP_HOST']
                ]),
                QUI::getLocale()->get('quiqqer/core', 'quiqqer.enable.mail2fa.mail.content', [
                    'code' => $digitCode,
                    'host' => $_SERVER['HTTP_HOST']
                ])
            );
        } catch (\Exception $exception) {
            QUI\System\Log::addError($exception->getMessage(), [
                'source' => self::class . '::sendEnableMailToSessionUser'
            ]);
        }
    }

    /**
     * @throws Exception
     */
    public static function enableByUser($code): bool
    {
        $uid = QUI::getSession()->get('uid');

        try {
            $User = QUI::getUsers()->get($uid);
        } catch (QUI\Exception) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.login.fail.user.not.found'],
            );
        }

        if (!($User instanceof QUI\Users\User)) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.login.fail.user.not.found'],
            );
        }

        // check if current user is nobody
        // 1fa must be successfully authenticated
        if (QUI::getUsers()->isNobodyUser(QUI::getUserBySession())) {
            if (!QUI::getSession()->get('auth-primary')) {
                throw new QUI\Users\Exception(
                    ['quiqqer/core', 'exception.2fa.mail.enable.not.authenticated'],
                );
            }
        }


        $userCode = QUI::getSession()->get(self::USER_CODE_VERIFYING_ATTRIBUTE);

        if ($code !== $userCode) {
            return false;
        }

        try {
            // verify mail
            if (method_exists($User, 'setStatusToVerifiableAttribute')) {
                $User->setStatusToVerifiableAttribute(
                    $User->getAttribute('email'),
                    QUI\Users\Attribute\Verifiable\MailAttribute::class,
                    AttributeVerificationStatus::VERIFIED
                );

                $User->save(QUI::getUsers()->getSystemUser());
            }

            // enable 2fa
            if (QUI::getUsers()->isNobodyUser(QUI::getUserBySession())) {
                $User->enableAuthenticator(
                    VerifiedMail2FA::class,
                    QUI::getUsers()->getSystemUser()
                );
            } else {
                $User->enableAuthenticator(VerifiedMail2FA::class);
            }
        } catch (QUI\Exception $e) {
            throw new QUI\Users\Exception($e->getMessage());
        }

        return true;
    }

    //endregion

    public function isPrimaryAuthentication(): bool
    {
        return false;
    }
}
