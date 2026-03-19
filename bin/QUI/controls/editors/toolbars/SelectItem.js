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
            var label = toolbar;

            if (label.indexOf(':') !== -1) {
                label = label.split(':').pop();
            }

            this.setAttribute('icon', 'fa fa-font');

            this.$Text.set({
                html: label,
                title: toolbar
            });

            return Promise.resolve();
        }
    });
});
