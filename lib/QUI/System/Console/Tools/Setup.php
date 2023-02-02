<?php

/**
 * This file contains \QUI\System\Console\Tools\Setup
 */

namespace QUI\System\Console\Tools;

use DateTimeInterface;
use QUI;

use function array_map;
use function flush;
use function implode;
use function ob_flush;
use function ob_get_contents;
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
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        QUI\System\Log::write(
            '====== EXECUTE SETUP ======',
            QUI\System\Log::LEVEL_NOTICE,
            [],
            'setup',
            true
        );

        $PackageManager = QUI::getPackageManager();
        $quiqqer        = QUI::getPackageManager()->getPackage('quiqqer/quiqqer');
        $reference      = '';

        if (isset($quiqqer['source']['reference'])) {
            $reference = $quiqqer['source']['reference'];
        }

        $data = QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.setup.start.data', [
            'version'     => QUI::version(),
            'versionDate' => $PackageManager->getLastUpdateDate(),
            'ref'         => $reference,
            'date'        => QUI::getLocale()->formatDate(
                $PackageManager->getLastUpdateCheckDate(),
                DateTimeInterface::RFC7231
            )
        ]);

        $data = explode('<br />', $data);
        $data = array_map(function ($entry) {
            return trim($entry);
        }, $data);

        $data = implode("\n", $data);

        $this->writeLn($data);
        $this->writeLn('');

        $this->writeLn(QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.setup.start.message'));
        QUI\Setup::all($this);

        $this->writeLn(QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.setup.message.success'));
        $this->writeLn('');
    }

    /**
     * Log the output buffer to the setup log
     */
    protected function logBuffer()
    {
        $buffer = ob_get_contents();
        $buffer = trim($buffer);

        if (!empty($buffer)) {
            QUI\System\Log::write(
                $buffer,
                QUI\System\Log::LEVEL_NOTICE,
                [],
                'setup',
                true
            );
        }

        flush();
        ob_flush();
    }
}
