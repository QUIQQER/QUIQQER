/**
 * Plugin Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 * @requires controls/system/Plugins
 *
 * @module classes/system/Plugins
 * @package com.pcsg.qui.js.classes.system.Manager
 * @namespace QUI.classes.system
 */

define('classes/system/Plugins', [

    'classes/DOM',
    'controls/system/Plugins'

], function(DOM, Check)
{
    QUI.namespace( 'classes.system' );

    /**
     * @class QUI.classes.system.Plugins
     *
     * @param {Object} params - Control param
     */
    QUI.classes.system.Plugins = new Class({

        Implements: [DOM],
        Type      : 'QUI.classes.system.Plugins',

        initialize : function(params)
        {
            this.init( params );
        },

        /**
         * Open the plugin manager in a panel
         *
         * @method QUI.classes.system.Plugins#openInPanel
         */
        openInPanel : function(Panel)
        {
            new QUI.controls.system.Plugins( this, Panel );
        },

        /**
         * Get the complete Plugin list
         *
         * @param {Function} oncomplete - callback function
         */
        getList : function(oncomplete)
        {
            QUI.Ajax.get('ajax_system_plugins_list', function(result, Request)
            {
                if ( Request.getAttribute('oncomplete') ) {
                    Request.getAttribute('oncomplete')( result, Request );
                }
            }, {
                oncomplete : oncomplete
            });
        },

        /**
         * Activate a plugin
         *
         * @param {String} plugin     - Plugin name
         * @param {Function} onfinish - callback function
         */
        activate : function(plugin, onfinish)
        {
            QUI.Ajax.post('ajax_system_plugins_activate', function(result, Request)
            {
                if ( Request.getAttribute('onfinish') ) {
                    Request.getAttribute('onfinish')( result, Request );
                }
            }, {
                onfinish : onfinish,
                plugin   : plugin
            });
        },

        /**
         * Deactivate a plugin
         *
         * @param {String} plugin     - Plugin name
         * @param {Function} onfinish - callback function
         */
        deactivate : function(plugin, onfinish)
        {
            QUI.Ajax.post('ajax_system_plugins_deactivate', function(result, Request)
            {
                if ( Request.getAttribute('onfinish') ) {
                    Request.getAttribute('onfinish')( result, Request );
                }
            }, {
                onfinish : onfinish,
                plugin   : plugin
            });
        },

        /**
         * Return all new Versions of the Plugin
         * Make a request to the Updateserver
         *
         * @param {String} plugin     - Plugin name
         * @param {Function} onfinish - callback function
         */
        getVersions : function(plugin, onfinish)
        {
            QUI.Ajax.get('ajax_system_plugins_versions', function(result, Request)
            {
                if ( Request.getAttribute('onfinish') ) {
                    Request.getAttribute('onfinish')( result, Request );
                }
            }, {
                onfinish : onfinish,
                plugin   : plugin
            });
        }
    });

    return QUI.classes.system.Plugins;
});