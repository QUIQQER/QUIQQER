<?php

/**
 * This file contains \QUI\System\Console\Tools\Licence
 */

namespace QUI\System\Console\Tools;

use League\CLImate\CLImate;
use QUI;

use function implode;
use function is_array;

/**
 * Show the licence
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Licence extends QUI\System\Console\Tool
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->systemTool = true;
        $this->setName('quiqqer:licence')
            ->setDescription('Show information about QUIQQER licences')
            ->addArgument('list', 'Print a list of all licenses', false, true)
            ->addArgument('show', 'Print the licence of a given package', false, true);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute(): void
    {
        $this->writeLn();

        if ($this->getArgument('list')) {
            $this->listLicences();

            return;
        }

        if ($this->getArgument('show')) {
            $this->showLicence();

            return;
        }

        $this->printSystemLicenseInformation();
        $this->writeLn();

        $this->write('To manage your QUIQQER system license use ');
        $this->write('"quiqqer:license-manager"', 'green');
        $this->resetColor();

        $this->writeLn();
        $this->writeLn();

        $this->write('To find out how to get information about package licenses use ');
        $this->write('"quiqqer:licence --help"', 'green');

        $this->resetColor();
        $this->writeLn();
    }

    private function showLicence(): void
    {
        $packageName = $this->getArgument('show');

        if ($packageName === '1') {
            $this->writeLn('Error: Please specify a package name along the "show" argument', 'red');

            return;
        }

        try {
            $Package = QUI::getPackage($packageName);
        } catch (QUI\Exception $e) {
            $this->writeLn("Error: Package '$packageName' is not installed or package name is invalid.", 'red');

            return;
        }

        $packageDirectory = $Package->getDir();

        $licenceFile = $packageDirectory . '/LICENSE';

        if (file_exists($licenceFile)) {
            $this->writeLn(file_get_contents($licenceFile));

            return;
        }

        $this->writeLn('Notice: Package has no dedicated "LICENSE" file.', 'yellow');
        $this->resetColor();

        $packageComposerData = $Package->getComposerData();

        if (isset($packageComposerData['license'])) {
            $this->writeLn("License according to package's composer.json file:");
            $this->writeLn($packageComposerData['license'], 'green');

            return;
        }

        try {
            QUI::getEvents()->fireEvent('quiqqerConsoleLicence', [$packageName]);
        } catch (QUI\Exception) {
        }

        $this->writeLn("Package '$packageName' does not specify a license.", 'red');
    }

    private function listLicences(): void
    {
        $installed = QUI::getPackageManager()->getInstalled();
        $data = [];

        foreach ($installed as $package) {
            $license = '';

            if (isset($package['license'])) {
                $license = $package['license'];
            } else {
                try {
                    // check composer json
                    $Package = QUI::getPackageManager()->getInstalledPackage($package['name']);
                    $composer = $Package->getComposerData();

                    if (isset($composer['license'])) {
                        $license = $composer['license'];
                    } elseif (isset($composer['licence'])) {
                        $license = $composer['licence'];
                    }
                } catch (QUI\Exception) {
                }
            }

            if (is_array($license)) {
                $license = implode(',', $license);
            }

            $data[] = [
                $package['name'],
                $license
            ];
        }

        try {
            QUI::getEvents()->fireEvent('quiqqerConsoleLicenceList', [&$data]);
        } catch (QUI\Exception) {
        }

        $Climate = new CLImate();
        $Climate->columns($data);
        $Climate->out('');
    }

    public function printSystemLicenseInformation(): void
    {
        try {
            $licenseStatus = QUI\System\License::getStatus();
        } catch (\Exception) {
            $this->writeLn('Error: Could not retrieve the status of the system license.', 'red');

            return;
        }

        if (!$licenseStatus) {
            $this->writeLn('The system does not have a registered license.');

            return;
        }


        $this->write('The system uses an ');

        if ($licenseStatus['active']) {
            $this->write('active ', 'green');
        } else {
            $this->write('inactive ', 'red');
        }

        $this->resetColor();
        $this->write('license.');
    }
}
