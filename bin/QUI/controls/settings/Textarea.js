/**
 * Control for settings that use a Textarea
 *
 * Reads/writes textarea lines in a JSON array
 *
 * @module package/quiqqer/quiqqer/bin/QUI/controls/settings/Textarea
 * @author www.pcsg.de (Patrick Müller)
 */
define('package/quiqqer/quiqqer/bin/QUI/controls/settings/Textarea', [

    'qui/controls/Control'

], function (QUIControl) {
    "use strict";

    return new Class({
        Extends: QUIControl,
        Type   : 'package/quiqqer/quiqqer/bin/QUI/controls/settings/Textarea',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * Event: onImport
         */
        $onImport: function () {
            var RealTextarea = this.getElm();

            RealTextarea.setStyle('display', 'none');

            var Textarea = new Element('textarea', {
                rows  : RealTextarea.get('rows'),
                cols  : RealTextarea.get('cols'),
                styles: {
                    width: 'calc(100% - 200px)'
                }
            }).inject(RealTextarea, 'after');

            if (RealTextarea.value) {
                var lines;

                try {
                    lines = JSON.decode(RealTextarea.value);
                } catch (e) {
                    lines = [];
                }

                Textarea.value = lines.join("\n");
            }

            Textarea.addEvents({
                keyup: function () {
                    var lines          = Textarea.value.split("\n");
                    RealTextarea.value = JSON.encode(lines);
                }
            });
        }
    });
});