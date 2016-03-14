
/**
 * Login Window
 *
 * @module controls/system/Login
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/windows/Confirm
 * @require qui/controls/buttons/Button
 * @require Locale
 * @require Ajax
 * @require css!controls/system/Login.css
 *
 * @event onSubmit [ {Array}, {this} ]
 */

define('controls/system/Login', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'qui/controls/buttons/Button',
    'Locale',
    'Ajax',

    'css!controls/system/Login.css'

], function (QUI, QUIConfirm, QUIButton, Locale, Ajax) {
    "use strict";

    /**
     * @class controls/lang/Popup
     */
    return new Class({

        Extends : QUIConfirm,
        Type    : 'controls/system/Login',

        Binds : [
            'submit',
            '$onCreate',
            '$onSubmit'
        ],

        options : {
            title     : Locale.get('quiqqer/system', 'login.title'),
            icon      : 'fa fa-sign-in',
            maxHeight : 300,
            maxWidth  : 500,
            autoclose : false,
            cancel_button : {
                text      : Locale.get('quiqqer/system', 'logout'),
                textimage : 'fa fa-remove'
            },
            ok_button : {
                text      : Locale.get('quiqqer/system', 'login'),
                textimage : 'fa fa-check'
            }
        },

        initialize : function (options) {
            this.parent(options);
            this.$opened = false;

            this.addEvent('cancel', function () {
                window.onbeforeunload = null;
                window.location = '/admin/';
            });
        },

        /**
         * Open the Login
         */
        open : function () {
            // check if one login window is still open
            var logins = QUI.Controls.getByType('controls/system/Login');

            if (logins.length >= 2) {
                for (var i = 0, len = logins.length; i < len; i++) {
                    if (logins[ i ].$opened) {
                        this.destroy();
                        return;
                    }
                }
            }

            this.$opened = true;
            this.parent();

            var Content = this.getContent();

            Content.getElements('.submit-body').destroy();

            Content.set(
                'html',

                '<form class="qui-control-login">' +
                    '<label>' +
                        Locale.get('quiqqer/system', 'username') +
                    '</label>' +
                    '<input type="text" value="" name="username" />' +
                    '<label>' +
                        Locale.get('quiqqer/system', 'password') +
                    '</label>' +
                    '<input type="password" value="" name="password" />' +
                '</form>'
            );

            Content.getElements('input').addEvent('keyup', function (event) {
                if (event.key == 'enter') {
                    this.submit();
                }
            }.bind(this));
        },

        /**
         * Close the Login
         */
        close : function () {
            this.$opened = false;
            this.parent();
        },

        /**
         * Submit the login
         */
        submit : function () {
            this.login();
        },

        /**
         * Loge In
         */
        login : function () {
            var self    = this,
                Content = this.getContent();

            this.Loader.show();

            Ajax.post('ajax_login_login', function () {
                window.fireEvent('login');

                self.close();
            }, {
                username : Content.getElement('[name="username"]').value,
                password : Content.getElement('[name="password"]').value,
                onError : function () {
                    self.Loader.hide();
                }
            });
        }
    });
});
