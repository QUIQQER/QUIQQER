/**
 * @module controls/email/SelectItem
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/email/SelectItem', [

    'qui/QUI',
    'qui/controls/elements/SelectItem'

], function (QUI, QUIElementSelectItem) {
    "use strict";

    return new Class({

        Extends: QUIElementSelectItem,
        Type   : 'controls/email/SelectItem',

        Binds: [
            'refresh'
        ],

        initialize: function (options) {
            this.parent(options);
            this.setAttribute('icon', 'fa fa-envelope');
        },

        /**
         * Refresh the display
         *
         * @returns {Promise}
         */
        refresh: function () {
            var self = this,
                id   = this.getAttribute('id');

            return Promise.resolve().then(function () {
                self.$Text.set({
                    html: id
                });
            });
        }
    });
});
