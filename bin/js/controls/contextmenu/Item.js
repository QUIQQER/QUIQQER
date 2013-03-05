/**
 * Context Menu Item
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/contextmenu/Item
 *
 * @module controls/contextmenu/Item
 * @package com.pcsg.qui.js.controls.contextmenu
 * @namespace QUI.controls.contextmenu
 */

define('controls/contextmenu/Item', [

    'controls/Control',
    'css!controls/contextmenu/Item.css'

], function(Control)
{
    QUI.namespace('controls.contextmenu.Item');

    /**
     * @class QUI.controls.contextmenu.Item
     *
     * @event onClick [this, event]
     * @event onMouseDown [this, event]
     * @event onActive [this]
     * @event onNormal [this]
     *
     * @param {Object} options
     */
    QUI.controls.contextmenu.Item = new Class({

        Implements : [ Control ],
        Type       : 'QUI.controls.contextmenu.Item',

        Binds : [
            '$onSetAttribute',
            '$stringEvent',
            '$onClick'
        ],

        options : {
            text   : '',
            icon   : '',
            styles : null
        },

        initialize : function(options)
        {
            var items  = options.items || [],
                events = options.events || false;

            delete options.items;
            delete options.events;

            this.init( options );

            this.$items = [];
            this.$Elm   = null;
            this.$Menu  = null;

            this.addEvent( 'onSetAttribute', this.$onSetAttribute );

            if ( items.length ) {
                this.insert( items );
            }

            if ( events )
            {
                for ( var event in events )
                {
                    if ( typeof events[ event ] === 'string' )
                    {
                        this.addEvent(event, this.$stringEvent.bind(
                            this,
                            events[ event ]
                        ));

                        continue;
                    }

                    this.addEvent( event, events[ event ] );
                }
            }
        },

        /**
         * Create the DOMNode for the Element
         *
         * @method QUI.controls.contextmenu.Item#create
         *
         * @return {DOMNode}
         */
        create : function()
        {
            var i, len;

            this.$Elm = new Element('div.qui-contextitem', {
                html   : '<div class="qui-contextitem-container">' +
                            '<span class="qui-contextitem-text"></span>' +
                         '</div>',

                'data-quiid' : this.getId(),
                tabindex : -1,

                events :
                {
                    click : this.$onClick,

                    mousedown : function(event)
                    {
                        this.fireEvent( 'mouseDown', [ this, event ] );
                    }.bind( this ),

                    mouseup : function(event)
                    {
                        event.stop();
                    }.bind( this ),

                    mouseenter : function(event)
                    {
                        if ( this.$Menu )
                        {
                            var size = this.$Elm.getSize();

                            this.$Menu.setPosition( size.x, 0 );
                            this.$Menu.show();

                            this.$Elm
                                .getChildren( '.qui-contextitem-container' )
                                .addClass( 'qui-contextitem-active' );
                        }

                        this.setActive();

                    }.bind( this ),

                    mouseleave : function(event)
                    {
                        if ( this.$Menu ) {
                            this.$Menu.hide();
                        }

                        this.$Elm
                            .getChildren( '.qui-contextitem-container' )
                            .removeClass( 'qui-contextitem-active' );

                        this.setNormal();

                    }.bind( this )
                }
            });

            if ( this.getAttribute( 'icon' ) && this.getAttribute( 'icon' ) !== '' )
            {
                this.$Elm
                    .getElement( '.qui-contextitem-container' )
                    .setStyle( 'background-image', 'url('+ this.getAttribute( 'icon' ) +')' );
            }

            if ( this.getAttribute( 'text' ) && this.getAttribute( 'text' ) !== '' )
            {
                this.$Elm
                    .getElement( '.qui-contextitem-text' )
                    .set( 'html', this.getAttribute( 'text' ) );
            }

            // Create sub menu, if sub items exist
            len = this.$items.length;

            if ( len )
            {
                this.$Elm.addClass( 'haschildren' );

                var Menu = this.getContextMenu();

                for ( i = 0; i < len; i++ )
                {
                    Menu.appendChild(
                        this.$items[i]
                    );
                }
            }

            return this.$Elm;
        },

        /**
         * Import children
         * from a php callback or an array
         *
         * @param {Array} list
         * @return {this}
         */
        insert : function(list)
        {
            for ( var i = 0, len = list.length; i < len; i++)
            {
                if ( list[ i ].type == 'Controls_Contextmenu_Seperator' )
                {
                    this.appendChild(
                        new QUI.controls.contextmenu.Seperator( list[ i ] )
                    );

                    continue;
                }

                this.appendChild(
                    new QUI.controls.contextmenu.Item( list[i] )
                );
            }

            return this;
        },

        /**
         * Add a Child to the Item
         *
         * @method QUI.controls.contextmenu.Item#appendChild
         *
         * @param {QUI.controls.contextmenu.Item] Child
         * @return {this}
         */
        appendChild : function(Child)
        {
            this.$items.push( Child );

            Child.setParent(this);

            if ( this.$Elm )
            {
                this.$Elm.addClass( 'haschildren' );
                Child.inject( this.getContextMenu() );
            }

            return this;
        },

        /**
         * Set the Item active
         *
         * @method QUI.controls.contextmenu.Item#setActive
         * @return {this}
         */
        setActive : function()
        {
            if ( this.$Elm && this.$Elm.hasClass('qui-contextitem-active') ) {
                return this;
            }

            if ( this.$Elm )
            {
                if ( this.$Menu )
                {
                    this.$Elm
                        .getChildren('.qui-contextitem-container')
                        .addClass('qui-contextitem-active');
                } else
                {
                    this.$Elm.addClass('qui-contextitem-active');
                }
            }

            this.fireEvent( 'active', [ this ] );

            return this;
        },

        /**
         * Normalize the item
         *
         * @method QUI.controls.contextmenu.Item#setNormal
         * @return {this}
         */
        setNormal : function()
        {
            if ( this.$Elm )
            {
                if ( this.$Menu )
                {
                    this.$Elm
                        .getChildren('.qui-contextitem-container')
                        .removeClass('qui-contextitem-active');
                } else
                {
                    this.$Elm.removeClass('qui-contextitem-active');
                }
            }

            this.fireEvent( 'normal', [ this ] );

            return this;
        },

        /**
         * All Context Menu Items
         *
         * @method QUI.controls.contextmenu.Item#getChildren
         *
         * @return {Array}
         */
        getChildren : function()
        {
            return this.$items;
        },

        /**
         * Clear the Context Menu Items
         *
         * @method QUI.controls.contextmenu.Item#clear
         *
         * @return {this}
         */
        clear : function()
        {
            this.getContextMenu().clear();
            this.$items = [];

            return this;
        },

        /**
         * Create the Context Menu if not exist
         *
         * @method QUI.controls.contextmenu.Item#getContextMenu
         *
         * @return {QUI.controls.contextmenu.Menu}
         */
        getContextMenu : function()
        {
            if ( this.$Menu ) {
                return this.$Menu;
            }

            this.$Menu = new QUI.controls.contextmenu.Menu({
                name   : this.getAttribute( 'name' ) +'-menu',
                events :
                {
                    onShow : function(Menu)
                    {
                        var children = Menu.getChildren();

                        for ( var i = 0, len = children.length; i < len; i++ ) {
                            children[i].setNormal();
                        }
                    }
                }
            });

            this.$Menu.inject( this.$Elm );
            this.$Menu.hide();

            return this.$Menu;
        },

        /**
         * onSetAttribute Event
         * Set the attribute to the DOMElement if setAttribute is execute
         *
         * @param {String} key
         * @param {unknown_type} value
         *
         * @ignore
         */
        $onSetAttribute : function(key, value)
        {
            if ( !this.$Elm ) {
                return;
            }

            if ( key == 'text' )
            {
                this.$Elm.getElement( '.qui-contextitem-text' )
                         .set( 'html', value );

                return;
            }

            if ( key == 'icon' )
            {
                this.$Elm.getElement( '.qui-contextitem-container' )
                         .setStyle( 'background-image', 'url('+ value +')' );

                return;
            }
        },

        /**
         * interpret a string event
         *
         * @param {String} event
         */
        $stringEvent : function(event)
        {
            eval( '('+ event +'(this));' );
        },

        /**
         * event : onclick
         *
         * @param {DOMEvent} event
         * @ignore
         */
        $onClick : function(event)
        {
            this.fireEvent( 'click', [ this, event ] );
        }
    });

    return QUI.controls.contextmenu.Item;
});