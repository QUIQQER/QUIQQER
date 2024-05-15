/**
 * @module package/quiqqer/areas/bin/controls/Areas
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/groups/SelectItem', [

    'qui/QUI',
    'qui/controls/elements/SelectItem',
    'Ajax',
    'Groups'

], function(QUI, QUIElementSelectItem, QUIAjax, Groups) {
    'use strict';

    return new Class({

        Extends: QUIElementSelectItem,
        Type: 'controls/groups/SelectItem',

        Binds: [
            'refresh'
        ],

        initialize: function(options) {
            this.parent(options);
            this.setAttribute('icon', 'fa fa-group');
        },

        /**
         * Refresh the display
         *
         * @returns {Promise}
         */
        refresh: function() {
            let id = this.getAttribute('id'),
                Group = Groups.get(id),
                Prom = Promise.resolve();

            if (!Group.isLoaded()) {
                Prom = Group.load();
            }

            return Prom.then(() => {
                // everyone is not deletable
                if (id == 1) {
                    this.$Destroy.setStyle('display', 'none');
                }

                this.$Text.set({
                    html: Group.getName()
                });
            }).catch(() => {
                this.destroy();
            });
        }
    });
});
