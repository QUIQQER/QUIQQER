
/**
 * A QUIQQER User
 *
 * @module classes/users/User
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/classes/DOM
 * @require Ajax
 * @require Locale
 *
 * @event onRefresh [ {classes/users/User} ]
 */

define('classes/users/User', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax',
    'Locale'

], function(QUI, DOM, Ajax, Locale)
{
    "use strict";

    /**
     * A QUIQQER User
     *
     * @class classes/users/User
     * @param {Number} uid - the user id
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : DOM,
        Type    : 'classes/users/User',

        attributes : {}, // user attributes

        initialize : function(uid)
        {
            this.$uid    = uid;
            this.$extras = {};
            this.$loaded = false;
        },

        /**
         * Get user id
         *
         * @method classes/users/User#getId
         * @return {Number} User-ID
         */
        getId : function()
        {
            return this.$uid;
        },

        /**
         * Return the user name
         *
         * @method classes/users/User#getName
         * @return {String} Username
         */
        getName : function()
        {
            return this.getAttribute( 'username' );
        },

        /**
         * Load the user attributes from the db
         *
         * @method classes/users/User#load
         * @param {Function} [onfinish] - (optional), callback
         */
        load: function(onfinish)
        {
            var self = this;

            Ajax.get('ajax_users_get', function(result)
            {
                self.$loaded = true;

                var uid = 0;

                if ( "id" in result && result.id > 10 ) {
                    uid = result.id;
                }

                // user not found
                if ( !uid )
                {
                    self.$uid = 0;

                    self.setAttributes({
                        username : 'not found'
                    });

                    if ( typeof onfinish !== 'undefined' ) {
                        onfinish( self );
                    }

                    self.fireEvent( 'refresh', [ self ] );

                    require(['Users'], function(Users) {
                        Users.onRefreshUser( self );
                    });

                    return;
                }


                if ( result.extras )
                {
                    self.$extras = result.extras;
                    delete result.extras;
                }

                self.setAttributes( result );

                if ( typeof onfinish !== 'undefined' ) {
                    onfinish( self );
                }

                self.fireEvent( 'refresh', [ self ] );

                require(['Users'], function(Users) {
                    Users.onRefreshUser( self );
                });

            }, {
                uid : this.getId()
            });
        },

        /**
         * the user has been loaded once?
         *
         * @return {Boolean}
         */
        isLoaded : function()
        {
            return this.$loaded;
        },

        /**
         * Save the user attributes to the database
         *
         * @method classes/users/User#save
         * @param {Function} [callback] - (optional),
         * @param {Object} [params]     - (optional), extra ajax params
         */
        save : function(callback, params)
        {
            if ( !this.$uid )
            {
                callback();
                return;
            }

            var self = this;

            require(['Users'], function(Users) {
                Users.saveUser( self, callback, params );
            });
        },

        /**
         * Activate the user
         *
         * @method classes/users/User#activate
         * @param {Function} [onfinish] - (optional), callback function, calls if activation is finish
         */
        activate : function(onfinish)
        {
            if ( !this.$uid )
            {
                onfinish();
                return;
            }

            var self = this;

            require(['Users'], function(Users) {
                Users.activate( [ self.getId() ], onfinish );
            });
        },

        /**
         * Deactivate the user
         *
         * @method classes/users/User#deactivate
         * @param {Function} [onfinish] - (optional), callback function, calls if deactivation is finish
         */
        deactivate : function(onfinish)
        {
            if ( !this.$uid )
            {
                onfinish();
                return;
            }

            var self = this;

            require(['Users'], function(Users) {
                Users.deactivate( [ self.getId() ], onfinish );
            });
        },

        /**
         * Saves a Password to the User
         *
         * @method classes/users/User#deactivate
         * @param {String} pass1 - Password
         * @param {String} pass2 - Password repeat
         * @param {Object} [options]    - (optional),
         * @param {Function} [onfinish] - (optional), callback
         */
        savePassword : function(pass1, pass2, options, onfinish)
        {
            if ( !this.$uid )
            {
                onfinish( false, false );
                return;
            }

            options = options || {};

            if ( pass1 != pass2 )
            {
                QUI.getMessageHandler(function(MH)
                {
                    MH.addError(
                        Locale.get(
                            'quiqqer/system',
                            'exception.user.wrong.passwords'
                        )
                    );
                });

                if ( onfinish ) {
                    onfinish( false, false );
                }

                return;
            }

            Ajax.post('ajax_users_set_password', function(result, Request)
            {
                if ( typeof onfinish !== 'undefined' ) {
                    onfinish( result, Request );
                }
            }, {
                uid    : this.getId(),
                pw1    : pass1,
                pw2    : pass2,
                params : JSON.encode( options )
            });
        },

        /**
         * Is the user activated?
         *
         * @return {Number} 0, 1, -1
         */
        isActive : function()
        {
            if ( !this.$uid ) {
                return 0;
            }

            return ( this.getAttribute( 'active' ) ).toInt();
        },

        /**
         * Attribute methods
         */

        /**
         * Set an attribute to the Object
         * You can extend the Object with everything you like
         * You can extend the Object width more than the default options
         *
         * @method classes/users/User#setAttribute
         *
         * @param {String} k - Name of the Attribute
         * @param {Object|String|Number|Array} v - value
         *
         * @return {Object} this (classes/users/User)
         */
        setAttribute : function(k, v)
        {
            this.attributes[ k ] = v;
            return this;
        },

        /**
         * If you want set more than one attribute
         *
         * @method classes/users/User#setAttribute
         *
         * @param {Object} attributes - Object with attributes
         * @return {Object} this (classes/users/User)
         *
         * @example Object.setAttributes({
         *   attr1 : '1',
         *   attr2 : []
         * })
         */
        setAttributes : function(attributes)
        {
            attributes = attributes || {};

            for ( var k in attributes )
            {
                if ( attributes.hasOwnProperty( k ) ) {
                    this.setAttribute( k, attributes[ k ] );
                }
            }

            return this;
        },

        /**
         * Return an attribute of the Object
         * returns the not the default attributes, too
         *
         * @method classes/users/User#setAttribute
         *
         * @param {Object} k - Object width attributes
         * @return {Boolean|String} The wanted attribute or false
         */
        getAttribute : function(k)
        {
            if ( typeof this.attributes[ k ] !== 'undefined' ) {
                return this.attributes[ k ];
            }

            return false;
        },

        /**
         * Return the default attributes
         *
         * @method classes/users/User#getAttributes
         * @return {Object} alle attributes
         */
        getAttributes : function()
        {
            return this.attributes;
        },

        /**
         * Return true if a attribute exist
         *
         * @method classes/users/User#existAttribute
         * @param {String} k - wanted attribute
         * @return {Boolean} true or false
         */
        existAttribute : function(k)
        {
            return typeof this.attributes[ k ] !== 'undefined';
        },

        /**
         * Return the extra entry
         *
         * @param {String} field
         * @return {String|Number|Array|Boolean}
         */
        getExtra : function(field)
        {
            if ( typeof this.$extras[ field ] !== 'undefined' ) {
                return this.$extras[ field ];
            }

            return false;
        },

        /**
         * Set a extra attribute
         *
         * @param {String} field - Name of the extra field
         * @param {String|Boolean} value - Value of the extra field
         */
        setExtra : function(field, value)
        {
            this.$extras[ field ] = value;
        },

        /**
         * Return all extra attributes
         *
         * @return {Object}
         */
        getExtras : function()
        {
            return this.$extras;
        }
    });
});
