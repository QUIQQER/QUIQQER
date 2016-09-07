/**
 * @module controls/users/password/Password
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require Ajax
 * @require css!controls/users/password/Password.css
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
            uid: false
        },

        initialize: function (options) {
            this.parent(options);

            if (!this.getAttribute('uid')) {
                this.setAttribute('uid', USER.id);
            }

            this.$Password         = null;
            this.$ShowPassCheckbox = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         *
         * @returns {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('form', {
                'class'     : 'qui-controle-user-password',
                html        : '<label>' +
                              '    <span class="qui-controle-user-password-title">' +
                              QUILocale.get(lg, 'user.panel.password.new') +
                              '    </span>' +
                              '    <input type="password" name="password" autocomplete="off" />' +
                              '</label>' +
                              '<label>' +
                              '    <span class="qui-controle-user-password-title">' +
                              QUILocale.get(lg, 'user.panel.password.repeat') +
                              '    </span>' +
                              '    <input type="password" name="password2"autocomplete="off" />' +
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
            this.$ShowPassCheckbox = this.$Elm.getElement('[name="show"]');

            this.$ShowPassCheckbox.addEvent('change', function () {
                if (this.$ShowPassCheckbox.checked) {
                    this.$Password.type  = 'text';
                    this.$Password2.type = 'text';
                } else {
                    this.$Password.type  = 'password';
                    this.$Password2.type = 'password';
                }
            }.bind(this));

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.$Password.focus();
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

            return new Promise(function (resolve, reject) {

                QUIAjax.post('ajax_users_set_password', function () {
                    resolve();
                    this.fireEvent('save', [this]);
                }.bind(this), {
                    uid    : this.getAttribute('uid'),
                    pw1    : this.$Password.value,
                    pw2    : this.$Password2.value,
                    onError: reject
                });
            }.bind(this));
        }
    });
});