/**
 * @package controls/lang/Select
 * @author www.pcsg.de (Henning Leutz)
 *
 * Sprachauswahl - DropDown
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
                    onChangeBegin: function (value) {
                        this.fireEvent('changeBegin', [this, value]);
                    }.bind(this),

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
                    onChangeBegin: function (value) {
                        this.fireEvent('changeBegin', [this, value]);
                    }.bind(this),
                    
                    onChange: function (value) {
                        this.fireEvent('change', [this, value]);
                    }.bind(this)
                }
            }).inject(this.$Elm);

            this.$buildSelect().then(function () {
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
            var Prom;

            if (typeof QUIQQER_FRONTEND !== 'undefined' && QUIQQER_PROJECT) {
                Prom = Promise.resolve(QUIQQER_PROJECT.languages.split(','));
            } else {
                Prom = new Promise(function (resolve, reject) {
                    require(['QUIQQER'], function (QUIQQER) {
                        QUIQQER.getAvailableLanguages().then(resolve).catch(reject);
                    });
                });
            }

            return Prom.then(function (languages) {
                for (var i = 0, len = languages.length; i < len; i++) {
                    self.$Select.appendChild(
                        QUILocale.get('quiqqer/core', 'language.' + languages[i]),
                        languages[i],
                        URL_BIN_DIR + '16x16/flags/' + languages[i] + '.png'
                    );

                    if (self.$first === false) {
                        self.$first = languages[i];
                    }
                }
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
