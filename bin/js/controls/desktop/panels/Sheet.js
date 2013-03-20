/**
 * A panel Sheet
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/desktop/panels/Sheet
 * @package com.pcsg.qui.js.controls.desktop.panels
 * @namespace QUI.controls.desktop.panels
 *
 * @event onOpen [this]
 * @event onClose [this]
 */

define('controls/desktop/panels/Sheet', [

    'controls/Control',
    'css!controls/desktop/panels/Sheet.css'

], function(Control)
{
    "use strict";

    QUI.namespace( 'controls.desktop.panels' );

    /**
     * @class QUI.controls.desktop.panels.Sheet
     */
    QUI.controls.desktop.panels.Sheet = new Class({

        Implements : [ Control ],
        Type       : 'QUI.controls.desktop.panels.Sheet',

        Binds : [
            '$fxComplete'
        ],

        options : {
            styles : false
        },

        initialize: function(options)
        {
            this.init( options );

            this.$Elm     = null;
            this.$Body    = null;
            this.$Buttons = null;
            this.$FX      = null;
        },

        /**
         * Create the DOMNode Element of the Sheet
         *
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element('div.qui-panel-sheet', {
                'data-quiid' : this.getId(),

                html : '<div class="qui-panel-sheet-body"></div>' +
                       '<div class="qui-panel-sheet-btn-container">' +
                            '<div class="qui-panel-sheet-buttons"></div>' +
                       '</div>',

               styles : {
                   visibility : 'hidden',
                   display    : 'none'
               }
            });

            if ( this.getAttribute('styles') ) {
                this.$Elm.setStyles( this.getAttribute('styles') );
            }

            this.$Body    = this.$Elm.getElement('.qui-panel-sheet-body');
            this.$Buttons = this.$Elm.getElement('.qui-panel-sheet-btn-container');

            this.$FX = moofx( this.$Elm );

            return this.$Elm;
        },

        /**
         * Return the panel body
         *
         * @return {DOMNode|null}
         */
        getBody : function()
        {
            return this.$Body;
        },

        /**
         * Return the button container
         *
         * @return {DOMNode|null}
         */
        getButtons : function()
        {
            return this.$Buttons;
        },

        /**
         * Add a button to the Sheet
         *
         * @param {QUI.controls.button.Button}
         * @return {this}
         */
        addButton : function(Btn)
        {
            var i, len, list;

            var Container = this.getButtons().getElement( '.qui-panel-sheet-buttons' ),
                width     = 0,
                styles    = Btn.getAttributes( 'styles' ) || {};

            styles.margin = '12px 5px';

            Btn.setAttribute( 'styles', styles );
            Btn.inject( Container );

            list = Container.getElements( 'button' );

            for ( i = 0, len = list.length; i < len; i++ ) {
                width = width + list[ i ].getSize().x + 10;
            }

            Container.setStyle( 'width', width );
        },

        /**
         * Show the panel sheet
         *
         * @return {this}
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


            var button_size = this.getButtons().getSize();

            this.getBody().setStyles({
                height  : size.y - button_size.y,
                width   : '100%',
                'float' : 'left'
            });

            this.addButton(
                new QUI.controls.buttons.Button({
                    text      : 'schließen / abbrechen',
                    textimage : URL_BIN_DIR +'16x16/cancel.png',
                    styles    : {
                        margin : '6px auto 0',
                        width  : 200
                    },
                    events : {
                        onClick : this.hide.bind( this )
                    }
                })
            );

            this.$FX.animate({
                left : 0
            }, {
                callback : this.$fxComplete
            });

            return this;
        },

        /**
         * Hide the panel sheet
         *
         * @return {this}
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
         * if panel is closed or opened
         */
        $fxComplete : function(Sheet)
        {
            if ( this.getElm().getStyle('left').toInt() >= 0 )
            {
                this.fireEvent( 'open', [ this ] );
                return;
            }

            this.fireEvent( 'close', [ this ] );
            this.destroy();
        }
    });

    return QUI.controls.desktop.panels.Sheet;
});