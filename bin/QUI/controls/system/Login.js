
/**
 * List all available languages
 *
 * @module controls/system/Login
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onSubmit [ {Array}, {this} ]
 */

define([

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
            title     : Locale.get( 'quiqqer/system', 'login.title' ),
            icon      : 'icon-signin',
            maxHeight : 300,
            maxWidth  : 500,
            autoclose : false
        },

        initialize : function(options)
        {
            this.parent( options );

            this.addEvent('cancel', function() {
                window.location = window.location;
            });
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
                        Locale.get( 'quiqqer/system', 'username' ) +
                    '</label>' +
                    '<input type="text" value="" name="username" />' +
                    '<label>' +
                        Locale.get( 'quiqqer/system', 'password' ) +
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