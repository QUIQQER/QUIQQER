/**
 * A Tool / Tabbar
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires controls/contextmenu/Item
 * @requires controls/buttons/Button
 *
 * @module controls/toolbar/Bar
 * @class QUI.controls.toolbar.Bar
 * @package com.pcsg.qui.js.controls.toolbar
 *
 * @event onClear [ this ]
 * @event onAppendChild [ this, Itm ]
 */

define('controls/toolbar/Bar', [

    'controls/Control',
    'controls/contextmenu/Item',
    'controls/buttons/Button',

    'css!controls/toolbar/Bar.css'

], function(Control, QUI_ContextMenuItem, QUI_Button)
{
    "use strict";

    QUI.namespace( 'controls.toolbar.Bar' );

    /**
     * @class QUI.controls.toolbar.Bar
     *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.controls.toolbar.Bar = new Class({

        Implements : [ Control ],
        Type       : 'QUI.controls.toolbar.Bar',

        options : {
            'menu-button' : true,
            'slide'       : true,

            'width' : false,
            'type'  : 'tabbar'
        },

        initialize : function(options)
        {
            this.Fx   = null;
            this.$Elm = null;
            this.Tabs = null;
            this.Menu = null;

            this.Container = null;
            this.Active    = null;
            this.BtnLeft   = null;
            this.BtnRight  = null;

            this.items = [];
            this.btns  = [];

            this.init( options );
        },

        /**
         * Destroy the Bar
         *
         * @method QUI.controls.toolbar.Bar#destroy
         */
        destroy : function()
        {
            var i, len;

            for ( i = 0, len = this.items.length; i < len; i++ ) {
                this.items[i].destroy();
            }

            for ( i = 0, len = this.btns.length; i < len; i++ ) {
                this.btns[i].destroy();
            }

            this.items = [];
            this.btns  = [];

            if ( this.$Elm ) {
                this.$Elm.destroy();
            }

            if ( this.BtnLeft ) {
                this.BtnLeft.destroy();
            }

            if ( this.BtnRight ) {
                this.BtnRight.destroy();
            }

            if ( this.Menu ) {
                this.Menu.destroy();
            }

            this.Fx   = null;
            this.$Elm = null;
            this.Tabs = null;
            this.Menu = null;

            this.Container = null;
            this.Active    = null;
            this.BtnLeft   = null;
            this.BtnRight  = null;
        },

        /**
         * Refresh the Bar
         *
         * @method QUI.controls.toolbar.Bar#refresh
         * @return {this}
         */
        refresh : function()
        {
            if ( !this.$Elm ) {
                return this;
            }

            var i, len, Itm;

            var items = this.items,
                width = 0;

            // items create

            this.Tabs.set( 'html', '' );

            for ( i = 0, len = items.length; i < len; i++ )
            {
                Itm = items[i];
                Itm.inject( this.Tabs );

                this.Menu.appendChild(
                    this.$addContextMenuItm( Itm )
                );
            }

            if ( this.getAttribute( 'menu-button' ) === true )
            {
                this.Menu.show();
            } else
            {
                this.Menu.hide();
            }

            if ( this.getAttribute( 'slide' ) === true )
            {
                this.BtnLeft.show();
                this.BtnRight.show();
            } else
            {
                this.BtnLeft.hide();
                this.BtnRight.hide();
            }

            this.resize();

            return this;
        },

        /**
         * Create the DOMNode for the Bar
         *
         * @method QUI.controls.toolbar.Bar#create
         * @return {DOMNode}
         */
        create : function()
        {
            if ( this.$Elm )
            {
                this.refresh();

                return this.$Elm;
            }


            this.$Elm = new Element('div', {
                'class'      : 'qui-toolbar',
                'data-quiid' : this.getId(),

                'html' : '<div class="qui-toolbar-container">' +
                    '<div class="qui-toolbar-tabs"></div>' +
                '</div>'
            });

            if ( this.getAttribute( 'styles' ) ) {
                this.$Elm.setStyles( this.getAttribute( 'styles' ) );
            }


            this.Tabs      = this.$Elm.getElement( '.qui-toolbar-tabs' );
            this.Container = this.$Elm.getElement( '.qui-toolbar-container' );

            var items = this.items,
                width = 0;

            // left / right
            this.BtnLeft = new QUI.controls.buttons.Button({
                name    : 'toLeft',
                text    : '<div class="qui-toolbar-left"></div>',
                events  :
                {
                    onClick : function(Btn) {
                        Btn.getAttribute('Toolbar').toLeft();
                    }
                },
                Toolbar : this
            });

            this.BtnRight = new QUI.controls.buttons.Button({
                name    : 'toRight',
                text    : '<div class="qui-toolbar-right"></div>',
                events  :
                {
                    onClick : function(Btn) {
                        Btn.getAttribute('Toolbar').toRight();
                    }
                },
                Toolbar : this
            });

            // create the left context menu
            this.Menu = new QUI.controls.buttons.Button({
                name : '_tab',
                text : '<div class="qui-toolbar-dropdown"></div>'
            });

            this.Menu.setParent( this );
            this.Menu.inject( this.$Elm, 'top' );

            // create the slide buttons
            this.BtnLeft.inject( this.$Elm, 'top' );
            this.BtnRight.inject( this.$Elm );

            this.Tabs.setStyles({
                position : 'relative',
                left     : 0,
                'float'  : 'left'
            });

            this.refresh();

            this.Fx = moofx( this.Tabs );

            return this.$Elm;
        },

        /**
         * Clear the whole bar and destroys the children
         *
         * @method QUI.controls.toolbar.Bar#clear
         * @return {this}
         */
        clear : function()
        {
            if ( !this.Tabs )
            {
                this.fireEvent( 'clear', [ this ] );

                return this;
            }

            var i, len;

            for ( i = 0, len = this.items.length; i < len; i++ ) {
                this.items[ i ].destroy();
            }

            for ( i = 0, len = this.btns.length; i < len; i++ ) {
                this.btns[ i ].destroy();
            }

            this.Tabs.set( 'html', '' );

            delete this.items;
            delete this.Active;

            this.items  = [];
            this.btns   = [];
            this.Active = null;


            if ( this.Menu )
            {
                this.Menu.clear();
                this.Menu.setDisable();
            }

            if ( this.moveLeft ) {
                this.moveLeft.setDisable();
            }

            if ( this.moveRight ) {
                this.moveRight.setDisable();
            }

            this.Tabs.setStyle( 'left', 0 );
            this.fireEvent( 'clear', [ this ] );

            return this;
        },

        /**
         * Hide the Bar
         *
         * @method QUI.controls.toolbar.Bar#hide
         * @return {this}
         */
        hide : function()
        {
            this.$Elm.setStyles({
                'height'   : 0,
                'overflow' : 'hidden'
            });

            return this;
        },

        /**
         * Show the Bar
         *
         * @method QUI.controls.toolbar.Bar#show
         * @return {this}
         */
        show : function()
        {
            this.$Elm.setStyle( 'height', null );
            this.$Elm.setStyle( 'overflow', null );

            return this;
        },

        /**
         * Scrolls the bar to the right
         *
         * @method QUI.controls.toolbar.Bar#toLeft
         * @return {this}
         */
        toLeft : function()
        {
            if ( !this.Fx ) {
                return;
            }


            this.Fx.stop();
            this.Fx.animate({
                left : 0
            });

            return this;
        },

        /**
         * Scrolls the bar to the right
         *
         * @method QUI.controls.toolbar.Bar#toRight
         * @return {this}
         */
        toRight : function()
        {
            this.Fx.stop();

            var left = this.Tabs.offsetLeft - 150;

            if ( left < ((this.Tabs.offsetWidth-150) * -1) ) {
                left = 0;
            }

            this.Fx.animate({
                left : left
            });

            return this;
        },

        /**
         * Scroll to a specific tab
         *
         * @method QUI.controls.toolbar.Bar#toTab
         *
         * @param {QUI.controls.toolbar.Tab} Tab
         * @return {this}
         */
        toTab : function(Tab)
        {
            if ( this.getAttribute( 'slide' ) === false ) {
                return this;
            }

            if ( !this.$Elm ) {
                return this;
            }

            this.Fx.stop();

            if ( Tab == this.firstChild() )
            {
                this.Fx.animate({
                    left : 0
                });

                return this;
            }

            var needle;

            var TabElm    = Tab.getElm(),
                Container = this.$Elm.getElement( '.qui-toolbar-container' ),
                Tabs      = this.$Elm.getElement( '.qui-toolbar-tabs' ),

                left      = Tabs.getStyle( 'left' ).toInt() + Tab.getElm().offsetLeft,
                pos       = Tab.getElm().offsetLeft + Tab.getElm().offsetWidth;

            if ( left < 0 )
            {
                this.Fx.animate({
                    left : Tabs.getStyle('left').toInt() + ( left*-1 ) + 20
                });

                return this;
            }

            // verschieben nach links
            if ( pos > Container.offsetWidth )
            {
                needle = pos - Container.offsetWidth;

                this.Fx.animate({
                    left : (needle + 20) * -1
                });

                return this;
            }

            return this;
        },

        /**
         * Get the first children
         *
         * @method QUI.controls.toolbar.Bar#firstChild
         * @return {QUI.controls.Control}
         */
        firstChild : function()
        {
            return this.items[ 0 ];
        },

        /**
         * Get the last child
         *
         * @method QUI.controls.toolbar.Bar#lastChild
         * @return {QUI.controls.Control}
         */
        lastChild : function()
        {
            return this.items[ this.items.length - 1 ];
        },

        /**
         * Return all children
         *
         * @method QUI.controls.toolbar.Bar#getChildren
         *
         * @param {String} name - [optional] name of the wanted Element
         *                        if no name given, all children will be return
         * @return {Array}
         */
        getChildren : function(name)
        {
            if ( typeof name !== 'undefined' ) {
                return this.getElement( name );
            }

            return this.items;
        },

        /**
         * Number of children in the toolbar
         *
         * @method QUI.controls.toolbar.Bar#count
         * @return {Integer}
         */
        count : function()
        {
            return this.items.length;
        },

        /**
         * Remove a child from the toolbar
         *
         * @method QUI.controls.toolbar.Bar#removeChild
         * @param {QUI.controls.Control} Child
         * @return {this}
         */
        removeChild : function(Child)
        {
            var i, len;

            var nitms     = [],
                itms      = this.items,
                childname = Child.getAttribute( 'name' );

            for ( i = 0, len = itms.length; i < len; i++ )
            {
                if ( itms[ i ].getAttribute( 'name' ) != childname )
                {
                    nitms.push( itms[ i ] );
                    continue;
                }

                itms[ i ].destroy();
            }

            this.items = nitms;

            return this;
        },

        /**
         * Move an child to another position
         *
         * @method QUI.controls.toolbar.Bar#moveChildToPos
         *
         * @param {QUI.controls.Control} Child - Item in the toolbar
         * @param {Integer} pos   - New position
         *
         * @return {this}
         */
        moveChildToPos : function(Child, pos)
        {
            // Array anpassen
            var Before = false,
                nitems = [],
                itms   = this.items;

            for ( var i = 0, len = itms.length; i < len; i++ )
            {
                if ( Child == itms[ i ] )
                {
                    itms[ i ].destroy();
                    continue;
                }

                nitems.push( itms[ i ] );
            }

            nitems.splice(pos-1, 0, Child);

            // Element nach dem davor schieben
            if ( pos-2 >= 0 ) {
                nitems[ pos-2 ].getElm().inject( Child.create(), 'after' );
            }

            this.items = nitems;

            return this;
        },

        /**
         * Get an Element by name
         *
         * @method QUI.controls.toolbar.Bar#getElement
         *
         * @param {String} name - name of the wanted Element
         * @return {null|QUI.controls.Control}
         */
        getElement : function(name)
        {
            var i, len;
            var items = this.items;

            for ( i = 0, len = items.length; i < len; i++ )
            {
                if ( items[ i ] &&
                     items[ i ].getAttribute( 'name' ) == name )
                {
                    return items[ i ];
                }
            }

            return null;
        },

        /**
         * Add an Child to the toolbar
         *
         * @method QUI.controls.toolbar.Bar#appendChild
         *
         * @param {
         *         QUI.controls.buttons.Button|
         *         QUI.controls.buttons.Seperator|
         *         QUI.controls.toolbar.Tab
         * } Itm
         * @return {this}
         */
        appendChild : function(Itm)
        {
            if ( typeof Itm === 'undefined' ) {
                return this;
            }

            var type = typeOf( Itm );

            switch ( type )
            {
                case 'QUI.controls.buttons.Button':
                case 'QUI.controls.buttons.Select':
                case 'QUI.controls.buttons.Seperator':
                case 'QUI.controls.toolbar.Tab':
                    // nothing
                break;

                // domnode
                case 'element':
                    Itm.inject( this.Tabs, 'before' );
                    return this;

                default:
                    return this;
            }

            Itm.setParent( this );
            this.fireEvent( 'appendChild', [ this, Itm ] );

            // Falls Toolbar eine Tabbar ist, Buttons an vorletzter Stelle
            if ( this.getAttribute( 'type' ) === 'tabbar' &&
                 type === 'QUI.control.buttons.Button' )
            {
                this.btns.push( Itm );

                Itm.inject( this.BtnRight.getElm(), 'before' );

                var Con = this.Container,
                    Btn = Itm.getElm();

                Con.setStyle(
                    'width',
                    Con.getStyle('width').toInt() - Btn.getSize().x
                );

                return this;
            }

            // start the normal toolbar
            this.items.push( Itm );

            if ( !this.$Elm ) {
                return this;
            }

            Itm.inject( this.Tabs );

             if ( this.Menu )
             {
                this.Menu.appendChild(
                    this.$addContextMenuItm( Itm )
                );
            }

            // neue Breite berechnen
            this.resize();

            if ( this.Menu ) {
                this.Menu.setEnable();
            }

            if ( this.BtnLeft ) {
                this.BtnLeft.setEnable();
            }

            if ( this.BtnRight ) {
                this.BtnRight.setEnable();
            }

            return this;
        },

        /**
         * Set an toolbar child active
         *
         * @method QUI.controls.toolbar.Bar#setItemActive
         * @param {QUI.controls.toolbar.Tab} Child
         * @return {this}
         */
        setItemActive : function(Child)
        {
            if ( this.Active )
            {
                this.Active.leave();
                this.Active.setNormal();

                if ( this.Active.getAttribute('ContextMenuItem') ) {
                    this.Active.getAttribute('ContextMenuItem').getElm().setStyle('fontWeight', '');
                }
            }

            this.Active = Child;
            this.Active.setActive();

            if ( this.Active.getAttribute('ContextMenuItem') ) {
                this.Active.getAttribute('ContextMenuItem').getElm().setStyle('fontWeight', 'bold');
            }

            return this;
        },

        /**
         * Returns the active child
         *
         * @method QUI.controls.toolbar.Bar#getActive
         * @return {null|QUI.controls.Control}
         */
        getActive : function()
        {
            return this.Active;
        },

        /**
         * Resize the whole toolbar
         *
         * @method QUI.controls.toolbar.Bar#resize
         * @return {this}
         */
        resize : function()
        {
            // tab width
            var i, len;

            var width  = 0,
                cwidth = 0,
                itms   = this.Tabs.getChildren();

            for ( i = 0, len = itms.length; i < len; i++ ) {
                width = width + ( itms[ i ].getSize().x.toInt() ) + 30;
            }

            if ( this.getAttribute( 'width' ) &&
                 this.getAttribute( 'width' ).toString().contains( '%' ) === false )
            {
                cwidth = ( this.getAttribute( 'width' ) ).toInt();

                if ( this.getAttribute( 'slide' ) ) {
                    cwidth = cwidth - 60;
                }

                if ( this.getAttribute( 'menu-button' ) ) {
                    cwidth = cwidth - 50;
                }

            } else if ( this.getAttribute( 'width' ) &&
                        this.getAttribute( 'width' ).toString().match('%') )
            {
                cwidth = '100%';
            }

            this.Tabs.setStyle( 'width', width );
            this.Container.setStyle( 'width', cwidth );

            this.getElm().setStyle( 'width', this.getAttribute( 'width' ) );

            return this;
        },

        /**
         * Add a ContextMenuItem for a child to the toolbar
         *
         * @method QUI.controls.toolbar.Bar#$addContextMenuItm
         * @return {QUI.controls.contextmenu.Item}
         * @ignore
         */
        $addContextMenuItm : function(Itm)
        {
            var ContextMenuItem = new QUI.controls.contextmenu.Item(
                Itm.getAttributes()
            );

            ContextMenuItem.setAttribute( 'TAB', Itm );
            ContextMenuItem.setAttribute( 'Toolbar', this );

            Itm.setAttribute( 'ContextMenuItem', ContextMenuItem );

            ContextMenuItem.addEvent('onMouseDown', function(Itm, event)
            {
                var Tab     = Itm.getAttribute( 'TAB' ),
                    Toolbar = Itm.getAttribute( 'Toolbar' );

                Tab.click();
                Toolbar.toTab( Tab );
            });

            return ContextMenuItem;
        }
    });

    return QUI.controls.toolbar.Bar;
});
