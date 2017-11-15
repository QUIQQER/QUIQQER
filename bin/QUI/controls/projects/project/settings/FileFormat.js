/**
 * Display the formatted format for a file format input field
 *
 * @module controls/projects/project/settings/FileFormat
 * @author www.pcsg.de (Henning Leutz)
 */

define('controls/projects/project/settings/FileFormat', [

    'qui/QUI',
    'qui/controls/Control',

    'css!controls/projects/project/settings/FileFormat.css'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/projects/project/settings/FileFormat',

        Binds: [
            '$onImport',
            'refresh'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Input = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event: on import
         */
        $onImport: function () {
            this.$Input = this.getElm();

            this.$Elm = new Element('div', {
                'class': 'qui-projects-settings-file-format'
            }).wraps(this.$Input);

            this.$Display = new Element('div', {
                'class': 'qui-projects-settings-file-format-display'
            }).inject(this.$Elm);

            this.$Input.addEvents({
                keyup : this.refresh,
                change: this.refresh,
                blur  : this.refresh
            });

            this.refresh();
        },

        /**
         * Refresh the display and format the value
         */
        refresh: function () {
            var size  = parseFloat(this.$Input.value);
            var sizes = ['Byte', 'KB', 'MB', 'GB', 'TB'];

            for (var i = 0, len = sizes.length; i < len - 1 && size >= 1024; i++) {
                size /= 1024;
            }

            var value = Math.round(size * 100) / 100 + ' ' + sizes[i];

            this.$Display.set('html', value);
        }
    });
});