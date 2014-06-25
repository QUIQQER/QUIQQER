/**
 * List all available languages
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onSubmit [ {Array}, {this} ]
 */

define('controls/system/Login', [

    'qui/controls/windows/Confirm',
    'qui/controls/buttons/Button',
    'Locale',
    'Ajax',

    'css!controls/system/Login.css'

], function(QUIConfirm, QUIButton, Locale, Ajax)
{
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
            title     : 'Login',
            icon      : 'icon-signin',
            maxHeight : 300,
            maxWidth  : 500,
            autoclose : false
        },

        initialize : function(options)
        {
            this.parent( options );
        },

        /**
         * event : onCreate
         */
        open : function()
        {
            this.parent();

            var self    = this,
                Content = this.getContent();

            Content.getElements( '.submit-body' ).destroy();

            Content.set(
                'html',

                '<form class="qui-control-login">' +
                    '<label>' +
                        'Benutzername' +
                    '</label>' +
                    '<input type="text" value="" name="username" />' +
                    '<label>' +
                        'Passwort' +
                    '</label>' +
                    '<input type="password" value="" name="password" />' +
                '</form>'
            );
        },

        /**
         * Submit the login
         */
        submit : function()
        {
            this.login();
        },

        /**
         * Loge In
         */
        login : function()
        {
            var self    = this,
                Content = this.getContent();

            this.Loader.show();

            Ajax.post('ajax_login_login', function(result)
            {
                self.close();
            }, {
                username : Content.getElement( '[name="username"]' ).value,
                password : Content.getElement( '[name="password"]' ).value
            });
        }
    });

});