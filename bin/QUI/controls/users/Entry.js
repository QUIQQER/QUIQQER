/**
 * A user field / display
 * the display updates itself
 *
 * @module controls/users/Entry
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onLoad [self, User]
 * @event onError [self, uid]
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

            this.$Elm = null;
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
            const self = this;

            this.$Elm = new Element('div', {
                'class'     : 'users-entry users-entry-enabled smooth',
                'data-id'   : this.$User.getId(),
                'data-quiid': this.getId(),
                title       : this.$User.getName() + ' - ' + this.$User.getId(),
                html        : '<div class="users-entry-icon fa"></div>' +
                              '<div class="users-entry-text"></div>' +
                              '<div class="users-entry-close fa fa-remove"></div>'
            });

            const Close = this.$Elm.getElement('.users-entry-close');

            Close.addEvent('click', function () {
                if (!self.isDisabled()) {
                    self.destroy();
                }
            });

            Close.set({
                alt  : Locale.get('quiqqer/core', 'users.entry.user.remove'),
                title: Locale.get('quiqqer/core', 'users.entry.user.remove')
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
            const UserIcon = this.$Elm.getElement('.users-entry-icon');

            if (!UserIcon) {
                return this;
            }

            UserIcon.removeClass('fa-user');
            UserIcon.addClass('fa-spinner');
            UserIcon.addClass('fa-spin');

            if (this.$User.getAttribute('name')) {
                this.$onUserUpdate(this.$User);
                return this;
            }

            const uid = this.$User.getId();

            if (uid === '') {
                this.fireEvent('error', [
                    this,
                    uid
                ]);
                this.destroy();
                return this;
            }

            this.$User.load().then(function () {
                this.fireEvent('load', [
                    this,
                    this.$User
                ]);
            }.bind(this)).catch(function () {
                this.fireEvent('error', [
                    this,
                    uid
                ]);
                this.destroy();
            }.bind(this));

            return this;
        },

        /**
         * Update the username
         *
         * @param {Object} User - classes/users/User
         * @return {Object} this (controls/users/Entry)
         */
        $onUserUpdate: function (User) {
            if (!this.$Elm) {
                return this;
            }

            const UserIcon = this.$Elm.getElement('.users-entry-icon');

            if (!UserIcon) {
                return this;
            }

            UserIcon.addClass('fa-user');
            UserIcon.removeClass('fa-spinner');
            UserIcon.removeClass('fa-spin');

            let displayName = User.getName();

            if (User.getAttribute('companyName')) {
                displayName = displayName + ', ' + User.getAttribute('companyName');
            }

            displayName = displayName + ' (' + User.getId() + ')';
            this.$Elm.getElement('.users-entry-text').set('html', displayName);

            return this;
        },

        /**
         * Disable the control
         * no changes are possible
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
