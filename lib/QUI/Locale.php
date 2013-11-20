<?php

/**
 * This file contains QUI_Locale
 */

/**
 * The locale object
 * translate the ui and all messages
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 *
 * @use gettext - if enable
 */

class QUI_Locale
{
    /**
     * The current lang
     * @var String
     */
    protected $_current = 'de';

    /**
     * the exist langs
     * @var array
     */
    protected $_langs = array();

    /**
     * gettext object
     * @var gettext
     */
    protected $_gettext = array();
    /**
     * no translation flag
     * @var Bool
     */
    public $no_translation = false;

    /**
     * ini file objects, if no gettext exist
     * @var array
     */
    protected $_inis = array();

    /**
     * Locale toString
     * @return String
     */
    public function __toString()
    {
        return 'Locale()';
    }

    /**
     * Set the current language
     *
     * @param String $lang
     */
    public function setCurrent($lang)
    {
        $this->_current = $lang;
    }

    /**
     * Return the current language
     *
     * @return String
     */
    public function getCurrent()
    {
        return $this->_current;
    }

    /**
     * Set translation
     *
     * @param String $lang   - Language
     * @param String $group  - Language group
     * @param String|array $key
     * @param String $value
     */
    public function set($lang, $group, $key, $value=false)
    {
        if ( !isset( $this->_langs[ $lang ] ) ) {
            $this->_langs[ $lang ] = $lang;
        }

        if ( !isset( $this->_langs[ $lang ][ $group ] ) ) {
            $this->_langs[ $lang ][ $group ] = array();
        }

        if ( !is_array( $key ) )
        {
            $this->_langs[ $lang ][ $group ][ $key ] = $value;
            return;
        }

        $this->_langs[ $lang ][ $group ] = array_merge(
            $this->_langs[ $lang ][ $group ],
            $key
        );
    }

    /**
     * Exist the variable in the translation?
     *
     * @param String $group - language group
     * @param String $value - language group variable, optional
     *
     * @return Bool
     */
    public function exists($group, $value=false)
    {
        $str = $this->_get( $group, $value );

        if ( $value === false )
        {
            if ( empty( $str ) ) {
                return false;
            }

            return true;
        }

        $_str = '['. $group .'] '. $value;

        if ( $_str === $str ) {
            return false;
        }

        return true;
    }

    /**
     * Get the translation
     *
     * @param String $group - Gruppe
     * @param String $value - Variable, optional
     * @param Array $replace
     *
     * @return String|array
     */
    public function get($group, $value=false, $replace=false)
    {
        if ( $replace === false || empty( $replace ) ) {
            return $this->_get( $group, $value );
        }

        $str = $this->_get( $group, $value );

        foreach ( $replace as $key => $value ) {
            $str = str_replace( '['. $key .']', $value, $str );
        };

        return $str;
    }

    /**
     * Translation helper method
     *
     * @param String $group
     * @param String $value
     *
     * @return String|Array
     * @see ->get()
     * @ignore
     */
    protected function _get($group, $value=false)
    {
        if ( $this->no_translation ) {
            return '['. $group .'] '. $value;
        }

        $current = $this->_current;

        // auf gettext wenn vorhanden
        $GetText = $this->initGetText( $group );

        if ( $GetText !== false )
        {
            $str = $GetText->get( $value );

            if ( $value != $str ) {
                return $str;
            }
        }

        if ( !isset( $this->_langs[ $current ] ) ||
             !isset( $this->_langs[ $current ][ $group ] ) )
        {
            // Kein gettext vorhanden, dann Config einlesen
            $this->_langs[ $current ][ $group ] = array();
            $this->initConfig( $group );
        }

        if ( !$value ) {
            return $this->_langs[ $current ][ $group ];
        }

        if ( isset( $this->_langs[ $current ][ $group ][ $value ] ) &&
             !empty( $this->_langs[ $current ][ $group ][ $value ] ) )
        {
            return $this->_langs[ $current ][ $group ][ $value ];
        }

        return '['. $group .'] '. $value;
    }

    /**
     * the GetText init
     *
     * @param $group - language group
     * @return Bool|Utils_Translation_GetText
     */
    public function initGetText($group)
    {
        $current = $this->_current;

        if ( isset( $this->_gettext[ $current ]) &&
             isset( $this->_gettext[ $current ][ $group ] ) )
        {
            return $this->_gettext[ $current ][ $group ];
        }

        if ( !function_exists( 'gettext' ) )
        {
            $this->_gettext[ $current ][ $group ] = false;
            return false;
        }


        $this->_gettext[ $current ][ $group ] = new \Utils_Translation_GetText(
            $current,
            $group,
            $this->dir()
        );

        if ( $this->_gettext[ $current ][ $group ]->fileExist() ) {
            return $this->_gettext[ $current ][ $group ];
        }

        $Gettext = $this->_gettext[ $current ][ $group ];

        $dir    = $Gettext->getAttribute( 'dir' );
        $domain = $Gettext->getAttribute( 'domain' );

        \QUI\System\Log::write(
            'Übersetzungsdatei für '. $group .' '. $dir .'de_DE/LC_MESSAGES/'. $domain .'.mo nicht gefunden.',
            'error'
        );

        $this->_gettext[ $current ][ $group ] = false;
        return false;
    }

    /**
     * read a config
     * @param String $group - translation group
     */
    public function initConfig($group)
    {
        $lang = $this->_current;
        $file = $this->getTranslationFile(
            $this->_current,
            $group
        );

        if ( !file_exists( $file ) ) {
            return;
        }

        if ( isset( $this->_inis[ $file ] ) )
        {
            $Config = $this->_inis[ $file ];
        } else
        {
            $Config = new \QUI\Config( $file );
        }

        $this->set( $lang, $group, $Config->toArray() );
    }

    /**
     * Get the translation file in dependence to the lang and group
     *
     * @param String $lang
     * @param String $group
     *
     * @return String
     */
    public function getTranslationFile($lang, $group)
    {
        $locale = \QUI\Utils\String::toLower( $lang ) .'_'. \QUI\Utils\String::toUpper( $lang );
        $group  = str_replace( '/', '_', $group );

        return $this->dir() .'/'. $locale .'/LC_MESSAGES/'. $group .'.ini';
    }

     /**
     * Folder located the translations
     * @return String
     */
    public function dir()
    {
        return VAR_DIR .'locale/';
    }
}
