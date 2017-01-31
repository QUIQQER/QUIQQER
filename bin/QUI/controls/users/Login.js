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
    'Ajax'

], function (QUI, QUIControl, QUILoader, QUIFormUtils, QUIAjax) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type: 'controls/users/Login',

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

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            this.Loader = new QUILoader().inject(this.getElm());

            this.getElm().set({
                action: '',
                method: 'POST',
                events: {
                    submit: function (event) {
                        event.stop();
                        this.auth().catch(function () {
                        });
                    }.bind(this)
                }
            });

            var onSuccess = this.getElm().get('data-onsuccess');

            if (typeof window[onSuccess] === 'function') {
                this.setAttribute('onSuccess', window[onSuccess]);
            }
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

                    // show next
                    console.log(result);

                    self.fireEvent('authNext', [self]);
                    resolve();
                }, {
                    authenticator: self.getElm().get('data-authenticator'),
                    params: JSON.encode(
                        QUIFormUtils.getFormData(self.getElm())
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
