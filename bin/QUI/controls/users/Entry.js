/**
 * A user field / display
 * the display updates itself
 *
 * @module controls/users/Entry
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/Control
 * @require Users
 * @require Locale
 * @require css!controls/users/Entry.css
 */

define('controls/users/Entry', [
    'qui/controls/Control',
    'Users',
    'Locale',
    'css!controls/users/Entry.css'
], function (QUIControl, Users, Locale) {
    "use strict";

    /**
     * @class controls/users/Entry
     *
     * @param {Number} uid - user-ID
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIControl,
        Type   : 'controls/users/Entry',

        Binds: [
            '$onUserUpdate',
            'destroy'
        ],

        initialize: function (uid, options) {
            this.$User = Users.get(uid);
            this.parent(options);

            this.$Elm      = null;
            this.$disabled = false;
        },

        /**
         * Return the binded user
         *
         * @return {Object} classes/users/User
         */
        getUser: function () {
            return this.$User;
        },

        /**
         * Create the DOMNode of the entry
         *
         * @return {HTMLElement}
         */
        create: function () {
            var self = this;

            this.$Elm = new Element('div', {
                'class'     : 'users-entry users-entry-enabled smooth',
                'data-id'   : this.$User.getId(),
                'data-quiid': this.getId(),

                html: '<div class="users-entry-icon"></div>' +
                      '<div class="users-entry-text"></div>' +
                      '<div class="users-entry-close icon-remove"></div>'
            });

            var Close = this.$Elm.getElement('.users-entry-close');

            Close.addEvent('click', function () {
                if (!self.isDisabled()) {
                    self.destroy();
                }
            });

            Close.set({
                alt  : Locale.get('quiqqer/system', 'users.entry.user.remove'),
                title: Locale.get('quiqqer/system', 'users.entry.user.remove')
            });

            this.$User.addEvent('onRefresh', this.$onUserUpdate);
            this.refresh();

            return this.$Elm;
        },

        /**
         * event : on entry destroy
         */
        $onDestroy: function () {
            this.$User.removeEvent('refresh', this.$onUserUpdate);
        },

        /**
         * Refresh the data of the users
         *
         * @return {Object} this (controls/users/Entry)
         */
        refresh: function () {
            var UserIcon = this.$Elm.getElement('.users-entry-icon');

            if (!UserIcon) {
                return this;
            }

            UserIcon.removeClass('icon-user');
            UserIcon.addClass('icon-refresh');
            UserIcon.addClass('icon-spin');

            if (this.$User.getAttribute('name')) {
                this.$onUserUpdate(this.$User);
                return this;
            }

            this.$User.load();

            return this;
        },

        /**
         * Update the user name
         *
         * @param {Object} User - classes/users/User
         * @return {Object} this (controls/users/Entry)
         */
        $onUserUpdate: function (User) {
            if (!this.$Elm) {
                return this;
            }

            var UserIcon = this.$Elm.getElement('.users-entry-icon');

            if (!UserIcon) {
                return this;
            }

            UserIcon.addClass('icon-user');
            UserIcon.removeClass('icon-refresh');
            UserIcon.removeClass('icon-spin');

            this.$Elm.getElement('.users-entry-text')
                .set('html', User.getName() + ' (' + User.getId() + ')');

            return this;
        },

        /**
         * Disable the control
         * no changes are posible
         */
        disable: function () {
            this.$Elm.removeClass('users-entry-enabled');
            this.$disabled = true;
        },

        /**
         * Disable the control
         * changes are posible
         */
        enable: function () {
            this.$Elm.addClass('users-entry-enabled');
            this.$disabled = false;
        },

        /**
         * Is it disabled?
         * if disabled, no changes are possible
         */
        isDisabled: function () {
            return this.$disabled;
        }
    });
});
