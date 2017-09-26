<?php

/**
 * This file contains \QUI\System\Console\Tools\Setup
 */

namespace QUI\System\Console\Tools;

use QUI;

/**
 * Execute the system setup
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Setup extends QUI\System\Console\Tool
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setName('quiqqer:setup')
            ->setDescription('Execute the setup from quiqqer');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        $PackageManager = QUI::getPackageManager();
        $quiqqer        = QUI::getPackageManager()->getPackage('quiqqer/quiqqer');


        $data = QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.setup.start.data', array(
            'version'     => QUI::version(),
            'versionDate' => $PackageManager->getLastUpdateDate(),
            'ref'         => $quiqqer['source']['reference'],
            'date'        => QUI::getLocale()->formatDate(
                $PackageManager->getLastUpdateCheckDate(),
                '%B %d %Y, %X %Z'
            )
        ));

        $data = explode('<br />', $data);
        $data = array_map(function ($entry) {
            return trim($entry);
        }, $data);

        $data = implode("\n", $data);

        $this->writeLn($data);
        $this->writeLn('');

        $this->writeLn(
            QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.setup.start.message')
        );

        QUI\Setup::all();

        $this->writeLn(
            QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.setup.message.success')
        );

        $this->writeLn('');
    }
}
