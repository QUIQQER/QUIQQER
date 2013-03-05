/**
 * Systemchecker
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 * @requires controls/system/Check
 *
 * @module classes/system/Check
 * @package com.pcsg.qui.js.classes.system.Manager
 * @namespace QUI.classes.system
 */

define('classes/system/Check', [

    'classes/DOM',
    'controls/system/Check'

], function(DOM, Check)
{
    QUI.namespace( 'classes.system' );

    /**
     * @class QUI.classes.project.media.Item
     *
     * @param {Object} params - Properties / attributes
     */
    QUI.classes.system.Check = new Class({

        Implements: [DOM],
        Type      : 'QUI.classes.system.Check',

        initialize : function(params)
        {
            this.init( params );
        },

        /**
         * Open the Systemcheck in a panel
         *
         * @method QUI.classes.system.Check#openInPanel
         * @param {MUI.Apppanel} Panel
         */
        openInPanel : function(Panel)
        {
            new QUI.controls.system.Check( this, Panel );
        },

        /**
         * Load the Systemcheck into a DOMNode Element
         *
         * @method QUI.classes.system.Check#loadTemplate
         * @param {Function} oncomplete - callback Function
         */
        loadTemplate : function(oncomplete)
        {
            QUI.Ajax.get('ajax_system_check', function(result, Request)
            {
                Request.getAttribute('oncomplete')( result, Request );
            }, {
                oncomplete : oncomplete
            });
        }
    });

    return QUI.classes.system.Check;
});