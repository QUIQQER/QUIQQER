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

        Binds : [
            '$onCreate',
            '$onResize'
        ],

        initialize: function(options)
        {
            // defaults
            this.setAttribute( 'title', 'Desktop' );
            //this.setAttribute( 'icon', URL_BIN_DIR +'16x16/apps/background.png' );

            this.setAttribute( 'header', false );
            this.setAttribute( 'footer', false );

            this.parent( options );

            this.$widgets   = [];
            this.$Sortables = null;

            this.addEvents({
                onCreate : this.$onCreate,
                onResize : this.$onResize
            });
        },

        /**
         * event: on create
         */
        $onCreate : function()
        {
            this.resize();

            var Body = this.getBody();

            Body.set({
                html   : '<iframe src="'+ URL_DIR +'quiqqer.php?desktop=1" class="qui-desktop-frame" />',
                styles : {
                    width    : '100%',
                    position : 'relative'
                }
            });
        },

        /**
         * event : on panel resize
         */
        $onResize : function()
        {
            if ( !this.getElm() ) {
                return;
            }

            var Body = this.getBody(),
                size = Body.getParent().getSize();

            if ( !Body.getElement( 'iframe' ) ) {
                return;
            }

            Body.setStyles({
                width  : size.x,
                height : size.y
            });

            Body.getElement( 'iframe' ).setStyles({
                width  : size.x,
                height : size.y
            });
        },

        /**
         * Add a Widget to the Desktop
         *
         * @param {QUI.controls.desktop.Widget} Widget
         * @return {this} self
         */
        addWidget : function(Widget)
        {
            this.$widgets.push( Widget );

            if ( !this.getBody() ) {
                return this;
            }

            Widget.inject( this.getBody() );

            return this;
        }
    });

    return QUI.controls.desktop.panels.Desktop;
});