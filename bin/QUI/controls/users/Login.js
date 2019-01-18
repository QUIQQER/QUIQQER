/**
 * @module controls/users/Login
 *
 * @event onLoad
 * @event onAuthBegin
 * @event onAuthNext
 * @event onSuccess
 * @event onAuthNext
 * @event onUserLoginError [error, this]
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
            '$refreshForm'
        ],

        options: {
            onSuccess     : false, //custom callback function
            showLoader    : true,
            authenticators: []  // fixed list of authenticators shown
        },

        /**
         * construct
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

            return new Promise(function (resolve, reject) {
                QUIAjax.post('ajax_users_login', function (result) {
                    // authentication was successful
                    if (!result.authenticator) {
                        window.QUIQQER_USER = result.user;

                        self.fireEvent('success', [self]);
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
                        self.fireEvent('authNext', [this]);

                        reject(e);
                    }
                });
            });
        }
    });
});
