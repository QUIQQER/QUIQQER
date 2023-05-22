<?php

/**
 * This file contains \QUI\Locale
 */

namespace QUI;

use ForceUTF8\Encoding;
use IntlDateFormatter;
use NumberFormatter;
use QUI;
use QUI\Utils\StringHelper;

use function DusanKasan\Knapsack\first;
use function explode;
use function file_exists;
use function floatval;
use function in_array;
use function is_array;
use function is_numeric;
use function is_object;
use function is_string;
use function preg_replace;
use function setlocale;
use function shell_exec;
use function str_contains;
use function str_replace;
use function strftime;
use function strlen;
use function strpos;
use function strtolower;
use function strtotime;
use function strtoupper;
use function trim;
use function usort;

/**
 * The locale object
 * translate the ui and all messages
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Locale
{
    /**
     * The current lang
     *
     * @var array|bool
     */
    protected $dateFormats = false;

    /**
     * The current lang
     *
     * @var string
     */
    protected string $current = 'en';

    /**
     * no translation flag
     *
     * @var boolean
     */
    public bool $no_translation = false;

    /**
     * ini file objects
     *
     * @var array
     */
    protected array $inis = [];

    /**
     * List of internal locale list for setlocale()
     *
     * @var array
     */
    protected array $localeList = [];

    /**
     * Saves the current language of this Locale if setTemporaryCurrent is used.
     *
     * @var bool
     */
    protected bool $tempCurrent = false;


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
     * Sets the current language of this locale to $lang.
     *
     * WARNING: It's STRONGLY advised to use resetCurrent() immediately after
     * your use case. Changing the global current language longer than that may otherwise have
     * unforeseeable consequences!
     *
     * @param string $lang
     * @return void
     */
    public function setTemporaryCurrent(string $lang)
    {
        if (empty($this->tempCurrent)) {
            $this->tempCurrent = $this->getCurrent();
        }

        $this->setCurrent($lang);
    }

    /**
     * Resets the current language to the initial state. Useful only after setTemporaryCurrent()
     * was used!
     *
     * @return void
     */
    public function resetCurrent()
    {
        if (!empty($this->tempCurrent)) {
            $this->setCurrent($this->tempCurrent);
            $this->tempCurrent = false;
        }
    }

    /**
     * Set the current language
     *
     * @param string $lang - en, en_EN, de, de_DE, de_AT
     */
    public function setCurrent(string $lang)
    {
        $lang = preg_replace('/[^a-zA-Z_]/', '', $lang);
        $lang = trim($lang);

        if (!empty($lang)) {
            $this->current = $lang;
        } else {
            $this->current = QUI::conf('globals', 'standardLanguage');
        }
    }

    /**
     * Return the current language
     *
     * @return string
     */
    public function getCurrent(): string
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
     * Refresh the locale
     * Clears the locale
     */
    public function refresh()
    {
    }

    /**
     * Format a number
     *
     * @param float|integer $number
     * @param integer $format
     * @return string
     */
    public function formatNumber($number, int $format = NumberFormatter::DECIMAL): string
    {
        $localeCode = QUI::getLocale()->getLocalesByLang(
            QUI::getLocale()->getCurrent()
        );

        $Formatter = new NumberFormatter($localeCode[0], $format);

        if (is_string($number)) {
            $number = floatval($number);
        }

        $decimalSeparator  = self::get('quiqqer/quiqqer', 'numberFormat.decimal_separator');
        $groupingSeparator = self::get('quiqqer/quiqqer', 'numberFormat.grouping_separator');
        $decimalPattern    = self::get('quiqqer/quiqqer', 'numberFormat.decimal_pattern');

        if (!empty($decimalSeparator)) {
            $Formatter->setSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL, $decimalSeparator);
        }

        if (!empty($groupingSeparator)) {
            $Formatter->setSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL, $groupingSeparator);
        }

        if (!empty($decimalPattern)) {
            $Formatter->setPattern($decimalPattern);
        }

        //  numberFormat.numbering_system
        //  numberFormat.percent_pattern
        //  numberFormat.currency_pattern
        //  numberFormat.accounting_currency_pattern

        //  "numbering_system": "latn",
        //  "decimal_pattern": "#,##0.###",
        //  "percent_pattern": "#,##0%",

        if ($number === null) {
            $number = 0;
        }

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
    public function formatDate($timestamp, $format = false): string
    {
        $Formatter = self::getDateFormatter();
        $current   = $this->getCurrent();

        if (!is_numeric($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        // new stuff, compatible with php9
        if (empty($format)) {
            return Encoding::toUTF8($Formatter->format($timestamp));
        }

        if (mb_strpos($format, '%') === false) {
            $Formatter->setPattern($format);

            return Encoding::toUTF8(
                $Formatter->format($timestamp)
            );
        }

        // deprecate log
        QUI\System\Log::addDeprecated('Deprecated formatDate usage');

        // old stuff with strftime
        $locales    = $this->getLocalesByLang($current);
        $localeCode = first($locales);

//        if ($format) {
        $oldLocale = setlocale(LC_TIME, "0");

        setlocale(LC_TIME, $localeCode);
        $result = strftime($format, $timestamp);
        setlocale(LC_TIME, $oldLocale);

        return Encoding::toUTF8($result);
//        }

//        $formats = $this->getDateFormats();
//
//        if (!empty($formats[$current])) {
//            $oldLocale = setlocale(LC_TIME, "0");
//
//            setlocale(LC_TIME, $localeCode);
//            $result = strftime($formats[$current], $timestamp);
//            setlocale(LC_TIME, $oldLocale);
//
//            return Encoding::toUTF8($result);
//        }
//
//        return Encoding::toUTF8(strftime('%D', $timestamp));
    }

    /**
     * Return a date formatter for the current language
     *
     * @param int $dateType
     * @param int $timeType
     * @return \IntlDateFormatter
     */
    public function getDateFormatter(
        int $dateType = IntlDateFormatter::SHORT,
        int $timeType = IntlDateFormatter::NONE
    ): IntlDateFormatter {
        $localeCode = $this->getLocalesByLang($this->getCurrent());

        return new IntlDateFormatter($localeCode[0], $dateType, $timeType);
    }

    /**
     * Return all available date formats
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
            $this->dateFormats = [];
        }

        return $this->dateFormats;
    }

    /**
     * Return the locale list for a language
     *
     * @param string $lang - Language code (de, en, fr ...)
     *
     * @return array
     */
    public function getLocalesByLang(string $lang): array
    {
        if (isset($this->localeList[$lang])) {
            return $this->localeList[$lang];
        }

        // no shell
        if (!QUI\Utils\System::isShellFunctionEnabled('locale')) {
            // if we cannot read locale list, so we must guess
            $langCode = strtolower($lang) . '_' . strtoupper($lang);

            $this->localeList[$lang] = [
                $langCode,
                $langCode . '.utf8',
                $langCode . '.UTF-8',
                $langCode . '@euro'
            ];

            return $this->localeList[$lang];
        }


        // via shell
        $locales = shell_exec('locale -a');
        $locales = explode("\n", $locales);

        $langList = [];

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
     * @param string $lang - Language
     * @param string $group - Language group
     * @param string|array $key
     * @param string|boolean $value
     * @deprecated
     *
     * Set translation
     *
     */
    public function set(string $lang, string $group, $key, $value = false)
    {
        if (!is_array($key)) {
            LocaleRuntimeCache::set($lang, $group, [$key => $value]);
            return;
        }

        LocaleRuntimeCache::set($lang, $group, $key);
    }

    /**
     * Exist the variable in the translation?
     *
     * @param string $group - language group
     * @param string|boolean $value - language group variable, optional
     *
     * @return boolean
     */
    public function exists(string $group, $value = false): bool
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
     * Exists the language in the locale?
     *
     * @param {string} $language - language eq: de, en
     * @return bool
     */
    public function existsLang($language): bool
    {
        return in_array(QUI::availableLanguages(), $language);
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
    public function get(string $group, $value = false, $replace = false)
    {
        $str = $this->getHelper($group, $value);

        if (empty($replace)) {
            return str_replace('{\n}', PHP_EOL, $str);
        }

        foreach ($replace as $key => $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }

            $str = str_replace('[' . $key . ']', $value, $str);
        }

        return str_replace('{\n}', PHP_EOL, $str);
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
    public function getByLang(string $lang, string $group, $value = false, $replace = false)
    {
        $str = $this->getHelper($group, $value, $lang);

        if (empty($replace)) {
            return str_replace('{\n}', PHP_EOL, $str);
        }

        foreach ($replace as $key => $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }

            $str = str_replace('[' . $key . ']', $value, $str);
        }

        return str_replace('{\n}', PHP_EOL, $str);
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
    protected function getHelper(string $group, $value = false, $current = false)
    {
        if ($this->no_translation) {
            return '[' . $group . '] ' . $value;
        }

        if (!$current) {
            $current = $this->current;
        }

        $translation = LocaleRuntimeCache::get($current, $group, $value);

        if ($translation !== null) {
            return $translation;
        }

        try {
            $this->initConfig($group, $current);

            $translation = LocaleRuntimeCache::get($current, $group, $value);

            if ($translation !== null) {
                return $translation;
            }
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        if ($translation !== null && str_contains($translation, ' ') && strlen($translation) === 1) {
            return ' ';
        }

        return '[' . $group . '] ' . $value;
    }

    /**
     * read a config
     *
     * @param string $group - translation group
     * @param string|null $lang - translation language
     *
     * @throws QUI\Exception
     */
    public function initConfig(string $group, ?string $lang = null): void
    {
        if (empty($lang)) {
            $lang = $this->current;
        }

        if (LocaleRuntimeCache::isCached($lang, $group)) {
            return;
        }

        $file = $this->getTranslationFile($lang, $group);

        if (!file_exists($file)) {
            return;
        }

        $Config = new QUI\Config($file);
        LocaleRuntimeCache::set($lang, $group, $Config->toArray());
    }

    /**
     * Get the translation file in dependence to the lang and group
     *
     * @param string $lang
     * @param string $group
     *
     * @return string
     */
    public function getTranslationFile(string $lang, string $group): string
    {
        $lang   = preg_replace('/[^a-zA-Z]/', '', $lang);
        $locale = StringHelper::toLower($lang);//. '_' . StringHelper::toUpper($lang);
        $group  = str_replace('/', '_', $group);

        return $this->dir() . '/' . $locale . '/LC_MESSAGES/' . $group . '.ini.php';
    }

    /**
     * Folder located the translations
     *
     * @return string
     */
    public function dir(): string
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
    public function isLocaleString(string $str): bool
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
    public function getPartsOfLocaleString(string $str): array
    {
        $str = explode(' ', $str);

        if (!isset($str[1])) {
            return $str;
        }

        $group = str_replace(['[', ']'], '', $str[0]);
        $var   = trim($str[1]);

        return [$group, $var];
    }

    /**
     * Parse a locale string and translate it
     * a locale strings looks like: [group] var.var.var
     *
     * @param string|array $title
     * @return string
     */
    public function parseLocaleString($title)
    {
        if (is_array($title)) {
            return $this->parseLocaleArray($title);
        }

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
    public function parseLocaleArray(array $locale)
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
