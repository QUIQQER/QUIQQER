<?php

/**
 * \QUI\System\Console\Tools\Defaults
 */

namespace QUI\System\Console\Tools;

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
        $this->setName('quiqqer:package')
            ->setDescription('Package management')
            ->addArgument('list', 'List all installed packages', false, true);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        if ($this->getArgument('list')) {
            $this->showList();

            return;
        }
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

        $Climate = new \League\CLImate\CLImate();
        $Climate->table($data);
    }
}
