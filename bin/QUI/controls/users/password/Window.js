/**
 * @module controls/users/password/Window
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/windows/Confirm
 * @require controls/users/password/Password
 * @require css!controls/users/password/Window.css
 */
define('controls/users/password/Window', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'controls/users/password/Password',
    'Locale'

], function (QUI, QUIConfirm, Password, QUILocale) {
    "use strict";

    return new Class({
        Extends: QUIConfirm,
        Type   : 'controls/users/password/Password',

        Binds: [
            '$onOpen'
        ],

        options: {
            icon     : 'fa fa-icon',
            title    : 'Passwort ändern',
            maxHeight: 400,
            maxWidth : 400,
            uid      : false,
            autoclose: false
        },

        initialize: function (options) {
            this.parent(options);

            if (!this.getAttribute('uid')) {
                this.setAttribute('uid', USER.id);
            }

            this.$Password = null;

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * event : on open
         */
        $onOpen: function (Win) {
            Win.getContent().set('html', '');

            this.$Password = new Password({
                uid: this.getAttribute('uid')
            }).inject(Win.getContent());
        },

        /**
         * Submit the new password
         */
        submit: function () {
            this.Loader.show();
            this.$Password.save().then(function () {
                this.close();
            }.bind(this)).catch(function () {
                this.Loader.hide();
            }.bind(this));
        }
    });
});