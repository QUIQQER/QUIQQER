/**
 * User Manager (Model)
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 *
 * @module classes/users/Manager
 *
 * @event onSwitchStatus [this, result, Request]
 * @event onActivate [this, result, Request]
 * @event onDeactivate [this, result, Request]
 * @event onDelete [this, uids]
 * @event onRefresh [this, User]
 * @event onSave [this, User]
 */

define('classes/users/Manager', [

    'qui/classes/DOM',
    'classes/users/User',
    'Ajax',
    'qui/utils/Object'

], function(DOM, User, Ajax, ObjectUtils)
{
    "use strict";

    /**
     * @class classes/users/Manager
     * @desc User Manager (Model)
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : DOM,                   // @member classes/users/Manager
        Type    : 'classes/users/Users', // @member classes/users/Manager

        $users : {},				     // @member classes/users/Manager

        /**
         * Return a user
         *
         * @method classes/users/Manager#get
         * @return {controls/users/User} User
         */
        get : function(uid)
        {
            if ( typeof this.$users[ uid ] === 'undefined' ) {
                this.$users[ uid ] = new User( uid );
            }

            return this.$users[ uid ];
        },

        /**
         * Return the loged in user (session user)
         *
         * @method classes/users/Manager#getUserBySession
         * @return {controls/users/User} User
         */
        getUserBySession : function()
        {
            if ( typeof this.$users[ USER.id ] === 'undefined' ) {
                this.$users[ USER.id ] = new User( USER.id );
            }

            return this.$users[ USER.id ];
        },

        /**
         * Return the user list
         *
         * @method classes/users/Manager#getList
         * @param {Object} search     - search options
         * @param {Function} onfinish - [optional] callback function
         * @param {Object} params     - [optional] extra params
         */
        getList : function(search, onfinish, params)
        {
            params = ObjectUtils.combine(params, {
                params : JSON.encode( search )
            });

            Ajax.get('ajax_users_search', function(result, Request)
            {
                if ( typeof onfinish !== 'undefined' ) {
                    onfinish( result, Request );
                }
            }, params);
        },

        /**
         * Switch the status to activate or deactivate from an user
         *
         * @method classes/users/Manager#switchStatus
         * @param {Array|Integer} uid - search options
         * @param {Function} onfinish - [optional] callback function
         * @param {Object} params     - [optional] extra params
         */
        switchStatus : function(uid, onfinish, params)
        {
            var self = this;

            params = ObjectUtils.combine(params, {
                uid : JSON.encode( uid )
            });

            Ajax.post('ajax_users_switchstatus', function(result, Request)
            {
                if ( typeof onfinish !== 'undefined' ) {
                    onfinish( result, Request );
                }

                self.fireEvent( 'switchStatus', [ self, result, Request ] );

            }, params);
        },

        /**
         * Activate the user / users
         *
         * @method classes/users/Manager#activate
         * @param {Array|Integer} uid - search options
         * @param {Function} onfinish - [optional] callback function
         * @param {Object} params     - [optional] extra params
         */
        activate : function(uid, onfinish, params)
        {
            var self = this;

            params = ObjectUtils.combine(params, {
                uid : JSON.encode( uid )
            });

            Ajax.post('ajax_users_activate', function(result, Request)
            {
                if ( typeof onfinish !== 'undefined' ) {
                    onfinish( result, Request );
                }

                self.fireEvent( 'activate', [ self, result, Request ] );

            }, params);
        },

        /**
         * Deactivate the user / users
         *
         * @method classes/users/Manager#deactivate
         * @param {Array|Integer} uid - search options
         * @param {Function} onfinish - [optional] callback function
         * @param {Object} params     - [optional] extra params
         */
        deactivate : function(uid, onfinish, params)
        {
            var self = this;

            params = ObjectUtils.combine(params, {
                uid : JSON.encode( uid )
            });

            Ajax.post('ajax_users_deactivate', function(result, Request)
            {
                if ( typeof onfinish !== 'undefined' ) {
                    onfinish( result, Request );
                }

                self.fireEvent( 'deactivate', [ self, result, Request ] );

            }, params);
        },

        /**
         * Checks if the username exists
         *
         * @method classes/users/Manager#existsUsername
         * @param {String} username   - Username
         * @param {Function} onfinish - callback function
         * @param {Object} params     - [optional] extra params
         */
        existsUsername : function(username, onfinish, params)
        {
            params = ObjectUtils.combine(params, {
                username : username
            });

            Ajax.get('ajax_users_exists', function(result, Request)
            {
                onfinish( result, Request );
            }, params);
        },

        /**
         * create a new user
         *
         * @method classes/users/Manager#createUser
         * @param {String} username   - Username
         * @param {Function} onfinish - [optional] callback function
         * @param {Object} params     - [optional] extra params
         */
        createUser : function(username, onfinish, params)
        {
            params = ObjectUtils.combine(params, {
                username : username
            });

            Ajax.post('ajax_users_create', function(result, Request)
            {
                if ( typeof onfinish !== 'undefined' ) {
                    onfinish( result, Request );
                }
            }, params);
        },

        /**
         * Delete users
         *
         * @method classes/users/Manager#deleteUsers
         * @param {Array} uids - User-IDs
         * @param {Function} onfinish - [optional] callback function
         * @param {Object} params     - [optional] extra params
         */
        deleteUsers : function(uids, onfinish, params)
        {
            var self = this;

            params = ObjectUtils.combine(params, {
                uid : JSON.encode( uids )
            });

            Ajax.post('ajax_users_delete', function(result, Request)
            {
                for ( var i = 0, len = uids.length; i < len; i++ )
                {
                    if ( typeof self.$users[ uids[ i ] ] !== 'undefined' ) {
                        delete self.$users[ uids[ i ] ];
                    }
                }

                self.fireEvent( 'delete', [ self, uids ] );

                if ( typeof onfinish !== 'undefined' ) {
                    onfinish( result, Request );
                }

            }, params);
        },

        /**
         * Triggerd by an user
         *
         * @method classes/users/Manager#onRefreshUser
         * @param {controls/users/User} User
         */
        onRefreshUser : function(User)
        {
            this.fireEvent( 'refresh', [ this, User ] );
        },

        /**
         * Save a user with its attributes and rights
         *
         * @method classes/users/Manager#saveUser
         * @param {controls/users/User} User
         * @param {Function} onfinish - [optional] callback
         * @param {params} Object     - [optional] extra params
         */
        saveUser : function(User, onfinish, params)
        {
            var self       = this,
                attributes = User.getAttributes();

            for ( var i in attributes )
            {
                if ( typeof attributes[ i ] === 'object' ) {
                    delete attributes[ i ];
                }
            }

            // attributes.extra = User.getExtras();

            params = ObjectUtils.combine(params, {
                uid        : User.getId(),
                attributes : JSON.encode( attributes )
            });

            Ajax.post('ajax_users_save', function(result, Request)
            {
                self.get( User.getId() );
                self.fireEvent( 'save', [ self, User ] );

                if ( typeof onfinish !== 'undefined' ) {
                    onfinish( User, Request );
                }

            }, params );
        }
    });
});
