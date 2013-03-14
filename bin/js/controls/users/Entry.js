/**
 * A user field / display
 * the display updates itself
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/users/Entry
 * @package com.pcsg.qui.js.controls.users
 * @namespace QUI.controls.users
 *
 * @require controls/Control
 * @require users
 */

define('controls/users/Entry', [

    'controls/Control',
    'Users',

    'css!controls/users/Entry.css'

], function(QUI_Control)
{
    QUI.namespace( 'controls.users' );

    /**
     * @class QUI.controls.users.Entry
     *
     * @param {Integer} uid - user-ID
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.controls.users.Entry = new Class({

        Implements : [ QUI_Control ],
        Type       : 'QUI.controls.users.Entry',

        Binds : [
            '$onUserUpdate'
        ],

        initialize : function(uid, options)
        {
            this.$User = QUI.Users.get( uid );
            this.init( options );

            this.$Elm = null;
        },

        /**
         * Create the DOMNode of the entry
         *
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class'   : 'users-entry radius5',
                'data-id' : this.$User.getId(),

                html : '<div class="text"></div>' +
                       '<div class="close"></div>',

                events :
                {
                    mouseover : function() {
                        this.addClass( 'hover' );
                    },
                    mouseout : function() {
                        this.removeClass( 'hover' );
                    }
                }
            });

            this.$Elm.getElement( '.close' ).addEvent( 'click', function()
            {
                this.destroy();
            }.bind( this ));

            this.$User.addEvent( 'onRefresh', this.$onUserUpdate );
            this.refresh();

            return this.$Elm;
        },

        /**
         * event : on entry destroy
         */
        $onDestroy : function()
        {
            this.$User.removeEvent( 'refresh', this.$onUserUpdate );
        },

        /**
         * Refresh the data of the users
         *
         * @return {this}
         */
        refresh : function()
        {
            this.$Elm.getElement( '.text' ).set(
                'html',
                '<img src="'+ URL_BIN_DIR +'images/loader.gif" />'
            );

            if ( this.$User.getAttribute( 'name' ) )
            {
                this.$onUserUpdate( this.$User );
                return this;
            }

            this.$User.load();

            return this;
        },

        /**
         * Update the user name
         *
         * @param {QUI.classes.users.User}
         * @return {this}
         */
        $onUserUpdate : function(User)
        {
            if ( !this.$Elm ) {
                return this;
            }

            this.$Elm.getElement( '.text' )
                     .set( 'html', User.getAttribute( 'name' ) );

            return this;
        }
    });

    return QUI.controls.users.Entry;
});