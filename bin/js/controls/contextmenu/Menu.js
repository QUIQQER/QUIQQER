/**
 * A Context Menu
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires controls/contextmenu/Item
 * @requires controls/contextmenu/Seperator
 *
 * @module controls/contextmenu/Menu
 * @package com.pcsg.qui.js.controls.contextmenu
 * @namespace QUI.controls.contextmenu
 */

define('controls/contextmenu/Menu', [

    'controls/Control',
    'controls/contextmenu/Item',
    'controls/contextmenu/Seperator',

    'css!controls/contextmenu/Menu.css'

], function(Control)
{
    QUI.namespace( 'controls.contextmenu.Menu' );

    /**
     * @class QUI.controls.contextmenu.Menu
     *
     * @fires onShow [this]
     * @fires onHide [this]
     * @fires onBlur [this]
     * @fires onFocus [this]
     *
     * @memberof! <global>
     */
    QUI.controls.contextmenu.Menu = new Class({

        Implements: [ Control ],
        Type      : 'QUI.controls.contextmenu.Menu',

        options : {
            styles : null,    // mootools css styles
            width  : 200,    // menü width
            title  : false,    // title of the menu (optional) : String
            shadow : true   // menü with shadow (true) or not (false)
        },

        initialize : function(options)
        {
            this.init( options );

            this.$items  = [];
            this.$Title  = null;
            this.$Active = null;
        },

        /**
         * Create the DOM Element
         *
         * @method QUI.controls.contextmenu.Menu#create
         *
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element('div.qui-contextmenu', {
                tabindex : -1,
                styles   : {
                    display : 'none',
                    outline : 'none',
                    '-moz-outline': 'none'
                },
                events :
                {
                    blur : function() {
                        this.fireEvent( 'blur', [ this ] );
                    }.bind( this ),

                    keyup : function(event) {
                        this.$keyup( event );
                    }.bind( this )
                },
                'data-quiid' : this.getId()
            });

            if ( this.getAttribute( 'width' ) ) {
                this.$Elm.setStyle( 'width', this.getAttribute( 'width' ) );
            }

            if ( this.getAttribute( 'styles' ) ) {
                this.$Elm.setStyles( this.getAttribute( 'styles' ) );
            }

            if ( this.getAttribute( 'title' ) ) {
                this.setTitle( this.getAttribute( 'title' ) );
            }

            if ( this.getAttribute( 'shadow' ) ) {
                this.$Elm.addClass( 'qui-contextmenu-shadow' );
            }


            for ( var i = 0, len = this.$items.length; i < len; i++ ) {
                this.$items[ i ].inject( this.$Elm );
            }

            return this.$Elm;
        },

        /**
         * Shows the Menu, clears the display style
         *
         * @method QUI.controls.contextmenu.Menu#show
         * @return {this}
         */
        show : function()
        {
            if ( !this.$Elm ) {
                return this;
            }

            var Parent = this.$Elm.getParent(),
                Elm    = this.$Elm;

            Elm.setStyle( 'display', '' );

            // if parent is the body element
            // context menu don't get out of the body
            if ( Parent.nodeName === 'BODY' )
            {
                var elm_size  = Elm.getSize(),
                    elm_pos   = Elm.getPosition(),
                    body_size = Parent.getSize();

                if ( elm_pos.x + elm_size.x > body_size.x ) {
                    this.$Elm.setStyle( 'left', body_size.x - elm_size.x - 10 );
                }

                if ( elm_pos.y + elm_size.y > body_size.y ) {
                    this.$Elm.setStyle( 'top', body_size.y - elm_size.y - 10 );
                }
            }

            if ( this.$Active ) {
                this.$Active.setActive();
            }

            this.fireEvent( 'show', [ this ] );

            return this;
        },

        /**
         * Hide the Menu, set the display style to none
         *
         * @method QUI.controls.contextmenu.Menu#hide
         *
         * @return {this}
         */
        hide : function()
        {
            this.getElm().setStyle( 'display', 'none' );
            this.fireEvent( 'hide', [ this ] );

            return this;
        },

        /**
         * Set the focus to the Menu, the blur event would be triggerd
         *
         * @method QUI.controls.contextmenu.Menu#focus
         *
         * @return {this}
         */
        focus : function()
        {
            this.getElm().focus();
            this.fireEvent( 'focus', [ this ] );

            return this;
        },

        /**
         * Set the Position of the Menu
         *
         * if parent is the body element
         * context menu don't get out of the body
         *
         * @method QUI.controls.contextmenu.Menu#setPosition
         *
         * @param {Integer} x - from the top (x axis)
         * @param {Integer}y - from the left (y axis)
         * @return {this}
         */
        setPosition : function(x, y)
        {
            if ( this.$Elm )
            {
                this.$Elm.setStyles({
                    left : x,
                    top  : y
                });
            }

            return this;
        },

        /**
         * Set and create the menu title
         *
         * @method QUI.controls.contextmenu.Menu#setTitle
         *
         * @param {String} text - Title text
         * @return {this}
         */
        setTitle : function(text)
        {
            if ( this.$Elm && !this.$Title )
            {
                this.$Title = new Element('div.qui-contextmenu-title');
                this.$Title.inject( this.$Elm, 'top' );
            }

            if ( this.$Title ) {
                this.$Title.set( 'html', text );
            }

            this.setAttribute( 'title', text );

            return this;
        },

        /**
         * Get an Child Element
         *
         * @method QUI.controls.contextmenu.Menu#getChildren
         *
         * @param {String} name : [Name of the Children, optional, if no name given, returns all Children]
         * @return {Array|false|QUI.controls.contextmenu.Item}
         */
        getChildren : function(name)
        {
            if ( typeof name !== 'undefined' )
            {
                var i, len;
                var items = this.$items;

                for ( i = 0, len = items.length; i < len; i++ )
                {
                    if ( items[ i ].getAttribute( 'name' ) == name ) {
                        return items[ i ];
                    }
                }

                return false;
            }

            return this.$items;
        },

        /**
         * Return the first child Element
         *
         * @method QUI.controls.contextmenu.Menu#firstChild
         * @return {false|QUI.controls.contextmenu.Item}
         */
        firstChild : function()
        {
            if ( this.$items[ 0 ] ) {
                return this.$items[ 0 ];
            }

            return false;
        },

        /**
         * Return the number of children
         *
         * @return {Integer}
         */
        count : function()
        {
            return this.$items.length;
        },

        /**
         * Add the Child to the Menü
         *
         * @method QUI.controls.contextmenu.Menu#appendChild
         *
         * @param {QUI.controls.contextmenu.Item} Child
         * @return {this}
         */
        appendChild : function(Child)
        {
            if ( !Child || typeof Child === 'undefined' ) {
                return this;
            }

            this.$items.push( Child );

            Child.setParent( this );

            // children events
            Child.addEvent( 'onClick', function(Item, event)
            {
                this.hide();

                document.body.focus();

                if ( typeof event !== 'undefined' ) {
                    event.stop();
                }
            }.bind( this ) );

            Child.addEvent( 'onActive', function(Item)
            {
                if ( this.$Active == Item ) {
                    return;
                }

                if ( this.$Active ) {
                    this.$Active.setNormal();
                }

                this.$Active = Item;
            }.bind( this ));

            if ( this.$Elm ) {
                Child.inject( this.$Elm );
            }

            return this;
        },

        /**
         * Destroy all children items
         *
         * @method QUI.controls.contextmenu.Menu#clearChildren
         *
         * @return {this}
         */
        clearChildren : function()
        {
            for ( var i = 0, len = this.$items.length; i < len; i++ )
            {
                if ( this.$items[ i ] ) {
                    this.$items[ i ].destroy();
                }
            }

            this.$items = [];

            return this;
        },

        /**
         * Return the active item
         *
         * @return {QUI.controls.contextmenu.Item|false}
         */
        getActive : function()
        {
            if ( this.$Active ) {
                return this.$Active;
            }

            return false;
        },

        /**
         * Return the next children / item of the item
         *
         * @param {QUI.controls.contextmenu.Item} Item
         * @return {QUI.controls.contextmenu.Item|false}
         */
        getNext : function(Item)
        {
            for ( var i = 0, len = this.$items.length; i < len; i++ )
            {
                if ( this.$items[ i ] != Item ) {
                    continue;
                }

                if ( typeof this.$items[ i + 1 ] !== 'undefined' ) {
                    return this.$items[ i + 1 ];
                }
            }

            return false;
        },

        /**
         * Return the previous children / item of the item
         *
         * @param {QUI.controls.contextmenu.Item} Item
         * @return {QUI.controls.contextmenu.Item|false}
         */
        getPrevious : function(Item)
        {
            var i = this.$items.length - 1;

            for ( ; i >= 0; i-- )
            {
                if ( i === 0 ) {
                    return false;
                }

                if ( this.$items[ i ] == Item ) {
                    return this.$items[ i - 1 ];
                }
            }

            return false;
        },

        /**
         * Deselect all children
         *
         * @return {this}
         */
        deselectItems : function()
        {
            if ( this.$Active ) {
                this.$Active = null;
            }

            return this;
        },

        /**
         * Keyup event if the menu has the focus
         * so you can select with keyboard the contextmenu items
         */
        $keyup : function(event)
        {
            if ( event.key === 'down' )
            {
                this.down();
                return;
            }

            if ( event.key === 'up' )
            {
                this.up( event );
                return;
            }

            if ( event.key === 'enter' )
            {
                this.select( event );
                return;
            }
        },

        /**
         * Simulate a arrow up, select the element up
         */
        up : function(event)
        {
            if ( !this.$items.length ) {
                return;
            }

            var len = this.$items.length;

            // select last element if nothing is active
            if ( !this.$Active )
            {
                this.$items[ len - 1 ].setActive();
                return;
            }

            var Prev = this.getPrevious( this.$Active );

            this.$Active.setNormal();

            if ( !Prev )
            {
                this.$items[ len - 1 ].setActive();
                return;
            }

            Prev.setActive();
        },

        /**
         * Simulate a arrow down, select the element down
         */
        down : function()
        {
            if ( !this.$items.length ) {
                return;
            }

            // select first element if nothing is selected
            if ( !this.$Active )
            {
                this.$items[ 0 ].setActive();
                return;
            }

            var Next = this.getNext( this.$Active );

            this.$Active.setNormal();

            if ( !Next )
            {
                this.$items[ 0 ].setActive();
                return;
            }

            Next.setActive();
        },

        /**
         * Makes a click on the active element
         *
         * @param {DOMEvent} event - [optional]
         */
        select : function(event)
        {
            // Last Element
            if ( this.$Active )
            {
                this.$Active.fireEvent( 'mouseDown', [ this.$Active, event ] );
                this.$Active.fireEvent( 'click', [ this.$Active, event ] );
            }
        }

    });

    return QUI.controls.contextmenu.Menu;
});
