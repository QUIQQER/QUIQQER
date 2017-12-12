/**
 * QUIQQER Authentication via email and password
 *
 * Includes password reset functionality
 *
 * @module controls/users/auth/QUIQQERLogin
 * @author Patrick Müller (www.pcsg.de)
 */
define('controls/users/auth/QUIQQERLogin', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'Locale',
    'Ajax'

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
            var self                = this,
                PasswordReset       = this.getElm().getElement('.quiqqer-auth-login-passwordreset'),

                PasswordResetCancel = this.getElm().getElement(
                    '.quiqqer-auth-login-passwordreset [name="cancel"]'
                );

            if (!PasswordReset) {
                return;
            }

            var size = this.getElm().getSize();

            this.getElm().setStyles({
                height: size.y,
                width : size.x
            });

            // events
            var PasswordResetLink = this.getElm().getElement(
                '.quiqqer-auth-login-passwordreset-link'
            );

            PasswordResetLink.addEvent('click', function (event) {
                event.stop();
                self.$showPasswordReset();
            });

            PasswordResetCancel.addEvent('click', function (event) {
                event.stop();
                self.$showPassword();
            });

            this.$initPasswordResetEvents();
        },

        /**
         * @return Promise
         */
        $showPasswordReset: function () {
            var self              = this,
                PasswordContainer = this.getElm().getElement('.quiqqer-auth-login-container'),
                PasswordReset     = this.getElm().getElement('.quiqqer-auth-login-passwordreset');

            if (!PasswordContainer) {
                return Promise.resolve();
            }

            PasswordContainer.setStyle('left', 0);
            PasswordContainer.setStyle('position', 'relative');

            return new Promise(function (resolve) {
                moofx(PasswordContainer).animate({
                    left   : -100,
                    opacity: 0
                }, {
                    duration: 250,
                    callback: function () {
                        PasswordContainer.setStyle('display', 'none');

                        PasswordReset.setStyle('opacity', 0);
                        PasswordReset.setStyle('display', 'inline');
                        PasswordReset.setStyle('left', -100);
                        PasswordReset.setStyle('position', 'absolute');

                        moofx(PasswordReset).animate({
                            left   : 0,
                            opacity: 1
                        }, {
                            duration: 250,
                            callback: function () {
                                self.getElm().getElement('input[name="email"]').focus();

                                resolve();
                            }
                        });
                    }
                });
            });
        },

        /**
         * @return Promise
         */
        $showPassword: function () {
            var PasswordContainer = this.getElm().getElement('.quiqqer-auth-login-container'),
                PasswordReset     = this.getElm().getElement('.quiqqer-auth-login-passwordreset');

            if (!PasswordContainer) {
                return Promise.resolve();
            }
            console.warn('$showPassword');
            return new Promise(function (resolve) {
                moofx(PasswordReset).animate({
                    left   : -100,
                    opacity: 0
                }, {
                    duration: 250,
                    callback: function () {
                        PasswordReset.setStyle('display', 'none');

                        PasswordContainer.setStyle('opacity', 0);
                        PasswordContainer.setStyle('display', 'inline');
                        PasswordContainer.setStyle('left', -100);

                        moofx(PasswordContainer).animate({
                            left   : 0,
                            opacity: 1
                        }, {
                            duration: 250,
                            callback: function () {
                                resolve();
                            }
                        });
                    }
                });
            });
        },

        /**
         * Init password reset events
         */
        $initPasswordResetEvents: function () {
            var self       = this,
                Elm        = this.getElm(),
                EmailInput = Elm.getElement('input[name="email"]'),
                SubmitBtn  = Elm.getElement('.quiqqer-auth-login-passwordreset input[type="submit"]'),
                MsgElm     = Elm.getElement('.quiqqer-auth-login-message');

            var submit = function() {
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

                    QUI.getMessageHandler().then(function (MH) {
                        MH.setAttribute('displayTimeMessages', 5000);
                        MH.addSuccess(
                            QUILocale.get(lg, 'controls.users.auth.quiqqerlogin.send_mail_success'),
                            Elm
                        );
                    });

                    self.$showPassword();
                }, function (e) {
                    self.Loader.hide();

                    QUI.getMessageHandler().then(function (MH) {
                        MH.setAttribute('displayTimeMessages', 5000);
                        MH.addAttention(
                            QUILocale.get(lg, 'controls.users.auth.quiqqerlogin.send_mail_error', {
                                error: e.getMessage()
                            }),
                            EmailInput
                        );
                    });

                    SubmitBtn.disabled = false;
                });
            };

            EmailInput.addEvent('keydown', function(event) {
                // stop login-form submit on enter
                if (event.code === 13) {
                    event.stop();
                    submit();
                }
            });

            SubmitBtn.addEvent('click', function (event) {
                event.stop();
                submit();
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
                );
            });
        }
    });
});
