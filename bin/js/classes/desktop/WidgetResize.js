/**
 * Resize option for a Desktop Widget
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.js.classes.desktop
 * @module classes/desktop/WidgetResize
 * @requires classes/DOM
 *
 * @namespace QUI.classes.desktop
 */

define('classes/desktop/WidgetResize', [

    'classes/DOM'

], function(DOM)
{
    "use strict";

    QUI.namespace( 'classes.desktop' );

    /**
     * @class QUI.classes.desktop.WidgetResize
     *
     * @param {QUI.classes.desktop.Widget} Widget
     * @param {Object} options
     */
    QUI.classes.desktop.WidgetResize = new Class({

        Implements: [DOM],

        options : {

        },

        $FX     : null,
        $Widget : null,

        initialize : function(Widget, options)
        {
            this.init( options );
            this.el = {};

            this.$Widget = Widget;
        },

        /**
         * Draw the Resizer for a Widget
         *
         * @method QUI.classes.desktop.WidgetResize#draw
         * @return {this}
         */
        draw : function()
        {
            var Container = new Element('div', {
                'class'  : 'widget-resize',
                styles : {
                    position   : 'absolute',
                    bottom     : 0,
                    right      : 0,
                    height     : 50,
                    width      : 50,
                    background : 'url("'+ URL_BIN_DIR +'22x22/resize.gif") no-repeat right bottom',
                    outline    : 0,
                    display    : 'none',
                    cursor     : 'se-resize'
                },
                events :
                {
                    mouseenter : function()
                    {

                    },

                    mousedown : function(event)
                    {
                        //event.stop();
                        var Elm  = this.$Widget.getElm(),
                            Drag = Elm.makeResizable({
                                stopPropagation : true,
                                limit : {
                                    x: [50],
                                    y: [50]
                                },
                                onComplete : function(Elm, event)
                                {
                                    var height = Elm.getSize().y,
                                        width  = Elm.getSize().x;

                                    this.$Widget.setAttribute('width', width);
                                    this.$Widget.setAttribute('height', height);

                                }.bind(this),

                                onStart : function(Elm)
                                {
                                    var DragElm = Elm.getParent().getElement('.desktop-widget-dragdrop');

                                    if (DragElm) {
                                        DragElm.setStyle('display', 'none');
                                    }
                                }
                            });

                        Elm.addEvent('mouseup', function()
                        {
                            this.detach();
                            document.body.focus();
                        }.bind(Drag));

                    }.bind( this )
                }
            });

            Container.inject( this.$Widget.getElm() );

            this.$FX = new Fx.Morph(Container, {
                duration: 'short'
            });

            this.$Widget.addEvent('onBlur', function()
            {
                this.hide();
            }.bind(this));

            this.el.Container = Container;

            return this;
        },

        /**
         * Destroy the Widget Resizer Object
         *
         * @method QUI.classes.desktop.WidgetSettings#destroy
         * @return {this}
         */
        destroy : function()
        {
            for (var i in this.el) {
                this.el[i].destroy();
            }

            return this;
        },

        /**
         * Show the Widget Resizer Object
         *
         * @method QUI.classes.desktop.WidgetSettings#show
         * @return {this}
         */
        show : function()
        {
            if ( !this.$FX ) {
                return this;
            }

            this.el.Container.setStyles({
                opacity : 0,
                display : 'inline'
            });

            this.$FX.cancel();
            this.$FX.start({
                opacity : 1
            });

            return this;
        },

        /**
         * Hide the Widget Resizer Object
         *
         * @method QUI.classes.desktop.WidgetSettings#hide
         * @return {this}
         */
        hide : function()
        {
            this.$FX.cancel();
            this.$FX.start({
                opacity : 0
            });

            return this;
        }
    });

    return QUI.classes.desktop.WidgetResize;
});
