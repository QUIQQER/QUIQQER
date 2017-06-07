/**
 * @package controls/lang/Select
 * @author www.pcsg.de (Henning Leutz)
 *
 * Sprachauswahl - DropDown
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Select
 * @require Ajax
 * @require Locale
 *
 * @event onChange
 */
define('controls/lang/Select', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Select',
    'Ajax',
    'Locale'

], function (QUI, QUIControl, QUISelect, QUIAjax, QUILocale) {
    "use strict";

    return new Class({
        Extends: QUIControl,

        Binds: [
            '$onInject',
            '$onImport',
            'getValue',
            'selectFirst'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Input  = null;
            this.$Select = null;
            this.$first  = false;

            this.addEvents({
                onImport: this.$onImport,
                onInject: this.$onInject
            });
        },

        /**
         * event : on inject
         */
        $onImport: function () {
            var Elm = this.getElm();

            if (Elm.nodeName === 'INPUT') {
                Elm.type = 'hidden';

                this.$Input = Elm;
                this.$Elm   = new Element('div').wraps(this.$Input);
            }

            this.$Select = new QUISelect({
                disabled: true,
                events  : {
                    onChange: function (value) {
                        this.$Input.value = value;
                        this.fireEvent('change', [this, value]);
                    }.bind(this)
                }
            }).inject(this.$Elm);

            if (this.$Elm.getParent('.field-container')) {
                this.$Elm.setStyles({
                    'flex': 1
                });

                this.$Select.getElm().setStyle('width', '100%');
            }

            this.$buildSelect().then(function () {
                if (this.$Input.value !== '') {
                    this.$Select.setValue(this.$Input.value);
                } else {
                    this.selectFirst();
                }
            }.bind(this));
        },

        /**
         * Select first entry
         */
        selectFirst: function () {
            this.$Select.setValue(this.$first);
        },

        /**
         * Event: onInject
         */
        $onInject: function () {
            var self = this;

            this.$Select = new QUISelect({
                disabled: true,
                events  : {
                    onChange: function (value) {
                        this.fireEvent('change', [this, value]);
                    }.bind(this)
                }
            }).inject(this.$Elm);

            this.$buildSelect().then(function() {
                self.selectFirst();
            });
        },

        /**
         * Build language select
         *
         * @return {Promise}
         */
        $buildSelect: function () {
            var self = this;

            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_system_getAvailableLanguages', function (languages) {
                    for (var i = 0, len = languages.length; i < len; i++) {
                        self.$Select.appendChild(
                            QUILocale.get('quiqqer/system', 'language.' + languages[i]),
                            languages[i],
                            URL_BIN_DIR + '16x16/flags/' + languages[i] + '.png'
                        );

                        if (self.$first === false) {
                            self.$first = languages[i];
                        }
                    }

                    resolve();
                }, {
                    onError: reject
                });
            });
        },

        /**
         * Get value
         */
        getValue: function () {
            return this.$Select.getValue();
        }
    });
});