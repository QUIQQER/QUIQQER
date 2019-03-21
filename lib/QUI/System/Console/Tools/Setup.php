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
     * @throws QUI\Exception
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


        ob_start();

        $this->logBuffer();

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
                '%B %d %Y, %X %Z'
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
        $this->logBuffer();

        QUI\Setup::all();

        $this->writeLn(QUI::getLocale()->get('quiqqer/quiqqer', 'console.tool.setup.message.success'));
        $this->logBuffer();

        $this->writeLn('');
        $this->logBuffer();
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
