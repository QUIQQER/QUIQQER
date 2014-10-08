
/**
 * Cache Settings
 *
 * @module controls/cache/Settings
 * @author www.pcsg.de (Henning Leutz)
 *
 */

define([

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'Ajax',
    'Locale'

], function(QUI, QUIControl, QUIButton, Ajax, Locale)
{
    "use strict";


    return new Class({

        Extends : QUIControl,
        Type    : 'controls/cache/Settings',

        Binds : [
            '$onImport'
        ],

        initialize : function(Settings)
        {
            this.$Settings = Settings;

            this.addEvents({
                onImport : this.$onImport
            });
        },

        /**
         * event : on inject
         */
        $onImport : function()
        {
            var self        = this,
                ClearButton = this.$Elm.getElement('[name="clearCache"]'),
                PurgeButton = this.$Elm.getElement('[name="purgeCache"]');

            new QUIButton({
                text      : 'Cache leeren',
                textimage : 'icon-trash',
                events    :
                {
                    onClick : function(Btn)
                    {
                        Btn.setAttribute( 'textimage', 'icon-refresh icon-spin' );

                        self.clear(function() {
                            Btn.setAttribute( 'textimage', 'icon-trash' );
                        });
                    }
                }
            }).replaces( ClearButton );

            new QUIButton({
                text      : 'Cache säubern',
                textimage : 'fa fa-paint-brush',
                events    :
                {
                    onClick : function(Btn)
                    {
                        Btn.setAttribute( 'textimage', 'icon-icon-trash icon-spin' );

                        self.purge(function() {
                            Btn.setAttribute( 'textimage', 'fa fa-paint-brush' );
                        });
                    }
                }
            }).replaces( PurgeButton );
        },

        /**
         * Clear the cache
         *
         * @param {Function} callback - [optional] callback function
         */
        clear : function(callback)
        {
            var params = {
                plugins : true,
                compile : true
            };

            if ( this.$Elm )
            {
                params.compile = this.$Elm.getElement('[name="compile"]').checked;
                params.plugins = this.$Elm.getElement('[name="plugins"]').checked;
            }

            Ajax.get('ajax_system_cache_clear', function()
            {
                if ( typeof callback !== 'undefined' ) {
                    callback();
                }

                QUI.getMessageHandler(function(QUI)
                {
                    QUI.addSuccess(
                        Locale.get( 'quiqqer/system', 'message.clear.cache.successful' )
                    );
                });
            }, {
                params : JSON.encode( params )
            });
        },

        /**
         * Purge the cache
         *
         * @param {Function} callback - [optional] callback function
         */
        purge : function(callback)
        {
            Ajax.get('ajax_system_cache_purge', function()
            {
                if ( typeof callback !== 'undefined' ) {
                    callback();
                }

                QUI.getMessageHandler(function(QUI)
                {
                    QUI.addSuccess(
                        Locale.get( 'quiqqer/system', 'message.clear.cache.successful' )
                    );
                });
            });
        }
    });

});