/**
 * @module controls/installation/Licence
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/installation/Licence', [

    'qui/QUI',
    'qui/controls/Control'

], function (QUI, QUIControl) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/installation/Licence',

        Binds: [
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onImport: this.$onImport
            });
        },

        $onImport: function () {
            this.getElm().getElement('form').addEvent('submit', function (e) {
                e.stop();
            });
        },

        next: function () {
            const Licence = this.getElm().getElement('[name="licence"]');

            if (Licence.checked) {
                return;
            }
            
            if ("checkValidity" in Licence) {
                Licence.checkValidity();
            }

            // chrome validate message
            if ("reportValidity" in Licence) {
                Licence.reportValidity();
            }

            return false;
        }
    });
});
