/**
 * QUIQQER Authentication via email and password
 *
 * Includes password reset functionality
 *
 * @module controls/users/auth/QUIQQERLogin
 * @authro Patrick Müller (www.pcsg.de)
 */
define('controls/users/auth/QUIQQERLogin', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',

    'Locale',
    'Ajax',

    //'css!controls/users/auth/QUIQQERLogin.css'

], function (QUI, QUIControl, QUILoader, QUILocale, QUIAjax) {
    "use strict";

    var lg = 'quiqqer/system';

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/users/auth/QUIQQERLogin',

        Binds: [
            '$onImport',
            '$passwordReset',
            '$initPasswordReset',
            '$sendPasswordResetConfirmMail'
        ],

        /**
         * construct
         * @param {Object} options
         */
        initialize: function (options) {
            this.parent(options);

            this.Loader = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            this.Loader = new QUILoader().inject(this.getElm());
            this.$initPasswordReset();
        },

        /**
         * Initialize password reset functionality
         */
        $initPasswordReset: function () {
            var self             = this;
            var PasswordResetElm = this.getElm().getElement('.quiqqer-auth-login-passwordreset');

            if (!PasswordResetElm) {
                return;
            }

            var PasswordResetLink = PasswordResetElm.getElement('.quiqqer-auth-login-passwordreset-start');

            PasswordResetLink.addEvent('click', function (event) {
                event.stop();

                PasswordResetLink.destroy();
                PasswordResetElm.getElement('label').setStyle('display', 'block');

                self.$passwordReset();
            });
        },

        /**
         * Start password reset process
         */
        $passwordReset: function () {
            var self = this;
            var Elm  = this.getElm();

            var UsernameInput = Elm.getElement('input[name="username"]').getParent('label');
            var PasswordInput = Elm.getElement('input[name="password"]').getParent('label');
            var EmailInput    = Elm.getElement('input[name="email"]');
            var SubmitBtn     = Elm.getElement('input[type="submit"]');
            var MsgElm        = Elm.getElement('.quiqqer-auth-login-message');

            UsernameInput.destroy();
            PasswordInput.destroy();
            EmailInput.focus();

            SubmitBtn.value = QUILocale.get(lg, 'controls.users.auth.quiqqerlogin.btn.password_reset');

            SubmitBtn.addEvent('click', function (event) {
                event.stop();

                var email = EmailInput.value.trim();

                if (email === '') {
                    EmailInput.focus();
                    return;
                }

                self.Loader.show();
                SubmitBtn.disabled = true;
                MsgElm.set('html', '');

                self.$sendPasswordResetConfirmMail(email).then(function () {
                    self.Loader.hide();

                    new Element('div', {
                        'class': 'content-message-success',
                        html   : '<span>' +
                        QUILocale.get(lg, 'controls.users.auth.quiqqerlogin.send_mail_success') +
                        '</span>'
                    }).inject(MsgElm);

                    SubmitBtn.destroy();
                    EmailInput.getParent('label').destroy();


                    //(function () {
                    //    window.location.reload();
                    //}.delay(5000));
                }, function (e) {
                    self.Loader.hide();

                    new Element('div', {
                        'class': 'content-message-error',
                        html   : '<span>' +
                        QUILocale.get(lg, 'controls.users.auth.quiqqerlogin.send_mail_error', {
                            error: e.getMessage()
                        }) +
                        '</span>'
                    }).inject(MsgElm);

                    SubmitBtn.disabled = false;
                });
            });
        },

        /**
         * Send e-mail to user to confirm password reset
         *
         * @param {String} email
         * @return {Promise}
         */
        $sendPasswordResetConfirmMail: function (email) {
            return new Promise(function (resolve, reject) {
                QUIAjax.post(
                    'ajax_users_authenticator_sendPasswordResetConfirmMail', resolve, {
                        email  : email,
                        onError: reject
                    }
                )
            });
        }
    });
});
