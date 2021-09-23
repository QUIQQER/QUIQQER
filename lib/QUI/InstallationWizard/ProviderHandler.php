<?php

namespace QUI\InstallationWizard;

use QUI;

/**
 * Class ProviderHandler
 *
 * - Installation provider handler
 */
class ProviderHandler
{
    const STATUS_SET_UP_NOT_STARTED = 0;

    const STATUS_SET_UP_STARTED = 1;

    const STATUS_SET_UP_DONE = 2;

    /**
     * @var QUI\Config|null
     */
    protected static ?QUI\Config $Config = null;

    /**
     * @return QUI\Config|null
     * @throws QUI\Exception
     */
    public static function getConfig(): ?QUI\Config
    {
        if (!\file_exists(ETC_DIR.'installationWizard.ini.php')) {
            \file_put_contents(ETC_DIR.'installationWizard.ini.php', '');
        }

        if (self::$Config === null) {
            self::$Config = QUI::getConfig('etc/installationWizard.ini.php');
        }

        return self::$Config;
    }

    /**
     * Return all available Installation provider
     *
     * @return InstallationWizardInterface[]
     */
    public static function getProviderList(): array
    {
        $providerList = [];
        $list         = [];
        $installed    = QUI::getPackageManager()->getInstalled();

        foreach ($installed as $package) {
            try {
                $Package = QUI::getPackage($package['name']);

                if (!$Package->isQuiqqerPackage()) {
                    continue;
                }

                $list = \array_merge($list, $Package->getProvider('installationWizard'));
            } catch (QUI\Exception $exception) {
            }
        }

        foreach ($list as $provider) {
            try {
                if (!\class_exists($provider)) {
                    continue;
                }

                $interfaces = \class_implements($provider);

                if (isset($interfaces['QUI\InstallationWizard\InstallationWizardInterface'])) {
                    $provider       = \trim($provider, '\\');
                    $providerList[] = new $provider();
                }
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return $providerList;
    }

    /**
     * @return InstallationWizardInterface[]
     */
    public static function getNotSetUpProviderList(): array
    {
        $notSetUp = [];
        $list     = self::getProviderList();

        foreach ($list as $Provider) {
            if ($Provider->getStatus() !== self::STATUS_SET_UP_DONE) {
                $notSetUp[] = $Provider;
            }
        }

        return $notSetUp;
    }

    /**
     * @param InstallationWizardInterface $Provider
     * @return int
     */
    public static function getProviderStatus(InstallationWizardInterface $Provider): int
    {
        try {
            return (int)self::getConfig()->get('status', \get_class($Provider));
        } catch (QUI\Exception $Exception) {
            return self::STATUS_SET_UP_NOT_STARTED;
        }
    }

    /**
     * @param InstallationWizardInterface $Provider
     * @param int $status
     * @throws QUI\Exception
     */
    public static function setProviderStatus(InstallationWizardInterface $Provider, int $status)
    {
        self::getConfig()->set('status', \get_class($Provider), $status);
        self::getConfig()->save();
    }
}
