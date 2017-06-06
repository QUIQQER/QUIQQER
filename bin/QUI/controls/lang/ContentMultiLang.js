/**
 * Write content (ckeditor) for multiple languages
 *
 * @module controls/lang/ContentMultiLang
 * @author www.pcsg.de (Henning Leutz)
 * @authro www.pcsg.de (Patrick Müller)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/loader/Loader
 * @require controls/lang/Select
 * @require Editors
 * @require css!controls/lang/ContentMultiLang.css
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

        initialize: function (options) {
            this.parent(options);

            this.$Container          = null;
            this.$Button             = null;
            this.$Input              = null;
            this.$Content            = {};
            this.$ContentContainer   = null;
            this.Loader              = new QUILoader();
            this.$CurrentEditor      = null;
            this.$editorSaveInterval = null;
            this.$LangSelect         = null;

            this.addEvents({
                onImport: this.$onImport
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
            var self = this,
                Elm  = this.getElm();

            this.$Container = new Element('div', {
                'class': 'field-container-field quiqqer-lang-contentmultilang',
                'html' : '<div class="quiqqer-lang-contentmultilang-langselect"></div>' +
                '<div class="quiqqer-lang-contentmultilang-content"></div>'
            }).inject(Elm, 'after');

            this.Loader.inject(this.$Container);

            this.$LangSelect = new LangSelect({
                'class': 'quiqqer-lang-contentmultilang-langselect-select',
                events : {
                    onChange: function (Control, lang) {
                        self.$loadLangContent(lang);
                    }
                }
            }).inject(
                this.$Container.getElement(
                    '.quiqqer-lang-contentmultilang-langselect'
                )
            );

            this.$Input            = Elm;
            this.$Input.type       = 'hidden';
            this.$ContentContainer = this.$Container.getElement(
                '.quiqqer-lang-contentmultilang-content'
            );

            if (this.$Input.value !== '') {
                this.$Content = JSON.decode(this.$Input.value);
            }
        },

        /**
         * Load content for a specific language
         *
         * @param {string} lang
         */
        $loadLangContent: function (lang) {
            var self    = this;
            var content = '';

            if (lang in this.$Content) {
                content = this.$Content[lang];
            }

            this.$ContentContainer.set('html', '');
            this.$clearEditorPeriodicalSave();
            this.Loader.show();

            Editors.getEditor().then(function (Editor) {
                Editor.addEvent('onLoaded', function () {
                    self.Loader.hide();
                });

                Editor.inject(self.$ContentContainer);
                Editor.setContent(content);

                self.$CurrentEditor = Editor;
                self.$startEditorPeriodicalSave();
            });
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
         * Save current editor content
         */
        $saveContent: function () {
            var currentLang = this.$LangSelect.getValue();

            this.$Content[currentLang] = this.$CurrentEditor.getContent();
            this.$Input.value          = JSON.encode(this.$Content);
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
        }
    });
});