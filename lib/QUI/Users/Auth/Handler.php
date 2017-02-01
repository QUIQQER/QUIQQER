<?php

/**
 * This file contains QUI\Users\Auth\Handler
 */
namespace QUI\Users\Auth;

use QUI;
use QUI\Users\AuthInterface;

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

        return $result;
    }

    /**
     * Returns a specific authenticator
     *
     * @param string $authenticator - name of the authenticator
     * @param string $username - name of the user
     *
     * @return AuthInterface
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

                if (isset($interfaces['QUI\Users\AuthInterface'])) {
                    $authList[] = trim($provider, '\\');
                }
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return $authList;
    }
}