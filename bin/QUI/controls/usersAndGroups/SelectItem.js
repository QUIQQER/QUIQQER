/**
 * @module controls/usersAndGroups/SelectItem
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/elements/Select
 * @require Ajax
 * @require Groups
 * @require Users
 */
define('controls/usersAndGroups/SelectItem', [

    'qui/QUI',
    'qui/controls/elements/SelectItem',
    'Ajax',
    'Groups',
    'Users'

], function (QUI, QUIElementSelectItem, QUIAjax, Groups, Users) {
    "use strict";

    return new Class({

        Extends: QUIElementSelectItem,
        Type   : 'controls/usersAndGroups/SelectItem',

        Binds: [
            'refresh'
        ],

        initialize: function (options) {
            this.parent(options);
            this.setAttribute('icon', 'fa fa-group');
        },

        /**
         * Refresh the display
         *
         * @returns {Promise}
         */
        refresh: function () {
            var id   = this.getAttribute('id'),
                Prom = Promise.resolve();

            // group
            if (id.charAt(0) === 'g') {
                this.setAttribute('icon', 'fa fa-group');

                var Group = Groups.get(parseInt(id.substring(1)));

                if (!Group.isLoaded()) {
                    Prom = Group.load();
                }

                return Prom.then(function () {
                    // everyone is not deletable
                    if (id == 1) {
                        this.$Destroy.setStyle('display', 'none');
                    }

                    this.$Text.set({
                        html: Group.getName()
                    });
                }.bind(this));
            }

            // user
            this.setAttribute('icon', 'fa fa-user');

            var User = Users.get(parseInt(id.substring(1)));

            if (!User.isLoaded()) {
                Prom = User.load();
            }

            return Prom.then(function () {
                // everyone is not deletable
                if (id == 1) {
                    this.$Destroy.setStyle('display', 'none');
                }

                this.$Text.set({
                    html: User.getName()
                });
            }.bind(this));
        }
    });
});