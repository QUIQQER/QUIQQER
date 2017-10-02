/**
 * Login Window
 *
 * @module controls/users/LoginWindow
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onSubmit [ Array, this ]
 * @event onLogin [self]
 */
define('controls/users/LoginWindow', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'qui/controls/buttons/Button',
    'Locale',
    'controls/users/Login',

    'css!controls/users/LoginWindow.css'

], function (QUI, QUIConfirm, QUIButton, Locale, Login) {
    "use strict";

    /**
     * @class controls/lang/Popup
     */
    return new Class({

        Extends: QUIConfirm,
        Type   : 'controls/users/LoginWindow',

        Binds: [
            'submit',
            '$onCreate',
            '$onSubmit'
        ],

        options: {
            title    : Locale.get('quiqqer/system', 'login.title'),
            icon     : 'fa fa-sign-in',
            maxHeight: 400,
            maxWidth : 400,
            autoclose: false,
            buttons  : false,
            logo     : false,
            message  : false
        },

        initialize: function (options) {
            this.parent(options);

            this.$opened = false;
            this.$Login  = null;


            this.addEvent('cancel', function () {
                window.onbeforeunload = null;

                if (typeof window.QUIQQER.inAdministration !== 'undefined') {
                    window.location = window.URL_SYS_DIR;
                }
            });
        },

        /**
         * Open the Login
         */
        open: function () {
            // check if one login window is still open
            var logins = QUI.Controls.getByType('controls/users/LoginWindow');

            if (logins.length >= 2) {
                for (var i = 0, len = logins.length; i < len; i++) {
                    if (logins[i].$opened) {
                        this.destroy();
                        return;
                    }
                }
            }

            this.$opened = true;
            this.parent();

            var self    = this,
                Content = this.getContent();

            Content.getElements('.submit-body').destroy();
            Content.addClass('quiqqer-loginWindow-content');

            if (this.getAttribute('message')) {
                new Element('div', {
                    'class': 'quiqqer-loginWindow-message message-attention',
                    html   : this.getAttribute('message')
                }).inject(Content);
            }

            if (this.getAttribute('logo')) {
                new Element('img', {
                    'class': 'quiqqer-login-logo',
                    src    : this.getAttribute('logo')
                }).inject(Content);
            }

            this.$Login = new Login({
                showLoader: false,
                onSuccess : function () {
                    self.close();
                    self.fireEvent('success', [self]);
                },
                events    : {
                    onAuthBegin: function () {
                        self.Loader.show();
                    },
                    onAuthNext : function () {
                        self.Loader.hide();
                    }
                }
            }).inject(Content);
        },

        /**
         * Close the Login
         */
        close: function () {
            this.$opened = false;
            this.parent();
        },

        /**
         * placeholder for submit
         */
        submit: function () {

        }
    });
});
