/**
 * List of new plugins
 * Makes request to the update server and can install new plugins
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 * @requires controls/system/plugins/NewPlugins
 *
 * @module classes/system/plugins/NewPlugins
 * @package com.pcsg.qui.js.classes.system.Manager
 * @namespace QUI.classes.system.plugins
 */

define('classes/system/plugins/NewPlugins', [

    'classes/DOM',
    'controls/system/plugins/NewPlugins'

], function(DOM)
{
    "use strict";

    QUI.namespace( 'classes.system.plugins' );

    /**
     * @class QUI.classes.system.NewPlugins
     *
     * @param {Object} params - Properties / attributes
     */
    QUI.classes.system.plugins.NewPlugins = new Class({

        Implements: [DOM],
        Type      : 'QUI.classes.system.plugins.NewPlugins',

        initialize : function(params)
        {
            this.init( params );
        },

        /**
         * Open the new plugin list in a panel
         *
         * @method QUI.classes.system.plugins.NewPlugins#openIn
         *
         * @param {DomNode} DomNode
         * @param {MUI.Apppanel} Panel
         * @param {QUI.controls.system.Plugins} Plugins
         */
        openIn : function(DomNode, Panel, Plugins)
        {
            new QUI.controls.system.plugins.NewPlugins( this, DomNode, Panel, Plugins );
        },

        /**
         * Return a list of new plugins from the update server
         *
         * @method QUI.classes.system.NewPlugins#getList
         * @param {Function} oncomplete - callback Function
         */
        getList : function(oncomplete)
        {
            QUI.Ajax.get('ajax_system_plugins_new_list', function(result, Request)
            {
                if ( Request.getAttribute('oncomplete') ) {
                    Request.getAttribute('oncomplete')( result, Request );
                }
            }, {
                oncomplete : oncomplete
            });
        }
    });

    return QUI.classes.system.plugins.NewPlugins;
});