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
 * @package com.pcsg.qui.js.classes.users
 * @namespace QUI.classes.users
 *
 * @event onRefresh [ {QUI.classes.users.User} ]
 */

define('classes/users/User', [

    'qui/classes/DOM',
    'Ajax'

], function(DOM, Ajax)
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
                })

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
                QUI.MH.addError(
                    QUI.Locale.get(
                        'quiqqer/system',
                        'exception.user.wrong.passwords'
                    )
                );

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
         * Opens the delete window
         *
         * @method classes/users/User#del
         */
        /*
        del : function()
        {
            QUI.Windows.create('submit', {
                name        : 'DeleteUser'+ this.getId(),
                title       : 'Möchten Sie den Benutzer wirklich löschen?',
                icon        : URL_BIN_DIR +'16x16/trashcan_full.png',
                texticon    : URL_BIN_DIR +'32x32/trashcan_full.png',
                text        : 'Den Benutzer '+ this.getAttribute('username') +' wirklich löschen?',
                information : 'Der Benutzer wird komplett aus dem System entfernt und kann nicht wieder hergestellt werden',

                width  : 500,
                height : 150,
                events :
                {
                    onsubmit : function(Win)
                    {
                        QUI.Ajax.post('ajax_users_delete', function(result, Ajax)
                        {
                            Ajax.getAttribute('User').Panel.close();
                        }, {
                            uid  : this.getId(),
                            User : this
                        });
                    }.bind( this )
                }
            });
        },
        */

        /**
         * Returns the user avatar url
         *
         * @method QUI.classes.users.User#getAvatar
         *
         * @return {String}
         */
        /*
        getAvatar : function()
        {
            return URL_DIR +'media/users/'+ this.getAttribute('avatar');
        },
        */
        /**
         * Load the Adresse management in the Panel
         *
         * @method classes/users/User#loadAdresses
         */
        /*
        loadAdresses : function()
        {
            var Body      = this.Panel.getBody(),
                Container = Body.getElement('form .adress-list'),
                User      = this;

            if ( !Container ) {
                return;
            }

            Container.setStyles({
                height : Body.getSize().y - 300,
                width  : Body.getSize().x - 40
            });

            require([
                'classes/users/Adresses',
                'classes/users/AdressesContact'
            ], function(Adresses, Contact)
            {
                User.$Adresses = new Adresses(User, Container);
            });
        },

        /**
         * Load the toolbar for the user panel
         *
         * @method QUI.classes.users.User#$loadToolbar
         * @param {Function} onfinish - callback
         * @ignore
         */
        /*
        $loadToolbar : function(onfinish)
        {
            QUI.Ajax.get('ajax_users_gettoolbar', function(result, Ajax)
            {
                var i, len, Btn, on_set_active;

                var User  = Ajax.getAttribute('User'),
                    Panel = User.Panel;

                on_set_active = function(Btn)
                {
                    var Parent = Btn.getParent();

                    if ( !Parent )
                    {
                        QUI.lib.Users.tabOnLoad( Btn );
                        return;
                    }

                    var Active = Btn.getParent().getActive();

                    // unload auf aktiven Button
                    if ( Active )
                    {
                        if ( Btn.getAttribute('name') == Active.getAttribute('name') ) {
                            return;
                        }

                        if ( QUI.lib.Users.tabOnUnLoad( Active ) === false )
                        {
                            Btn.setNormal();
                            Active.setActive();
                            return;
                        }
                    }

                    QUI.lib.Users.tabOnLoad( Btn );
                };

                for ( i = 0, len = result.length; i < len; i++ )
                {
                    Btn = new QUI.controls.buttons.Button( result[i] );
                    Btn.setAttribute('Panel', Panel);
                    Btn.setAttribute('User', User);

                    if ( Btn.getAttribute('onload') )
                    {
                        Btn.setAttribute('onUserLoad', Btn.getAttribute('onload'));
                        Btn.setAttribute('onload', false);
                    }

                    if ( Btn.getAttribute('onunload') )
                    {
                        Btn.setAttribute('onUserUnLoad', Btn.getAttribute('onunload'));
                        Btn.setAttribute('onunload', false);
                    }

                    Btn.addEvents({
                        onSetActive : on_set_active
                    });

                    User.Panel.appendChild( Btn );
                }

                Ajax.getAttribute('onfinish')(result, Ajax);
            }, {
                uid  : this.getId(),
                User : this,
                onfinish : onfinish
            });
        }
        */



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