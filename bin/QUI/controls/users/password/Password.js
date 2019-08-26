/**
 * @module controls/users/password/Password
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require Ajax
 * @require Locale
 * @require css!controls/users/password/Password.css
 *
 * @event onSaveBegin [self]
 * @event onSaveEnd [self]
 * @event onSave [self]
 */
define('controls/users/password/Password', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',
    'Locale',

    'css!controls/users/password/Password.css'

], function (QUI, QUIControl, QUIAjax, QUILocale) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    return new Class({
        Extends: QUIControl,
        Type   : 'controls/users/password/Password',

        Binds: [
            '$onInject'
        ],

        options: {
            uid       : false,
            mustChange: false
        },

        initialize: function (options) {
            this.parent(options);

            if (!this.getAttribute('uid')) {
                var uid = false;

                if (typeof USER !== 'undefined') {
                    uid = USER.id;
                } else if (typeof QUIQQER_USER !== 'undefined') {
                    uid = QUIQQER_USER.id;
                }

                this.setAttribute('uid', uid);
            }

            this.$Password         = null;
            this.$Password2        = null;
            this.$OldPassword      = null;
            this.$ShowPassCheckbox = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the domnode element
         *
         * @returns {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('form', {
                'class'     : 'qui-control-user-password',
                html        : '<label>' +
                '    <span class="qui-control-user-password-title">' +
                QUILocale.get(lg, 'user.panel.password.old') +
                '    </span>' +
                '    <input type="password" name="oldPassword" required autocomplete="off" />' +
                '</label>' +
                '<label>' +
                '    <span class="qui-control-user-password-title">' +
                QUILocale.get(lg, 'user.panel.password.new') +
                '    </span>' +
                '    <input type="password" name="password" required autocomplete="off" />' +
                '</label>' +
                '<label>' +
                '    <span class="qui-control-user-password-title">' +
                QUILocale.get(lg, 'user.panel.password.repeat') +
                '    </span>' +
                '    <input type="password" name="password2" required autocomplete="off" />' +
                '</label>' +
                '<label>' +
                '    <input type="checkbox" name="show" />' +
                QUILocale.get(lg, 'user.panel.password.show') +
                '</label>',
                autocomplete: "off"
            });

            this.$Elm.addEvent('submit', function (event) {
                event.stop();
            });

            this.$Password         = this.$Elm.getElement('[name="password"]');
            this.$Password2        = this.$Elm.getElement('[name="password2"]');
            this.$OldPassword      = this.$Elm.getElement('[name="oldPassword"]');
            this.$ShowPassCheckbox = this.$Elm.getElement('[name="show"]');

            this.$ShowPassCheckbox.addEvent('change', function () {
                if (this.$ShowPassCheckbox.checked) {
                    this.$Password.type    = 'text';
                    this.$Password2.type   = 'text';
                    this.$OldPassword.type = 'text';
                } else {
                    this.$Password.type    = 'password';
                    this.$Password2.type   = 'password';
                    this.$OldPassword.type = 'password';
                }
            }.bind(this));

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.$OldPassword.focus();
        },

        /**
         * Save the password
         *
         * @returns {Promise}
         */
        save: function () {
            if (!this.getAttribute('uid')) {
                return Promise.resolve();
            }

            this.fireEvent('saveBegin', [this]);

            return new Promise(function (resolve, reject) {
                QUIAjax.post('ajax_users_set_passwordChange', function () {
                    resolve();
                    this.fireEvent('save', [this]);
                    this.fireEvent('saveEnd', [this]);
                }.bind(this), {
                    uid           : this.getAttribute('uid'),
                    newPassword   : this.$Password.value,
                    passwordRepeat: this.$Password2.value,
                    oldPassword   : this.$OldPassword.value,
                    onError       : reject
                });
            }.bind(this));
        }
    });
});