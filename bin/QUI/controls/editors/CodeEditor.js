/**
 * @module controls/editors/CodeEditor
 * @author www.pcsg.de (Henning Leutz)
 */

require.config({
    paths: {
        'ace/ace'           : URL_OPT_DIR + 'bin/quiqqer-asset/ace-builds/ace-builds/src-min/ace',
        'ace/theme/twilight': URL_OPT_DIR + 'bin/quiqqer-asset/ace-builds/ace-builds/src-min/theme-twilight'
    }
});

define('controls/editors/CodeEditor', [

    'qui/QUI',
    'qui/controls/Control',

    "ace/ace"

], function (QUI, QUIControl, Ace) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/editors/Editor',

        Binds: [
            '$onImport',
            '$onInject'
        ],

        options: {
            type: ''
        },

        initialize: function (options) {
            this.parent(options);

            this.$Editor = null;
            this.$value = null;

            this.addEvents({
                onImport: this.$onImport,
                onInject: this.$onInject
            });

            this.fireEvent('init', [this]);
        },

        $onImport: function () {
            console.log('onimport');
        },

        $onInject: function () {
            this.getElm().setStyles({
                height: '100%'
            });

            this.$Editor = Ace.edit(this.getElm());

            let requireFiles = [];

            switch (this.getAttribute('type')) {
                case 'css':
                    requireFiles.push(
                        URL_OPT_DIR + 'bin/quiqqer-asset/ace-builds/ace-builds/src/mode-css.js'
                    );
                    break;

                case 'javascript':
                    requireFiles.push(
                        URL_OPT_DIR + 'bin/quiqqer-asset/ace-builds/ace-builds/src/mode-javascript.js'
                    );
                    break;
            }

            require(requireFiles, () => {
                let req = null;

                switch (this.getAttribute('type')) {
                    case 'css':
                        req = "ace/mode/css";
                        break;

                    case 'javascript':
                        req = "ace/mode/javascript";
                        break;
                }

                require([req], (Module) => {
                    if (Module.Mode) {
                        this.$Editor.session.setMode(new Module.Mode());
                    }

                    if (this.$value) {
                        this.$Editor.setValue(this.$value);
                    }

                    this.fireEvent('load');
                });
            });
        },

        setValue: function (value) {
            if (this.$Editor) {
                this.$Editor.setValue(value);
                return;
            }

            this.$value = value;
        },

        getValue: function () {
            if (this.$Editor) {
                return this.$Editor.getValue();
            }

            return '';
        }
    });
});