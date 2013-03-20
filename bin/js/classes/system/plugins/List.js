/**
 * Plugin list
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 * @requires controls/system/plugins/List
 *
 * @module classes/system/plugins/List
 * @package com.pcsg.qui.js.classes.system.Manager
 * @namespace QUI.classes.system.plugins
 */

define('classes/system/plugins/List', [

    'classes/DOM',
    'controls/system/plugins/List'

], function(DOM, List)
{
    "use strict";

    QUI.namespace( 'classes.system.plugins' );

    /**
     * @class QUI.classes.system.List
     *
     * @param {Object} params - Properties / attributes
     */
    QUI.classes.system.plugins.List = new Class({

        Implements: [DOM],
        Type      : 'QUI.classes.system.plugins.List',

        initialize : function(params)
        {
            this.init( params );
        },

        /**
         * Open the Systemcheck in a panel
         *
         * @method QUI.classes.system.List#openIn
         *
         * @param {DomNode} DomNode
         * @param {MUI.Apppanel} Panel
         * @param {QUI.controls.system.Plugins} Plugins
         */
        openIn : function(DomNode, Panel, Plugins)
        {
            new QUI.controls.system.plugins.List( this, DomNode, Panel, Plugins );
        },

        /**
         * Return all available plugins in the system
         *
         * @method QUI.classes.system.List#getList
         * @param {Function} oncomplete - callback Function
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
        }
    });

    return QUI.classes.system.plugins.List;
});