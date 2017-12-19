/**
 * Logout popup / window
 *
 * @author www.pcsg.de (Henning Leutz)
 * @module controls/users/LogoutWindow
 *
 * @require qui/QUI
 * @require qui/controls/windows/Confirm
 * @require controls/users/Login
 * @require Locale
 */
define('controls/users/LogoutWindow', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'controls/users/Login',
    'Locale',
    'Ajax'

], function (QUI, QUIConfirm, Login, QUILocale, Ajax) {
    "use strict";

    var lg = 'quiqqer/system';

    return new Class({

        Extends: QUIConfirm,
        Type   : 'controls/users/LogoutWindow',

        Binds: [
            'logout'
        ],

        options: {
            icon         : 'icon-sign-out fa fa-sign-out',
            title        : QUILocale.get(lg, 'window.logout.title'),
            text         : QUILocale.get(lg, 'window.logout.text'),
            texticon     : 'icon-sign-out fa fa-sign-out',
            information  : QUILocale.get(lg, 'window.logout.information'),
            maxWidth     : 500,
            maxHeight    : 300,
            cancel_button: {
                text     : QUILocale.get(lg, 'window.logout.button.cancel'),
                textimage: 'icon-remove fa fa-remove'
            },
            ok_button    : {
                text     : QUILocale.get(lg, 'window.logout.button.ok'),
                textimage: 'fa fa-sign-out'
            }
        },

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onSubmit: this.logout
            });
        },

        /**
         * Execute the logout
         */
        logout: function () {
            this.Loader.show();

            Ajax.post('ajax_users_logout', function () {
                window.location.reload();
            });
        }
    });
});
