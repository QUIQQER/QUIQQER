/**
 * @module controls/users/Login
 *
 * @event onLoad [self]
 * @event onAuthBegin [self]
 * @event onAuthNext [self]
 * @event onSuccess [self]
 * @event onUserLoginError [error, self]
 *
 * @event onQuiqqerUserAuthLoginLoad [self]
 * @event onQuiqqerUserAuthLoginUserLoginError [error, self]
 * @event onQuiqqerUserAuthLoginAuthBegin [self]
 * @event onQuiqqerUserAuthLoginSuccess [self]
 * @event onQuiqqerUserAuthNext [self]
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
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/users/Login',

        Binds: [
            '$onImport',
            '$onInject',
            '$refreshForm',
            '$onShowPassword',
            'onShowPasswordReset'
        ],

        options: {
            onSuccess     : false, //custom callback function
            showLoader    : true,
            authenticators: []  // fixed list of authenticators shown
        },

        /**
         * construct
         *
         * @param {Object} options
         */
        initialize: function (options) {
            this.parent(options);

            this.Loader  = null;
            this.$forms  = [];
            this.$loaded = false;

            this.addEvents({
                onImport: this.$onImport,
                onInject: this.$onInject
            });
        },

        /**
         * Create the domnode
         *
         * @returns {HTMLDivElement}
         */
        create: function () {
            this.$Elm = this.parent();
            this.$Elm.addClass('quiqqer-login');

            this.Loader = new QUILoader().inject(this.getElm());

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
            if (this.getAttribute('showLoader')) {
                this.Loader.show();
            }

            var self = this;

            QUIAjax.get('ajax_users_loginControl', function (result) {
                self.$buildAuthenticator(result).then(function () {
                    self.fireEvent('load', [self]);
                    QUI.fireEvent('quiqqerUserAuthLoginLoad', [self]);
                });
            }, {
                isAdminLogin  : typeof QUIQQER_IS_ADMIN_LOGIN !== 'undefined' ? 1 : 0,
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
        },

        /**
         * Refresh the form data and set events to the current form
         */
        $refreshForm: function () {
            var self = this;

            this.$forms.set({
                action: '',
                method: 'POST',
                events: {
                    submit: function (event) {
                        var Target = null;

                        if (typeOf(event) === 'element') {
                            Target = event;
                        }

                        if (typeOf(event) === 'domevent') {
                            event.stop();
                            Target = event.target;
                        }

                        if (!Target) {
                            console.error('No target given.');
                            return;
                        }

                        this.auth(Target).catch(function (err) {
                            self.fireEvent('userLoginError', [err, self]);
                            QUI.fireEvent('quiqqerUserAuthLoginUserLoginError', [err, self]);
                        });
                    }.bind(this)
                }
            });

            var onSuccess = this.getAttribute('onSuccess');

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
            var Container = new Element('div', {
                html: html
            });

            var elements = Container.getChildren(),
                forms    = Container.getElements('form'),

                children = elements.filter(function (Node) {
                    return !Node.get('data-qui');
                });

            if (!forms.length) {
                QUIAjax.post('ajax_user_logout', function () {
                    window.location.reload();
                });

                return Promise.resolve();
            }

            forms.setStyle('opacity', 0);
            forms.inject(this.getElm());

            for (var i = 1, len = forms.length; i < len; i++) {
                new Element('div', {
                    'class': 'quiqqer-login-or',
                    html   : '<span>' + QUILocale.get('quiqqer/system', 'controls.users.auth.login.or') + '</span>'
                }).inject(forms[i], 'before');
            }

            this.$forms = forms;
            this.$refreshForm();

            children.each(function (Child) {
                Child.inject(forms[0]);
            });

            return QUI.parse(forms).then(function () {
                this.Loader.hide();

                var Node = this.getElm().getElement('[data-qui="controls/users/auth/QUIQQERLogin"]');

                if (Node && Node.get('data-quiid')) {
                    var Control = QUI.Controls.getById(Node.get('data-quiid'));

                    if (Control) {
                        Control.addEvents({
                            onShowPassword     : this.$onShowPassword,
                            onShowPasswordReset: this.onShowPasswordReset
                        });
                    }
                }

                forms.setStyle('top', 20);

                moofx(forms).animate({
                    opacity: 1,
                    top    : 0
                }, {
                    duration: 200,
                    callback: function () {
                        forms[0].elements[0].focus();
                    }
                });
            }.bind(this));
        },

        /**
         * Execute the current authentication
         */
        auth: function (Form) {
            var self = this;

            if (this.getAttribute('showLoader')) {
                this.Loader.show();
            }

            this.fireEvent('authBegin', [this]);
            QUI.fireEvent('quiqqerUserAuthLoginAuthBegin', [this]);

            return new Promise(function (resolve, reject) {
                QUIAjax.post('ajax_users_login', function (result) {
                    // authentication was successful
                    if (!result.authenticator) {
                        window.QUIQQER_USER = result.user;

                        self.fireEvent('success', [self]);
                        QUI.fireEvent('quiqqerUserAuthLoginSuccess', [self]);
                        resolve(self);

                        if (typeof self.getAttribute('onSuccess') === 'function') {
                            self.getAttribute('onSuccess')(self);
                            return;
                        }

                        window.location.reload();
                        return;
                    }

                    var Or = self.getElm().getElements('.quiqqer-login-or');

                    if (Or.length) {
                        moofx(Or).animate({
                            opacity: 0
                        }, {
                            duration: 200
                        });
                    }

                    moofx(self.$forms).animate({
                        top    : 20,
                        opacity: 0
                    }, {
                        duration: 250,
                        callback: function () {
                            if (Or.length) {
                                Or.destroy();
                            }

                            self.$forms.destroy();
                            self.$buildAuthenticator(result.control);
                        }
                    });
                }, {
                    showLogin    : false,
                    authenticator: Form.get('data-authenticator'),
                    globalauth   : !!Form.get('data-globalauth') ? 1 : 0,
                    params       : JSON.encode(
                        QUIFormUtils.getFormData(Form)
                    ),
                    onError      : function (e) {
                        self.Loader.hide();
                        self.fireEvent('authNext', [self]);
                        QUI.fireEvent('quiqqerUserAuthNext', [self]);

                        reject(e);
                    }
                });
            });
        },

        /**
         * event: on show password
         */
        $onShowPassword: function () {
            var i, len, height, Form, Rule;
            var rule  = this.getElm().getElements('.quiqqer-login-or');
            var forms = this.getElm().getElements('form').filter(function (form) {
                return form.get('data-authenticator') !== 'QUI\\Users\\Auth\\QUIQQER';
            });

            rule.setStyle('display', null);

            var done = function () {
                this.setStyle('overflow', null);
                this.setStyle('height', null);
            };

            for (i = 0, len = rule.length; i < len; i++) {
                Rule   = rule[i];
                height = Rule.measure(function () {
                    return this.getSize();
                }).y;

                Rule.setStyle('overflow', null);

                moofx(Rule).animate({
                    height : height,
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
                    height : height,
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
            var rule  = this.getElm().getElements('.quiqqer-login-or');
            var forms = this.getElm().getElements('form').filter(function (form) {
                return form.get('data-authenticator') !== 'QUI\\Users\\Auth\\QUIQQER';
            });

            rule.setStyle('overflow', 'hidden');
            forms.setStyle('overflow', 'hidden');

            if (rule.length) {
                moofx(rule).animate({
                    height : 0,
                    opacity: 0
                }, {
                    callback: function () {
                        rule.setStyle('display', 'none');
                    }
                });
            }

            if (forms.length) {
                moofx(forms).animate({
                    height : 0,
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
