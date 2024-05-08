<?php

namespace QUI\InstallationWizard;

use QUI;

use function array_merge;
use function class_exists;
use function class_implements;
use function file_exists;
use function file_put_contents;
use function get_class;
use function trim;

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

    protected static ?QUI\Config $Config = null;

    /**
     * @return InstallationWizardInterface[]
     */
    public static function getNotSetUpProviderList(): array
    {
        $notSetUp = [];
        $list = self::getProviderList();

        foreach ($list as $Provider) {
            if ($Provider->getStatus() !== self::STATUS_SET_UP_DONE) {
                $notSetUp[] = $Provider;
            }
        }

        return $notSetUp;
    }

    /**
     * Return all available Installation provider
     *
     * @return InstallationWizardInterface[]
     */
    public static function getProviderList(): array
    {
        $providerList = [];
        $list = [];
        $installed = QUI::getPackageManager()->getInstalled();

        foreach ($installed as $package) {
            try {
                $Package = QUI::getPackage($package['name']);

                if (!$Package->isQuiqqerPackage()) {
                    continue;
                }

                $list = array_merge($list, $Package->getProvider('installationWizard'));
            } catch (QUI\Exception) {
            }
        }

        foreach ($list as $provider) {
            try {
                if (!class_exists($provider)) {
                    continue;
                }

                $interfaces = class_implements($provider);

                if (isset($interfaces[InstallationWizardInterface::class])) {
                    $provider = trim($provider, '\\');
                    $providerList[] = new $provider();
                }
            } catch (\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        return $providerList;
    }

    public static function getProviderStatus(InstallationWizardInterface $Provider): int
    {
        try {
            return (int)self::getConfig()->get('status', $Provider::class);
        } catch (QUI\Exception) {
            return self::STATUS_SET_UP_NOT_STARTED;
        }
    }

    /**
     * @throws QUI\Exception
     */
    public static function getConfig(): ?QUI\Config
    {
        if (!file_exists(ETC_DIR . 'installationWizard.ini.php')) {
            file_put_contents(ETC_DIR . 'installationWizard.ini.php', '');
        }

        if (self::$Config === null) {
            self::$Config = QUI::getConfig('etc/installationWizard.ini.php');
        }

        return self::$Config;
    }

    /**
     * @throws QUI\Exception
     */
    public static function setProviderStatus(InstallationWizardInterface $Provider, int $status): void
    {
        self::getConfig()->set('status', $Provider::class, $status);
        self::getConfig()->save();
    }
}
