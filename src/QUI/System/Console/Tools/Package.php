<?php

/**
 * \QUI\System\Console\Tools\Defaults
 */

namespace QUI\System\Console\Tools;

use League\CLImate\CLImate;
use QUI;

use function array_keys;
use function file_get_contents;
use function is_array;
use function json_decode;
use function ksort;

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
                QUI::getLocale()->get('quiqqer/core', 'console.tool.package.help.description'),
                false,
                true
            )
            ->addArgument(
                'list',
                QUI::getLocale()->get('quiqqer/core', 'console.tool.package.installed.description'),
                false,
                true
            )
            ->addArgument(
                'show',
                QUI::getLocale()->get('quiqqer/core', 'console.tool.package.show.description'),
                false,
                true
            )
            ->addArgument(
                'search',
                QUI::getLocale()->get('quiqqer/core', 'console.tool.package.search.description'),
                false,
                true
            )
            ->addArgument(
                'install',
                QUI::getLocale()->get('quiqqer/core', 'console.tool.package.install.description'),
                false,
                true
            )
            ->addArgument(
                'setup',
                QUI::getLocale()->get('quiqqer/core', 'console.tool.package.setup.description'),
                false,
                true
            )
            ->addArgument(
                'purge',
                QUI::getLocale()->get('quiqqer/core', 'console.tool.package.purge.description'),
                false,
                true
            )
            ->addArgument(
                'remove',
                QUI::getLocale()->get('quiqqer/core', 'console.tool.package.purge.description'),
                false,
                true
            );
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute(): void
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

        if ($this->getArgument('search')) {
            $this->searchPackage($this->getArgument('search'));

            return;
        }

        if ($this->getArgument('remove')) {
            $this->removePackage($this->getArgument('remove'));

            return;
        }

        if ($this->getArgument('purge')) {
            $this->removePackage($this->getArgument('purge'));

            return;
        }

        $this->outputHelp();
    }

    /**
     * Show the package list with its versions
     */
    protected function showList(): void
    {
        $installed = QUI::getPackageManager()->getInstalledVersions();

        ksort($installed);

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
     * Execute the setup for a package
     *
     * @param string $package
     */
    protected function executePackageSetup(string $package): void
    {
        $this->writeLn();
        $Climate = new CLImate();

        try {
            $Package = QUI::getPackage($package);

            $Climate->output->write(
                QUI::getLocale()->get('quiqqer/core', 'console.tool.package.message.setup.execute')
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
    protected function installPackage(string $package): void
    {
        $this->writeLn();
        $Climate = new CLImate();

        if (empty($package) || $package === '1') {
            $Climate->output->write(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'console.tool.package.message.install.noPackage',
                )
            );

            exit;
        }

        Update::writeToLog('Install package ' . $package);

        try {
            QUI::getPackage($package);
            $this->writeLn('Package already exists');
            Update::writeToLog('Package already exists');
            exit;
        } catch (QUI\Exception) {
            Update::writeToLog(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'console.tool.package.message.install.execute',
                    ['package' => $package]
                )
            );

            $Climate->output->write(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'console.tool.package.message.install.execute',
                    ['package' => $package]
                )
            );

            $CLIOutput = new QUI\System\Console\Output();
            $CLIOutput->Events->addEvent('onWrite', function ($message) {
                Update::onCliOutput($message, $this);
            });

            $Packages = QUI::getPackageManager();
            $Composer = $Packages->getComposer();
            $Composer->setOutput($CLIOutput);
            $Composer->unmute();

            $Composer->requirePackage($package, '', [
                '-vvv' => true
            ]);
        }
    }

    /**
     * Show package information
     *
     * @param string $package
     */
    protected function showPackageInformation(string $package): void
    {
        $this->writeLn();
        $Climate = new CLImate();

        try {
            $Package = QUI::getPackage($package);
            $Climate->lightGreen(' ' . $package);
            $Climate->out('');

            $composer = $Package->getComposerData();
            $data = [];

            foreach ($composer as $key => $entry) {
                if (is_array($entry)) {
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

    protected function searchPackage($search): void
    {
        $this->writeLn();

        $Spinner = new QUI\System\Console\Spinner(
            QUI\System\Console\Spinner::DOTS
        );

        $Spinner->run('Searching...', function () use ($search, $Spinner) {
            $Composer = QUI::getPackageManager()->getComposer();
            $result = $Composer->search($search);
            $Spinner->stop();

            // remove first element, because of wrong output, first line is not a package
            array_shift($result);
            $table = [];

            foreach ($result as $k => $v) {
                $table[] = [trim($k), trim($v)];
            }

            $Climate = new CLImate();
            $Climate->out('');
            $Climate->lightGreen(
                ' ' . QUI::getLocale()->get('quiqqer/core', 'console.tool.package.search.resultMessage', [
                    'count' => count($table)
                ])
            );
            $Climate->out('');
            $Climate->table($table);
        });
    }

    protected function removePackage(string $package): void
    {
        if ($package === 'quiqqer/core') {
            $this->writeLn(
                QUI::getLocale()->get('quiqqer/core', 'console.tool.package.cannot.be.removed', [
                    'package' => $package
                ]),
                'red'
            );

            $this->resetColor();
            $this->writeLn();
            return;
        }

        // check composer json
        $composer = file_get_contents(VAR_DIR . 'composer/composer.json');
        $composer = json_decode($composer, true);

        $require = $composer['require'];

        unset($require['php']);

        if (!isset($require[$package])) {
            $this->writeLn(
                QUI::getLocale()->get('quiqqer/core', 'console.tool.package.cannot.be.removed', [
                    'package' => $package
                ]),
                'red'
            );

            $this->resetColor();
            $this->writeLn();
            $this->writeLn(QUI::getLocale()->get('quiqqer/core', 'console.tool.package.can.removed'));
            $this->writeLn('========================================');
            $this->writeLn();
            $this->writeLn();

            $require = array_keys($require);

            $Climate = new CLImate();
            $Climate->columns($require);
            $Climate->out('');
            return;
        }


        $Composer = QUI::getPackageManager()->getComposer();
        $Runner = $Composer->getRunner();

        // start update routines
        $CLIOutput = new QUI\System\Console\Output();
        $CLIOutput->Events->addEvent('onWrite', function ($message) {
            Update::onCliOutput($message, $this);
        });

        $Runner->setOutput($CLIOutput);
        $Runner->executeComposer('remove', ['packages' => [$package]]);
    }
}
