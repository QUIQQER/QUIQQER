<?php

/**
 * This file contains \QUI\System\Console\Tools\Licence
 */

namespace QUI\System\Console\Tools;

use QUI;

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
        $this->setName('quiqqer:licence')->setDescription('Show the licence information');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\System\Console\Tool::execute()
     */
    public function execute()
    {
        $licenceFile = OPT_DIR.'quiqqer/quiqqer/LICENSE';
        $content     = file_get_contents($licenceFile);

        echo $content;
        echo PHP_EOL;
        echo PHP_EOL;
        exit;
    }
}
