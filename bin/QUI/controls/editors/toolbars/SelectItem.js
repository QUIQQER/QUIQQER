/**
 * @module controls/editors/toolbars/SelectItem
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/elements/Select
 */
define('controls/editors/toolbars/SelectItem', [

    'qui/QUI',
    'qui/controls/elements/SelectItem'

], function (QUI, QUIElementSelectItem) {
    "use strict";

    return new Class({

        Extends: QUIElementSelectItem,
        Type   : 'controls/editors/toolbars/SelectItem',

        Binds: [
            'refresh'
        ],

        initialize: function (options) {
            this.parent(options);
            this.setAttribute('icon', 'fa fa-font');
        },

        /**
         * Refresh the display
         *
         * @returns {Promise}
         */
        refresh: function () {
            var toolbar = this.getAttribute('id');

            this.setAttribute('icon', 'fa fa-font');

            this.$Text.set({
                html: toolbar
            });

            return Promise.resolve();
        }
    });
});
