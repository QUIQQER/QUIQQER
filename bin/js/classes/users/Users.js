/**
 * User Manager (Model)
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 *
 * @module controls/users/Users
 * @package com.pcsg.qui.js.classes.users.Users
 * @namespace QUI.classes.users
 *
 * @event onSwitchStatus [this, result, Request]
 * @event onActivate [this, result, Request]
 * @event onDeactivate [this, result, Request]
 * @event onDelete [this, uids]
 * @event onRefresh [this, User]
 * @event onSave [this, User]
 */

define('classes/users/Users', [

    'classes/DOM',
    'classes/users/User'

], function(DOM, Grid)
{
    "use strict";

    QUI.namespace( 'classes.users' );

    /**
     * @class QUI.classes.users.Users
     * @desc User Manager (Model)
     *
     * @memberof! <global>
     */
    QUI.classes.users.Users = new Class({

        Implements : [ DOM ],                   // @member QUI.classes.users.Users
        Type       : 'QUI.classes.users.Users',	// @member QUI.classes.users.Users

        $users : {},							// @member QUI.classes.users.Users

        /**
         * Return a user
         *
         * @method QUI.classes.users.Users#get
         * @return {QUI.classes.users.User} User
         */
        get : function(uid)
        {
            if ( typeof this.$users[ uid ] === 'undefined' ) {
                this.$users[ uid ] = new QUI.classes.users.User( uid );
            }

            return this.$users[ uid ];
        },

        /**
         * Return the loged in user (session user)
         *
         * @method QUI.classes.users.Users#getUserBySession
         * @return {QUI.classes.users.User} User
         */
        getUserBySession : function()
        {
            if ( typeof this.$users[ USER.id ] === 'undefined' ) {
                this.$users[ USER.id ] = new QUI.classes.users.User( USER.id );
            }

            return this.$users[ USER.id ];
        },

        /**
         * Return the user list
         *
         * @method QUI.classes.users.Users#getList
         * @param {Object} search     - search options
         * @param {Function} onfinish - [optional] callback function
         * @param {Object} params     - [optional] extra params
         */
        getList : function(search, onfinish, params)
        {
            params = QUI.Utils.combine(params, {
                params   : JSON.encode( search ),
                onfinish : onfinish
            });

            QUI.Ajax.get('ajax_users_search', function(result, Request)
            {
                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }
            }, params);
        },

        /**
         * Switch the status to activate or deactivate from an user
         *
         * @method QUI.classes.users.Users#switchStatus
         * @param {Array|Integer} uid - search options
         * @param {Function} onfinish - [optional] callback function
         * @param {Object} params     - [optional] extra params
         */
        switchStatus : function(uid, onfinish, params)
        {
            params = QUI.Utils.combine(params, {
                Users    : this,
                uid      : JSON.encode( uid ),
                onfinish : onfinish
            });

            QUI.Ajax.post('ajax_users_switchstatus', function(result, Request)
            {
                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }

                var Users = Request.getAttribute( 'Users' );
                    Users.fireEvent( 'switchStatus', [ Users, result, Request ] );

            }, params);
        },

        /**
         * Activate the user / users
         *
         * @method QUI.classes.users.Users#activate
         * @param {Array|Integer} uid - search options
         * @param {Function} onfinish - [optional] callback function
         * @param {Object} params     - [optional] extra params
         */
        activate : function(uid, onfinish, params)
        {
            params = QUI.Utils.combine(params, {
                Users    : this,
                uid      : JSON.encode( uid ),
                onfinish : onfinish
            });

            QUI.Ajax.post('ajax_users_activate', function(result, Request)
            {
                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }

                var Users = Request.getAttribute( 'Users' );
                    Users.fireEvent( 'activate', [ Users, result, Request ] );

            }, params);
        },

        /**
         * Deactivate the user / users
         *
         * @method QUI.classes.users.Users#deactivate
         * @param {Array|Integer} uid - search options
         * @param {Function} onfinish - [optional] callback function
         * @param {Object} params     - [optional] extra params
         */
        deactivate : function(uid, onfinish, params)
        {
            params = QUI.Utils.combine(params, {
                Users    : this,
                uid      : JSON.encode( uid ),
                onfinish : onfinish
            });

            QUI.Ajax.post('ajax_users_deactivate', function(result, Request)
            {
                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }

                var Users = Request.getAttribute( 'Users' );
                    Users.fireEvent( 'deactivate', [ Users, result, Request ] );

            }, params);
        },

        /**
         * Checks if the username exists
         *
         * @method QUI.classes.users.Users#existsUsername
         * @param {String} username   - Username
         * @param {Function} onfinish - callback function
         * @param {Object} params     - [optional] extra params
         */
        existsUsername : function(username, onfinish, params)
        {
            params = QUI.Utils.combine(params, {
                username : username,
                onfinish : onfinish
            });

            QUI.Ajax.get('ajax_users_exists', function(result, Request)
            {
                Request.getAttribute( 'onfinish' )( result, Request );
            }, params);
        },

        /**
         * create a new user
         *
         * @method QUI.classes.users.Users#createUser
         * @param {String} username   - Username
         * @param {Function} onfinish - [optional] callback function
         * @param {Object} params     - [optional] extra params
         */
        createUser : function(username, onfinish, params)
        {
            params = QUI.Utils.combine(params, {
                username : username,
                onfinish : onfinish
            });

            QUI.Ajax.post('ajax_users_create', function(result, Request)
            {
                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }
            }, params);
        },

        /**
         * Delete users
         *
         * @method QUI.classes.users.Users#deleteUsers
         * @param {Array} uids - User-IDs
         * @param {Function} onfinish - [optional] callback function
         * @param {Object} params     - [optional] extra params
         */
        deleteUsers : function(uids, onfinish, params)
        {
            params = QUI.Utils.combine(params, {
                uid      : JSON.encode( uids ),
                onfinish : onfinish,
                Users    : this
            });

            QUI.Ajax.post('ajax_users_delete', function(result, Request)
            {
                var Users = Request.getAttribute( 'Users' );

                for ( var i = 0, len = uids.length; i < len; i++ )
                {
                    if ( typeof Users.$users[ uids[ i ] ] !== 'undefined' ) {
                        delete Users.$users[ uids[ i ] ];
                    }
                }

                Users.fireEvent( 'delete', [ this, uids ] );

                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }
            }, params);
        },

        /**
         * Triggerd by an user
         *
         * @method QUI.classes.users.Users#onRefreshUser
         * @param {QUI.classes.users.User} User
         */
        onRefreshUser : function(User)
        {
            this.fireEvent( 'refresh', [ this, User ] );
        },

        /**
         * Save a user with its attributes and rights
         *
         * @method QUI.classes.users.Users#saveUser
         * @param {QUI.classes.users.User} User
         * @param {Function} onfinish - [optional] callback
         * @param {params} Object     - [optional] extra params
         */
        saveUser : function(User, onfinish, params)
        {
            params = QUI.Utils.combine(params, {
                uid        : User.getId(),
                attributes : JSON.encode( User.getAttributes() ),
                //rights     : JSON.encode( User.getRights() ),
                onfinish   : onfinish,
                Users      : this
            });

            QUI.Ajax.post('ajax_users_save', function(result, Request)
            {
                var Users = Request.getAttribute( 'Users' ),
                    User  = Users.get( Request.getAttribute( 'uid' ) );

                Users.fireEvent( 'save', [ Users, User ] );

                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( User, Request );
                }

            }, params );
        }
    });

    return QUI.classes.users.Users;
});
