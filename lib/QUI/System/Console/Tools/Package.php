<?php

/**
 * \QUI\System\Console\Tools\Defaults
 */

namespace QUI\System\Console\Tools;

use QUI;
use League\CLImate\CLImate;

/**
 * Package console tool
 * Package handling via CLI
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Package extends QUI\System\Console\Tool
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setName('quiqqer:package')
            ->setDescription('Package management')
            ->addArgument('help', 'List the help message', false, true)
            ->addArgument('list', 'List all installed packages', false, true)
            ->addArgument('install', 'Install a package', false, true)
            ->addArgument('remove', 'Remove a package', false, true)
            ->addArgument('setup', 'List all installed packages', false, true);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        if ($this->getArgument('help')) {
            $this->showHelp();

            return;
        }

        if ($this->getArgument('list')) {
            $this->showList();

            return;
        }

        if ($this->getArgument('setup')) {
            $this->executePackageSetup($this->getArgument('setup'));

            return;
        }

        if ($this->getArgument('show')) {
            $this->showPackageInformation($this->getArgument('show'));

            return;
        }
    }

    /**
     * Prints the help
     */
    protected function showHelp()
    {
        $this->writeLn();

        $Climate = new CLImate();

        $Climate->arguments->add([
            'help'    => [
                'longPrefix'  => 'help',
                'description' => 'List the help message',
                'noValue'     => true
            ],
            'list'    => [
                'longPrefix'  => 'list',
                'description' => 'List all installed packages',
                'noValue'     => true
            ],
            'setup'   => [
                'longPrefix'  => 'setup',
                'description' => 'Execute a package setup'
            ],
            'install' => [
                'longPrefix'  => 'install',
                'description' => 'Install a package'
            ],
//            'remove'  => [
//                'longPrefix'  => 'remove',
//                'description' => 'Remove a package'
//            ],
            'show'    => [
                'longPrefix'  => 'show',
                'description' => 'Show package information'
            ]
        ]);

        $Climate->usage(array(
            'quiqqer.php package'
        ));
        exit;
    }

    /**
     * Show the package list with its versions
     */
    protected function showList()
    {
        $installed = QUI::getPackageManager()->getInstalled();

        usort($installed, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        $data = [];

        foreach ($installed as $package) {
            $data[] = array(
                'name'    => $package['name'],
                'version' => $package['version']
            );
        }

        $this->writeLn();

        $Climate = new CLImate();
        $Climate->table($data);
    }

    /**
     * Show package information
     *
     * @param string $package
     */
    protected function showPackageInformation($package)
    {
        $this->writeLn();
        $Climate = new CLImate();

        try {
            $Package = QUI::getPackage($package);
            $Climate->lightGreen(' '.$package);
            $Climate->out('');

            $composer = $Package->getComposerData();
            $data     = array();

            foreach ($composer as $key => $entry) {
                if (is_array($entry)) {
                    continue;
                }

                $data[] = array($key, $entry);
            }


            // default data
            $Climate->table($data);

            if (!empty($composer['authors'])) {
                $Climate->out('');
                $Climate->lightGreen(' Authors');
                $Climate->out('');

                $Climate->table($composer['authors']);
            }

            if (!empty($composer['support'])) {
                $Climate->out('');
                $Climate->lightGreen(' Support');
                $Climate->out('');

                $support = [];

                foreach ($composer['support'] as $key => $value) {
                    $support[] = [$key, $value];
                }

                $Climate->table($support);
            }

            if (!empty($composer['require'])) {
                $Climate->out('');
                $Climate->lightGreen(' Require');
                $Climate->out('');

                $require = [];

                foreach ($composer['require'] as $key => $value) {
                    $require[] = [$key, $value];
                }

                $Climate->table($require);
            }
        } catch (QUI\Exception $Exception) {
            $Climate->error($Exception->getMessage());
            exit;
        }
    }

    /**
     * Execute the setup for a package
     *
     * @param string $package
     */
    protected function executePackageSetup($package)
    {
        $this->writeLn();
        $Climate = new CLImate();

        try {
            $Package = QUI::getPackage($package);

            $Climate->output->write('Execute setup for package');
            $Climate->lightGreen($package);
            $Package->setup();
        } catch (QUI\Exception $Exception) {
            $Climate->error($Exception->getMessage());
            exit;
        }
    }

    /**
     * Install a package
     *
     * @param string $package
     */
    protected function installPackage($package)
    {
        $this->writeLn();
        $Climate = new CLImate();

        $Climate->output->write('Package installation from '.$package);
        $PackageManager = QUI::getPackageManager();
        $PackageManager->install($package);
    }
}
