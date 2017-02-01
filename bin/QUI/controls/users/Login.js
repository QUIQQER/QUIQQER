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
            this.$Form = null;

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
            this.$Form = this.getElm().getElement('form');

            if (this.getElm().get('data-onsuccess')) {
                this.setAttribute('onSuccess', this.getElm().get('data-onsuccess'));
            }

            this.$refreshForm();
        },

        /**
         * Refresh the form data and set events to the current form
         */
        $refreshForm: function () {
            this.$Form.set({
                action: '',
                method: 'POST',
                events: {
                    submit: function (event) {
                        console.warn(11);
                        event.stop();
                        this.auth().catch(function () {
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
                Form = Container.getElement('form'),

                children = elements.filter(function (Node) {
                    return !Node.get('data-qui');
                });

            if (!Form) {
                QUIAjax.post('ajax_user_logout', function () {
                    window.location.reload();
                });
                return;
            }

            Form.setStyle('opacity', 0);
            Form.inject(this.getElm());

            this.$Form = Form;
            this.$refreshForm();

            children.each(function (Child) {
                Child.inject(Form);
            });

            QUI.parse(Form).then(function () {
                return this.Loader.hide()
            }.bind(this)).then(function () {
                Form.setStyle('top', 20);
                moofx(Form).animate({
                    opacity: 1,
                    top: 0
                }, {
                    duration: 200,
                    callback: function () {
                        Form.elements[0].focus();
                    }
                });
            });
        },

        /**
         * Execute the current authentication
         */
        auth: function () {
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

                    moofx(self.$Form).animate({
                        top: 20,
                        opacity: 0
                    }, {
                        duration: 250,
                        callback: function () {
                            self.$Form.destroy();
                            self.$buildAuthenticator(result.control);
                        }
                    });
                }, {
                    showLogin: false,
                    authenticator: self.$Form.get('data-authenticator'),
                    params: JSON.encode(
                        QUIFormUtils.getFormData(self.$Form)
                    ),
                    onError: function () {
                        self.Loader.hide();
                        reject();
                    }
                });
            });
        }
    });
});
