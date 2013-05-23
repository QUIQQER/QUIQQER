/**
 * The widget list for the desktop control
 * List all available widgets
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/desktop/widget/List
 * @package com.pcsg.qui.js.controls.desktop
 * @namespace QUI.controls.desktop.widget
 */

define('controls/desktop/widget/List', [

    'controls/Control',
    'controls/loader/Loader',
    'controls/desktop/Widget',

    'css!controls/desktop/widget/List.css'

], function(Control)
{
    "use strict";

    QUI.namespace( 'controls.desktop.widget' );

    /**
     * @class QUI.controls.desktop.widget.List
     *
     * @param {jquery gridster} Desktop
     * @param {Option} options
     *
     * @memberof! <global>
     */
    QUI.controls.desktop.widget.List = new Class({

        Extends : Control,
        Type    : 'QUI.controls.desktop.widget.List',

        Binds : [
            'hide',
            'show',
            '$fxComplete',
            '$onEntryClick'
        ],

        options : {

        },

        initialize: function(Wall, options)
        {
            this.init( options );

            this.$Wall    = Wall || null;
            this.$Loader  = new QUI.controls.loader.Loader();
            this.$FX      = null;
        },

        /**
         * Return the Main DomNode Element
         *
         * @return {DOMNode} DIV
         */
        create : function()
        {
            this.$Elm = new Element('div', {
                'class' : 'qui-desktop-widget-list box',
                html    : '<div class="qui-desktop-widget-list-content box">'+
                              '<div class="qui-desktop-widget-list-close box smooth">' +
                                  '<span class="icon-remove-sign"></span> schließen'+
                              '</div>' +
                          '</div>',
                'data-qui' : this.getId()
            });

            this.$Loader.inject( this.$Elm );
            this.$FX = moofx( this.$Elm );


            if ( this.getAttribute( 'styles' ) ) {
                this.$Elm.setStyles( this.getAttribute( 'styles' ) );
            }

            this.$Elm.getElement( '.qui-desktop-widget-list-close' ).addEvent(
                'click',
                this.hide
            );

            return this.$Elm;
        },

        /**
         * Show the widget list
         *
         * @return {this} self
         */
        show : function()
        {
            var Elm     = this.getElm(),
                Parent  = Elm.getParent(),
                size    = Parent.getSize();

            Elm.setStyles({
                visibility : null,
                left       : (size.x + 50) * -1,
                height     : size.y
            });

            Elm.setStyle( 'display', null );

            this.$FX.animate({
                left : 0
            }, {
                callback : this.$fxComplete
            });

            return this;
        },

        /**
         * Hide the widget list
         *
         * @return {this} self
         */
        hide : function()
        {
            var Elm    = this.getElm(),
                Parent = Elm.getParent(),
                size   = Parent.getSize();

            this.$FX.animate({
                left : (size.x + 50) * -1
            }, {
                callback : this.$fxComplete
            });

            return this;
        },

        /**
         * fx complete action
         * if list is closed or opened
         */
        $fxComplete : function()
        {
            if ( this.getElm().getStyle('left').toInt() >= 0 )
            {
                var i, len, Elm;

                var widgets = QUI_DESKTOP_WIDGETS || [],
                    Content = this.$Elm.getElement( '.qui-desktop-widget-list-content' );

                for ( i = 0, len = widgets.length; i < len; i++ )
                {
                    Elm = new Element('div', {
                        'class'   : 'qui-desktop-widget-list-entry box smooth',
                        html      : '<span>'+ (widgets[ i ].title || '') +'</span>',
                        'data-no' : i,
                        events : {
                            click : this.$onEntryClick
                        }
                    }).inject( Content );

                    if ( widgets[ i ].icon )
                    {
                        new Element('span', {
                            'class' : widgets[ i ].icon +' icon'
                        }).inject( Elm );
                    }
                }

                this.fireEvent( 'open', [ this ] );
                return;
            }

            this.fireEvent( 'close', [ this ] );
            this.destroy();
        },

        /**
         * Click to add the widget to the Desktop
         *
         * @param {DOMEvent} event
         */
        $onEntryClick : function(event)
        {
            this.hide();

            var Target = event.target;

            if ( !Target.hasClass( 'qui-desktop-widget-list-entry' ) ) {
                Target = Target.getParent( '.qui-desktop-widget-list-entry' );
            }

            if ( !Target ) {
                return;
            }

            var no = Target.get( 'data-no' );

            if ( typeof QUI_DESKTOP_WIDGETS[ no ] === 'undefined' ) {
                return;
            }

            this.$Wall.appendWidget(
                new QUI.controls.desktop.Widget(
                    QUI_DESKTOP_WIDGETS[ no ]
                )
            );
        }
    });

    return QUI.controls.desktop.widget.List;
});