<?php

namespace QUI\System\Console\Tools;

use League\CLImate\CLImate;
use QUI;

use function implode;
use function is_array;

/**
 * Manage the QUIQQER store license
 *
 * @author  www.pcsg.de (Jan Wennrich)
 * @licence For copyright and license information, please view the /README.md
 */
class LicenseManager extends QUI\System\Console\Tool
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->systemTool = true;
        $this->setName('quiqqer:license-manager')
            ->setDescription('Manage your QUIQQER system license')
            ->addArgument('register-license', 'Register the given license file', false, true)
            ->addArgument('activate-registered-license', 'Activate the registered license file', false, true)
            ->addArgument('delete-registered-license', 'Show information about your registered license', false, true)
            ->addArgument('show-license-status', 'Show the status of your registered license', false, true)
            ->addArgument('show-license', 'Show information about your registered license', false, true)
            ->addArgument('show-system-id', 'Show the ID of your QUIQQER system', false, true);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute(): void
    {
        $this->writeLn();

        if ($this->getArgument('register-license')) {
            $this->registerLicense();

            exit;
        }

        if ($this->getArgument('activate-registered-license')) {
            $this->activateLicense();

            exit;
        }

        if ($this->getArgument('delete-registered-license')) {
            $this->deleteLicense();

            exit;
        }

        if ($this->getArgument('show-license-status')) {
            $this->showLicenseStatus();

            exit;
        }

        if ($this->getArgument('show-license')) {
            $this->showLicense();

            exit;
        }

        if ($this->getArgument('show-system-id')) {
            $this->showSystemId();

            exit;
        }

        $this->writeLn('Unknown "quiqqer:license-manager" command specified.', 'yellow');
        $this->writeLn('Use "quiqqer:license-manager --help" to print usage information.', 'yellow');

        exit;
    }

    private function registerLicense(): bool
    {
        $filepath = $this->getArgument('register-license');

        if ($filepath == 1) {
            $this->writeLn('Error: Please specify the path to your license file via "--register=path/to/file.license"', 'red');
            return false;
        }

        if (!file_exists($filepath)) {
            $this->writeLn("Error: License file does not exist at path \"$filepath\".", 'red');
            return false;
        }

        $File = new QUI\QDOM();
        $File->setAttribute('filepath', $filepath);

        try {
            QUI\System\License::registerLicenseFile($File);
        } catch (\Exception $Exception) {
            $this->writeLn('Error: ' . $Exception->getMessage(), 'red');
            return false;
        }

        $this->writeLn('License successfully registered.', 'green');

        return true;
    }

    private function activateLicense(): bool
    {
        try {
            $activationResponse = QUI\System\License::activateSystem();
        } catch (\Exception $Exception) {
            $this->writeLn('Error: ' . $Exception->getMessage(), 'red');
            return false;
        }

        if (!isset($activationResponse['error'])) {
            $this->writeLn('Error: Communication with license server failed. Please try again later.', 'red');

            return false;
        }

        if ($activationResponse['error']) {
            $this->writeLn('Error: The license could not be activated', 'red');

            if (isset($activationResponse['msg'])) {
                $this->writeLn($activationResponse['msg'], 'red');
            }

            return false;
        }

        $this->writeLn('The license was successfully activated', 'green');

        if (isset($activationResponse['msg'])) {
            $this->writeLn($activationResponse['msg'], 'green');
        }

        return true;
    }

    private function showLicenseStatus(): bool
    {
        try {
            $licenseStatus = QUI\System\License::getStatus();
        } catch (\Exception $Exception) {
            $this->writeLn('Error: Could not retrieve license status:', 'red');
            $this->writeLn($Exception->getMessage(), 'red');

            return false;
        }

        if (!$licenseStatus) {
            $this->writeLn('Error: The system does not have a registered license.', 'red');
            $this->writeLn('See "quiqqer:license-manager --help" for information on how to register a license.', 'red');

            return true;
        }

        $this->write('Active: ');
        if ($licenseStatus['active']) {
            $this->write('Yes', 'green');
        } else {
            $this->write('No', 'red');
        }
        $this->resetColor();
        $this->writeLn();

        $this->write('Reason Code: ');
        $this->write($licenseStatus['reasonCode'] ?: '-', 'green');
        $this->resetColor();
        $this->writeLn();

        $this->write('Remaining Activations: ');
        $this->write($licenseStatus['remainingActivations'] ?: '-', 'green');
        $this->resetColor();
        $this->writeLn();

        return true;
    }

    private function showLicense(): bool
    {
        try {
            $licenseData = QUI\System\License::getLicenseData();
        } catch (\Exception $Exception) {
            $this->writeLn('Error: Could not retrieve license status:', 'red');
            $this->writeLn($Exception->getMessage(), 'red');

            return false;
        }

        if (!$licenseData) {
            $this->writeLn('Error: The system does not have a registered license.', 'red');
            $this->writeLn('See "quiqqer:license-manager --help" for information on how to register a license.', 'red');

            return true;
        }

        $this->write('License ID: ');
        $this->write($licenseData['id'], 'green');
        $this->resetColor();
        $this->writeLn();

        $this->write('Created on: ');
        $this->write(date('Y-m-d H:i:s', $licenseData['created']), 'green');
        $this->resetColor();
        $this->writeLn();

        $this->write('Issued to: ');
        $this->write($licenseData['name'], 'green');
        $this->resetColor();
        $this->writeLn();

        $this->write('Valid until: ');
        $this->write($licenseData['validUntil'], 'green');
        $this->resetColor();
        $this->writeLn();

        $this->write('License hash: ');
        $this->write($licenseData['licenseHash'], 'green');
        $this->resetColor();
        $this->writeLn();

        return true;
    }

    private function deleteLicense(): bool
    {
        try {
            QUI\System\License::deleteLicense();
        } catch (QUI\Exception $Exception) {
            $this->writeLn('Error: Could not retrieve license status:', 'red');
            $this->writeLn($Exception->getMessage(), 'red');

            return false;
        }

        $this->writeLn('License successfully deleted', 'green');

        return true;
    }

    private function showSystemId(): bool
    {
        $this->writeLn('Your system ID is:');
        $this->writeLn(QUI\System\License::getSystemId(), 'green');

        return true;
    }
}
