/**
 * quiqqer locale
 * Extends the QUI locale with some quiqqer relevant methods
 *
 * @module classes/Locale
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/Locale
 */
define('classes/Locale', ['qui/classes/Locale'], function (QUILocale) {
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
         * Return decimal seperator
         * @returns {String}
         */
        getDecimalSeperator: function () {
            return this.get('quiqqer/quiqqer', 'numberFormat.decimal_separator');
        },

        /**
         * Return grouping seperator
         * @returns {String}
         */
        getGroupingSeperator: function () {
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

            if (typeof options === 'undefined') {
                return Intl.NumberFormat(locale);
            }

            //return Intl.NumberFormat(locale, {
            //    //style                : 'currency',
            //    //currency             : 'EUR',
            //    minimumFractionDigits: 8
            //});

            return Intl.NumberFormat(locale, options);
        }
    });
});