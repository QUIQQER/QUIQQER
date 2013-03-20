/**
 * Desktop Widget
 *
 * @@author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.js.classes.desktop
 * @module classes/desktop/Widget
 *
 * @requires classes/DOM
 * @requires classes/desktop/WidgetSettings
 * @requires classes/desktop/WidgetResize
 *
 * @namespace QUI.classes.desktop
 */

define('classes/desktop/Widget', [

    'classes/DOM',
    'classes/desktop/WidgetSettings',
    'classes/desktop/WidgetResize',

    'css!classes/desktop/Widget.css'

], function(DOM, WidgetSettings, WidgetResize)
{
    "use strict";

    QUI.namespace( 'classes.desktop' );

    /**
     * @class QUI.classes.desktop.Widget
     *
     * @fires onDrawEnd
     * @fires onDrawBegin
     * @fires onClose
     * @fires onFocus
     * @fires onBlur
     *
     * @param {Object} options
     * @param {QUI.classes.desktop.Desktop} Desktop
     */
    QUI.classes.desktop.Widget = new Class({

        Implements: [DOM],

        options : {
            title     : 'Widget',
            content   : '',
            width     : 400,
            height    : 240,
            resizable : true
        },

        $Desktop   : null,
        $Settings  : null,
        $Resize    : null,
        $mouseover : false,
        $FX        : null,

        initialize : function(options, Desktop)
        {
            this.init( options );
            this.el = {};

            this.$Settings = new QUI.classes.desktop.WidgetSettings(this);
            this.$Resize   = new QUI.classes.desktop.WidgetResize(this);
            this.$Desktop  = Desktop || null;

            this.addEvent('onSetAttribute', function(k, v)
            {
                var Elm = this.getElm();

                if (!Elm) {
                    return;
                }

                if (k === 'width' || k === 'height') {
                    this.resize(k, v);
                }

            }.bind(this));
        },

        /**
         * Destroy the Widget Object
         *
         * @method QUI.classes.desktop.Widget#destroy
         */
        destroy : function()
        {
            this.$Settings.destroy();
            this.$Resize.destroy();

            this.el.Elm.set('html', '');

            new Fx.Morph(this.el.Elm,
            {
                onComplete : function()
                {
                    for (var i in this.el) {
                        this.el[i].destroy();
                    }
                }.bind(this)
            }).start({
                width  : 0,
                height : 0
            });
        },

        /**
         * Create the Widget DOMNode Object
         *
         * @method QUI.classes.desktop.Widget#create
         * @return {DOMNode}
         */
        create : function()
        {
            this.fireEvent('drawBegin', [this]);

            var Elm = new Element('div', {
                'class'  : 'desktop-widget radius5',
                html     : '<div class="inner-container box-sizing"></div>',
                tabindex : -1,
                styles   : {
                    display : 'none',
                    opacity : 0,
                    outline : 0
                },
                events :
                {
                    mouseenter : function() {
                        this.mouseenter();
                    }.bind(this),

                    mouseleave : function() {
                        this.mouseleave();
                    }.bind(this),

                    blur : function() {
                        this.fireEvent('blur', [this]);
                    }.bind(this),

                    focus : function() {
                        this.fireEvent('focus', [this]);
                    }.bind(this)
                }
            });

            this.el.Elm = Elm;
            this.resize();

            this.$FX = new Fx.Morph( Elm );
            this.$Settings.draw();
            this.$Resize.draw();

            this.fireEvent('drawEnd', [this]);

            return this.el.Elm;
        },

        /**
         * Resize the Widget
         *
         * @method QUI.classes.desktop.Widget#resize
         *
         * @param k - key (width || height)
         * @param v - value
         * @return {this}
         */
        resize : function(k, v)
        {
            k = k || false;

            var width  = this.getAttribute('width'),
                height = this.getAttribute('height');


            if (k === 'width') {
                width = v;
            }

            if (k === 'height') {
                height = v;
            }

            this.getElm().setStyles({
                width  : width,
                height : height
            });

            this.getElm().getElement('.inner-container').setStyles({
                width  : width -10,
                height : height -10
            });

            return this;
        },

        /**
         * Get the DOMNode Element
         *
         * @method QUI.classes.desktop.Widget#getElm
         * @return {DOMNode}
         */
        getElm : function()
        {
            return this.el.Elm;
        },

        /**
         * Get the DOMNode Body Element
         *
         * @method QUI.classes.desktop.Widget#getBody
         * @return {DOMNode}
         */
        getBody : function()
        {
            return this.getElm().getElement('.inner-container');
        },

        /**
         * Get the Desktop Parent Element
         *
         * @method QUI.classes.desktop.Widget#getDesktop
         * @return {QUI.classes.desktop.Desktop}
         */
        getDesktop : function()
        {
            return this.$Desktop;
        },

        /**
         * Shows the Widget
         *
         * @method QUI.classes.desktop.Widget#show
         * @return {this}
         */
        show : function()
        {
            if (this.$FX) {
                this.$FX.cancel();
            }

            this.$FX.start({
                display : 'inline',
                opacity : 1
            });

            return this;
        },

        /**
         * Hide the Widget
         *
         * @method QUI.classes.desktop.Widget#hide
         * @return {this}
         */
        hide : function()
        {
            this.$FX.start({
                display : 'none',
                opacity : 1
            });

            return this;
        },

        /**
         * Close the Widget
         *
         * @method QUI.classes.desktop.Widget#close
         * @return {this}
         */
        close : function()
        {
            this.fireEvent('close', [this]);
            this.destroy();

            return this;
        },

        /**
         * Set the foxus to the Widget
         *
         * @method QUI.classes.desktop.Widget#close
         * @return {this}
         */
        focus : function()
        {
            this.getElm().focus();

            return this;
        },

        /**
         * Mouseenter - Hover Effekt
         *
         * If the Desktop is unlock and the Widget have Settings.
         * The Resizer and Settings are showed after 500 miliseconds,
         *
         * @method QUI.classes.desktop.Starter#mouseenter
         * @return {this}
         */
        mouseenter : function()
        {
            this.$mouseover = true;

            if (this.$Desktop &&
                this.$Desktop.getAttribute('lock-widget-settings') === true)
            {
                return this;
            }

            this.getElm().addClass('desktop-widget-hover');

            if (this.getAttribute('resizable')) {
                this.$Resize.show();
            }

            (function()
            {
                if (!this.$mouseover) {
                    return;
                }

                this.$Settings.show();
                this.focus();

            }).delay(500, this);

            return this;
        },

        /**
         * Mouseleave - Hover Effekt
         *
         * Hide Settings and Resizer
         *
         * @method QUI.classes.desktop.Starter#mouseleave
         * @return {this}
         */
        mouseleave : function()
        {
            this.$mouseover = false;
            this.getElm().removeClass('desktop-widget-hover');

            this.$Resize.hide();

            return this;
        }
    });

    return QUI.classes.desktop.Widget;
});
