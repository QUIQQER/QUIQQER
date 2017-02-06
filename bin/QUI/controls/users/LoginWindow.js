/**
 * Login Window
 *
 * @module controls/users/LoginWindow
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/windows/Confirm
 * @require qui/controls/buttons/Button
 * @require Locale
 * @require controls/users/Login
 *
 * @event onSubmit [ Array, this ]
 * @event onLogin [self]
 */
define('controls/users/LoginWindow', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'qui/controls/buttons/Button',
    'Locale',
    'controls/users/Login'

], function (QUI, QUIConfirm, QUIButton, Locale, Login) {
    "use strict";

    /**
     * @class controls/lang/Popup
     */
    return new Class({

        Extends: QUIConfirm,
        Type: 'controls/users/LoginWindow',

        Binds: [
            'submit',
            '$onCreate',
            '$onSubmit'
        ],

        options: {
            title: Locale.get('quiqqer/system', 'login.title'),
            icon: 'fa fa-sign-in',
            maxHeight: 400,
            maxWidth: 400,
            autoclose: false,
            buttons: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$opened = false;
            this.$Login = null;

            this.addEvent('cancel', function () {
                window.onbeforeunload = null;
                window.location = '/admin/';
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

            var self = this,
                Content = this.getContent();

            Content.getElements('.submit-body').destroy();

            this.$Login = new Login({
                onSuccess: function () {
                    self.close();
                    self.fireEvent('success', [self]);
                },
                events: {
                    onAuthBegin: function () {
                        self.Loader.show();
                    },
                    onAuthNext: function () {
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
