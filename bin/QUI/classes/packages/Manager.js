
/**
 * Package manager
 *
 * @module classes/packages/Manager
 * @author www.pcsg.de (Henning Leutz)
 */

define([

    'qui/QUI',
    'qui/classes/DOM',

    'Ajax',
    'Locale'

], function(QUI, QDOM, Ajax, Locale)
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
         * @param {String} pkg - [optional] Package name, if no package name given, complete setup are executed
         * @param {Function} callback - [optional] callback function
         */
        setup : function(pkg, callback)
        {
            Ajax.post('ajax_system_setup', function(result, Request)
            {
                if ( typeof callback !== 'undefined' ) {
                    callback();
                }

            }, {
                'package' : pkg || false
            });
        },

        /**
         * Execute a system or plugin update
         *
         * @param {String} pkg - [optional] Package name, if no package name given, complete update are executed
         * @param {Function} callback - [optional] callback function
         */
        update : function(pkg, callback)
        {
            Ajax.post('ajax_system_update', function(result)
           {
               if ( typeof callback !== 'undefined' ) {
                   callback( result );
               }
           }, {
               'package' : pkg || false
           });
        },

        /**
         * Check, if updates are available
         *
         * @param {Function} callback - [optional] callback function
         */
        checkUpdate : function(callback)
        {
            Ajax.get( 'ajax_system_update_check', callback );
        },

        /**
         * Return the data of one package
         *
         * @param {String} pkg        - Package name
         * @param {Function} onfinish - callback function
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

                if ( typeof callback !== 'undefined' ) {
                    callback( result );
                }
            }, {
                'package' : pkg
            });
        }

    });

});