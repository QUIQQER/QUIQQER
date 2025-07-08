<?php

namespace QUI\Users\Auth;

use QUI;
use QUI\Interfaces\Users\User;
use QUI\Locale;
use QUI\Users\AbstractAuthenticator;
use QUI\Users\Attribute\Verifiable\MailAttribute;
use QUI\Users\Exception;
use QUI\Utils\Security\Orthos;

/**
 * Class Mail2FA
 * - only as second authentication
 */
class VerifiedMail2FA extends AbstractAuthenticator
{
    public const USER_CODE_ATTRIBUTE = 'quiqqer.verified.2fa.mail.code';

    protected ?User $User = null;

    protected ?string $username = null;

    protected bool $authenticated = false;

    public function __construct(array | int | string $user = '')
    {
        $user = Orthos::clear($user);
        $this->username = $user;
    }

    public static function getLoginControl(): QUI\Control
    {
        return new Controls\VerifiedMail2FA();
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

    /**
     * @throws Exception
     */
    public function getUser(): User
    {
        if (!is_null($this->User)) {
            return $this->User;
        }

        try {
            $this->User = QUI::getUsers()->getUserByName($this->username);
        } catch (QUI\Exception) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.login.fail.user.not.found'],
                404
            );
        }

        return $this->User;
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
                ['quiqqer/core', 'exception.login.fail'],
                401
            );
        }

        try {
            $User = QUI::getUsers()->get($uid);
        } catch (QUI\Exception) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.permission.no.admin'],
            );
        }

        if (!($User instanceof QUI\Users\User)) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.permission.no.admin'],
            );
        }

        $email = $User->getAttribute('email');
        $isVerified = $User->isAttributeVerified($email, MailAttribute::class);

        if (!$isVerified) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.permission.no.admin'],
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
     */
    public static function sendAuthMailToSessionUser(): void
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
                ['quiqqer/core', 'exception.permission.no.admin'],
            );
        }

        if (!($User instanceof QUI\Users\User)) {
            throw new QUI\Permissions\Exception(
                ['quiqqer/core', 'exception.permission.no.admin'],
            );
        }

        $email = $User->getAttribute('email');
        $isVerified = $User->isAttributeVerified($email, MailAttribute::class);

        if (!$isVerified) {
            throw new QUI\Permissions\Exception(
                ['quiqqer/core', 'exception.permission.no.admin'],
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

    public function isPrimaryAuthentication(): bool
    {
        return false;
    }
}
