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
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 * @package com.pcsg.qui
 *
 * @use     gettext - if enabled
 * @todo    integrate http://php.net/intl
 */
class Locale
{
    /**
     * The current lang
     *
     * @var string
     */
    protected $dateFormats = false;

    /**
     * The current lang
     *
     * @var string
     */
    protected $current = 'en';

    /**
     * the exist langs
     *
     * @var array
     */
    protected $langs = array();

    /**
     * gettext object
     *
     * @var array
     */
    protected $gettext = array();
    /**
     * no translation flag
     *
     * @var boolean
     */
    public $no_translation = false;

    /**
     * ini file objects, if no gettext exist
     *
     * @var array
     */
    protected $inis = array();

    /**
     * List of internal locale list for setlocale()
     *
     * @var array
     */
    protected $localeList = array();


    /**
     * Locale tostring
     *
     * @return string
     */
    public function __toString()
    {
        return 'Locale()';
    }

    /**
     * Set the current language
     *
     * @param string $lang - en, en_EN, de, de_DE, de_AT
     */
    public function setCurrent($lang)
    {
        $this->current = $lang;
    }

    /**
     * Return the current language
     *
     * @return string
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * @return string
     */
    public function getDecimalSeparator()
    {
        return $this->get('quiqqer/quiqqer', 'numberFormat.decimal_separator');
    }

    /**
     * @return string
     */
    public function getGroupingSeparator()
    {
        return $this->get('quiqqer/quiqqer', 'numberFormat.grouping_separator');
    }

    /**
     * @return array|string
     */
    public function getDecimalPattern()
    {
        return $this->get('quiqqer/quiqqer', 'numberFormat.decimal_pattern');
    }

    /**
     * @return array|string
     */
    public function getPercentPattern()
    {
        return $this->get('quiqqer/quiqqer', 'numberFormat.percent_pattern');
    }

    /**
     * @return array|string
     */
    public function getCurrencyPattern()
    {
        return $this->get('quiqqer/quiqqer', 'numberFormat.currency_pattern');
    }

    /**
     * @return array|string
     */
    public function getAccountingCurrencyPattern()
    {
        return $this->get('quiqqer/quiqqer', 'numberFormat.accounting_currency_pattern');
    }

    /**
     * Format a number
     *
     * @param float|integer $number
     * @param integer $format
     * @return string
     */
    public function formatNumber($number, $format = \NumberFormatter::DECIMAL)
    {
        $localeCode = QUI::getLocale()->getLocalesByLang(
            QUI::getLocale()->getCurrent()
        );

        $Formatter = new \NumberFormatter($localeCode[0], $format);

        if (is_string($number)) {
            $number = floatval($number);
        }

        //  numberFormat.decimal_separator
        //  numberFormat.grouping_separator
        //  numberFormat.numbering_system

        //  numberFormat.decimal_pattern
        //  numberFormat.percent_pattern
        //  numberFormat.currency_pattern
        //  numberFormat.accounting_currency_pattern

        //  "numbering_system": "latn",
        //	"decimal_pattern": "#,##0.###",
        //	"percent_pattern": "#,##0%",

        return $Formatter->format($number);
    }

    /**
     * Format a date timestamp
     *
     * @param             $timestamp
     * @param bool|string $format - (optional) ;if not given, it uses the quiqqer system format
     *
     * @return string
     */
    public function formatDate($timestamp, $format = false)
    {
        if (!is_numeric($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        $current = $this->getCurrent();

        if ($format) {
            $oldlocale = setlocale(LC_TIME, "0");
            setlocale(LC_TIME, $this->getLocalesByLang($current));

            $result = utf8_encode(strftime($format, $timestamp));

            setlocale(LC_TIME, $oldlocale);

            return $result;
        }

        $formats = $this->getDateFormats();

        if (isset($formats[$current])) {
            $oldlocale = setlocale(LC_TIME, "0");
            setlocale(LC_TIME, $this->getLocalesByLang($current));

            $result = utf8_encode(strftime($formats[$current], $timestamp));

            setlocale(LC_TIME, $oldlocale);

            return $result;
        }

        return utf8_encode(strftime('%D', $timestamp));
    }

    /**
     * Return all available dateformats
     *
     * @return array
     */
    protected function getDateFormats()
    {
        if ($this->dateFormats) {
            return $this->dateFormats;
        }

        $this->dateFormats = QUI::conf('date_formats');

        if (!$this->dateFormats) {
            $this->dateFormats = array();
        }

        return $this->dateFormats;
    }

    /**
     * Return the locale list for a language
     *
     * @param  string $lang - Language code (de, en, fr ...)
     *
     * @return array
     */
    public function getLocalesByLang($lang)
    {
        if (isset($this->localeList[$lang])) {
            return $this->localeList[$lang];
        }

        // no shell
        if (!QUI\Utils\System::isShellFunctionEnabled('locale')) {
            // if we cannot read locale list, so we must guess
            $langCode = strtolower($lang) . '_' . strtoupper($lang);

            $this->localeList[$lang] = array(
                $langCode,
                $langCode . '.utf8',
                $langCode . '.UTF-8',
                $langCode . '@euro'
            );

            return $this->localeList[$lang];
        }


        // via shell
        $locales = shell_exec('locale -a');
        $locales = explode("\n", $locales);

        $langList = array();

        foreach ($locales as $locale) {
            if (strpos($locale, $lang) !== 0) {
                continue;
            }

            $langList[] = $locale;
        }

        $langCode = strtolower($lang) . '_' . strtoupper($lang);

        // not the best solution
        if ($lang == 'en') {
            $langCode = 'en_GB';
        }

        // sort, main locale to the top
        usort($langList, function ($a, $b) use ($langCode) {
            if ($a == $b) {
                return 0;
            }

            if (strpos($a, $langCode) === 0) {
                return -1;
            }

            if (strpos($b, $langCode) === 0) {
                return 1;
            }

            return $a > $b;
        });

        $this->localeList[$lang] = $langList;

        return $this->localeList[$lang];
    }

    /**
     * Set translation
     *
     * @param string $lang - Language
     * @param string $group - Language group
     * @param string|array $key
     * @param string|boolean $value
     */
    public function set($lang, $group, $key, $value = false)
    {
        if (!isset($this->langs[$lang])) {
            $this->langs[$lang] = array();
        }

        if (!isset($this->langs[$lang][$group])) {
            $this->langs[$lang][$group] = array();
        }

        if (!is_array($key)) {
            $this->langs[$lang][$group][$key] = $value;

            return;
        }

        $this->langs[$lang][$group] = array_merge(
            $this->langs[$lang][$group],
            $key
        );
    }

    /**
     * Exist the variable in the translation?
     *
     * @param string $group - language group
     * @param string|boolean $value - language group variable, optional
     *
     * @return boolean
     */
    public function exists($group, $value = false)
    {
        $str = $this->getHelper($group, $value);

        if ($value === false) {
            if (empty($str)) {
                return false;
            }

            return true;
        }

        $_str = '[' . $group . '] ' . $value;

        if ($_str === $str) {
            return false;
        }

        return true;
    }

    /**
     * Get the translation
     *
     * @param string $group - Gruppe
     * @param string|boolean $value - (optional) Variable, optional
     * @param array|boolean $replace - (optional)
     *
     * @return string|array
     */
    public function get($group, $value = false, $replace = false)
    {
        if ($replace === false || empty($replace)) {
            return $this->getHelper($group, $value);
        }

        $str = $this->getHelper($group, $value);

        foreach ($replace as $key => $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }

            $str = str_replace('[' . $key . ']', $value, $str);
        };

        return $str;
    }

    /**
     * Get the translation from a specific language
     *
     * @param string $lang
     * @param string $group
     * @param string|boolean $value
     * @param array|boolean $replace
     *
     * @return string|array
     */
    public function getByLang($lang, $group, $value = false, $replace = false)
    {
        if ($replace === false || empty($replace)) {
            return $this->getHelper($group, $value, $lang);
        }

        $str = $this->getHelper($group, $value, $lang);

        foreach ($replace as $key => $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }

            $str = str_replace('[' . $key . ']', $value, $str);
        };

        return $str;
    }

    /**
     * Translation helper method
     *
     * @param string $group
     * @param string|boolean $value - (optional)
     * @param string|boolean $current - (optional) wanted language
     *
     * @return string|array
     * @see ->get()
     * @ignore
     */
    protected function getHelper($group, $value = false, $current = false)
    {
        if ($this->no_translation) {
            return '[' . $group . '] ' . $value;
        }

        if (!$current) {
            $current = $this->current;
        }

        // auf gettext wenn vorhanden
        $GetText = $this->initGetText($group, $current);

        if ($GetText !== false) {
            $str = $GetText->get($value);

            if ($value != $str) {
                return $str;
            }
        }

        if (!isset($this->langs[$current])
            || !isset($this->langs[$current][$group])
        ) {
            // Kein gettext vorhanden, dann Config einlesen
            $this->langs[$current][$group] = array();
            $this->initConfig($group, $current);
        }

        if (!$value) {
            return $this->langs[$current][$group];
        }

        if (isset($this->langs[$current][$group][$value])) {
            return $this->langs[$current][$group][$value];
        }

        return '[' . $group . '] ' . $value;
    }

    /**
     * the GetText init
     *
     * @param string $group - language group
     * @param string|null $lang - optional, language
     *
     * @return boolean|\QUI\Utils\Translation\GetText
     */
    public function initGetText($group, $lang = null)
    {
        $current = $this->current;

        if (is_string($lang)) {
            $current = $lang;
        }

        if (isset($this->gettext[$current])
            && isset($this->gettext[$current][$group])
        ) {
            return $this->gettext[$current][$group];
        }

        if (!function_exists('gettext')) {
            $this->gettext[$current][$group] = false;
            return false;
        }


        $GetText = new QUI\Utils\Translation\GetText(
            $current,
            $group,
            $this->dir()
        );

        $this->gettext[$current][$group] = $GetText;

        if ($GetText->fileExist()) {
            return $GetText;
        }

        $file = $GetText->getFile();

        // #locale
        System\Log::addWarning(
            'Übersetzungsdatei für ' . $file . ' nicht gefunden.'
        );

        $this->gettext[$current][$group] = false;

        return false;
    }

    /**
     * read a config
     *
     * @param string $group - translation group
     * @param string|bool $lang - translation language
     */
    public function initConfig($group, $lang = false)
    {
        if ($lang === false) {
            $lang = $this->current;
        }

        $file = $this->getTranslationFile($lang, $group);

        if (!file_exists($file)) {
            return;
        }

        if (isset($this->inis[$file])) {
            $Config = $this->inis[$file];
        } else {
            $Config = new QUI\Config($file);
        }

        $this->set($lang, $group, $Config->toArray());
    }

    /**
     * Get the translation file in dependence to the lang and group
     *
     * @param string $lang
     * @param string $group
     *
     * @return string
     */
    public function getTranslationFile($lang, $group)
    {
        $locale = QUI\Utils\StringHelper::toLower($lang)
                  . '_'
                  . QUI\Utils\StringHelper::toUpper($lang);

        $group = str_replace('/', '_', $group);

        return $this->dir() . '/' . $locale . '/LC_MESSAGES/' . $group . '.ini.php';
    }

    /**
     * Folder located the translations
     *
     * @return string
     */
    public function dir()
    {
        return VAR_DIR . 'locale/';
    }

    /**
     * Verified the string if the string is a locale string
     * a locale strings looks like: [group] var.var.var
     *
     * @param string $str
     * @return bool
     */
    public function isLocaleString($str)
    {
        if (strpos($str, ' ') === false
            || strpos($str, '[') === false
            || strpos($str, ']') === false
        ) {
            return false;
        }

        return true;
    }

    /**
     * Return the parts of a locale string
     * a locale strings looks like: [group] var.var.var
     *
     * @param string $str
     * @return array -  [0=>group, 1=>var]
     */
    public function getPartsOfLocaleString($str)
    {
        $str = explode(' ', $str);

        if (!isset($str[1])) {
            return $str;
        }

        $group = str_replace(array('[', ']'), '', $str[0]);
        $var   = trim($str[1]);

        return array($group, $var);
    }

    /**
     * Parse a locale string and translate it
     * a locale strings looks like: [group] var.var.var
     *
     * @param $title
     * @return string
     */
    public function parseLocaleString($title)
    {
        if (!$this->isLocaleString($title)) {
            return $title;
        }

        $parts = $this->getPartsOfLocaleString($title);

        return $this->get($parts[0], $parts[1]);
    }

    /**
     * Parse a locale array and translate it
     *
     * @param array $locale - with group, translation var and replacement vars (optional)
     * @return string
     */
    public function parseLocaleArray($locale)
    {
        if (!isset($locale[0]) || !isset($locale[1])) {
            return '';
        }

        if (!isset($locale[2])) {
            return $this->get($locale[0], $locale[1]);
        }

        return $this->get($locale[0], $locale[1], $locale[2]);
    }
}
