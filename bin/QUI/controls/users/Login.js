/**
 * @module controls/users/Login
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/loader/Loader
 * @require qui/utils/Form
 * @require Ajax
 *
 * @event onAuthBegin
 * @event onAuthNext
 * @event onSuccess
 * @event onAuthNext
 */
define('controls/users/Login', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'qui/utils/Form',
    'Ajax',

    'css!controls/users/Login.css'

], function (QUI, QUIControl, QUILoader, QUIFormUtils, QUIAjax) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type: 'controls/users/Login',

        Binds: [
            '$onImport',
            '$onInject',
            '$refreshForm'
        ],

        options: {
            onSuccess: false //custom callback function
        },

        /**
         * construct
         * @param {Object} options
         */
        initialize: function (options) {
            this.parent(options);

            this.Loader = null;
            this.$forms = [];

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
         * event : on inject
         */
        $onInject: function () {
            this.Loader.show();

            QUIAjax.get('ajax_users_loginControl', function (result) {
                this.$buildAuthenticator(result);
            }.bind(this));
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
        },

        /**
         * Refresh the form data and set events to the current form
         */
        $refreshForm: function () {
            this.$forms.set({
                action: '',
                method: 'POST',
                events: {
                    submit: function (event) {
                        var Target = null;

                        if (typeOf(event) === 'element') {
                            Target = event;
                        }
                        if (typeOf(event) == 'domevent') {
                            event.stop();
                            Target = event.target;
                        }

                        if (!Target) {
                            console.error('No target given.');
                            return;
                        }

                        this.auth(Target).catch(function () {
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
         */
        $buildAuthenticator: function (html) {
            var Container = new Element('div', {
                html: html
            });

            var elements = Container.getChildren(),
                forms = Container.getElements('form'),

                children = elements.filter(function (Node) {
                    return !Node.get('data-qui');
                });

            if (!forms.length) {
                QUIAjax.post('ajax_user_logout', function () {
                    window.location.reload();
                });
                return;
            }

            forms.setStyle('opacity', 0);
            forms.inject(this.getElm());

            for (var i = 1, len = forms.length; i < len; i++) {
                new Element('div', {
                    'class': 'quiqqer-login-or',
                    html: '<span>or</span>'
                }).inject(forms[i], 'before');
            }

            this.$forms = forms;
            this.$refreshForm();

            children.each(function (Child) {
                Child.inject(forms[0]);
            });

            QUI.parse(forms).then(function () {
                return this.Loader.hide()
            }.bind(this)).then(function () {
                forms.setStyle('top', 20);

                moofx(forms).animate({
                    opacity: 1,
                    top: 0
                }, {
                    duration: 200,
                    callback: function () {
                        forms[0].elements[0].focus();
                    }
                });
            });
        },

        /**
         * Execute the current authentication
         */
        auth: function (Form) {
            var self = this;

            this.Loader.show();
            this.fireEvent('authBegin', [this]);

            return new Promise(function (resolve, reject) {
                QUIAjax.post('ajax_users_login', function (result) {
                    // authentication was successful
                    if (!result.authenticator) {
                        self.fireEvent('success');
                        resolve();

                        if (typeof self.getAttribute('onSuccess') === 'function') {
                            self.getAttribute('onSuccess')();
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
                        top: 20,
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
                    showLogin: false,
                    authenticator: Form.get('data-authenticator'),
                    globalauth: !!Form.get('data-globalauth') ? 1 : 0,
                    params: JSON.encode(
                        QUIFormUtils.getFormData(Form)
                    ),
                    onError: function () {
                        self.Loader.hide();
                        self.fireEvent('authNext', [this]);
                        reject();
                    }
                });
            });
        }
    });
});
