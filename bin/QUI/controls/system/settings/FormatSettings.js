/**
 * Settings for formating
 * - Currency, Percent, Accounting Patterns
 * - Grouping, Seperators Pattern
 *
 * @module controls/system/settings/FormatSettings
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require Ajax
 */
define('controls/system/settings/FormatSettings', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',
    'Locale',
    'Mustache',
    'package/quiqqer/translator/bin/classes/Translator',

    'text!controls/system/settings/FormatSettings.Entry.html',
    'css!controls/system/settings/FormatSettings.css'

], function (QUI, QUIControl, QUIAjax, QUILocale, Mustache, Translate, templateEntry) {
    "use strict";

    var lg         = 'quiqqer/system';
    var Translator = new Translate();

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/system/settings/FormatSettings',

        Binds: [
            '$onImport'
        ],

        initialize: function (Panel) {
            this.parent();

            this.$Panel = Panel;
            this.$Input = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var Elm  = this.getElm();
            Elm.type = 'hidden';

            this.$Input = Elm;
            this.$Elm   = new Element('div', {
                'data-quiid': this.getId(),
                'class'     : 'quiqqer-formatsettings-list'
            }).wraps(Elm);

            QUIAjax.get([
                'ajax_system_getAvailableLanguages',
                'package_quiqqer_translator_ajax_refreshLocale'
            ], function (languages, locales) {

                var i, len, lang, data;

                for (i = 0, len = languages.length; i < len; i++) {
                    lang = languages[i];
                    data = locales[lang]['quiqqer/quiqqer'];

                    new Element('div', {
                        'class'    : 'quiqqer-formatsettings',
                        'data-lang': lang,
                        html       : Mustache.render(templateEntry, {
                            title: QUILocale.get(lg, 'language.' + lang),
                            flag : '<img src="' + URL_BIN_DIR + '16x16/flags/' + lang + '.png" />',

                            decimal_separator          : data['numberFormat.decimal_separator'],
                            grouping_separator         : data['numberFormat.grouping_separator'],
                            decimal_pattern            : data['numberFormat.decimal_pattern'],
                            percent_pattern            : data['numberFormat.percent_pattern'],
                            currency_pattern           : data['numberFormat.currency_pattern'],
                            accounting_currency_pattern: data['numberFormat.accounting_currency_pattern']
                        })
                    }).inject(this.getElm());
                }

            }.bind(this), {
                'package': 'quiqqer/translator'
            });
        },

        /**
         * Saves the data and refresh the locale
         *
         * @returns {Promise}
         */
        save: function () {
            return new Promise(function (resolve, reject) {

                var i, len, lang, Container;
                var container = this.getElm().getElements('.quiqqer-formatsettings'),
                    langs     = [];

                var list = {
                    'numberFormat.decimal_separator'          : {},
                    'numberFormat.grouping_separator'         : {},
                    'numberFormat.decimal_pattern'            : {},
                    'numberFormat.percent_pattern'            : {},
                    'numberFormat.currency_pattern'           : {},
                    'numberFormat.accounting_currency_pattern': {}
                };

                for (i = 0, len = container.length; i < len; i++) {
                    Container = container[i];
                    lang      = Container.get('data-lang');

                    langs.push(Container.get('data-lang'));

                    list['numberFormat.decimal_separator'][lang]  = Container.getElement('[name="decimal_separator"]').value;
                    list['numberFormat.grouping_separator'][lang] = Container.getElement('[name="grouping_separator"]').value;
                    list['numberFormat.decimal_pattern'][lang]    = Container.getElement('[name="decimal_pattern"]').value;
                    list['numberFormat.percent_pattern'][lang]    = Container.getElement('[name="percent_pattern"]').value;
                    list['numberFormat.currency_pattern'][lang]   = Container.getElement('[name="currency_pattern"]').value;

                    list['numberFormat.accounting_currency_pattern'][lang] = Container.getElement('[name="accounting_currency_pattern"]').value;
                }

                var promises = [];

                for (i in list) {
                    if (list.hasOwnProperty(i)) {
                        promises.push(
                            Translator.setTranslation('quiqqer/quiqqer', i, list[i])
                        );
                    }
                }

                QUI.getMessageHandler().then(function (MH) {
                    MH.setAttribute('showMessages', false);

                }).then(function () {
                    return Promise.all(promises);

                }).then(function () {
                    return QUI.getMessageHandler();

                }).then(function (MH) {
                    MH.setAttribute('showMessages', true);

                    return Translator.publish('quiqqer/quiqqer');

                }).then(resolve, reject);

            }.bind(this));
        }
    });
});
