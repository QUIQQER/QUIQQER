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
            'open',
            'close',

            '$loadContent',
            '$onDestroy'
        ],

        options : {
            name    : '',
            open    : false,
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

            this.$Content  = null;
            this.$TitleBox = null;
            this.$Footer   = null;
            this.$TitleFX  = null;

            this.addEvents({
                onDestroy : this.$onDestroy
            });
        },

        /**
         * Serialize the widget data, for saving the desktop
         *
         * @return {Object}
         */
        serialize : function()
        {
            var attr = this.getAttributes(),
                Elm  = this.getElm();

            attr.col   = Elm.get( 'data-col' );
            attr.row   = Elm.get( 'data-row' );
            attr.sizex = Elm.get( 'data-sizex' );
            attr.sizey = Elm.get( 'data-sizey' );

            return attr;
        },

        /**
         * Return the Main DomNode Element
         *
         * @return {DOMNode} DIV
         */
        create : function()
        {
            var Control = this;

            this.$Elm = new Element('li', {
                'class' : 'qui-desktop-widget box smooth',
                html    : '<div class="qui-desktop-widget-content box"></div>' +
                          '<div class="qui-desktop-widget-title box"></div>' +
                          '<div class="qui-desktop-widget-footer box">'+
                              '<span class="btn opener icon-check-empty smooth radius5"></span>' +
                              '<span class="btn delete icon-trash smooth radius5"></span>' +
                              '<span class="btn edit icon-pencil smooth radius5"></span>' +
                          '</div>',
                'data-quiid' : this.getId()
            });

            this.Loader.inject( this.$Elm );

            this.$Content  = this.$Elm.getElement( '.qui-desktop-widget-content' );
            this.$TitleBox = this.$Elm.getElement( '.qui-desktop-widget-title' );
            this.$Footer   = this.$Elm.getElement( '.qui-desktop-widget-footer' );
            this.$Opener   = this.$Footer.getElement( '.opener' );

            // title events
            this.$TitleBox.set({
                html : this.getAttribute( 'title' ) +
                       '<span class="icon icon-play-circle"></span>',
                events : {
                    click : this.open
                }
            });

            this.$TitleFX = moofx( this.$TitleBox );

            this.$Opener.setStyle( 'float', 'left' );

            this.$Opener.addEvent('click', function()
            {
                if ( Control.getAttribute( 'open' ) )
                {
                    Control.close();
                } else
                {
                    Control.open();
                }
            });

            // is widget refreshable
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
                    this.$Footer
                );
            }

            if ( this.getAttribute( 'styles' ) ) {
                this.$Elm.setStyles( this.getAttribute( 'styles' ) );
            }

            return this.$Elm;
        },

        /**
         * Open the widget and load it
         *
         * @return {this} self
         */
        open : function()
        {
            var Control = this;

            this.setAttribute( 'open', true );

            this.$TitleFX.animate({
                left : (this.$TitleBox.getSize().x + 10) * -1
            }, function()
            {
                Control.Loader.show();

                Control.$Opener.removeClass( 'icon-check-empty' );
                Control.$Opener.addClass( 'icon-check' );

                if ( Control.$Footer.getElement('.icon-refresh') )
                {
                    Control.$Footer.getElement('.icon-refresh')
                                   .setStyle( 'display', '' );
                }

                if ( !Control.getAttribute('require').length ||
                     typeOf( Control.getAttribute('height') ) != 'array' )
                {
                    Control.$loadContent();
                    return;
                }

                require( Control.getAttribute('require'), function() {
                    Control.$loadContent();
                });
            });

            return this;
        },

        /**
         * Close the Widget
         *
         * @return {this} self
         */
        close : function()
        {
            this.setAttribute( 'open', false );

            this.$Opener.removeClass( 'icon-check' );
            this.$Opener.addClass( 'icon-check-empty' );

            if ( this.$Footer.getElement('.icon-refresh') ) {
                this.$Footer.getElement('.icon-refresh').setStyle( 'display', 'none' );
            }

            this.$TitleFX.animate({
                left : 0
            });
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
            if ( !this.$Content ) {
                return this;
            }

            this.$Content.set( 'html', html );

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

            var Li = this.create();

            Gridster.add_widget(
                Li,
                this.getAttribute('width'),
                this.getAttribute('height'),
                col,
                row
            );

            this.$Content.setStyle(
                'height',
                Li.getSize().y - this.$Footer.getSize().y
            );

            this.$Footer.getElement( '.icon-trash' ).addEvents({
                click : this.destroy
            });

            if ( this.getAttribute( 'open' ) )
            {
                this.open();
                return this;
            }

            this.Loader.hide();

            return this;
        },

        /**
         * Loads the content of the widget
         */
        $loadContent : function()
        {
            this.Loader.show();

            var Control = this,
                content = this.getAttribute('content');

            if ( content.type == 'ajax' &&
                 content.func !== '' )
            {
                QUI.Ajax.get( content.func, function( result )
                {
                    Control.setContent( result );
                    Control.Loader.hide();
                });

                return;
            }

            if ( content.type == 'html' )
            {
                QUI.Ajax.get('ajax_desktop_widgets_content', function( result, Request )
                {
                    Control.setContent( result );
                    Control.Loader.hide();
                }, {
                    name : this.getAttribute( 'name' )
                });

                return;
            }

            Control.Loader.hide();
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