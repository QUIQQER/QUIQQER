/**
 * System Package Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 * @requires controls/system/Packages
 *
 * @module classes/system/Packages
 * @package com.pcsg.qui.js.classes.system.Packages
 * @namespace QUI.classes.system
 */

define('classes/system/Packages', [

    'classes/DOM',
    'controls/system/Packages'

], function(DOM, Update)
{
    QUI.namespace( 'classes.system' );

    /**
     * @class QUI.classes.projects.media.Item
     *
     * @param {QUI.classes.projects.Media} Media
     */
    QUI.classes.system.Packages = new Class({

        Implements: [DOM],
        Type      : 'QUI.classes.system.Packages',

        initialize : function(params)
        {
            this.init( params );
        },

        /**
         * Open the Systemcheck in a panel
         */
        openInPanel : function(Panel)
        {
            new QUI.controls.system.Packages( this, Panel );
        },

        /**
         * Get the Packagelist
         *
         * @method QUI.classes.system.Packages#getList
         * @param {Function} oncomplete - callback Function
         */
        getList : function(oncomplete)
        {
            QUI.Ajax.get('ajax_system_packages_list', function(result, Request)
            {
                if ( Request.getAttribute('oncomplete') ) {
                    Request.getAttribute('oncomplete')( result, Request );
                }
            }, {
                oncomplete : oncomplete
            });
        }
    });

    return QUI.classes.system.Packages;
});