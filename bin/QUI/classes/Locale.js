/**
 * quiqqer locale
 * Extends the QUI locale with some quiqqer relevant methods
 *
 * @module classes/Locale
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/Locale
 */
var needle = ['qui/classes/Locale'];

// intl polyfill
if (typeof window.Intl === 'undefined') {
    define('qui/classes/intl', [
        URL_OPT_DIR + 'bin/intl/dist/Intl.js'
    ], function (Intl) {
        window.Intl         = Intl;
        window.IntlPolyfill = Intl;
    });

    needle.push('intl/en');
    needle.push('intl/de');

    require.config({
        paths: {
            'intl/en': URL_OPT_DIR + 'bin/intl/locale-data/jsonp/en',
            'intl/de': URL_OPT_DIR + 'bin/intl/locale-data/jsonp/de'
        },
        shim : {
            'intl/en': {
                deps: ['qui/classes/intl']
            },
            'intl/de': {
                deps: ['qui/classes/intl']
            }
        }
    });
}

define('classes/Locale', needle, function (QUILocale) {
    "use strict";

    return new Class({

        Extends: QUILocale,
        Type   : 'classes/Locale',

        /**
         * Translate a locale code de_DE, en_EN, de_AT
         *
         * @param localeId
         * @returns {String}
         */
        translateCode: function (localeId) {
            var lang    = localeId.split('_')[0],
                country = localeId.split('_')[1];

            var locLang    = QUILocale.get('quiqqer/quiqqer', 'language.' + lang),
                locCountry = QUILocale.get('quiqqer/countries', 'country.' + country);

            return locLang + ' (' + locCountry + ')';
        },

        /**
         * Return decimal separator
         * @returns {String}
         */
        getDecimalSeparator: function () {
            return this.get('quiqqer/quiqqer', 'numberFormat.decimal_separator');
        },

        /**
         * Return grouping separator
         * @returns {String}
         */
        getGroupingSeparator: function () {
            return this.get('quiqqer/quiqqer', 'numberFormat.grouping_separator');
        },

        /**
         * Return the decimal pattern
         * @returns {String}
         */
        getDecimalPattern: function () {
            return this.get('quiqqer/quiqqer', 'numberFormat.decimal_pattern');
        },

        /**
         * Return the percent pattern
         * @returns {String}
         */
        getPercentPattern: function () {
            return this.get('quiqqer/quiqqer', 'numberFormat.percent_pattern');
        },

        /**
         * Return the currency pattern
         * @returns {String}
         */
        getCurrencyPattern: function () {
            return this.get('quiqqer/quiqqer', 'numberFormat.currency_pattern');
        },

        /**
         * Return the accounting currency pattern
         * @returns {String}
         */
        getAccountingCurrencyPattern: function () {
            return this.get('quiqqer/quiqqer', 'numberFormat.accounting_currency_pattern');
        },

        /**
         * Return a Intl.NumberFormat object dependent on the locale
         *
         * @param {Object} options
         * @returns {Object} Intl.NumberFormat
         */
        getNumberFormatter: function (options) {
            var locale = this.getCurrent();

            if (!locale.match('_')) {
                locale = locale.toLowerCase() + '_' + locale.toUpperCase();
            }

            locale = locale.replace('_', '-');

            try {
                if (typeof options === 'undefined') {
                    return window.Intl.NumberFormat(locale);
                }

                return window.Intl.NumberFormat(locale, options);
            } catch (e) {
                return window.Intl.NumberFormat(locale);
            }
        },

        /**
         *
         * @param options
         * @return {Intl.DateTimeFormat}
         */
        getDateTimeFormatter: function (options) {
            var locale = this.getCurrent();

            if (!locale.match('_')) {
                locale = locale.toLowerCase() + '_' + locale.toUpperCase();
            }

            locale = locale.replace('_', '-');

            try {
                if (typeof options === 'undefined') {
                    return window.Intl.DateTimeFormat(locale);
                }

                return window.Intl.DateTimeFormat(locale, options);
            } catch (e) {
                return window.Intl.DateTimeFormat(locale);
            }
        },

        /**
         * Return a locale which represent the system locale (not the user locale)
         *
         * @return {Promise}
         */
        getSystemLocale: function () {
            var standardLanguage = QUIQQER_CONFIG.globals.standardLanguage || false;

            return new Promise(function (resolve) {
                require(['classes/Locale'], function (Locale) {
                    if (!standardLanguage) {
                        return resolve(new Locale());
                    }

                    var L = new Locale();
                    L.setCurrent(standardLanguage);

                    resolve(L);
                });
            });
        }
    });
});