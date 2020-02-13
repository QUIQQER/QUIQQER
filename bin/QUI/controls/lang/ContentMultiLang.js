/**
 * Write content (ckeditor) for multiple languages
 *
 * @module controls/lang/ContentMultiLang
 * @author www.pcsg.de (Henning Leutz)
 * @author www.pcsg.de (Patrick Müller)
 */
define('controls/lang/ContentMultiLang', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'controls/lang/Select',
    'Editors',

    'css!controls/lang/ContentMultiLang.css'

], function (QUI, QUIControl, QUILoader, LangSelect, Editors) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/lang/ContentMultiLang',

        Binds: [
            'toggle',
            '$onImport',
            '$onInject',
            '$clearEditorPeriodicalSave',
            '$startEditorPeriodicalSave',
            '$saveContent'
        ],

        options: {
            styles: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$data = {};

            this.Loader = new QUILoader();

            this.$Elm    = null;
            this.$Input  = null;
            this.$Button = null;
            this.$Input  = null;

            this.$ContentContainer   = null;
            this.$Editor             = null;
            this.$editorSaveInterval = null;
            this.$LangSelect         = null;

            this.addEvents({
                onImport: this.$onImport,
                onInject: this.$onInject
            });
        },

        /**
         * Create the domnode element
         *
         * @return {Element|null}
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class': 'field-container-field quiqqer-lang-contentmultilang',
                'html' : '<div class="quiqqer-lang-contentmultilang-langselect"></div>' +
                    '<div class="quiqqer-lang-contentmultilang-content"></div>',
                styles : {
                    minHeight: 300
                }
            });

            if (this.getAttribute('styles')) {
                this.$Elm.setStyles(this.getAttribute('styles'));
            }

            if (!this.$Input) {
                this.$Input = new Element('input', {
                    type : 'hidden',
                    value: this.getAttribute('value'),
                    name : this.getAttribute('name')
                });
            }

            this.Loader.inject(this.$Elm);

            this.$LangSelect = new LangSelect({
                'class': 'quiqqer-lang-contentmultilang-langselect-select',
                events : {
                    onChange: function (Control, lang) {
                        this.$loadLangContent(lang);
                    }.bind(this)
                }
            }).inject(
                this.$Elm.getElement(
                    '.quiqqer-lang-contentmultilang-langselect'
                )
            );

            this.$ContentContainer = this.$Elm.getElement(
                '.quiqqer-lang-contentmultilang-content'
            );

            if (this.$Input.value !== '') {
                try {
                    this.$data = JSON.decode(this.$Input.value);
                } catch (e) {
                }

                if (typeOf(this.$data) !== 'object') {
                    this.$data = {};
                }
            }

            return this.$Elm;
        },

        /**
         * on inject
         */
        $onInject: function () {
            var self = this;

            if (!this.$Elm.getParent('.field-container')) {
                this.$Elm.removeClass('field-container-field');
            }

            Editors.getEditor().then(function (Editor) {
                Editor.addEvent('onLoaded', function () {
                    self.Loader.hide();
                    self.fireEvent('load', [self]);
                });

                Editor.inject(self.$ContentContainer);
                Editor.setContent('');

                self.$Editor = Editor;
                self.$loadLangContent(self.$LangSelect.getValue());
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            this.$Input = this.getElm();

            if (this.$Input.nodeName === 'TEXTAREA') {
                this.$Input.setStyle('display', 'none');
            } else {
                this.$Input.type = 'hidden';
            }

            this.create().wraps(this.$Input);
            this.$onInject();
        },

        /**
         * Load content for a specific language
         *
         * @param {string} lang
         */
        $loadLangContent: function (lang) {
            if (!this.$Editor) {
                return;
            }

            var content = '';

            if (lang in this.$data) {
                content = this.$data[lang];
            }

            this.$clearEditorPeriodicalSave();
            this.$Editor.setContent(content);
            this.$startEditorPeriodicalSave();
        },

        /**
         * Clear editor content save interval
         */
        $clearEditorPeriodicalSave: function () {
            if (!this.$editorSaveInterval) {
                return;
            }

            clearInterval(this.$editorSaveInterval);
            this.$editorSaveInterval = null;
        },

        /**
         * Start editor content save interval (every 1 second)
         */
        $startEditorPeriodicalSave: function () {
            var self = this;

            this.$editorSaveInterval = setInterval(function () {
                self.$saveContent();
            }, 1000);
        },

        /**
         * Save editor content
         */
        $saveContent: function () {
            var currentLang = this.$LangSelect.getValue();

            this.$data[currentLang] = this.$Editor.getContent();
            this.$Input.value       = JSON.encode(this.$data);
        },

        /**
         * Return the input value
         *
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
            this.$data        = this.getData();

            if (this.$LangSelect) {
                this.$loadLangContent(this.$LangSelect.getValue());
            }
        }
    });
});
