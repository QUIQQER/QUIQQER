<?php

/**
 * This file contains QUI\Users\Auth\Handler
 */

namespace QUI\Users\Auth;

use QUI;
use QUI\Users\AuthenticatorInterface;

/**
 * Class Handler
 * Main Class, Handling class for authenticators
 *
 * @package QUI
 */
class Handler
{
    /**
     * global instance
     *
     * @var Handler
     */
    protected static $Instance;

    /**
     * Return the global QUI\Users\Auth\Handler instance
     *
     * @return Handler
     */
    public static function getInstance()
    {
        if (is_null(self::$Instance)) {
            self::$Instance = new self();
        }

        return self::$Instance;
    }

    /**
     * @param QUI\Package\Package $Package
     */
    public static function onPackageSetup(QUI\Package\Package $Package)
    {
        // create auth provider as user permissions
        $authProviders = $Package->getProvider('auth');

        if (empty($authProviders)) {
            return;
        }

        // <permission name="quiqqer.auth.AUTH.canUse" type="bool" />
        $Locale      = new QUI\Locale();
        $Permissions = new QUI\Permissions\Manager();
        $User        = QUI::getUserBySession();

        $Locale->no_translation = true;

        foreach ($authProviders as $authProvider) {
            if (trim($authProvider, '\\') == QUIQQER::class) {
                continue;
            }

            /* @var $Authenticator AuthenticatorInterface */
            $Authenticator  = new $authProvider($User->getUsername());
            $permissionName = Helper::parseAuthenticatorToPermission($authProvider);

            $Permissions->addPermission(array(
                'name'         => $permissionName,
                'title'        => str_replace(array('[', ']'), '', $Authenticator->getTitle($Locale)),
                'desc'         => str_replace(array('[', ']'), '', $Authenticator->getDescription($Locale)),
                'type'         => 'bool',
                'area'         => '',
                'src'          => $Package->getName(),
                'defaultvalue' => 0
            ));
        }
    }

    /**
     * Return all global active authenticators
     *
     * @return array
     */
    public function getGlobalAuthenticators()
    {
        $config = QUI::conf('auth');

        if (empty($config)) {
            return array(
                QUIQQER::class
            );
        }

        $result = array();

        $available = $this->getAvailableAuthenticators();
        $available = array_flip($available);

        foreach ($config as $authenticator => $status) {
            if ($status != 1) {
                continue;
            }

            if (isset($available[$authenticator])) {
                $result[] = $authenticator;
            }
        }

        if (empty($result)) {
            return array(
                QUIQQER::class
            );
        }

        // sorting
        usort($result, function ($a, $b) {
            if ($a == QUIQQER::class) {
                return 1;
            }

            if ($b == QUIQQER::class) {
                return 1;
            }

            return strcmp($a, $b);
        });

        return $result;
    }

    /**
     * Returns a specific authenticator
     *
     * @param string $authenticator - name of the authenticator
     * @param string $username - QUIQQER username of the user
     *
     * @return AuthenticatorInterface
     *
     * @throws QUI\Users\Auth\Exception
     */
    public function getAuthenticator($authenticator, $username)
    {
        $authenticators = $this->getAvailableAuthenticators();
        $authenticators = array_flip($authenticators);

        if (isset($authenticators[$authenticator])) {
            return new $authenticator($username);
        }

        throw new QUI\Users\Auth\Exception(
            array(
                'quiqqer/system',
                'exception.authenticator.not.found'
            ),
            404
        );
    }

    /**
     * Return all available authenticators
     *
     * @todo cache
     * @return array
     */
    public function getAvailableAuthenticators()
    {
        $authList  = array();
        $list      = array();
        $installed = QUI::getPackageManager()->getInstalled();

        foreach ($installed as $package) {
            try {
                $Package = QUI::getPackage($package['name']);

                if (!$Package->isQuiqqerPackage()) {
                    continue;
                }

                $list = array_merge($list, $Package->getProvider('auth'));
            } catch (QUI\Exception $exception) {
            }
        }

        foreach ($list as $provider) {
            try {
                if (!class_exists($provider)) {
                    continue;
                }

                $interfaces = class_implements($provider);

                if (isset($interfaces['QUI\Users\AuthenticatorInterface'])) {
                    $authList[] = trim($provider, '\\');
                }
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return $authList;
    }

    /**
     * Send e-mail to the user to confirm password reset
     *
     * @param QUI\Users\User $User
     * @return void
     *
     * @throws QUI\Exception
     */
    public function sendPasswordResetVerificationMail($User)
    {
        if (!$this->isQuiqqerVerificationPackageInstalled()) {
            throw new QUI\Exception(array(
                'quiqqer/system',
                'exception.user.auth.handler.verification_package_not_installed'
            ));
        }

        $email = $User->getAttribute('email');

        if (empty($email)) {
            return;
        }

        $EmailConfirmVerification = new PasswordResetVerification($User->getId());

        $confirmLink = QUI\Verification\Verifier::startVerification($EmailConfirmVerification, true);

        $L      = QUI::getLocale();
        $lg     = 'quiqqer/system';
        $tplDir = QUI::getPackage('quiqqer/quiqqer')->getDir() . 'lib/templates/mail/auth/';

        $Mailer = new QUI\Mail\Mailer();
        $Engine = QUI::getTemplateManager()->getEngine();

        $Engine->assign(array(
            'body' => $L->get($lg, 'mail.auth.password_reset_confirm.body', array(
                'username'    => $User->getUsername(),
                'date'        => $L->formatDate(time()),
                'confirmLink' => $confirmLink
            ))
        ));

        $template = $Engine->fetch($tplDir . 'password_reset_confirm.html');

        $Mailer->addRecipient($email);
        $Mailer->setSubject(
            $L->get($lg, 'mail.auth.password_reset_confirm.subject')
        );

        $Mailer->setBody($template);
        $Mailer->send();
    }

    /**
     * Check if the package "quiqqer/verification" is installed
     *
     * @return bool
     */
    public function isQuiqqerVerificationPackageInstalled()
    {
        $isInstalled = true;

        try {
            QUI::getPackage('quiqqer/verification');
        } catch (\Exception $Exception) {
            $isInstalled = false;
        }

        return $isInstalled;
    }
}
