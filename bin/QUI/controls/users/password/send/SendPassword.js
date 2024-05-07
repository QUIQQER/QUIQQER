/**
 * Send e-mail to a QUIQQER user
 *
 * @module controls/users/password/send/SendPassword
 * @author www.pcsg.de (Patrick Müller)
 *
 * @event onLoad [this] - Fires if control has finished loading everything
 */
define('controls/users/password/send/SendPassword', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'qui/controls/buttons/Button',
    'qui/utils/Form',

    'Ajax',
    'Locale',
    'Mustache',

    'text!controls/users/password/send/SendPassword.html',
    'css!controls/users/password/send/SendPassword.css'

], function (QUI, QUIConfirm, QUIButton, QUIFormUtils, QUIAjax, QUILocale, Mustache, template) {
    "use strict";

    var lg = 'quiqqer/core';

    return new Class({

        Extends: QUIConfirm,
        type   : 'controls/users/password/send/SendPassword',

        Binds: [
            '$onOpen',
            '$onSubmit',
            '$generatePassword'
        ],

        options: {
            userId: false,  // QUIQQER user ID

            maxHeight: 420,
            maxWidth : 800
        },

        initialize: function (options) {
            this.parent(options);

            this.setAttributes({
                icon         : 'fa fa-envelope',
                title        : QUILocale.get(lg, 'controls.SendPassword.title'),
                autoclose    : false,
                cancel_button: {
                    textimage: 'fa fa-close',
                    text     : QUILocale.get('quiqqer/system', 'close')
                },
                ok_button    : {
                    textimage: 'fa fa-envelope',
                    text     : QUILocale.get(lg, 'controls.SendPassword.submit')
                }
            });

            this.$Password       = null;
            this.$PasswordRepeat = null;
            this.$ForceNew       = null;

            this.addEvents({
                onOpen  : this.$onOpen,
                onSubmit: this.$onSubmit
            });
        },

        /**
         * event: on open
         */
        $onOpen: function () {
            var self    = this,
                Content = this.getContent();

            Content.set({
                html: Mustache.render(template, {
                    header             : QUILocale.get(lg, 'user.panel.password'),
                    labelPassword      : QUILocale.get(lg, 'user.panel.password.new'),
                    labelPasswordRepeat: QUILocale.get(lg, 'user.panel.password.repeat'),
                    labelPasswordShow  : QUILocale.get(lg, 'user.panel.password.show'),
                    labelForceNew      : QUILocale.get(lg, 'user.settings.setNewPassword'),
                    descForceNew       : QUILocale.get(lg, 'user.settings.setNewPassword.text')
                })
            });

            this.$Password       = Content.getElement('input[name="password"]');
            this.$PasswordRepeat = Content.getElement('input[name="password2"]');
            this.$ForceNew       = Content.getElement('input[name="quiqqer.set.new.password"]');

            var ShowPasswords = Content.getElement('input[name="showPasswords"]');

            ShowPasswords.addEvent('change', function () {
                var PasswordFields = Content.getElements(
                    '[name="password2"],[name="password"]'
                );

                if (this.checked) {
                    PasswordFields.set('type', 'text');
                    return;
                }

                PasswordFields.set('type', 'password');
            });

            new QUIButton({
                textimage: 'fa fa-lock',
                text     : QUILocale.get(lg, 'users.user.btn.password.generate'),
                events   : {
                    onClick: self.$generatePassword
                }
            }).inject(this.$Password, 'after');

            this.$generatePassword();

            ShowPasswords.click();
        },

        /**
         * Event: onSubmit
         */
        $onSubmit: function () {
            var self = this;

            var pw1 = this.$Password.value.trim(),
                pw2 = this.$PasswordRepeat.value.trim();

            if (pw1 !== pw2) {
                QUI.getMessageHandler().then(function (MH) {
                    MH.addAttention(
                        QUILocale.get(lg, 'controls.SendPassword.passwords_not_equal')
                    );
                });

                return;
            }

            this.Loader.show();

            this.$setAndSendPassword().then(function () {
                self.close();
            }, function () {
                self.Loader.hide();
            });
        },

        /**
         * Generate a random password and set it to the password fields
         * it saves not the passwords!!
         */
        $generatePassword: function () {
            var newPassword = Math.random().toString(36).slice(-8);

            this.$Password.value       = newPassword;
            this.$PasswordRepeat.value = newPassword;
        },

        /**
         * Send an e-mail to a QUIQQER user
         *
         * @return {Promise}
         */
        $setAndSendPassword: function () {
            var self = this;

            return new Promise(function (resolve, reject) {
                QUIAjax.get('ajax_user_setAndSendPassword', resolve, {
                    'package'  : 'quiqqer/core',
                    userId     : self.getAttribute('userId'),
                    newPassword: self.$Password.value.trim(),
                    forceNew   : self.$ForceNew.checked ? 1 : 0,
                    onError    : reject
                });
            });
        }
    });
});