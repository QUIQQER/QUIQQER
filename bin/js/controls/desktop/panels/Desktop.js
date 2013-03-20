/**
 * A desktop panel
 *
 * A panel where you can organize widgets
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/desktop/panels/Desktop
 * @package com.pcsg.qui.js.controls.desktop
 * @namespace QUI.controls.desktop.panels
 */

define('controls/desktop/panels/Desktop', [

    'controls/desktop/Panel',
    'controls/loader/Loader',

    'css!controls/desktop/panels/Desktop.css'

], function(QUI_Panel)
{
    "use strict";

    QUI.namespace( 'controls.desktop.panels' );

    /**
     * @class QUI.controls.desktop.panels.Desktop
     */
    QUI.controls.desktop.panels.Desktop = new Class({

        Extends : QUI.controls.desktop.Panel,
        Type    : 'QUI.controls.desktop.panels.Desktop',

        initialize: function(options)
        {
            this.init( options );

            // defaults
            this.setAttribute( 'header', false );
            this.setAttribute( 'footer', false );

            this.setAttribute( 'title', 'Desktop' );
            this.setAttribute( 'icon', URL_BIN_DIR +'16x16/desktop.png' );

            this.Loader = new QUI.controls.loader.Loader();

            this.$Elm       = null;
            this.$Header    = null;
            this.$Footer    = null;
            this.$Content   = null;
            this.$Container = null;

            this.addEvent('onDrawEnd', function()
            {
                this.$create();
                this.fireEvent( 'load', [ this ] );
            }.bind( this ));
        },

        /**
         * Internal creation
         */
        $create : function()
        {
            var bookmark;
            var Body = this.getBody();

            this.$Container = new Element( 'div' ).inject( Body );
        }
    });

    return QUI.controls.desktop.panels.Desktop;
});