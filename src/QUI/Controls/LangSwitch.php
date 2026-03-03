<?php

/**
 * This file contains \QUI\Controls\LangSwitch
 */

namespace QUI\Controls;

use QUI;

/**
 * Class LangSwitch
 * @deprecated use QUI\Bricks\Controls\LangSwitch
 */
class LangSwitch extends QUI\Bricks\Controls\LanguageSwitches\DropDown
{
    public function __construct(array $attributes)
    {
        parent::__construct($attributes);

        QUI\System\Log::addNotice(
            '\QUI\Controls\LangSwitch is deprecated. ' .
            'Please use QUI\Bricks\Controls\LanguageSwitches\DropDown'
        );
    }
}
