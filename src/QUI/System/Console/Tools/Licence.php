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
            ->setDescription('Show the licence information')
            ->addArgument('list', 'Print a list of all licenses', false, true);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute(): void
    {
        if ($this->getArgument('list')) {
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

            exit;
        }

        $licenceFile = OPT_DIR . 'quiqqer/core/LICENSE';
        $content = file_get_contents($licenceFile);

        echo $content;
        echo PHP_EOL;
        echo PHP_EOL;
        exit;
    }
}
