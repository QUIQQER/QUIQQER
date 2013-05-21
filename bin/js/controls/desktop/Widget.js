/**
 * A desktop widgets
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/desktop/Widget
 * @package com.pcsg.qui.js.controls
 * @namespace QUI.controls.desktop
 *
 * @event onRefresh [ {this} ]
 */

define('controls/desktop/Widget', [

    'controls/Control',
    'controls/loader/Loader',

    'css!controls/desktop/Widget.css'

], function(Control)
{
    "use strict";

    QUI.namespace( 'controls.desktop' );

    /**
     * @class QUI.controls.desktop.Widget
     *
     * @memberof! <global>
     */
    QUI.controls.desktop.Widget = new Class({

        Extends : Control,
        Type    : 'QUI.controls.desktop.Widget',

        Binds : [
            'destroy',
            'refresh',

            '$loadContent',
            '$onDestroy'
        ],

        options : {
            width   : 2,
            height  : 2,
            title   : '',
            refresh : false,
            content : {},
            require : [] // require files, theses files would be load on opening the widget
        },

        initialize: function(options)
        {
            this.init( options );

            this.Loader = new QUI.controls.loader.Loader();

            this.addEvents({
                onDestroy : this.$onDestroy
            });
        },

        /**
         * Return the Main DomNode Element
         *
         * @return {DOMNode} DIV
         */
        create : function()
        {
            this.$Elm = new Element('li', {
                'class' : 'qui-desktop-widget box smooth',
                html    : '<div class="qui-desktop-widget-content box"></div>' +
                          '<div class="qui-desktop-widget-footer box">'+
                              '<span class="btn icon-trash smooth radius5"></span>' +
                              '<span class="btn icon-pencil smooth radius5"></span>' +
                          '</div>',
                'data-qui' : this.getId()
            });

            this.Loader.inject( this.$Elm );

            if ( this.getAttribute( 'refresh' ) )
            {
                new Element('span', {
                    'class' : 'btn icon-refresh smooth radius5',
                    styles : {
                        'float' : 'left'
                    },
                    events : {
                        click : this.refresh
                    }
                }).inject(
                    this.$Elm.getElement( '.qui-desktop-widget-footer' )
                );
            }

            if ( this.getAttribute( 'styles' ) ) {
                this.$Elm.setStyles( this.getAttribute( 'styles' ) );
            }

            return this.$Elm;
        },

        /**
         * Refresh the widget
         */
        refresh : function()
        {
            if ( !this.getAttribute( 'refresh' ) ) {
                return;
            }

            this.$loadContent();
            this.fireEvent( 'refresh', [ this ] );
        },

        /**
         * Set the content of the widget
         *
         * @param {String} html - html content
         * @return {this} self
         */
        setContent : function(html)
        {
            if ( !this.$Elm ) {
                return this;
            }

            var Content = this.$Elm.getElement( '.qui-desktop-widget-content' );

            if ( Content ) {
                Content.set( 'html', html );
            }

            return this;
        },

        /**
         * Append the Element to a gridster instance
         *
         * @param {jquery gridster} Gridster
         * @param {Integer} col - Column pos
         * @param {Integer} row - Row pos
         *
         * @return {this} self
         */
        appendToGridster : function(Gridster, col, row)
        {
            this.Loader.show();

            col = col || 1;
            row = row || 1;

            var Li      = this.create(),
                Content = this.$Elm.getElement( '.qui-desktop-widget-content' ),
                Footer  = this.$Elm.getElement( '.qui-desktop-widget-footer' );

            Gridster.add_widget(
                Li,
                this.getAttribute('width'),
                this.getAttribute('height'),
                col,
                row
            );

            Content.setStyle(
                'height',
                Li.getSize().y - Footer.getSize().y
            );

            Footer.getElement( '.icon-trash' ).addEvents({
                click : this.destroy
            });


            if ( !this.getAttribute('require').length ||
                 typeOf( this.getAttribute('height') ) != 'array' )
            {
                this.$loadContent();
                return this;
            }


            var Control = this;

            require( Control.getAttribute('require'), function() {
                Control.$loadContent();
            });

            return this;
        },

        /**
         * Loads the content of the widget
         */
        $loadContent : function()
        {
            this.Loader.show();

            var content = this.getAttribute('content');

            if ( content.type == 'ajax' &&
                 content.func !== '' )
            {
                var Control = this;

                QUI.Ajax.get( content.func, function( result )
                {
                    Control.setContent( result );
                    Control.Loader.hide();
                });

                return;
            }


            this.Loader.hide();
        },

        /**
         * event : on Destroy
         */
        $onDestroy : function()
        {
            this.getParent().getDesktop().remove_widget(
                this.getElm()
            );
        }
    });

    return QUI.controls.desktop.Widget;
});