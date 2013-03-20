/**
 * QUI Control - Select Box DropDown
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires controls/contextmenu/Menu
 *
 * @module controls/buttons/Button
 * @package com.pcsg.qui.js.controls.buttons
 * @namespace QUI.controls.buttons
 *
 * @event onChange [value, this]
 * @event onClick [this, event]
 */

define('controls/buttons/Select', [

    'controls/Control',
    'controls/contextmenu/Menu',
    'css!controls/buttons/Select.css'

], function(Control)
{
    "use strict";

    QUI.namespace( 'controls.buttons.Select' );

    /**
     * @class QUI.controls.buttons.Select
     *
     * @memberof! <global>
     */
    QUI.controls.buttons.Select = new Class({

        Implements : [ Control ],
        Type       : 'QUI.controls.buttons.Select',

        Binds : [
            'open',
            'set',
            '$set',
            '$onDestroy',
            '$onBlur',
            '$onKeyUp'
        ],

        options : {
            name    : 'select-box',
            'style' : {},      // mootools css style attributes
            'class' : false    // extra CSS Class
        },

        params : {},

        initialize : function(options)
        {
            this.init( options );

            this.$Menu = new QUI.controls.contextmenu.Menu();

            this.$Elm      = null;
            this.$value    = null;
            this.$disabled = false;

            this.addEvent( 'onDestroy', this.$onDestroy );
        },

        /**
         * Create the DOMNode Element
         *
         * @method QUI.controls.buttons.Select#create
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element('div.qui-select', {

                html : '<div class="icon"></div>' +
                       '<div class="text"></div>' +
                       '<div class="drop-icon"></div>',

                tabindex : -1,
                styles   : {
                    outline : 0,
                    cursor  : 'pointer'
                },

                'data-quiid' : this.getId()
            });

            if ( this.getAttribute( 'styles' ) ) {
                this.$Elm.setStyles( this.getAttribute( 'styles' ) );
            }

            if ( this.getAttribute( 'class' ) ) {
                this.$Elm.addClass( this.getAttribute( 'class' ) );
            }

            this.$Elm.addEvents({
                focus : this.open,
                blur  : this.$onBlur,
                keyup : this.$onKeyUp
            });

            this.$Menu.inject( document.body );
            this.$Menu.hide();

            this.$Menu.getElm().addClass( 'qui-dropdown' );
            this.$Menu.getElm().addEvent( 'mouseleave', function()
            {
                var Option = this.$Menu.getChildren(
                    this.getAttribute( 'name' ) + this.getValue()
                );

                if ( Option ) {
                    Option.setActive();
                }
            }.bind( this ));

            if ( this.$Elm.getStyle( 'width' )  )
            {
                var width = this.$Elm.getStyle( 'width' ).toInt();

                this.$Elm.getElement( '.text' ).setStyles({
                    width    : width - 50,
                    overflow : 'hidden'
                });
            }

            return this.$Elm;
        },

        /**
         * Set the value and select the option
         *
         * @method QUI.controls.buttons.Select#setValue
         * @param {String} value
         * @return {this}
         */
        setValue : function(value)
        {
            var i, len;
            var children = this.$Menu.getChildren();

            for ( i = 0, len = children.length; i < len; i++ )
            {
                if ( children[ i ].getAttribute( 'value' ) == value )
                {
                    this.$set( children[ i ] );
                    return this;
                }
            }

            return this;
        },

        /**
         * Return the current value of the select box
         *
         * @method QUI.controls.buttons.Select#getValue
         * @return {String|Integer}
         */
        getValue : function()
        {
            return this.$value;
        },

        /**
         * Add a option to the select box
         *
         * @method QUI.controls.buttons.Select#appendChild
         *
         * @param {String} text
         * @param {String} value
         * @param {String} icon - [optional]
         */
        appendChild : function(text, value, icon)
        {
            this.$Menu.appendChild(
                new QUI.controls.contextmenu.Item({
                    name   : this.getAttribute( 'name' ) + value,
                    text   : text,
                    value  : value,
                    icon   : icon || false,
                    events : {
                        onMouseDown : this.$set
                    }
                })
            );
        },

        /**
         * Return the first option child
         *
         * @method QUI.controls.buttons.Select#firstChild
         * @return {QUI.controls.contextmenu.Item|false}
         */
        firstChild : function()
        {
            if ( !this.$Menu ) {
                return false;
            }

            return this.$Menu.firstChild();
        },

        /**
         * Remove all children
         *
         * @method QUI.controls.buttons.Select#clear
         */
        clear : function()
        {
            this.$value = '';
            this.$Menu.clearChildren();

            if ( this.$Elm.getElement( '.text' ) ) {
                this.$Elm.getElement( '.text' ).set( 'html', '' );
            }

            if ( this.$Elm.getElement( '.icon' ) ) {
                this.$Elm.getElement( '.icon' ).setStyle( 'background', null );
            }
        },

        /**
         * Opens the select box
         *
         * @method QUI.controls.buttons.Select#open
         * @return {this}
         */
        open : function()
        {
            if ( this.isDisabled() ) {
                return this;
            }

            if ( document.activeElement != this.getElm() )
            {
                // because onclick and mouseup makes a focus on body
                (function() {
                    this.getElm().focus();
                }).delay( 100, this );

                return this;
            }

            var pos = this.$Elm.getPosition();

            this.getElm().addClass( 'qui-select-open' );

            this.$Menu.setPosition(
                pos.x + 2,
                pos.y + 21
            );

            this.$Menu.show();

            var Option = this.$Menu.getChildren(
                this.getAttribute( 'name' ) + this.getValue()
            );

            if ( Option ) {
                Option.setActive();
            }

            return this;
        },

        /**
         * hide the dropdown menu
         *
         * @method QUI.controls.buttons.Select#close
         */
        close : function()
        {
            document.body.focus();
            this.$onBlur();
        },

        /**
         * Disable the select
         *
         * @method QUI.controls.buttons.Select#disable
         * @return {this}
         */
        disable : function()
        {
            this.$disabled = true;
            this.getElm().addClass( 'qui-select-disable' );
            this.$Menu.hide();
        },

        /**
         * Is the select disabled?
         *
         * @method QUI.controls.buttons.Select#isDisabled
         */
        isDisabled : function()
        {
            return this.$disabled;
        },

        /**
         * Enable the select
         *
         * @method QUI.controls.buttons.Select#enable
         * @return {this}
         */
        enable : function()
        {
            this.$disabled = false;
            this.getElm().removeClass( 'qui-select-disable' );
        },

        /**
         * internal click, mousedown event of the context menu item
         * set the value to the select box
         *
         * @method QUI.controls.buttons.Select#$set
         * @param {QUI.controls.contextmenu.Item} Item
         */
        $set : function(Item)
        {
            this.$value = Item.getAttribute( 'value' );

            if ( this.$Elm.getElement( '.text' ) )
            {
                this.$Elm.getElement( '.text' )
                         .set( 'html', Item.getAttribute( 'text' ) );
            }

            if ( Item.getAttribute( 'icon' ) && this.$Elm.getElement( '.icon' ) )
            {
                this.$Elm.getElement( '.icon' ).setStyle(
                    'background',
                    'url('+ Item.getAttribute( 'icon' ) +') center center no-repeat'
                );
            }

            this.fireEvent( 'change', [ this.$value, this ] );
        },

        /**
         * event : on control destroy
         *
         * @method QUI.controls.buttons.Select#$onDestroy
         */
        $onDestroy : function()
        {
            this.$Menu.destroy();
        },

        /**
         * event : on menu blur
         *
         * @method QUI.controls.buttons.Select#$onBlur
         */
        $onBlur : function()
        {
            this.$Menu.hide();
            this.getElm().removeClass( 'qui-select-open' );
        },

        /**
         * event : on key up
         * if the element has the focus
         *
         * @method QUI.controls.buttons.Select#$onKeyUp
         * @param {DOMNode} event
         */
        $onKeyUp : function(event)
        {
            if ( typeof event === 'undefined' ) {
                return;
            }

            if ( event.key !== 'down' &&
                 event.key !== 'up' &&
                 event.key !== 'enter' )
            {
                return;
            }

            this.$Menu.show();

            if ( event.key === 'down' )
            {
                this.$Menu.down();
                return;
            }

            if ( event.key === 'up' )
            {
                this.$Menu.up();
                return;
            }

            if ( event.key === 'enter' )
            {
                this.$Menu.select();
                return;
            }
        }
    });

    return QUI.controls.buttons.Select;

});