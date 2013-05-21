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

    'css!controls/desktop/Wall.css',
    'css!controls/desktop/Wall.Loader.css',

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
            'openWidgetList'
        ],

        options : {

        },

        initialize: function(options)
        {
            this.init( options );

            this.$Desktop = null;
            this.$Loader  = null;

            this.$EnableDisable = null;

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

            var AddWidget = new QUI.controls.buttons.Button({
                text   : '<span class="icon-ellipsis-vertical qui-add-widget"></span>' +
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
                'class' : 'btn-green',
                text    : 'sperren',
                styles  : {
                    padding: '7px 10px',
                    margin: '3px 10px',
                    'float' : 'right'
                },
                events : {
                    onClick : this.switchEnableDisable
                }
            }).inject(
                document.getElement( '.qui-wall-controls' )
            );

            // load grister
            jQuery('.qui-wall-gridster').width(
                jQuery( document.body ).width() - 20
            );

            var min_cols   = 5,
                margins    = 10,
                dimensions = 140;

            /*
            min_cols = this.$Elm.getSize().x / ( dimensions + (margins*2) );
            min_cols = min_cols.floor();
            */

            jQuery(".qui-wall-gridster ul").gridster({
                widget_margins: [ margins, margins ],
                widget_base_dimensions: [ dimensions, dimensions ]
            });

            this.Loader.hide();
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
         * Switch the enable / disable status
         */
        switchEnableDisable : function()
        {
            if ( this.$EnableDisable.getElm().hasClass( 'btn-green' ) )
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

            Elm.addClass( 'btn-green' );
            Elm.removeClass( 'btn-red' );

            Btn.setAttribute( 'class', 'btn-green' );
            Btn.setAttribute( 'text', 'sperren' );

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

            Elm.removeClass( 'btn-green' );
            Elm.addClass( 'btn-red' );

            Btn.setAttribute( 'class', 'btn-red' );
            Btn.setAttribute( 'text', 'entsperren' );

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
        },

        /**
         * Open a list of available widgets
         */
        openWidgetList : function()
        {
            var Control = this;

            require([
                 'controls/desktop/widget/List'
            ], function(List) {
                new List( Control ).inject( document.body  ).show();
            });
        }
    });

    return QUI.controls.desktop.Wall;
});