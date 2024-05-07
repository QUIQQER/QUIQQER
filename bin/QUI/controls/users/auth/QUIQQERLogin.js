/**
 * QUIQQER Authentication via email and password
 *
 * Includes password reset functionality
 *
 * @event showPassword [self]
 * @event showPasswordReset [self]
 *
 * @module controls/users/auth/QUIQQERLogin
 * @author Patrick Müller (www.pcsg.de)
 * @author Henning Leutz (www.pcsg.de)
 */
define('controls/users/auth/QUIQQERLogin', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'Locale',
    'Ajax'

], function(QUI, QUIControl, QUILoader, QUILocale, QUIAjax) {
    'use strict';

    var lg = 'quiqqer/core';

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
        initialize: function(options) {
            this.parent(options);

            this.Loader = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function() {
            this.Loader = new QUILoader().inject(this.getElm());
            this.$initPasswordReset();
        },

        /**
         * Initialize password reset functionality
         */
        $initPasswordReset: function() {
            var self = this,
                PasswordReset = this.getElm().getElement('.quiqqer-auth-login-passwordreset'),
                PasswordResetCancel = this.getElm().getElement(
                    '.quiqqer-auth-login-passwordreset [name="cancel"]'
                );

            if (!PasswordReset) {
                return;
            }

            var size = this.getElm().getSize();

            this.getElm().setStyles({
                height: size.y,
                width : '100%'
            });

            // events
            var PasswordResetLink = this.getElm().getElement(
                '.quiqqer-auth-login-passwordreset-link'
            );

            PasswordResetLink.addEvent('click', function(event) {
                event.stop();
                self.$showPasswordReset();
            });

            PasswordResetCancel.addEvent('click', function(event) {
                event.stop();
                self.$showPassword();
            });

            this.$initPasswordResetEvents();
        },

        /**
         * @return Promise
         */
        $showPasswordReset: function() {
            const Container = this.getElm(), PasswordContainer = Container.getElement('.quiqqer-auth-login-container'),
                PasswordReset = Container.getElement('.quiqqer-auth-login-passwordreset');

            if (!PasswordContainer) {
                return Promise.resolve();
            }

            Container.style.height = Container.offsetHeight + 'px';
            Container.style.overflow = 'hidden';

            this.fireEvent('showPasswordReset', [this]);

            return new Promise(function(resolve) {
                moofx(PasswordContainer).animate({
                    opacity: 0
                }, {
                    duration: 250,
                    callback: function() {
                        PasswordContainer.setStyle('display', 'none');

                        PasswordReset.setStyle('opacity', 0);
                        PasswordReset.setStyle('display', null);

                        moofx(Container).animate({
                            height: PasswordReset.getSize().y
                        }, {
                            duration: 250,
                            callback: function() {
                                PasswordReset.getElement('input[name="email"]').focus();
                                Container.style.overflow = null;
                                Container.style.height = null;
                                resolve();
                            }
                        });

                        moofx(PasswordReset).animate({
                            opacity: 1
                        }, {
                            duration: 250,
                            callback: function() {
//                                PasswordReset.getElement('input[name="email"]').focus();
//                                Container.style.overflow = null;
//                                Container.style.height = null;
//                                resolve();
                            }
                        });
                    }
                });
            });
        },

        /**
         * @return Promise
         */
        $showPassword: function() {
            const Container = this.getElm(),
                PasswordContainer = Container.getElement('.quiqqer-auth-login-container'),
                PasswordReset = Container.getElement('.quiqqer-auth-login-passwordreset');

            if (!PasswordContainer) {
                return Promise.resolve();
            }

            Container.style.height = Container.offsetHeight + 'px';
            Container.style.overflow = 'hidden';

            this.fireEvent('showPassword', [this]);

            return new Promise(function(resolve) {
                moofx(PasswordReset).animate({
                    opacity: 0
                }, {
                    duration: 250,
                    callback: function() {
                        PasswordReset.setStyle('display', 'none');

                        PasswordContainer.setStyle('opacity', 0);
                        PasswordContainer.setStyle('display', null);

                        moofx(Container).animate({
                            height: PasswordContainer.getSize().y
                        }, {
                            duration: 250,
                            callback: function() {
                                Container.style.height = null;
                            }
                        });

                        moofx(PasswordContainer).animate({
                            opacity: 1
                        }, {
                            duration: 250,
                            callback: function() {
                                PasswordContainer.getElement('input[name="email"]').focus();
                                Container.style.overflow = null;
                                Container.style.height = null;
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
        $initPasswordResetEvents: function() {
            var self = this,
                Elm = this.getElm(),
                EmailInput = Elm.getElement('input[name="email"]'),
                SubmitBtn = Elm.getElement('.quiqqer-auth-login-passwordreset button.reset-password'),
                MsgElm = Elm.getElement('.quiqqer-auth-login-message');

            var submit = function() {
                var email = EmailInput.value.trim();

                if (email === '') {
                    EmailInput.focus();
                    return;
                }

                self.Loader.show();

                SubmitBtn.disabled = true;
                MsgElm.set('html', '');

                var showHideMessage = function(Message) {
                    moofx(Message).animate({
                        opacity: 1,
                        top    : 0
                    }, {
                        duration: 200,
                        callback: function() {
                            (function() {
                                moofx(Message).animate({
                                    opacity: 0,
                                    top    : -20
                                }, {
                                    duration: 200,
                                    callback: function() {
                                        Message.destroy();
                                        self.$showPassword().then(function() {
                                            EmailInput.value = '';
                                            EmailInput.setStyle('display', null);
                                        });
                                    }
                                });
                            }).delay(4000);
                        }
                    });
                };

                self.$sendPasswordResetConfirmMail(email).then(function() {
                    self.Loader.hide();

                    var Message = new Element('div', {
                        html   : QUILocale.get(lg, 'controls.users.auth.quiqqerlogin.send_mail_success'),
                        'class': 'message-success',
                        styles : {
                            height  : '100%',
                            opacity : 0,
                            padding : 20,
                            position: 'absolute',
                            top     : -20,
                            width   : '100%'
                        }
                    }).inject(self.getElm());

                    showHideMessage(Message);
                    EmailInput.setStyle('display', 'none');
                    SubmitBtn.disabled = false;
                }, function(e) {
                    self.Loader.hide();

                    var Message = new Element('div', {
                        html   : QUILocale.get(lg, 'controls.users.auth.quiqqerlogin.send_mail_error', {
                            error: e.getMessage()
                        }),
                        'class': 'message-error',
                        styles : {
                            height  : '100%',
                            opacity : 0,
                            padding : 20,
                            position: 'absolute',
                            top     : -20,
                            width   : '100%'
                        }
                    }).inject(self.getElm());

                    showHideMessage(Message);

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

            SubmitBtn.addEvent('click', function(event) {
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
        $sendPasswordResetConfirmMail: function(email) {
            return new Promise(function(resolve, reject) {
                QUIAjax.post('ajax_users_authenticator_sendPasswordResetConfirmMail', resolve, {
                    email    : email,
                    onError  : reject,
                    showError: false
                });
            });
        }
    });
});
