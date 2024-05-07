<?php

/**
 * This file contains \QUI\System\Console\Tools\Setup
 */

namespace QUI\System\Console\Tools;

use Exception;
use QUI;

use function array_map;
use function implode;
use function trim;

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
        $this->systemTool = true;

        $this->setName('quiqqer:setup')
            ->setDescription('Execute the setup from quiqqer');
    }

    /**
     * (non-PHPdoc)
     *
     * @throws QUI\Exception
     * @throws Exception
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute(): void
    {
        QUI\System\Log::write(
            '====== EXECUTE SETUP ======',
            QUI\System\Log::LEVEL_NOTICE,
            [],
            'setup',
            true
        );

        $PackageManager = QUI::getPackageManager();
        $quiqqer = QUI::getPackageManager()->getPackage('quiqqer/quiqqer');
        $reference = '';

        if (isset($quiqqer['source']['reference'])) {
            $reference = $quiqqer['source']['reference'];
        }

        $data = QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.setup.start.data', [
            'version' => QUI::version(),
            'versionDate' => $PackageManager->getLastUpdateDate(),
            'ref' => $reference,
            'date' => QUI::getLocale()->formatDate(
                $PackageManager->getLastUpdateCheckDate()
            )
        ]);

        $data = explode('<br />', $data);
        $data = array_map(fn($entry) => trim($entry), $data);

        $data = implode("\n", $data);

        $this->writeLn($data);
        $this->writeLn();

        $this->writeLn(QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.setup.start.message'));
        QUI\Setup::all($this);

        $this->writeLn(QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.setup.message.success'));
        $this->writeLn();
    }
}
