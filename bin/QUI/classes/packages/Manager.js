
/**
 * Package manager
 *
 * @module classes/packages/Manager
 * @author www.pcsg.de (Henning Leutz)
 */

define('classes/packages/Manager', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax'

], function(QUI, QDOM, Ajax)
{
    "use strict";


    return new Class({

        Extends : QDOM,
        Type    : 'classes/packages/Manager',

        initialize : function(options)
        {
            this.parent( options );

            this.$packages = {};
        },

        /**
         * Execute a system or plugin setup
         *
         * @param {String} [pkg] - (optional), Package name, if no package name given, complete setup are executed
         * @param {Function} [callback] - (optional), callback function
         */
        setup : function(pkg, callback)
        {
            Ajax.post('ajax_system_setup', function()
            {
                if ( typeOf(callback) === 'function' ) {
                    callback();
                }

            }, {
                'package' : pkg || false
            });
        },

        /**
         * Execute a system or plugin update
         *
         * @param {String} [pkg] - (optional), Package name, if no package name given, complete update are executed
         * @param {Function} [callback] - (optional), callback function
         */
        update : function(pkg, callback)
        {
            Ajax.post('ajax_system_update', function(result)
            {
                if ( typeOf(callback) === 'function' ) {
                    callback( result );
                }
            }, {
                'package' : pkg || false
            });
        },

        /**
         * Execute a system or plugin update with an internal local server
         *
         * @param {Function} [callback]
         */
        updateWithLocalServer : function(callback)
        {
            Ajax.post('ajax_system_updateWithLocalServer', function(result)
            {
                if ( typeOf(callback) === 'function' ) {
                    callback( result );
                }
            });
        },

        /**
         * Check, if updates are available
         *
         * @param {Function} callback - callback function
         */
        checkUpdate : function(callback)
        {
            Ajax.get( 'ajax_system_update_check', callback );
        },

        /**
         * Return the data of one package
         *
         * @param {String} pkg        - Package name
         * @param {Function} callback - callback function
         */
        getPackage : function(pkg, callback)
        {
            if ( this.$packages[ pkg ] )
            {
                callback( this.$packages[ pkg ] );
                return;
            }

            var self = this;

            Ajax.get('ajax_system_packages_get', function(result)
            {
                self.$packages[ pkg ] = result;

                if ( typeOf(callback) === 'function' ) {
                    callback( result );
                }
            }, {
                'package' : pkg
            });
        },

        /**
         * Change / Set the Version for a package
         *
         * @param {String} pkg - Name of the package
         * @param {String} version - Version of the package
         * @param {Function} callback - callback function
         */
        setVersion : function(pkg, version, callback)
        {
            var self = this;

            Ajax.post('ajax_system_packages_setVersion', function(result)
            {
                self.update(pkg, function()
                {
                    if ( typeOf(callback) === 'function' ) {
                        callback( result );
                    }
                });

            }, {
                packages : JSON.encode( pkg ),
                version  : version
            });
        }
    });

});
