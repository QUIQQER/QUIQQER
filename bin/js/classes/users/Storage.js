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
     * @class QUI.classes.users.Storage
     *
     * @memberof! <global>
     */
    QUI.classes.users.Storage = new Class({

        Implements : [ QDOM ],
        Type       : 'QUI.classes.users.Storage',

        /**
         * session user
         *
         * @param {QUI.classes.users.User} User
         */
        initialize : function(User)
        {
            this.$User = User;
        },

        /**
         * Set the value of a key
         *
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
         * @param {String} key
         * @return {unknown_type}
         */
        get : function(key)
        {
            return window.localStorage.getItem( key );
        }

    });

    return QUI.classes.users.Storage;
});