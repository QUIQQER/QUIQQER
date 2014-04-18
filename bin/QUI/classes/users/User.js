/**
 * A QUIQQER User
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 * @requires classes/users/Adresses
 * @requires classes/users/AdressesContact
 *
 * @module classes/users/User
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
     * @param {Integer} uid - the user id
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
         * @return {Integer} User-ID
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
         * @param {Function} onfinish - [optional] callback
         */
        load: function(onfinish)
        {
            var self = this;

            Ajax.get('ajax_users_get', function(result, Request)
            {
                self.$loaded = true;

                if ( result.extras )
                {
                    self.$extras = result.extras;
                    delete result.extras;
                }

                self.setAttributes( result );

                if ( typeof onfinish !== 'undefined' ) {
                    onfinish( self, Request );
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
         * @return {Bool}
         */
        isLoaded : function()
        {
            return this.$loaded;
        },

        /**
         * Save the user attributes to the database
         *
         * @method classes/users/User#save
         * @param {Function} callback - [optional]
         * @param {params} Object     - [optional] extra ajax params
         */
        save : function(callback, params)
        {
            var self = this;

            require(['Users'], function(Users) {
                Users.saveUser( self, callback, params );
            });
        },

        /**
         * Activate the user
         *
         * @method classes/users/User#activate
         *
         * @param {Function} onfinish     - callback function, calls if activation is finish
         * @param {Object} params         - callback params
         */
        activate : function(onfinish, params)
        {
            var self = this;

            require(['Users'], function(Users) {
                Users.activate( [ Users.getId() ] );
            });
        },

        /**
         * Activate the user
         *
         * @method classes/users/User#deactivate
         * @param {Function} onfinish     - callback function, calls if deactivation is finish
         * @param {Object} params         - callback params
         */
        deactivate : function(onfinish, params)
        {
            var self = this;

            require(['Users'], function(Users) {
                Users.deactivate( [ self.getId() ] );
            });
        },

        /**
         * Saves a Password to the User
         *
         * @method classes/users/User#deactivate
         * @param {String} pass1 - Password
         * @param {String} pass2 - Password repeat
         * @param {Object} options - [optional]
         * @param {Function} onfinish - [optional] callback
         */
        savePassword : function(pass1, pass2, options, onfinish)
        {
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
         * @return {Integer} 0, 1, -1
         */
        isActive : function()
        {
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
         * @param {Object|String|Integer|Array} v - value
         *
         * @return {this} self
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
         * @return {this} self
         *
         * @example Object.setAttributes({
         *   attr1 : '1',
         *   attr2 : []
         * })
         */
        setAttributes : function(attributes)
        {
            attributes = attributes || {};

            for ( var k in attributes ) {
                this.setAttribute( k, attributes[k] );
            }

            return this;
        },

        /**
         * Return an attribute of the Object
         * returns the not the default attributes, too
         *
         * @method classes/users/User#setAttribute
         *
         * @param {Object} attributes - Object width attributes
         * @return {unknown_type|Bool} The wanted attribute or false
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
         * @return {Bool} true or false
         */
        existAttribute : function(k)
        {
            if ( typeof this.attributes[ k ] !== 'undefined' ) {
                return true;
            }

            return false;
        },

        /**
         * Return the extra entry
         *
         * @param {String} $field
         * @return {String|Integer|Array|Bool}
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
         * @param {String|Bool} value - Value of the extra field
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