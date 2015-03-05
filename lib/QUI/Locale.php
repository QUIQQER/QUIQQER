<?php

/**
 * This file contains \QUI\Locale
 */

namespace QUI;

use QUI;

/**
 * The locale object
 * translate the ui and all messages
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 *
 * @use gettext - if enable
 * @todo integrate http://php.net/intl
 */

class Locale
{
    /**
     * The current lang
     * @var String
     */
    protected $_dateFormats = false;

    /**
     * The current lang
     * @var String
     */
    protected $_current = 'en';

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
     * List of internal locale list for setlocale()
     * @var array
     */
    protected $_localeList = array();


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
     * Format a date timestamp
     *
     * @param $timestamp
     * @param bool|string $format - (optional) ;if not given, it uses the quiqqer system format
     * @return String
     */
    public function formatDate($timestamp, $format=false)
    {
        if ( !is_numeric( $timestamp ) ) {
            $timestamp = strtotime( $timestamp );
        }

        $current = $this->getCurrent();

        if ( $format )
        {
            $oldlocale = setlocale( LC_TIME, "0" );
            setlocale( LC_TIME, $this->_getLocalesByLang( $current ) );

            $result = strftime( $format, $timestamp );

            setlocale( LC_TIME, $oldlocale );

            return $result;
        }

        $formats = $this->_getDateFormats();

        if ( isset( $formats[ $current ] ) )
        {
            $oldlocale = setlocale( LC_TIME, "0" );
            setlocale( LC_TIME, $this->_getLocalesByLang( $current ) );

            $result = strftime( $formats[ $current ], $timestamp );

            setlocale( LC_TIME, $oldlocale );

            return $result;
        }

        return strftime( '%D', $timestamp );
    }

    /**
     * Return all available dateformats
     * @return Array
     */
    protected function _getDateFormats()
    {
        if ( $this->_dateFormats ) {
            return $this->_dateFormats;
        }

        $this->_dateFormats = QUI::conf( 'date_formats' );

        if ( !$this->_dateFormats ) {
            $this->_dateFormats = array();
        }

        return $this->_dateFormats;
    }

    /**
     * Return the locale list for a language
     *
     * @param {String} $lang - Language code (de, en, fr ...)
     * @return {Array}
     */
    public function _getLocalesByLang($lang)
    {
        if ( isset( $this->_localeList[ $lang ] ) ) {
            return $this->_localeList[ $lang ];
        }

        // no shell
        if ( !QUI\Utils\System::isShellFunctionEnabled( 'locale' ) )
        {
            // if we cannot read locale list, so we must guess
            $langCode = strtolower( $lang ) .'_'. strtoupper( $lang );

            $this->_localeList[ $lang ] = array(
                $langCode,
                $langCode .'.utf8',
                $langCode .'.UTF-8',
                $langCode .'@euro'
            );

            return $this->_localeList[ $lang ];
        }


        // via shell
        $locales = shell_exec( 'locale -a' );
        $locales = explode( "\n" , $locales );

        $langList = array();

        foreach ( $locales as $locale )
        {
            if ( strpos( $locale, $lang ) !== 0 ) {
                continue;
            }

            $langList[] = $locale;
        }

        $langCode = strtolower( $lang ) .'_'. strtoupper( $lang );

        // not the best solution
        if ( $lang == 'en' ) {
            $langCode = 'en_GB';
        }

        // sort, main locale to the top
        usort($langList, function($a, $b) use ($langCode)
        {
            if ( $a == $b ) {
                return 0;
            }

            if ( strpos( $a, $langCode ) === 0 ) {
                return -1;
            }

            if ( strpos( $b, $langCode ) === 0 ) {
                return 1;
            }

            return $a > $b;
        });

        $this->_localeList[ $lang ] = $langList;

        return $this->_localeList[ $lang ];
    }

    /**
     * Set translation
     *
     * @param String $lang   - Language
     * @param String $group  - Language group
     * @param String|array $key
     * @param String|bool $value
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
     * @param String|bool $value - language group variable, optional
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
     * @param String|bool $value - (optional) Variable, optional
     * @param Array|bool $replace - (optional)
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
     * @param String|bool $value - (optional)
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
     * @return Bool|\QUI\Utils\Translation\GetText
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


        $Gettext = new QUI\Utils\Translation\GetText( $current, $group, $this->dir() );

        $this->_gettext[ $current ][ $group ] = $Gettext;

        if ( $Gettext->fileExist() ) {
            return $Gettext;
        }

        $dir    = $Gettext->getAttribute( 'dir' );
        $domain = $Gettext->getAttribute( 'domain' );

        System\Log::write(
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
            $Config = new QUI\Config( $file );
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
        $locale = QUI\Utils\String::toLower( $lang ) .'_'. QUI\Utils\String::toUpper( $lang );
        $group  = str_replace( '/', '_', $group );

        return $this->dir() .'/'. $locale .'/LC_MESSAGES/'. $group .'.ini.php';
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
