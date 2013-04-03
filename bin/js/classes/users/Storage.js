/**
 * User setting storage
 * uses local storage
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module classes/users/Storage
 * @package com.pcsg.qui.js.classes.users.storage
 * @namespace QUI.classes.users
 */

define('classes/users/Storage', [

    'classes/DOM',
    'lib/polyfills/Storage'

],function(QDOM)
{
    "use strict";

    QUI.namespace( 'classes.users' );

    /**
     * Local storage for user settings
     *
     * @class QUI.classes.users.Storage
     * @param {QUI.classes.users.User} User
     *
     * @memberof! <global>
     */
    QUI.classes.users.Storage = new Class({

        Extends : QDOM,
        Type    : 'QUI.classes.users.Storage',

        initialize : function(User)
        {
            this.$User = User;
        },

        /**
         * Set the value of a key
         *
         * @method QUI.classes.users.Storage#set
         * @param {String} key
         * @param {String|Integer} value
         */
        set : function(key, value)
        {
            window.localStorage.setItem( key, value );
        },

        /**
         * Return the value of stored the key
         *
         * @method QUI.classes.users.Storage#get
         * @param {String} key
         * @return {unknown_type} the wanted storage
         */
        get : function(key)
        {
            return window.localStorage.getItem( key );
        }
    });

    return QUI.classes.users.Storage;
});