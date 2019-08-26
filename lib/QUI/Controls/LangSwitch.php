<?php

/**
 * This file contains \QUI\Controls\LangSwitch
 */

namespace QUI\Controls;

use QUI;

/**
 * Class LangSwitch
 *
 * @package quiqqer/quiqqer
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 * @deprecated use QUI\Bricks\Controls\LangSwitch
 */
class LangSwitch extends QUI\Bricks\Controls\LanguageSwitches\DropDown
{
    /**
     * LangSwitch constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes)
    {
        parent::__construct($attributes);

        QUI\System\Log::addNotice(
            '\QUI\Controls\LangSwitch is deprecated. '.
            'Please use QUI\Bricks\Controls\LanguageSwitches\DropDown'
        );
    }
}
