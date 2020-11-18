<?php

/**
 * This class contains \QUI\System\Tests\UpdateServer
 */

namespace QUI\System\Tests;

use QUI;

/**
 * update.quiqqer.com and composer.quiqqer.com Test
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class UpdateServer extends QUI\System\Test
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setAttributes(array(
            'title'       => 'Update Server: composer.quiqqer.com AND update.composer.com',
            'description' => ''
        ));

        $this->isRequired = self::TEST_IS_OPTIONAL;
    }

    /**
     * Check, if update.quiqqer.com and composer.quiqqer.com
     *
     * @return self::STATUS_OK|self::STATUS_ERROR
     */
    public function execute()
    {
        $servers = QUI::getPackageManager()->getServerList();

        $updateServer = false;
        $composerServer = false;

        foreach ($servers as $server => $serverData) {
            if ($server == 'http://update.quiqqer.com/') {
                $updateServer = true;
            }

            if ($server == 'http://composer.quiqqer.com/') {
                $composerServer = true;
            }
        }


        if ($composerServer && $updateServer) {
            return self::STATUS_OK;
        }

        return self::STATUS_ERROR;
    }
}
