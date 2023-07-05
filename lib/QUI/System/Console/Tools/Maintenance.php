<?php

/**
 * \QUI\System\Console\Tools\Health
 */

namespace QUI\System\Console\Tools;

use QUI;

use function file_exists;
use function file_put_contents;

/**
 * Checks the system health
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Maintenance extends QUI\System\Console\Tool
{
    /**
     * Konstruktor
     */
    public function __construct()
    {
        $this->setName('quiqqer:maintenance')
            ->setDescription('Set the maintenance status. Available commands: --status [on|off]');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        try {
            $this->writeLn('Set maintenance: ');

            $Config = QUI::getConfig('etc/conf.ini.php');

            if ($this->getArgument('status') == 'on') {
                $this->write('on');
                $Config->set('globals', 'maintenance', 1);

                // copy maintenance file
                $file = OPT_DIR . 'quiqqer/quiqqer/lib/templates/maintenance.html';

                if (file_exists(ETC_DIR . 'maintenance.html')) {
                    $file = ETC_DIR . 'maintenance.html';
                }

                $Smarty = QUI::getTemplateManager()->getEngine();
                $Project = QUI::getProjectManager()->getStandard();

                $Smarty->assign([
                    'Project' => $Project,
                    'URL_DIR' => URL_DIR,
                    'URL_BIN_DIR' => URL_BIN_DIR,
                    'URL_LIB_DIR' => URL_LIB_DIR,
                    'URL_VAR_DIR' => URL_VAR_DIR,
                    'URL_OPT_DIR' => URL_OPT_DIR,
                    'URL_USR_DIR' => URL_USR_DIR,
                    'URL_TPL_DIR' => URL_USR_DIR . $Project->getName() . '/',
                    'TPL_DIR' => OPT_DIR . $Project->getName() . '/',
                ]);


                file_put_contents(
                    CMS_DIR . 'maintenance.html',
                    $Smarty->fetch($file)
                );
            }

            if ($this->getArgument('status') == 'off') {
                $this->write('off');
                $Config->set('globals', 'maintenance', 0);

                // delete maintenance file
                if (file_exists(CMS_DIR . 'maintenance.html')) {
                    unlink(CMS_DIR . 'maintenance.html');
                }
            }

            $Config->save();

            $this->writeLn('');
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }
}
