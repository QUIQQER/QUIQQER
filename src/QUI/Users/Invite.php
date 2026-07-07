<?php

/**
 * This file contains QUI\Users\Invite
 */

namespace QUI\Users;

use QUI;
use QUI\Interfaces\Users\User as QUIUserInterface;
use QUI\Mail\Mailer;
use QUI\Security\Password;

use function filter_var;
use function trim;

use const FILTER_VALIDATE_EMAIL;

/**
 * Handles invitations for one new user.
 */
class Invite
{
    /**
     * @param int[] $groups
     *
     * @throws QUI\Exception
     */
    public function invite(string $email, array $groups): QUIUserInterface
    {
        $email = trim($email);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception(
                QUI::getLocale()->get('quiqqer/core', 'controls.email.select.email_invalid')
            );
        }

        $Users = QUI::getUsers();
        $SystemUser = $Users->getSystemUser();
        $User = $Users->createChild($email);
        $password = Password::generateRandom();

        foreach ($groups as $groupId) {
            $User->addToGroup($groupId);
        }

        $User->setAttribute('email', $email);
        $User->setAttribute('quiqqer.set.new.password', true);
        $User->setPassword($password, $SystemUser);
        $User->save($SystemUser);
        $User->activate('', $SystemUser);

        $this->sendMail($User, $password);

        return $User;
    }

    /**
     * @throws QUI\Exception
     */
    protected function sendMail(QUIUserInterface $User, string $password): void
    {
        $email = $User->getAttribute('email');

        if (empty($email)) {
            return;
        }

        $Locale = $User->getLocale();
        $Mailer = new Mailer();
        $host = QUI::conf('globals', 'host') ?: '';

        $Mailer->addRecipient($email);
        $replacements = [
            'email' => $email,
            'password' => $password,
            'username' => QUI::getUserBySession()->getName(),
            'domain' => $host,
            'backendUrl' => $host . URL_DIR . 'admin/'
        ];

        $Mailer->setSubject(
            $Locale->get('quiqqer/core', 'users.invite.mail.subject', $replacements)
        );
        $Mailer->setBody(
            $Locale->get('quiqqer/core', 'users.invite.mail.body', $replacements)
        );
        $Mailer->send();
    }
}
