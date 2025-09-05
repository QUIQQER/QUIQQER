/**
 * @module controls/users/Login
 *
 * @event onLoad [this]
 * @event onAuthBegin [this]
 * @event onAuthNext [this]
 * @event onSuccess [this]
 * @event onUserLoginError [error, this]
 * @event onBuildAuthenticator [this]
 *
 * @event onQuiqqerUserAuthLoginLoad [this]
 * @event onQuiqqerUserAuthLoginUserLoginError [error, this]
 * @event onQuiqqerUserAuthLoginAuthBegin [this]
 * @event onQuiqqerUserAuthLoginSuccess [this]
 * @event onQuiqqerUserAuthNext [this]
 * @event onQuiqqerUserAuthLoginBuildAuthenticator [this]
 */
define('controls/users/Login', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'qui/utils/Form',
    'Ajax',
    'Locale',

    'css!controls/users/Login.css'

], function (QUI, QUIControl, QUILoader, QUIFormUtils, QUIAjax, QUILocale) {
    'use strict';

    let onInjectIsRunning = false;

    return new Class({

        Extends: QUIControl,
        Type: 'controls/users/Login',

        Binds: [
            '$onImport',
            '$onInject',
            '$refreshForm',
            '$onShowPassword',
            'onShowPasswordReset'
        ],

        options: {
            onSuccess: false, //custom callback function
            showLoader: true,
            authenticators: []  // fixed list of authenticators shown
        },

        /**
         * construct
         *
         * @param {Object} options
         */
        initialize: function (options) {
            this.parent(options);

            this.Loader = null;
            this.$forms = [];
            this.$loaded = false;
            this.$authStep = 'primary'; // primary || secondary

            this.addEvents({
                onImport: this.$onImport,
                onInject: this.$onInject
            });
        },

        /**
         * Create the dom-node
         *
         * @returns {HTMLDivElement}
         */
        create: function () {
            this.$Elm = this.parent();
            this.$Elm.addClass('quiqqer-login');

            this.Loader = new QUILoader({
                'type': 'fa-circle-o-notch'
            }).inject(this.getElm());

            return this.$Elm;
        },

        /**
         * Refresh the display
         */
        refresh: function () {
            this.$Elm.set('html', '');
            this.$onInject();
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            if (onInjectIsRunning) {
                return;
            }

            onInjectIsRunning = true;

            if (this.getAttribute('showLoader')) {
                this.Loader.show();
            }

            QUIAjax.get('ajax_users_loginControl', (result) => {
                this.$authStep = result.authStep;

                this.$buildAuthenticator(result.control).then(() => {
                    onInjectIsRunning = false;

                    this.fireEvent('load', [this]);
                    QUI.fireEvent('quiqqerUserAuthLoginLoad', [this]);

                    moofx(
                        this.getElm().querySelector('[data-name="quiqqer-users-login-container"]')
                    ).animate({
                        opacity: 1
                    }, {
                        duration: 250
                    });
                });
            }, {
                isAdminLogin: typeof QUIQQER_IS_ADMIN_LOGIN !== 'undefined' ? 1 : 0,
                authenticators: JSON.encode(this.getAttribute('authenticators'))
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            this.Loader = new QUILoader().inject(this.getElm());
            this.$forms = this.getElm().getElements('form');

            if (this.getElm().get('data-onsuccess')) {
                this.setAttribute('onSuccess', this.getElm().get('data-onsuccess'));
            }

            this.$refreshForm();
            this.fireEvent('load', [this]);
            QUI.fireEvent('quiqqerUserAuthLoginLoad', [this]);

            moofx(
                this.getElm().querySelector('[data-name="quiqqer-users-login-container"]')
            ).animate({
                opacity: 1
            }, {
                duration: 250
            });
        },

        /**
         * Refresh the form data and set events to the current form
         */
        $refreshForm: function () {
            const onSubmit = (e) => {
                e.stopPropagation();
                e.preventDefault();

                this.auth(e.target).catch((err) => {
                    this.fireEvent('userLoginError', [err, this]);
                    QUI.fireEvent('quiqqerUserAuthLoginUserLoginError', [err, this]);
                });
            }

            Array.from(this.$forms).forEach((form) => {
                form.action = '';
                form.method = 'POST';
                form.addEventListener('submit', onSubmit);
            });

            const onSuccess = this.getAttribute('onSuccess');

            if (typeof window[onSuccess] === 'function') {
                this.setAttribute('onSuccess', window[onSuccess]);
            }
        },

        /**
         * Build the authenticator from the ajax html
         *
         * @param {String} html
         * @return {Promise}
         */
        $buildAuthenticator: function (html) {
            const container = this.getElm();
            const ghost = document.createElement('div');
            ghost.innerHTML = html;

            container.style.overflow = 'hidden';

            let socialLoginContainer = container.querySelector('[data-name="social-logins"]');
            let mailLoginContainer = container.querySelector('[data-name="mail-logins"]');

            if (!socialLoginContainer && !mailLoginContainer) {
                container.innerHTML = html;

                socialLoginContainer = container.querySelector('[data-name="social-logins"]');
                mailLoginContainer = container.querySelector('[data-name="mail-logins"]');
            } else {
                Array.from(
                    ghost.querySelectorAll('style')
                ).forEach((style) => {
                    container.appendChild(style);
                });
            }

            // social logins
            if (socialLoginContainer) {
                socialLoginContainer.innerHTML = '';

                Array.from(
                    ghost.querySelectorAll('[data-name="social-logins"] form')
                ).forEach((form) => {
                    form.style.opacity = 0;
                    socialLoginContainer.appendChild(form);
                });
            }

            // mail logins
            if (mailLoginContainer) {
                mailLoginContainer.innerHTML = '';

                Array.from(
                    ghost.querySelectorAll('[data-name="mail-logins"] form')
                ).forEach((form) => {
                    form.style.opacity = 0;
                    mailLoginContainer.appendChild(form);
                });
            }

            this.$forms = container.querySelectorAll('form');
            this.$refreshForm();

            Array.from(this.$forms).forEach((form) => {
                QUI.parse(form);
            });

            this.Loader.hide();

            const Node = container.querySelector('[data-qui="controls/users/auth/QUIQQERLogin"]');

            if (Node && Node.getAttribute('data-quiid')) {
                const Control = QUI.Controls.getById(Node.getAttribute('data-quiid'));

                if (Control) {
                    Control.addEvents({
                        onShowPassword: this.$onShowPassword,
                        onShowPasswordReset: this.onShowPasswordReset
                    });
                }
            }

            return new Promise((resolve) => {
                container.style.overflow = '';

                if (!this.$forms || !this.$forms.length) {
                    resolve();
                    return;
                }

                moofx(this.$forms).animate({
                    opacity: 1
                }, {
                    duration: 500,
                    callback: () => {
                        if (typeof this.$forms[0].elements[0] !== 'undefined') {
                            this.$forms[0].elements[0].focus();
                        }

                        resolve();
                        this.fireEvent('buildAuthenticator', [this]);
                        QUI.fireEvent('quiqqerUserAuthLoginBuildAuthenticator', [this]);
                    }
                });
            });
        },

        /**
         * Execute the current authentication
         */
        auth: function (Form) {
            if (this.getAttribute('showLoader')) {
                this.Loader.show();
            }

            this.fireEvent('authBegin', [this]);
            QUI.fireEvent('quiqqerUserAuthLoginAuthBegin', [this]);

            return new Promise((resolve, reject) => {
                QUIAjax.post('ajax_users_login', (result) => {
                    this.$authStep = result.authStep;

                    // authentication was successful
                    if (!result.authenticator || !result.authenticator.length) {
                        window.QUIQQER_USER = result.user;

                        this.fireEvent('success', [this]);
                        QUI.fireEvent('quiqqerUserAuthLoginSuccess', [this, Form.get('data-authenticator')]);
                        resolve(this);

                        if (typeof this.getAttribute('onSuccess') === 'function') {
                            this.getAttribute('onSuccess')(this);
                            return;
                        }

                        window.location.reload();
                        return;
                    }

                    moofx(this.$forms).animate({
                        opacity: 0
                    }, {
                        duration: 250,
                        callback: () => {
                            Array.from(this.$forms).forEach((form) => {
                                form.parentNode.removeChild(form);
                            });

                            this.$buildAuthenticator(result.control);
                        }
                    });
                }, {
                    showLogin: false,
                    authenticator: Form.get('data-authenticator'),
                    authStep: this.$authStep,
                    params: JSON.encode(
                        QUIFormUtils.getFormData(Form)
                    ),
                    onError: (e) => {
                        if (e.getAttribute('type') === 'QUI\\Users\\Auth\\Exception2FA') {
                            this.$authStep = 'secondary';
                            this.auth();
                            reject(e);
                            return;
                        }

                        this.Loader.hide();
                        this.fireEvent('authNext', [this]);
                        QUI.fireEvent('quiqqerUserAuthNext', [this]);

                        reject(e);
                    }
                });
            });
        },

        /**
         * event: on show password
         */
        $onShowPassword: function () {
            let i, len, height, Form, Rule;
            let rule = this.getElm().getElements('.quiqqer-login-or');
            let forms = this.getElm().getElements('form').filter(function (form) {
                return form.get('data-authenticator') !== 'QUI\\Users\\Auth\\QUIQQER';
            });

            rule.setStyle('display', null);

            const done = function () {
                this.setStyle('overflow', null);
                this.setStyle('height', null);
            };

            for (i = 0, len = rule.length; i < len; i++) {
                Rule = rule[i];
                height = Rule.measure(function () {
                    return this.getSize();
                }).y;

                Rule.setStyle('overflow', null);

                moofx(Rule).animate({
                    height: height,
                    opacity: 1
                }, {
                    duration: 250,
                    callback: done.bind(Rule)
                });
            }

            for (i = 0, len = forms.length; i < len; i++) {
                Form = forms[i];

                Form.setStyle('position', 'absolute');
                Form.setStyle('visibility', 'hidden');
                Form.setStyle('display', null);
                Form.setStyle('height', null);

                height = Form.getSize().y;

                Form.setStyle('height', 0);
                Form.setStyle('position', null);
                Form.setStyle('visibility', null);

                moofx(Form).animate({
                    height: height,
                    opacity: 1
                }, {
                    duration: 250,
                    callback: done.bind(Form)
                });
            }
        },

        /**
         * event: on show password reset
         */
        onShowPasswordReset: function () {
            let rule = this.getElm().getElements('.quiqqer-login-or');
            let forms = this.getElm().getElements('form').filter(function (form) {
                return form.get('data-authenticator') !== 'QUI\\Users\\Auth\\QUIQQER';
            });

            rule.setStyle('overflow', 'hidden');
            forms.setStyle('overflow', 'hidden');

            if (rule.length) {
                moofx(rule).animate({
                    height: 0,
                    opacity: 0
                }, {
                    callback: function () {
                        rule.setStyle('display', 'none');
                    }
                });
            }

            if (forms.length) {
                moofx(forms).animate({
                    height: 0,
                    opacity: 0
                }, {
                    callback: function () {
                        forms.setStyle('display', 'none');
                    }
                });
            }
        }
    });
});
