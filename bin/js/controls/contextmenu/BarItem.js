/**
 * Context Menu Bar Item
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/contextmenu/BarItem
 *
 * @module controls/contextmenu/BarItem
 * @package com.pcsg.qui.js.controls.contextmenu
 * @namespace QUI.controls.contextmenu
 *
 * @event onClick [ {this}, {DOMEvent} ]
 * @event onFocus [ {this} ]
 * @event onBlur [ {this} ]
 * @event onMouseLeave [ {this} ]
 * @event onMouseEnter [ {this} ]
 */

define('controls/contextmenu/BarItem', [

    'controls/Control',

    'css!controls/contextmenu/BarItem.css'

], function(Control)
{
    QUI.namespace( 'controls.contextmenu' );

    /**
     * @class QUI.controls.contextmenu.BarItem
     *
     * @event onClick [this, event]
     * @event onMouseDown [this, event]
     * @event onActive [this]
     * @event onNormal [this]
     *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.controls.contextmenu.BarItem = new Class({

        Implements: [ Control ],
        Type      : 'QUI.controls.contextmenu.BarItem',

        Binds : [
            '$onSetAttribute',
            '$onClick',
            '$onMouseEnter',
            '$onMouseLeave',
            'blur',
            'focus'
        ],

        options : {
            text   : '',
            icon   : '',
            styles : null
        },

        initialize : function(options)
        {
            var items = options.items || [];
            delete options.items;

            this.init( options );

            this.$Elm   = null;
            this.$Menu  = null;

            this.addEvents({
                'onSetAttribute' : this.$onSetAttribute
            });

            if ( items.length ) {
                this.insert( items );
            }
        },

        /**
         * Create the DOMNode for the Element
         *
         * @method QUI.controls.contextmenu.BarItem#create
         * @return {DOMNode}
         */
        create : function()
        {
            var i, len;

            this.$Elm = new Element('div', {
                'class'      : 'qui-contextmenu-baritem smooth',
                html         : '<span class="qui-contextmenu-baritem-text radius10 smooth"></span>',
                'data-quiid' : this.getId(),
                tabindex     : -1,

                styles : {
                    outline : 0
                },

                events : {
                    click : this.$onClick,
                    blur  : function()
                    {
                        this.blur();
                        return true;
                    }.bind( this ),
                    focus : function(event)
                    {
                        this.focus();
                        return true;
                    }.bind( this ),
                    mouseenter : this.$onMouseEnter,
                    mouseleave : this.$onMouseLeave,

                    mousedown : function(event) {
                        event.stop();
                    },
                    mouseup : function(event) {
                        event.stop();
                    }
                }
            });

            if ( this.getAttribute( 'icon' ) &&
                 this.getAttribute( 'icon' ) !== '' )
            {
                this.$Elm
                    .setStyle( 'background-image', 'url('+ this.getAttribute( 'icon' ) +')' );
            }

            if ( this.getAttribute( 'text' ) &&
                 this.getAttribute( 'text' ) !== '' )
            {
                this.$Elm.getElement( '.qui-contextmenu-baritem-text' )
                         .set( 'html', this.getAttribute('text') );
            }

            // Create sub menu, if it exist
            if ( this.$Menu ) {
                this.$Menu.inject( this.$Elm );
            }

            return this.$Elm;
        },

        /**
         * Focus the item
         *
         * @method QUI.controls.contextmenu.BarItem#focus
         * @return {this}
         */
        focus : function()
        {
            this.$Elm.focus();
            this.fireEvent( 'focus', [ this ] );

            this.show();
            this.setActive();

            return this;
        },

        /**
         * Blur the item
         *
         * @method QUI.controls.contextmenu.BarItem#blur
         * @return {this}
         */
        blur : function()
        {
            this.fireEvent( 'blur', [ this ] );

            this.hide();
            this.setNormal();

            return this;
        },

        /**
         * Import children
         * from a php callback or an array
         *
         * @method QUI.controls.contextmenu.BarItem#insert
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
                    new QUI.controls.contextmenu.Item( list[ i ] )
                );
            }

            return this;
        },

        /**
         * Opens the submenu
         *
         * @method QUI.controls.contextmenu.BarItem#show
         * @return {this}
         */
        show : function()
        {
            if ( this.isActive() ) {
                return this;
            }

            if ( this.getContextMenu().count() )
            {
                if ( this.getContextMenu().$Active ) {
                    this.getContextMenu().$Active.setNormal();
                }

                this.getContextMenu().show();
                this.getElm().addClass( 'bar-menu' );
            }

            return this;
        },

        /**
         * Close the submenu
         *
         * @method QUI.controls.contextmenu.BarItem#hide
         */
        hide : function()
        {
            this.getElm().removeClass( 'bar-menu' );
            this.getContextMenu().hide();
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
            this.getContextMenu().appendChild( Child );

            Child.setParent( this );

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
            return this.getContextMenu().getChildren();
        },

        /**
         * Clear the Context Menu Items
         *
         * @method QUI.controls.contextmenu.Item#clear
         * @return {this}
         */
        clear : function()
        {
            this.getContextMenu().clear();

            return this;
        },

        /**
         * Create the Context Menu if not exist
         *
         * @method QUI.controls.contextmenu.Item#getContextMenu
         * @return {QUI.controls.contextmenu.Menu}
         */
        getContextMenu : function()
        {
            if ( !this.$Menu )
            {
                this.$Menu = new QUI.controls.contextmenu.Menu({
                    name   : this.getAttribute( 'name' ) +'-menu',
                    shadow : false,
                    events :
                    {
                        onShow : function(Menu)
                        {
                            var children = Menu.getChildren();

                            for (var i = 0, len = children.length; i < len; i++) {
                                children[ i ].setNormal();
                            }
                        }
                    }
                });
            }

            if ( this.$Elm )
            {
                this.$Menu.inject( this.$Elm );
                this.$Menu.hide();
                this.$Menu.setPosition( 0, this.$Elm.getSize().y );
            }

            return this.$Menu;
        },

        /**
         * Set the Item active
         *
         * @method QUI.controls.contextmenu.BarItem#setActive
         * @return {this}
         */
        setActive : function()
        {
            if ( this.isActive() ) {
                return this;
            }

            this.fireEvent( 'active', [ this ] );
            this.$Elm.addClass( 'qui-contextmenu-baritem-active' );

            return this;
        },

        /**
         * Is the Item active?
         *
         * @method QUI.controls.contextmenu.BarItem#isActive
         * @return {Boolean}
         */
        isActive : function()
        {
            if ( this.$Elm && this.$Elm.hasClass( '.qui-contextmenu-baritem-active' ) ) {
                return true;
            }

            return false;
        },

        /**
         * Set the Item active
         *
         * @method QUI.controls.contextmenu.BarItem#setNormal
         */
        setNormal : function()
        {
            this.$Elm.removeClass( 'qui-contextmenu-baritem-active' );
            this.fireEvent( 'normal', [ this ] );

            return this;
        },

        /**
         * onSetAttribute Event
         * Set the attribute to the DOMElement if setAttribute is execute
         *
         * @method QUI.controls.contextmenu.BarItem#$onSetAttribute
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
                this.$Elm.getElement('.qui-contextmenu-baritem-text')
                         .set('html', value);

                return;
            }

            if ( key == 'icon' )
            {
                this.$Elm.setStyle('background-image', 'url('+ value +')');
                return;
            }
        },

        /**
         * event : onclick
         *
         * @method QUI.controls.contextmenu.BarItem#$onClick
         */
        $onClick : function(event)
        {
            this.fireEvent( 'click', [ this, event ] );
            this.focus();
        },

        /**
         * event : on mouse enter
         *
         * @method QUI.controls.contextmenu.BarItem#$onMouseEnter
         */
        $onMouseEnter : function()
        {
            this.fireEvent( 'mouseEnter', [ this ] );
        },

        /**
         * event : on mouse leave
         *
         * @method QUI.controls.contextmenu.BarItem#$onMouseLeave
         */
        $onMouseLeave : function()
        {
            this.fireEvent( 'mouseLeave', [ this ] );
        }
    });

    return QUI.controls.contextmenu.BarItem;
});