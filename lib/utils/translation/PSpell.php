<?php

/**
 * This file contains Utils_Translation_PSpell
 */

/**
 * Easier Access to pspell
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils.translation
 *
 * @uses pspell
 * @todo check it, class is at the moment not in use
 *
 * @example $Trans = new Utils_Translation_PSpell(array(
 * 		'lang'    => 'en',
 * 		'dialect' => 'american'
 * ));
 *
 * $Trans->translate('House');
 */

class Utils_Translation_PSpell extends \QUI\QDOM
{
    /**
     * internal pspell object
     * @var pspell_new
     */
    protected $_Spell;

    /**
     * Constructor
     *
     * @param array $settings - array(
     * 		lang
     *  	dialect
     *  	personal
     * );
     */
    public function __construct(array $settings)
    {
        // defaults
        $this->setAttribute('lang', 'en');
        $this->setAttribute('dialect', 'american');

        $this->setAttributes($settings);


        // PSpell Config
        $Config = pspell_config_create(
            $this->getAttribute('lang'),
            $this->getAttribute('dialect')
        );

        pspell_config_mode($Config, "PSPELL_FAST");

        if ($this->getAttribute('personal')) {
            pspell_config_personal($Config, $this->getAttribute('personal'));
        }

        $this->_Spell = pspell_new($Config);
    }

    /**
     * Check if pspell is installed
     *
     * @return Bool|throw \QUI\Exception
     */
    static function check()
    {
        if (!function_exists('pspell_new')) {
            throw new \QUI\Exception('PSpell ist nicht installiert');
        }

        return true;
    }

    /**
     * Translate a String
     *
     * @param String $word
     */
    public function translate($word)
    {
        return pspell_suggest($this->_Spell, $word);
    }
}
?>