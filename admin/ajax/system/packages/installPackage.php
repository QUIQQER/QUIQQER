<?php

use QUI\Package\PackageInstallException;

/**
 * Install a wanted package
 * - used via the store
 *
 * @param string|array $packages - Name of the package
 * @return bool - success
 *
 * @throws PackageInstallException
 */
QUI::$Ajax->registerFunction(
    'ajax_system_packages_installPackage',
    function ($packageName, $packageVersion, $server) {
        $Packages = QUI::getPackageManager();
        $server   = \json_decode($server, true);

        if ($server && \is_array($server)) {
            foreach ($server as $s) {
                $Packages->addServer($s['server'], [
                    'type' => $s['type']
                ]);
            }
        }

        try {
            $Packages->install($packageName, $packageVersion);
        } catch (PackageInstallException $Exception) {
            throw $Exception;
        } catch (\QUI\Exception $Exception) {
            QUI::getMessagesHandler()->addError($Exception->getMessage());

            return false;
        } catch (\Exception $Exception) {
            return false;
        }

        return true;
    },
    ['packageName', 'packageVersion', 'server'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
