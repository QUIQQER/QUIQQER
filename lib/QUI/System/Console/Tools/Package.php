<?php

/**
 * \QUI\System\Console\Tools\Defaults
 */

namespace QUI\System\Console\Tools;

use League\CLImate\CLImate;
use QUI;

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
        $this->systemTool = true;

        $this->setName('quiqqer:package')
            ->setDescription('Package management')
            ->addArgument(
                'help',
                QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.package.help.description'),
                false,
                true
            )
            ->addArgument(
                'list',
                QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.package.installed.description'),
                false,
                true
            )
            ->addArgument(
                'show',
                QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.package.show.description'),
                false,
                true
            )
            ->addArgument(
                'install',
                QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.package.install.description'),
                false,
                true
            )
            ->addArgument(
                'setup',
                QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.package.setup.description'),
                false,
                true
            );
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        if ($this->getArgument('help')) {
            $this->outputHelp();

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

        if ($this->getArgument('install')) {
            $this->installPackage($this->getArgument('install'));

            return;
        }

        if ($this->getArgument('show')) {
            $this->showPackageInformation($this->getArgument('show'));

            return;
        }

        $this->outputHelp();
    }

    /**
     * Show the package list with its versions
     */
    protected function showList()
    {
        $installed = QUI::getPackageManager()->getInstalledVersions();

        \ksort($installed);

        $data = [];

        foreach ($installed as $package => $version) {
            $data[] = [
                'name' => $package,
                'version' => $version
            ];
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
            $Climate->lightGreen(' ' . $package);
            $Climate->out('');

            $composer = $Package->getComposerData();
            $data     = [];

            foreach ($composer as $key => $entry) {
                if (\is_array($entry)) {
                    continue;
                }

                $data[] = [$key, $entry];
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

            $Climate->output->write(
                QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.package.message.setup.execute')
            );
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

        if (empty($package)) {
            $Climate->output->write(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'console.tool.package.message.install.noPackage',
                )
            );
        }

        try {
            QUI::getPackage($package);
            $this->writeLn('Package already exists');
        } catch (QUI\Exception $Exception) {
            $Climate->output->write(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'console.tool.package.message.install.execute',
                    ['package' => $package]
                )
            );

            $PackageManager = QUI::getPackageManager();
            $Composer       = $PackageManager->getComposer();
            $Console        = $this;

            $Composer->addEvent('output', function ($self, $data, $type) use ($Console) {
                $Console->writeLn($data);
            });

            $Composer->unmute();
            $Composer->requirePackage($package);
            //$PackageManager->install($package);
        }
    }
}
