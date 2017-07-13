/**
 * @module controls/editors/ToolbarConfigurator
 */
define('controls/editors/ToolbarConfigurator', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'qui/utils/Form',
    'Ajax',

    'css!controls/editors/ToolbarConfigurator.css'

], function (QUI, QUIControl, QUILoader, FormUtils, Ajax) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/editors/ToolbarConfigurator',

        options: {
            toolbar: false
        },

        Binds: [
            '$onInject'
        ],

        initialize: function (options) {
            this.parent(options);

            this.Loader = new QUILoader();

            this.$Elm      = null;
            this.$Select   = null;
            this.$Textarea = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the DOMNode Element
         *
         * @return {HTMLElement}
         */
        create: function () {
            var self = this;

            this.$Elm = new Element('div', {
                'class': 'control-editors-configurator box',
                html   : '<label for="">Verfügbare Buttons:</label>' +
                '<select class="control-editors-configurator-buttons" ' +
                ' size="5"></select>' +
                '<label for="">Toolbar</label>' +
                '<textarea class="control-editors-configurator-toolbar box"></textarea>'
            });

            this.Loader.inject(this.$Elm);

            this.$Select = this.$Elm.getElement(
                '.control-editors-configurator-buttons'
            );

            this.$Textarea = this.$Elm.getElement(
                '.control-editors-configurator-toolbar'
            );


            this.$Select.addEvents({
                mousedown: function (event) {
                    event.stop();

                    var Target = event.target;

                    if (Target.nodeName !== 'OPTION') {
                        return;
                    }

                    var value = Target.value,
                        str   = '<button>' + value + '</button>' + "\n";

                    if (value === 'separator') {
                        str = '<separator></separator>' + "\n";
                    }

                    FormUtils.insertTextAtCursor(self.$Textarea, str);
                }
            });

            // load button list
            var options = {
                'separator': 'Separator',

                'Source'               : 'Quelltext',
                'Templates'            : 'Vorlagen',
                'Cut'                  : 'Ausschneiden',
                'Copy'                 : 'kopieren',
                'Paste'                : 'einfügen',
                'PasteText'            : 'Text einfügen',
                'PasteFromWord'        : 'Von Word einfügen',
                'Undo'                 : 'Rückgängig',
                'Redo'                 : 'Wiederherstellen',
                'Find'                 : 'Suchen',
                'Replace'              : 'Ersetzen',
                'SelectAll'            : 'Alles markieren',
                'Scayt'                : 'Rechtschreibprüfung',
                'Form'                 : 'Formular',
                'Checkbox'             : 'Checkbox / Auswahlbox',
                'Radio'                : 'Radio-Button',
                'TextField'            : 'Textfeld einzeilig',
                'Textarea'             : 'Textfeld',
                'Select'               : 'Select Box',
                'Button'               : 'Button',
                'ImageButton'          : 'Bildbutton',
                'HiddenField'          : 'Verstecktes Feld',
                'Bold'                 : 'Fett',
                'Italic'               : 'Kursiv',
                'Underline'            : 'Unterstrichen',
                'Strike'               : 'Durchgestrichen',
                'Subscript'            : 'Tiefgestellt',
                'Superscript'          : 'Hochgestellt',
                'RemoveFormat'         : 'Formatierung entfernen',
                'NumberedList'         : 'Nummerierte Liste',
                'BulletedList'         : 'Liste',
                'Outdent'              : 'Einzug verringern',
                'Indent'               : 'Einzug erhöhen',
                'Blockquote'           : 'Zitatblock',
                'CreateDiv'            : 'DIV Container erzeugen',
                'JustifyLeft'          : 'Linksbündig',
                'JustifyCenter'        : 'Zentriert',
                'JustifyRight'         : 'Rechtsbündig',
                'JustifyBlock'         : 'Blocksatz',
                'BidiLtr'              : 'Leserichtung von Links nach Rechts',
                'BidiRtl'              : 'Leserichtung von Rechts nach Links',
                'Language'             : 'Sprache',
                'Link'                 : 'Link einfügen / editieren',
                'Unlink'               : 'Link entfernen',
                'Anchor'               : 'Anker einfügen / editieren',
                'Image'                : 'Bild einfügen / editieren',
                'Flash'                : 'Flash einfügen / editieren',
                'Table'                : 'Tabelle',
                'HorizontalRule'       : 'Horizontale Linie einfügen',
                'Smiley'               : 'Smiley',
                'SpecialChar'          : 'Sonderzeichen einfügen / editieren',
                'PageBreak'            : 'Seitenumbruch einfügen',
                'Iframe'               : 'IFrame',
                'Styles'               : 'Formatierungsstil',
                'Format'               : 'Format',
                'Font'                 : 'Schriftart',
                'FontSize'             : 'Schriftgröße',
                'TextColor'            : 'Textfarbe',
                'BGColor'              : 'Hintergrundfarbe',
                'Html5Audio'           : 'Audio',
                'Html5Video'           : 'Video',
                'Code'                 : 'Codetag',
                'Zoom'                 : 'Zoom',
                'Youtube'              : 'Youtube',
                'Videodetector'        : 'Videos',
                'Tweetabletext'        : 'Twitter',
                'Createtoken'          : 'Token hinzufügen',
                'TransformTextSwitcher': 'Transform Switcher',
                'TransformToUppercase' : 'Transform Uppercase',
                'TransformToLowercase' : 'Transform Lowercase',
                'TransformCapitalize'  : 'Transform Capitalize',
                't2s_button'           : 'Text to Speech',
                'Symbol'               : 'Symbole',
                'pre'                  : 'Pre tag',
                'pbckeditor'           : 'PBCKEditor',
                'page2images'          : 'Page 2 Image',
                'Markdown'             : 'Markdown',
                'Loremipsum'           : 'Lorem Ipsum',
                'inserthtml4x'         : 'Insert HTML',
                'EqnEditor'            : 'Formel Editor',
                'cssanim'              : 'CSS Animationen',
                'WebSpeechEnabled'     : 'WebSpeech',
                'WebSpeechSettings'    : 'WebSpeech Settings',
                'base64image'          : 'Base64Image',
                'Autocorrect'          : 'Autocorrect'
            };

            for (var i in options) {
                if (!options.hasOwnProperty(i)) {
                    continue;
                }

                new Element('option', {
                    html : options[i],
                    value: i
                }).inject(this.$Select);
            }

            return this.$Elm;
        },

        /**
         * save the toolbar
         */
        save: function () {
            var self = this;

            return new Promise(function (resolve, reject) {
                self.Loader.show();

                Ajax.post('ajax_editor_toolbar_save', function () {
                    self.Loader.hide();
                    resolve();
                }, {
                    toolbar: self.getAttribute('toolbar'),
                    xml    : self.$Textarea.value,
                    onError: function () {
                        self.Loader.hide();
                        reject();
                    }
                });
            });
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.Loader.show();

            if (!this.getAttribute('toolbar')) {
                this.Loader.hide();
                return;
            }

            var self = this;

            Ajax.get('ajax_editor_get_toolbar_xml', function (result) {
                self.$Textarea.value = result;
                self.Loader.hide();
            }, {
                toolbar: this.getAttribute('toolbar')
            });
        }
    });

});
