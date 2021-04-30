/**
 * HTML Preview
 *
 * @package controls/editors/Input
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/editors/Preview', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : '',

        Binds: [
            '$onLoad'
        ],

        options: {
            styles: null
        },

        initialize: function (options) {
            this.parent(options);

            this.$Input = null;
            this.$Editor = null;
            this.$Project = null;
            this.$Preview = null;

            this.$loaded = false;
            this.$cssFileCount = {};

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * create DOMNode element
         *
         * @return {HTMLIFrameElement}
         */
        create: function () {
            this.$Elm = new Element('iframe', {
                'class': 'control-editor-preview',
                src    : URL_BIN_DIR + 'QUI/controls/editors/Preview.php?cid=' + this.getId(),
                styles : {
                    border: 'none',
                    height: '100%',
                    width : '100%'
                }
            });

            if (this.getAttribute('styles')) {
                this.$Elm.setStyles(this.getAttribute('styles'));
            }

            return this.$Elm;
        },

        /**
         * event : on load
         */
        $onLoad: function () {
            this.$loaded = true;
            this.fireEvent('load', [this]);
        },

        /**
         * Set the content to the frame
         */
        setContent: function (value) {
            if (!this.$loaded) {
                return;
            }

            this.$Elm.contentWindow.document.body.set('html', value);
        },

        /**
         * Add a css file to the content
         *
         * @param {String} file
         */
        addCSSFile: function (file) {
            // workaround if dom is not responsible
            if (!this.$Elm.contentWindow.document.head) {
                if (typeof this.$cssFileCount[file] === 'undefined') {
                    this.$cssFileCount[file] = 0;
                }

                if (this.$cssFileCount[file] > 20) {
                    return; // shit happens
                }

                this.$cssFileCount[file]++;

                (function () {
                    this.addCSSFile(file);
                }).delay(100, this);
                return;
            }

            new Element('link', {
                href: file,
                rel : "stylesheet",
                type: "text/css"
            }).inject(this.$Elm.contentWindow.document.head);
        }
    });
});
