/**
 * @module controls/lang/InputMultiLang
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/lang/InputMultiLang', [

    'QUIQQER',
    'qui/QUI',
    'qui/controls/Control',
    'Ajax',
    'Locale',
    'css!controls/lang/InputMultiLang.css'

], function (QUIQQER, QUI, QUIControl, QUIAjax, QUILocale) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/lang/InputMultiLang',

        Binds: [
            'toggle',
            '$onImport',
            '$onInject'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Container = null;
            this.$Button = null;
            this.$Input = null;
            this.$disabled = false;
            this.$loaded = false;

            this.addEvents({
                onImport: this.$onImport,
                onInject: this.$onImport
            });
        },

        create: function () {
            this.$Elm = new Element('input', {
                type : 'hidden',
                value: this.getAttribute('value'),
                name : this.getAttribute('name')
            });

            return this.$Elm;
        },

        /**
         * event : on import
         */
        $onImport: function () {
            const self = this,
                  Elm  = this.getElm(),
                  path = URL_BIN_DIR + '16x16/flags/';

            this.$Input = Elm;
            this.$Input.type = 'hidden';

            this.$Button = new Element('span', {
                'class': 'field-container-item quiqqer-inputmultilang-button',
                html   : '<span class="fa fa-spinner fa-spin"></span>',
                styles : {
                    textAlign: 'center',
                    width    : 50
                }
            }).inject(Elm, 'after');

            this.$Elm = new Element('div', {
                'class': 'field-container-field'
            }).wraps(Elm, 'after');

            this.$Elm.addClass('quiqqer-inputmultilang__minimize');

            QUIQQER.getAvailableLanguages().then((languages) => {
                if (!this.$Elm) {
                    return;
                }

                let i, len, flag, lang, LangContainer, InputField;
                let current = QUILocale.getCurrent(),
                    data    = {};

                try {
                    data = JSON.decode(Elm.value);
                } catch (e) {
                    if (Elm.value.indexOf('https://') !== -1) {
                        for (i = 0, len = languages.length; i < len; i++) {
                            lang = languages[i];
                            data[lang] = Elm.value;
                        }
                    } else {
                        console.error(Elm.value);
                        console.error(e);
                    }
                }

                // php <-> js -> array / object conversion fix
                if (typeOf(data) === 'array') {
                    let newData = {};

                    Array.each(data, function (o) {
                        Object.merge(newData, o);
                    });

                    data = newData;
                }

                if (typeOf(data) !== 'object') {
                    data = {};
                }

                // current language to the top
                languages.sort(function (a, b) {
                    if (a === current) {
                        return -1;
                    }

                    if (b === current) {
                        return 1;
                    }

                    return 0;
                });

                const onChange = function () {
                    self.refreshData();
                };

                for (i = 0, len = languages.length; i < len; i++) {
                    lang = languages[i];
                    flag = path + lang + '.png';
                    
                    LangContainer = new Element('div', {
                        'class': 'quiqqer-inputmultilang-entry',
                        html   : '<input type="text" name="' + lang + '" />'
                    }).inject(this.$Elm);

                    InputField = LangContainer.getElement('input');
                    InputField.setStyles({
                        backgroundImage: "url('" + flag + "')"
                    });

                    if (i > 0) {
                        LangContainer.setStyles({
                            display: 'none',
                            opacity: 0
                        });
                    }

                    if (lang in data) {
                        if (data.hasOwnProperty(lang)) {
                            InputField.value = data[lang];
                        }
                    }

                    InputField.addEvent('change', onChange);
                }

                if (languages.length <= 1) {
                    self.$Button.setStyle('display', 'none');
                    self.$Button.destroy(); // needed because of css bug -> not last child
                }

                self.$Button.set({
                    html  : '<span class="fa fa-arrow-circle-o-right"></span>',
                    styles: {
                        cursor: 'pointer'
                    }
                });

                self.$Button.addEvent('click', self.toggle);
                self.refreshData();

                if (self.$disabled) {
                    self.disable();
                }

                self.$loaded = true;
                self.fireEvent('load', [self]);
            });
        },

        refresh: function() {
            let lang, Input;
            const inputData = this.getData();

            for (lang in inputData) {
                if (!inputData.hasOwnProperty(lang)) {
                    continue;
                }

                Input = this.getElm().getElement('[name="' + lang + '"]');

                if (Input) {
                    Input.value = inputData[lang];
                }
            }
        },

        /**
         * disable this control
         */
        disable: function () {
            this.$disabled = true;

            this.$Button.disabled = true;
            this.$Button.setStyle('cursor', 'not-allowed');

            this.$Elm.getElements('input').set('disabled', true);
        },

        /**
         * disable this control
         */
        enable: function () {
            this.$disabled = false;

            this.$Button.disabled = false;
            this.$Button.setStyle('cursor', 'pointer');

            this.$Elm.getElements('input').set('disabled', false);
        },

        isLoaded: function () {
            return this.$loaded;
        },

        /**
         * Return the input value
         * @returns {String}
         */
        getValue: function () {
            return this.$Input.value;
        },

        /**
         * Return the real data
         *
         * @returns {Object}
         */
        getData: function () {
            return JSON.decode(this.getValue());
        },

        /**
         * Set data
         *
         * @param data
         */
        setData: function (data) {
            if (!this.$Input) {
                return;
            }

            if (typeOf(data) !== 'string') {
                data = JSON.encode(data);
            }

            this.$Input.value = data;
            this.refresh();
        },

        /**
         * Toggle the open status
         */
        toggle: function (event) {
            if (this.$disabled) {
                return;
            }

            if (typeOf(event) === 'domevent') {
                event.stop();
            }

            if (this.$Button.getElement('span').hasClass('fa-arrow-circle-o-right')) {
                this.open();
            } else {
                this.close();
            }
        },

        /**
         * shows all translation entries
         */
        open: function () {
            if (this.$disabled) {
                return;
            }

            const self = this,
                  list = this.$Elm.getElements('.quiqqer-inputmultilang-entry');

            this.$Elm.removeClass(
                'quiqqer-inputmultilang__minimize'
            );

            const First = list.shift();

            list.setStyles({
                display: null,
                height : 0
            });

            moofx(First).animate({
                height: 34
            });

            if (list.length) {
                moofx(list).animate({
                    height : 34,
                    opacity: 1
                }, {
                    duration: 200,
                    callback: function () {
                        self.$Button.getElement('span')
                            .addClass('fa-arrow-circle-o-down')
                            .removeClass('fa-arrow-circle-o-right');
                    }
                });
            }
        },

        /**
         * shows all translation entries
         */
        close: function () {
            if (this.$disabled) {
                return;
            }

            const self = this,
                  list = this.$Elm.getElements(
                      '.quiqqer-inputmultilang-entry'
                  );

            const First = list.shift();

            First.setStyle('height', null);

            if (!list.length) {
                return;
            }

            moofx(list).animate({
                height : 0,
                opacity: 0
            }, {
                duration: 200,
                callback: function () {
                    self.$Elm.addClass(
                        'quiqqer-inputmultilang__minimize'
                    );

                    self.$Button.getElement('span')
                        .removeClass('fa-arrow-circle-o-down')
                        .addClass('fa-arrow-circle-o-right');
                }
            });
        },

        /**
         * Updates the data to the input field
         */
        refreshData: function () {
            const result = {};
            const fields = this.$Elm.getElements('input');

            fields.each(function (Field) {
                result[Field.name] = Field.value;
            });

            this.$Input.value = JSON.encode(result);
        }
    });
});
