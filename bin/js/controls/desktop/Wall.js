/**
 * A desktop widgets
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/Utils
 * @package com.pcsg.qui.js.controls
 * @namespace QUI.controls.desktop
 */

define('controls/desktop/Wall', [

    'controls/Control',
    'controls/loader/Loader',
    'controls/buttons/Button',
    'controls/buttons/Select',
    'controls/desktop/Widget',

    'css!controls/desktop/Wall.css',
    'css!controls/desktop/Wall.Loader.css'

], function(Control)
{
    "use strict";

    QUI.namespace( 'controls.desktop' );

    /**
     * @class QUI.controls.desktop.Widget
     *
     * @memberof! <global>
     */
    QUI.controls.desktop.Wall = new Class({

        Extends : Control,
        Type    : 'QUI.controls.desktop.Wall',

        Binds : [
            'switchEnableDisable',
            'openDesktopList',
            'openWidgetList',
            'save'
        ],

        options : {

        },

        initialize: function(options)
        {
            this.init( options );

            this.$Desktop = null;
            this.$Loader  = null;

            this.$EnableDisable = null;
            this.$DesktopList   = null;
            this.$WidgetList    = null;

            /**
             * wall loader object
             */
            this.Loader = {
                /**
                 * Show the wall loader
                 */
                show : function() {
                    this.$Loader.setStyle( 'display', '' );
                }.bind( this ),

                /**
                 * Hide the wall loader
                 */
                hide : function() {
                    this.$Loader.setStyle( 'display', 'none' );
                }.bind( this )
            };
        },

        /**
         * create the dom node elements
         *
         * @return {DOMNode} DIV
         */
        create : function()
        {
            this.$Elm = new Element('div.qui-wall', {
                html : '<div class="qui-wall-controls"></div>' +
                       '<div class="qui-wall-gridster gridster">' +
                            '<ul style="position: relative;"></ul>' +
                        '</div>'
            });

            if ( !document.body.getElement( '.qui-wall-loader' ) )
            {
                new Element('div.qui-wall-loader', {
                    html : '<div class="windows8">' +
                                '<div class="wBall" id="wBall_1">' +
                                '<div class="wInnerBall"></div>' +
                            '</div>' +
                            '<div class="wBall" id="wBall_2">' +
                                '<div class="wInnerBall"></div>' +
                            '</div>' +
                            '<div class="wBall" id="wBall_3">' +
                                '<div class="wInnerBall"></div>' +
                            '</div>' +

                            '<div class="wBall" id="wBall_4">' +
                                '<div class="wInnerBall"></div>' +
                            '</div>' +

                            '<div class="wBall" id="wBall_5">' +
                                '<div class="wInnerBall"></div>' +
                            '</div>' +
                        '</div>'
                }).inject( document.body );
            }

            this.$Loader = document.body.getElement( '.qui-wall-loader' );

            return this.$Elm;
        },

        /**
         * Load the grid and the controls
         */
        load : function()
        {
            this.Loader.show();

            // load the gridster and the controls
            this.$Desktop = jQuery(".qui-wall-gridster ul").gridster().data('gridster');

            var Gridster = this.$Desktop;

            var DesktopList = new QUI.controls.buttons.Button({
                text   : '<span class="icon-ellipsis-vertical qui-desktop-manager"></span>',
                alt    : 'Desktop-Verwaltung',
                title  : 'Desktop-Verwaltung',
                styles : {
                    padding: '7px 10px',
                    margin: '3px 10px'
                },
                events : {
                    onClick : this.openDesktopList
                }
            }).inject(
                document.getElement( '.qui-wall-controls' )
            );

            var AddWidget = new QUI.controls.buttons.Button({
                text   : '<span class="icon-th-large qui-add-widget"></span>' +
                         'Widget hinzufügen',
                 styles : {
                     padding: '7px 10px',
                     margin: '3px 10px'
                 },
                 events : {
                     onClick : this.openWidgetList
                 }
            }).inject(
                document.getElement( '.qui-wall-controls' )
            );


            this.$EnableDisable = new QUI.controls.buttons.Button({
                'class' : 'qui-wall-btn-enable',
                styles  : {
                    padding : '7px 10px',
                    margin  : '3px 10px',
                    width   : 150,
                    'float' : 'right'
                },
                events : {
                    onClick : this.switchEnableDisable
                }
            }).inject(
                document.getElement( '.qui-wall-controls' )
            );

            this.disable();

            // load grister
            jQuery('.qui-wall-gridster').width(
                jQuery( document.body ).width() - 20
            );

            var min_cols   = 5,
                margins    = 10,
                dimensions = 100;

            /*
            min_cols = this.$Elm.getSize().x / ( dimensions + (margins*2) );
            min_cols = min_cols.floor();
            */

            jQuery(".qui-wall-gridster ul").gridster({
                widget_margins: [ margins, margins ],
                widget_base_dimensions: [ dimensions, dimensions ]
            });

            this.$Desktop.options.draggable.stop = this.save;

            // if a desktop is in the url given, load it
            var loc = window.location.search;

            if ( !loc.match('id=') || !loc.match('desktop=') )
            {
                this.openDesktopList();
                return;
            }

            var i, len, spl;

            var loc_params = {};

            loc = loc.replace( '?', '' );
            loc = loc.split('&');

            for ( i = 0, len = loc.length; i < len; i++ )
            {
                spl = loc[ i ].split( '=' );

                loc_params[ spl[ 0 ] ] = spl[ 1 ];
            }

            if ( typeof loc_params.id === 'undefined' )
            {
                this.openDesktopList();
                return;
            }

            this.loadDesktop( loc_params.id );
        },

        /**
         * Return the jQuery gridster Object
         *
         * @return {gridster} jQuery gridster Object
         */
        getDesktop : function()
        {
            return this.$Desktop;
        },

        /**
         * Return the actual Desktop-ID
         *
         * @return {Integer} id
         */
        getDesktopId : function()
        {
            return this.$id;
        },

        /**
         * Save the desktop wall to the user
         */
        save : function()
        {
            if ( typeof USER === 'undefined' )
            {
                QUI.MH.addError(
                    'Cannot save desktop, User not exist'
                );

                return;
            }

            var widgets = [],
                list    = this.$Elm.getElements( '.qui-wall-gridster li.qui-desktop-widget' );

            var i, len, quiid, Widget;

            for ( i = 0, len = list.length; i < len; i++ )
            {
                quiid  = list[i].get( 'data-quiid' );
                Widget = QUI.Controls.getById( quiid );

                widgets.push( Widget.serialize() );
            }

            QUI.Ajax.post('ajax_desktop_save', function(result, Request)
            {

            }, {
                widgets : JSON.encode( widgets ),
                did     : this.getDesktopId()
            });
        },

        /**
         * Switch the enable / disable status
         */
        switchEnableDisable : function()
        {
            if ( this.$Elm.hasClass( 'enabled' ) )
            {
                this.disable();
                return;
            }

            this.enable();
        },

        /**
         * Enable the Wall, so widgets can be moved
         */
        enable : function()
        {
            var Btn = this.$EnableDisable,
                Elm = Btn.getElm();

            Elm.removeClass( 'btn-green' );
            Elm.addClass( 'btn-red' );

            Btn.setAttribute( 'class', 'qui-wall-btn-enable btn-red' );
            Btn.setAttribute(
                'text',
                '<span class="icon-check-empty"></span>entsperrt'
            );

            this.$Elm.removeClass( 'disabled' );
            this.$Elm.addClass( 'enabled' );

            this.$Desktop.enable();

            this.fireEvent( 'enable', [ this ] );
        },

        /**
         * Disable the Wall, so widgets cant be moved
         */
        disable : function()
        {
            var Btn = this.$EnableDisable,
                Elm = Btn.getElm();

            Elm.addClass( 'btn-green' );
            Elm.removeClass( 'btn-red' );

            Btn.setAttribute( 'class', 'qui-wall-btn-enable btn-green' );
            Btn.setAttribute(
                'text',
                '<span class="icon-check"></span>gesperrt'
            );

            this.$Elm.addClass( 'disabled' );
            this.$Elm.removeClass( 'enabled' );

            this.$Desktop.disable();

            this.fireEvent( 'disable', [ this ] );
        },

        /**
         * Add a widget to the wall
         *
         * @param {QUI.controls.desktop.Widget} Widget
         */
        appendWidget : function(Widget)
        {
            var col = 1,
                row = 1;

            // find the last widget pos
            var list = this.$Elm.getElements( '.qui-wall-gridster li' );

            if ( list && list.length )
            {
                var _sizey, _row;

                for ( var i = 0, len = list.length; i < len; i++ )
                {
                    _row   = list[ i ].get( 'data-row' );
                    _sizey = list[ i ].get( 'data-sizey' );

                    _row   = ( _row ).toInt();
                    _sizey = ( _sizey ).toInt();

                    if ( row < _row + _sizey ) {
                        row = _row + _sizey;
                    }
                }
            }

            Widget.setParent( this );
            Widget.appendToGridster( this.$Desktop, col, row );

            this.save();
        },

        /**
         * Open a list of available widgets
         */
        openWidgetList : function()
        {
            if ( this.$WidgetList )
            {
                this.$WidgetList.show();
                return;
            }

            var Control = this;

            require([
                 'controls/desktop/widget/List'
            ], function(List)
            {
                Control.$WidgetList = new List( Control ).inject( document.body  );
                Control.$WidgetList.show();
            });
        },

        /**
         * Open the Desktop list
         */
        openDesktopList : function()
        {
            if ( this.$DesktopList )
            {
                this.$DesktopList.show();
                return;
            }


            var Control = this;

            require([
                 'controls/desktop/WallDesktopList'
            ], function(List)
            {
                Control.$DesktopList = new List( Control ).inject( document.body );
                Control.$DesktopList.show();
            });
        },

        /**
         * Creates a new desktop
         *
         * @param {String} title - Title of the Desktop
         */
        createDesktop : function(title)
        {
            var Control = this;

            Control.Loader.show();

            QUI.Ajax.post('ajax_desktop_create', function(result, Request)
            {
                Control.openDesktop( result );
            }, {
                title : title
            });
        },

        /**
         * Open the desktop with the id
         *
         * @param {String} id - Desktop-ID
         */
        openDesktop : function(id)
        {
            this.Loader.show();

            window.location = window.location.pathname +'?desktop=1&id='+ id;
        },

        /**
         * Load the widgets from a desktop
         *
         * @param {Integer} id - Desktop-ID
         */
        loadDesktop : function(id)
        {
            var Control = this;

            Control.Loader.show();
            Control.$id = id;

            QUI.Ajax.get('ajax_desktop_load', function(result, Request)
            {
                if ( !result || !result.length )
                {
                    Control.Loader.hide();
                    return;
                }

                var i, len, params, Widget;

                for ( i = 0, len = result.length; i < len; i++ )
                {
                    params = result[ i ];

                    Widget = new QUI.controls.desktop.Widget( params );
                    Widget.setParent( Control );

                    Widget.appendToGridster(
                        Control.getDesktop(),
                        params.col,
                        params.row
                    );
                }

                Control.Loader.hide();
            }, {
                did : id
            });
        }
    });

    return QUI.controls.desktop.Wall;
});