/**
 * System Update
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 * @requires controls/system/Update
 *
 * @module classes/system/Check
 * @package com.pcsg.qui.js.classes.system.Manager
 * @namespace QUI.classes.system
 */

define('classes/system/Update', [

    'classes/DOM',
    'controls/system/Update'

], function(DOM, Update)
{
    QUI.namespace( 'classes.system' );

    /**
     * @class QUI.classes.project.media.Item
     *
     * @param {QUI.classes.project.Media} Media
     */
    QUI.classes.system.Update = new Class({

        Implements: [DOM],
        Type      : 'QUI.classes.system.Update',

        initialize : function(params)
        {
            this.init( params );
        },

        /**
         * Open the Systemcheck in a panel
         */
        openInPanel : function(Panel)
        {
            new QUI.controls.system.Update( this, Panel );
        },

        /**
         * Load the Update Template
         *
         * @method QUI.classes.system.Update#loadTemplate
         * @param {Function} oncomplete - callback Function
         */
        loadTemplate : function(oncomplete)
        {
            QUI.Ajax.get('ajax_system_update_template', function(result, Request)
            {
                Request.getAttribute('oncomplete')( result, Request );
            }, {
                oncomplete : oncomplete
            });
        },

        /**
         * Get the newest available QUIQQER Version
         *
         * @method QUI.classes.system.Update#getVersion
         *
         * @param {String} plugin - [optional] Pluginname, if no plugin name, it return the cms version
         * @param {Function} oncomplete - callback Function
         */
        getVersion : function(plugin, oncomplete)
        {
            QUI.Ajax.get('ajax_system_getversion', function(result, Request)
            {
                Request.getAttribute('oncomplete')( result, Request );
            }, {
                oncomplete : oncomplete,
                plugin     : plugin
            });
        },

        /**
         * exec the setup
         *
         * @method QUI.classes.system.Update#setup
         * @param {Function} oncomplete - callback Function
         */
        setup : function(oncomplete)
        {
            QUI.Ajax.post('ajax_system_setup', function(result, Request)
            {
                Request.getAttribute('oncomplete')( result, Request );
            }, {
                oncomplete : oncomplete
            });
        },

        /**
         * exec the optimization
         *
         * @method QUI.classes.system.Update#optimize
         * @param {Function} oncomplete - callback Function
         */
        optimize : function(oncomplete)
        {
            QUI.Ajax.post('ajax_system_optimize', function(result, Request)
            {
                Request.getAttribute('oncomplete')( result, Request );
            }, {
                oncomplete : oncomplete
            });
        }
    });

    return QUI.classes.system.Update;
});