/**
 * Available language list control
 * list all available languages from the system
 *
 * @module controls/system/AvailableLanguages
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/loader/Loader
 * @require Ajax
 * @require Locale
 * @require css!controls/system/AvailableLanguages.css
 */
define('controls/system/AvailableLanguages', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'Ajax',
    'Locale',

    'css!controls/system/AvailableLanguages.css'

], function (QUI, QUIControl, QUILoader, QUIAjax, QUILocale) {
    "use strict";

    return new Class({

        Type   : 'controls/system/AvailableLanguages',
        Extends: QUIControl,

        Binds: [
            '$onInject',
            '$onImport'
        ],

        options: {
            values     : false,
            placeholder: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Input  = null;
            this.$loaded = false;

            this.Loader = new QUILoader();

            this.addEvents({
                onInject: this.$onInject,
                onImport: this.$onImport
            });
        },

        /**
         * Create the DOMNode Element
         *
         * @return {HTMLElement}
         */
        create: function () {
            if (!this.$Elm) {
                this.$Elm = this.parent();

                this.$Input = new Element('input', {
                    type: 'hidden'
                }).inject(this.$Elm);
            }

            if (!this.getAttribute('placeholder') &&
                this.$Input.get('placeholder')) {
                this.setAttribute('placeholder', this.$Input.get('placeholder'));
            }

            if (!this.getAttribute('placeholder')) {
                this.setAttribute('placeholder', '%D');
            }

            this.Loader.inject(this.$Elm);

            return this.$Elm;
        },

        /**
         * set the values to the fields
         *
         * @param {Object} values - list of the lang values
         */
        setValue: function (values) {
            if (typeOf(values) !== 'object') {
                return;
            }

            if (this.$loaded === false || !this.$Input) {
                this.setAttribute('values', values);
                return;
            }

            var lang, name, Input;
            var parentName = this.$Input.get('name');

            for (lang in values) {
                if (!values.hasOwnProperty(lang)) {
                    continue;
                }

                name  = parentName + '.' + lang;
                Input = this.$Elm.getElement('[name="' + name + '"]');

                if (Input) {
                    Input.value = values[lang];
                }
            }

            this.setAttribute('values', values);
        },

        /**
         * event on inject
         */
        $onInject: function () {
            var self = this;

            this.Loader.show();

            this.getAvailableLanguages(function (list) {
                var i, len, flag, name, langtext;

                var parentName  = self.$Input.get('name'),
                    placeholder = self.getAttribute('placeholder');

                for (i = 0, len = list.length; i < len; i++) {
                    flag = '<span class="quiqqer-available-flag">' +
                           '<img src="' + URL_BIN_DIR + '16x16/flags/' + list[i] + '.png" />' +
                           '</span>';

                    langtext = QUILocale.get('quiqqer/quiqqer', 'language.' + list[i]);
                    name     = parentName + '.' + list[i];

                    new Element('label', {
                        'class'    : 'quiqqer-available-languages-entry',
                        'data-lang': list[i],
                        html       : '<input type="text" name="' + name + '" placeholder="' + placeholder + '" />' +
                                     '<span class="quiqqer-available-languages-entry-text">' +
                                     flag + langtext +
                                     '</span>'
                    }).inject(self.getElm());
                }

                self.$loaded = true;

                if (self.getAttribute('values')) {
                    self.setValue(self.getAttribute('values'));
                }

                self.Loader.hide();
            });
        },

        /**
         * event on import
         */
        $onImport: function () {
            if (this.$Elm.nodeName === 'INPUT') {
                this.$Elm.set('type', 'hidden');

                var Elm = new Element('div', {
                    'class': 'quiqqer-availableLanguages'
                });

                Elm.wraps(this.$Elm);

                this.$Elm   = Elm;
                this.$Input = this.$Elm.getElement('input');
            }

            this.create();
            this.$onInject();
        },

        /**
         * Return the available languages
         * @param {Function} callback
         */
        getAvailableLanguages: function (callback) {
            QUIAjax.get('ajax_system_getAvailableLanguages', callback);
        }
    });
});
