/**
 * List of new plugins
 *
 * Makes request to the update server and ask to new plugins
 * and can install new plugins
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 * @requires controls/system/plugins/DeletePlugins
 *
 * @module classes/system/plugins/DeletePlugins
 * @package com.pcsg.qui.js.classes.system.Manager
 * @namespace QUI.classes.system.plugins
 */

define('classes/system/plugins/DeletePlugins', [

    'classes/DOM',
    'controls/system/plugins/DeletePlugins'

], function(DOM)
{
    "use strict";

    QUI.namespace( 'classes.system.plugins' );

    /**
     * @class QUI.classes.system.DeletePlugins
     *
     * @param {Object} params - Properties / attributes
     */
    QUI.classes.system.plugins.DeletePlugins = new Class({

        Implements: [DOM],
        Type      : 'QUI.classes.system.plugins.DeletePlugins',

        initialize : function(params)
        {
            this.init( params );
        },

        /**
         * Open the new plugin list in a panel
         *
         * @method QUI.classes.system.plugins.DeletePlugins#openIn
         *
         * @param {DomNode} DomNode
         * @param {MUI.Apppanel} Panel
         * @param {QUI.controls.system.Plugins} Plugins
         */
        openIn : function(DomNode, Panel, Plugins)
        {
            new QUI.controls.system.plugins.DeletePlugins(
                this,
                DomNode,
                Panel,
                Plugins
            );
        },

        /**
         * Return a list of new plugins from the update server
         *
         * @method QUI.classes.system.DeletePlugins#getList
         * @param {Function} oncomplete - callback Function
         */
        getList : function(oncomplete)
        {
            QUI.Ajax.get('ajax_system_plugins_delete_list', function(result, Request)
            {
                if ( Request.getAttribute('oncomplete') ) {
                    Request.getAttribute('oncomplete')( result, Request );
                }
            }, {
                oncomplete : oncomplete
            });
        }
    });

    return QUI.classes.system.plugins.DeletePlugins;
});