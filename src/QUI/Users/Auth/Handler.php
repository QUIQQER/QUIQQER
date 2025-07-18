<?php

/**
 * This file contains QUI\Users\Auth\Handler
 */

namespace QUI\Users\Auth;

use Composer\Semver\Semver;
use QUI;
use QUI\Database\Exception;
use QUI\Users\AuthenticatorInterface;
use QUI\Verification\Interface\VerificationFactoryInterface;
use QUI\Verification\VerificationFactory;

use function array_flip;
use function array_merge;
use function class_exists;
use function class_implements;
use function is_null;
use function str_replace;
use function strcmp;
use function time;
use function trim;
use function usort;

/**
 * Class Handler
 * Main Class, Handling class for authenticators
 */
class Handler
{
    /**
     * global instance
     */
    protected static ?Handler $Instance = null;

    public function __construct(private ?VerificationFactoryInterface $verificationFactory = null)
    {
        if (is_null($this->verificationFactory) && $this->isQuiqqerVerificationPackageInstalled()) {
            $this->verificationFactory = new VerificationFactory();
        }
    }

    /**
     * Return the global QUI\Users\Auth\Handler instance
     */
    public static function getInstance(): Handler
    {
        if (is_null(self::$Instance)) {
            self::$Instance = new self();
        }

        return self::$Instance;
    }

    /**
     * @throws Exception
     */
    public static function onPackageSetup(QUI\Package\Package $Package): void
    {
        // create auth provider as user permissions
        $authProviders = $Package->getProvider('auth');

        if (empty($authProviders)) {
            return;
        }

        // <permission name="quiqqer.auth.AUTH.canUse" type="bool" />
        $Locale = new QUI\Locale();
        $Permissions = new QUI\Permissions\Manager();
        $User = QUI::getUserBySession();

        $Locale->no_translation = true;

        foreach ($authProviders as $authProvider) {
            if (trim($authProvider, '\\') === QUIQQER::class) {
                continue;
            }

            /* @var $Authenticator AuthenticatorInterface */
            $Authenticator = new $authProvider($User->getUsername());
            $permissionName = Helper::parseAuthenticatorToPermission($authProvider);

            $Permissions->addPermission([
                'name' => $permissionName,
                'title' => str_replace(['[', ']'], '', $Authenticator->getTitle($Locale)),
                'desc' => str_replace(['[', ']'], '', $Authenticator->getDescription($Locale)),
                'type' => 'bool',
                'area' => '',
                'src' => $Package->getName(),
                'defaultvalue' => 0
            ]);
        }
    }

    /**
     * Return all global active authenticators for the frontend authentication
     * - alias for getGlobalFrontendAuthenticators
     */
    public function getGlobalAuthenticators(): array
    {
        return $this->getGlobalFrontendAuthenticators();
    }

    /**
     * Return all global active authenticators for the frontend authentication
     */
    public function getGlobalFrontendAuthenticators(): array
    {
        return $this->getAuthenticatorFromConfig(QUI::conf('auth_frontend') ?: []);
    }

    public function getGlobalFrontendSecondaryAuthenticators(): array
    {
        if (empty(QUI::conf('auth_frontend_secondary'))) {
            return [];
        }

        return $this->getAuthenticatorFromConfig(QUI::conf('auth_frontend_secondary'));
    }

    protected function getAuthenticatorFromConfig(array $authenticatorConfig = []): array
    {
        if (empty($authenticatorConfig)) {
            return [
                QUIQQER::class
            ];
        }

        $result = [];

        $available = $this->getAvailableAuthenticators();
        $available = array_flip($available);

        foreach ($authenticatorConfig as $authenticator => $status) {
            if ($status != 1) {
                continue;
            }

            if (isset($available[$authenticator])) {
                $result[] = $authenticator;
            }
        }

        if (empty($result)) {
            return [
                QUIQQER::class
            ];
        }

        // sorting
        usort($result, static function ($a, $b): int {
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
     * Return all available authenticators
     */
    public function getAvailableAuthenticators(): array
    {
        $cache = 'quiqqer/permissions/authenticator/available';

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception) {
        }

        $authList = [];
        $list = [];
        $installed = QUI::getPackageManager()->getInstalled();

        foreach ($installed as $package) {
            try {
                $Package = QUI::getPackage($package['name']);

                if (!$Package->isQuiqqerPackage()) {
                    continue;
                }

                $list = array_merge($list, $Package->getProvider('auth'));
            } catch (QUI\Exception) {
            }
        }

        foreach ($list as $provider) {
            try {
                if (!class_exists($provider)) {
                    continue;
                }

                $interfaces = class_implements($provider);

                if (isset($interfaces[AuthenticatorInterface::class])) {
                    $authList[] = trim($provider, '\\');
                }
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        QUI\Cache\Manager::set($cache, $authList);

        return $authList;
    }

    /**
     * Return all global active authenticators for the backend authentication
     */
    public function getGlobalBackendAuthenticators(): array
    {
        return $this->getAuthenticatorFromConfig(QUI::conf('auth_backend') ?: []);
    }

    /**
     * Return all global active authenticators for the backend authentication
     */
    public function getGlobalBackendSecondaryAuthenticators(): array
    {
        if (empty(QUI::conf('auth_backend_secondary'))) {
            return [];
        }

        return $this->getAuthenticatorFromConfig(QUI::conf('auth_backend_secondary'));
    }

    /**
     * Returns a specific authenticator
     *
     * @param string $authenticator - name of the authenticator
     * @param string $username - QUIQQER username of the user
     * @return AuthenticatorInterface
     *
     * @throws QUI\Users\Auth\Exception
     */
    public function getAuthenticator(string $authenticator, string $username): AuthenticatorInterface
    {
        $authenticators = $this->getAvailableAuthenticators();
        $authenticators = array_flip($authenticators);

        if (isset($authenticators[$authenticator])) {
            return new $authenticator($username);
        }

        throw new QUI\Users\Auth\Exception(
            ['quiqqer/core', 'exception.authenticator.not.found'],
            404
        );
    }

    /**
     * Send e-mail to the user to confirm password reset
     *
     *
     * @throws QUI\Exception
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function sendPasswordResetVerificationMail(QUI\Users\User $User): void
    {
        if (!$this->isQuiqqerVerificationPackageInstalled()) {
            throw new QUI\Exception([
                'quiqqer/core',
                'exception.user.auth.handler.verification_package_not_installed'
            ]);
        }

        $email = $User->getAttribute('email');

        if (empty($email)) {
            return;
        }

        $Project = QUI::getRewrite()->getProject();

        $verification = $this->verificationFactory->createLinkVerification(
            'resetpassword-' . $User->getUUID(),
            new PasswordResetVerification(),
            [
                'uuid' => $User->getUUID(),
                'project' => $Project->getName(),
                'projectLang' => $Project->getLang()
            ],
            true
        );

        $L = QUI::getLocale();
        $lg = 'quiqqer/core';
        $tplDir = QUI::getPackage('quiqqer/core')->getDir() . 'src/templates/mail/auth/';

        $Mailer = new QUI\Mail\Mailer();
        $Engine = QUI::getTemplateManager()->getEngine();

        $Engine->assign([
            'body' => $L->get($lg, 'mail.auth.password_reset_confirm.body', [
                'username' => $User->getUsername(),
                'date' => $L->formatDate(time()),
                'confirmLink' => $verification->getVerificationUrl()
            ])
        ]);

        $template = $Engine->fetch($tplDir . 'password_reset_confirm.html');

        $Mailer->addRecipient($email);
        $Mailer->setSubject(
            $L->get($lg, 'mail.auth.password_reset_confirm.subject')
        );

        $Mailer->setBody($template);
        $Mailer->send();
    }

    /**
     * Check if the package "quiqqer/verification" is installed and has the minimal required version.
     */
    public function isQuiqqerVerificationPackageInstalled(): bool
    {
        if (!QUI::getPackageManager()->isInstalled('quiqqer/verification')) {
            return false;
        }

        try {
            $package = QUI::getPackage('quiqqer/verification');
            $requiredVersion = "^3|dev-*";

            return Semver::satisfies($package->getVersion(), $requiredVersion);
        } catch (\Exception $exception) {
            QUI\System\Log::writeDebugException($exception);
            return false;
        }
    }
}
