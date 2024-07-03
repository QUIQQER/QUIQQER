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
            ->addArgument('show', 'Print the QUIQQER licence', false, true);
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

            exit;
        }

        if ($this->getArgument('show')) {
            $this->showLicence();

            exit;
        }

        $this->write('To view the QUIQQER licence use ');
        $this->write('"quiqqer:licence --show"', 'green');

        $this->resetColor();
        $this->writeLn();
        $this->writeLn();

        $this->write('To list the licences of all QUIQQER packages use ');
        $this->write('"quiqqer:licence --list"', 'green');

        $this->resetColor();
        $this->writeLn();
        $this->writeLn();

        $this->write('For further usage information use ');
        $this->write('"quiqqer:licence --help"', 'green');

        $this->resetColor();
        $this->writeLn();
        $this->writeLn();

        $this->write('Hint: ', 'yellow');
        $this->resetColor();
        $this->write('To manage your QUIQQER system license use ');
        $this->write('"quiqqer:license-manager"', 'green');

        $this->resetColor();

        exit;
    }

    private function showLicence(): void
    {
        $licenceFile = OPT_DIR . 'quiqqer/core/LICENSE';
        $content = file_get_contents($licenceFile);

        $this->writeLn($content);
        $this->writeLn();
        $this->writeLn();
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

        $Climate = new CLImate();
        $Climate->columns($data);
        $Climate->out('');
    }
}
