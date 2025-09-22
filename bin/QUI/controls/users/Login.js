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
            'onShowPasswordReset',
            '$clickShowControl',
            '$clickBackToAuthList'
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

            this.getLoginControl().then((responseData) => {
                return this.$handleLoginResponse(responseData);
            }).then((responseData) => {
                return this.$buildAuthenticator(responseData.control);
            }).then(() => {
                onInjectIsRunning = false;

                this.fireEvent('load', [this]);
                QUI.fireEvent('quiqqerUserAuthLoginLoad', [this]);

                const container = this.getElm().querySelector(
                    '[data-name="quiqqer-users-login-container"]'
                );

                if (container) {
                    moofx(container).animate({
                        opacity: 1
                    }, {
                        duration: 250
                    });
                }
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

        $handleLoginResponse: function (responseData) {
            this.$authStep = responseData.authStep;

            let response = Promise.resolve(responseData);
            let authenticators = null;
            let secondaryType = responseData.secondaryLoginType;

            if (responseData.authStep === 'secondary') {
                const dividers = Array.from(
                    this.getElm().querySelectorAll('[data-name="or-divider"]')
                );

                dividers.forEach((node) => {
                    node.style.display = 'none';
                });
            }

            if (
                typeof responseData.loggedIn !== 'undefined'
                && responseData.loggedIn
            ) {
                if (secondaryType !== 2) {
                    return response;
                }

                // 2fa info, because user can activate it but don't have to
                return this.$show2FAInfo().then((closeType) => {
                    if (closeType === 'hasSeen2faInformation') {
                        return responseData;
                    }

                    if (closeType === 'cancel') {
                        return [];
                    }

                    return responseData;
                });
            }

            if (
                typeof responseData.authenticator !== 'undefined'
                && responseData.authenticator
                && responseData.authenticator.length
            ) {
                authenticators = responseData.authenticator;
            }

            if (
                responseData.authStep === 'primary'
                && !authenticators
                && secondaryType !== 0
            ) {
                // 2fa + no authenticators and primary step, smth is wrong
                // kill the session and start again
                return this.destroySession().then(() => {
                    return this.getLoginControl();
                });
            }

            if (responseData.authStep === 'primary' && !authenticators) {
                // no 2fa + no authenticators and primary step, login is ok
                return this.isAuth().then((userData) => {
                    if (userData.type === 'QUI\\Users\\Nobody') {
                        return this.destroySession().then(() => {
                            return this.getLoginControl();
                        });
                    }

                    return responseData;
                });
            }

            if (
                secondaryType === 1
                && responseData.authStep === 'secondary'
                && !authenticators
            ) {
                // 2fa is a must
                return this.$show2FAEnable().then(() => {
                    return this.getLoginControl();
                });
            }

            return response.then(() => {
                return responseData;
            });
        },

        $show2FAEnable: function () {
            this.Loader.show();

            return new Promise((resolve) => {
                require([
                    'controls/users/auth/EnableSecondaryAuthenticatorWindow'
                ], (EnableSecondaryAuthenticatorWindow) => {
                    new EnableSecondaryAuthenticatorWindow({
                        events: {
                            onCompleted: () => {
                                this.Loader.hide();
                                resolve();
                            }
                        }
                    }).open();
                });
            });
        },

        $show2FAInfo: function () {
            this.Loader.show();

            return new Promise((resolve) => {
                QUIAjax.get('ajax_user_getHasSeen2faInformation', (hasSeen) => {
                    if (hasSeen) {
                        resolve('hasSeen2faInformation');
                        return;
                    }

                    require([
                        'controls/users/auth/ShowSecondaryAuthenticatorWindow'
                    ], (ShowSecondaryAuthenticatorWindow) => {
                        new ShowSecondaryAuthenticatorWindow({
                            events: {
                                onCompleted: () => {
                                    this.Loader.hide();
                                    resolve('completed');
                                },
                                onCancel: () => {
                                    this.Loader.hide();
                                    resolve('cancel');
                                }
                            }
                        }).open();
                    });
                });
            });
        },

        getLoginControl: function () {
            return new Promise((resolve, reject) => {
                QUIAjax.get('ajax_users_loginControl', resolve, {
                    isAdminLogin: typeof QUIQQER_IS_ADMIN_LOGIN !== 'undefined' ? 1 : 0,
                    authenticators: JSON.encode(this.getAttribute('authenticators')),
                    onError: reject
                });
            });
        },

        destroySession: function () {
            return new Promise((resolve, reject) => {
                QUIAjax.get('ajax_session_destroy', resolve, {
                    onError: reject
                });
            });
        },

        isAuth: function () {
            return new Promise((resolve, reject) => {
                QUIAjax.get('ajax_isAuth', resolve, {
                    onError: reject
                });
            });
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
                const loginNode = ghost.querySelector('[data-qui="controls/users/Login"]');

                if (loginNode) {
                    container.innerHTML = loginNode.innerHTML;
                    container.setAttribute('data-qui', loginNode.getAttribute('data-qui'));

                    loginNode.classList.forEach((cls) => {
                        container.classList.add(cls);
                    });
                } else {
                    container.innerHTML = ghost.innerHTML;
                }

                socialLoginContainer = container.querySelector('[data-name="social-logins"]');
                mailLoginContainer = container.querySelector('[data-name="mail-logins"]');

                Array.from(
                    ghost.querySelectorAll('style')
                ).forEach((style) => {
                    container.appendChild(style);
                });
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
                    form.style.display = 'none';
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
                    form.style.display = 'none';
                    mailLoginContainer.appendChild(form);
                });
            }

            this.$forms = container.querySelectorAll('form');
            this.$refreshForm();

            if (this.$authStep === 'primary') {
                Array.from(this.$forms).forEach((form) => {
                    form.style.display = '';
                    form.querySelector('button[name="show-control"]').style.display = 'none';
                });

                Array.from(this.$forms).forEach((form) => {
                    QUI.parse(form);
                });
            }

            if (this.$authStep === 'secondary') {
                if (this.$forms.length >= 2) {
                    // show buttons
                    Array.from(this.$forms).forEach((form) => {
                        const button = form.querySelector('button[name="show-control"]');

                        form.style.display = '';
                        button.style.display = '';
                        form.querySelector('[data-name="quiqqer-users-login-social-control"]').style.display = 'none';

                        button.addEventListener('click', this.$clickShowControl);
                    });

                    container.querySelector('[data-name="mail-logins"]').style.marginTop = '0.5rem';
                } else {
                    Array.from(this.$forms).forEach((form) => {
                        form.style.display = '';
                        form.querySelector('button[name="show-control"]').style.display = 'none';
                    });

                    Array.from(this.$forms).forEach((form) => {
                        QUI.parse(form);
                    });
                }
            }

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
                QUIAjax.post('ajax_users_login', resolve, {
                    showLogin: false,
                    authenticator: Form.get('data-authenticator'),
                    authStep: this.$authStep,
                    params: JSON.encode(
                        QUIFormUtils.getFormData(Form)
                    ),
                    onError: (e) => {
                        if (e.getAttribute('type') === 'QUI\\Users\\Auth\\Exception2FA') {
                            this.$authStep = 'secondary';
                            this.auth(Form);
                            reject(e);
                            return;
                        }

                        this.Loader.hide();
                        this.fireEvent('authNext', [this]);
                        QUI.fireEvent('quiqqerUserAuthNext', [this]);

                        reject(e);
                    }
                });
            }).then((responseData) => {
                return this.$handleLoginResponse(responseData)
            }).then((responseData) => {
                // authentication was successful
                if (!responseData.authenticator || !responseData.authenticator.length) {
                    window.QUIQQER_USER = responseData.user;

                    this.fireEvent('success', [this]);
                    QUI.fireEvent('quiqqerUserAuthLoginSuccess', [this, Form.get('data-authenticator')]);

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

                        this.$buildAuthenticator(responseData.control);
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
        },

        $clickShowControl: function (e) {
            e.stopPropagation();
            e.preventDefault();

            const targetForm = e.target.closest('form');
            const container = targetForm.closest('[data-name="quiqqer-users-login-container"]');
            const forms = container.querySelectorAll('form');

            Array.from(forms).forEach((form) => {
                const button = form.querySelector('button[name="show-control"]');

                moofx(button).animate({
                    opacity: 0
                }, {
                    duration: 250,
                    callback: () => {
                        button.style.display = 'none';
                    }
                });
            });

            setTimeout(() => {
                const controlContainer = targetForm.querySelector('[data-name="quiqqer-users-login-social-control"]');

                controlContainer.style.display = 'block';
                QUI.parse(controlContainer).then(() => {
                    const back = document.createElement('button');
                    back.innerHTML = 'Zurück';
                    back.classList.add('btn', 'btn-secondary');
                    back.name = 'back-to-auth-list';
                    back.addEventListener('click', this.$clickBackToAuthList)

                    this.getElm().appendChild(back);
                });
            }, 300);
        },

        $clickBackToAuthList: function (e) {
            e.preventDefault();
            e.stopPropagation();

            const container = this.getElm();
            const controlContainer = container.querySelectorAll('[data-name="quiqqer-users-login-social-control"]');
            const backButton = container.querySelector('[name="back-to-auth-list"]');
            const controls = container.querySelectorAll('[name="show-control"]');

            Array.from(controlContainer).forEach((node) => {
                node.style.display = 'none';
            });

            if (backButton) {
                backButton.parentNode.removeChild(backButton);
            }

            Array.from(controls).forEach((control) => {
                control.style.opacity = 0;
                control.style.display = 'block';

                moofx(control).animate({
                    opacity: 1
                })
            });
        }
    });
});
