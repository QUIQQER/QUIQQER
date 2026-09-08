<?php

namespace QUI\InstallationWizard;

use QUI;

use function array_merge;
use function class_exists;
use function class_implements;
use function fclose;
use function file_exists;
use function file_put_contents;
use function flock;
use function fopen;
use function get_class;
use function is_array;
use function is_resource;
use function is_string;
use function json_decode;
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
                    $Provider = new $provider();

                    if (!$Provider instanceof InstallationWizardInterface) {
                        continue;
                    }

                    $providerList[] = $Provider;
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
    public static function getConfig(): QUI\Config
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

    public static function prepareExecution(string $provider, string $data): bool
    {
        if (
            !class_exists($provider)
            || !is_a($provider, InstallationWizardInterface::class, true)
        ) {
            return false;
        }

        $lock = self::acquireExecutionLock();

        try {
            $Config = self::getConfig();
            $Config->reload();
            $Config->set('execute', 'provider', $provider);
            $Config->set('execute', 'data', $data);
            $Config->set('status', $provider, self::STATUS_SET_UP_STARTED);
            $Config->save();
        } finally {
            self::releaseExecutionLock($lock);
        }

        return true;
    }

    /**
     * Atomically returns and consumes the pending provider execution.
     *
     * @return array{provider: class-string<InstallationWizardInterface>, data: array<array-key, mixed>}
     */
    public static function claimExecution(): array
    {
        $lock = self::acquireExecutionLock();

        try {
            $Config = self::getConfig();
            $Config->reload();
            $provider = $Config->get('execute', 'provider');
            $data = $Config->get('execute', 'data');

            if (
                !is_string($provider)
                || !is_a($provider, InstallationWizardInterface::class, true)
                || (int)$Config->get('status', $provider) !== self::STATUS_SET_UP_STARTED
            ) {
                throw new \UnexpectedValueException('No pending installation wizard execution.');
            }

            $data = is_string($data) ? json_decode($data, true) : null;

            if (!is_array($data)) {
                throw new \UnexpectedValueException('Invalid installation wizard execution data.');
            }

            $Config->del('execute');
            $Config->save();

            /** @var class-string<InstallationWizardInterface> $provider */
            return [
                'provider' => $provider,
                'data' => $data
            ];
        } finally {
            self::releaseExecutionLock($lock);
        }
    }

    /**
     * @return resource
     */
    private static function acquireExecutionLock()
    {
        $Config = self::getConfig();
        $lock = fopen($Config->getFilename(), 'c+');

        if ($lock === false) {
            throw new \RuntimeException('Could not open installation wizard execution lock.');
        }

        if (!flock($lock, LOCK_EX)) {
            fclose($lock);
            throw new \RuntimeException('Could not acquire installation wizard execution lock.');
        }

        return $lock;
    }

    /**
     * @param resource $lock
     */
    private static function releaseExecutionLock($lock): void
    {
        if (!is_resource($lock)) {
            return;
        }

        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
