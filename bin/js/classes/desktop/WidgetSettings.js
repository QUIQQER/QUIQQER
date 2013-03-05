/**
 * Settings for a Desktop Widget
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.js.classes.desktop
 * @module classes/desktop/WidgetSettings
 * @requires classes/DOM
 *
 * @namespace QUI.classes.desktop
 */

define('classes/desktop/WidgetSettings', ['classes/DOM'], function(DOM)
{
    QUI.namespace('classes.desktop');

    /**
     * @class QUI.classes.desktop.WidgetSettings
     *
     * @param {QUI.classes.desktop.Widget} Widget
     * @param {Object} options
     */
    QUI.classes.desktop.WidgetSettings = new Class({

        Implements: [DOM],

        options : {
            size : 16
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
         * Draw the Settings for a Widget
         *
         * @method QUI.classes.desktop.WidgetSettings#draw
         * @return {this}
         */
        draw : function()
        {
            var size      = this.getAttribute('size') +'x'+ this.getAttribute('size'),

                Container = new Element('div', {
                    'class'  : 'widget-settings box-sizing',
                    styles : {
                        position   : 'absolute',
                        top        : -35,
                        left       : 0,
                        height     : 35,
                        width      : '100%',
                        background : 'rgba(0,0,0, 0.5)',
                        padding    : 5
                    }
                });

            Container.inject( this.$Widget.getElm() );

            // hide bei widget blur
            this.$Widget.addEvent('onBlur', function()
            {
                this.hide();
            }.bind(this));


            this.$FX = new Fx.Morph(Container, {
                duration: 'short'
            });

            new QUI.controls.buttons.Button({
                name   : 'widgetSetting',
                image  : URL_BIN_DIR + size +'/settings.png',
                Widget : this.$Widget,
                events :
                {
                    onMousedown : function(Btn, event)
                    {
                        event.stop();
                        Btn.getAttribute('Widget').fireEvent('openSettings');
                    }
                }
            }).create().inject( Container );

            new QUI.controls.buttons.Button({
                name   : 'widgetClose',
                image  : URL_BIN_DIR + size +'/cancel.png',
                styles : {
                    'float' : 'right'
                },
                Widget : this.$Widget,
                events :
                {
                    onmousedown : function(Btn, event) {
                        Btn.getAttribute('Widget').close();
                    }
                }
            }).create().inject( Container );

            this.el.Container = Container;

            return this;
        },

        /**
         * Destroy the Widget Setting Object
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
         * Show the Widget Setting Object
         *
         * @method QUI.classes.desktop.WidgetSettings#show
         * @return {this}
         */
        show : function()
        {
            if (!this.$FX) {
                return;
            }

            this.$FX.cancel();
            this.$FX.start({
                top : 0
            });

            return this;
        },

        /**
         * Hide the Widget Setting Object
         *
         * @method QUI.classes.desktop.WidgetSettings#hide
         * @return {this}
         */
        hide : function()
        {
            this.$FX.cancel();
            this.$FX.start({
                top : -35
            });

            return this;
        }
    });

    return QUI.classes.desktop.WidgetSettings;
});
