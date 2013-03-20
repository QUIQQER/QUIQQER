/**
 * Systemchecker
 * Check the system for all needle settings on the server
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Settings
 *
 * @module controls/system/Check
 * @package com.pcsg.qui.js.controls.system.Manager
 * @namespace QUI.controls.system
 *
 * @depricated
 */

define('controls/system/Check', [

    'controls/Control',
    'classes/system/Check'

], function(Control)
{
    "use strict";

    QUI.namespace( 'controls.system' );

    /**
     * @class QUI.controls.system.Check
     */
    QUI.controls.system.Check = new Class({

        Implements: [Control],
        Type      : 'QUI.controls.system.Check',

        initialize : function(Control, Panel)
        {
            this.$Control = Control;
            this.$Panel   = Panel;

            this.load();
        },

        /**
         * Load the Systemchecker
         *
         * @method QUI.controls.system.Check#load
         */
        load : function()
        {
            this.$Panel.Loader.show();

            this.$Control.loadTemplate(function(result, Request)
            {
                this.$Panel.getBody().set('html', result);
                this.$Panel.Loader.hide();
            }.bind( this ));
        }
    });

    return QUI.controls.system.Check;
});