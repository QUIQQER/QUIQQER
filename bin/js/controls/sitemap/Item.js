/**
 * Sitemap Item
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires controls/contextmenu/Menu
 * @requires controls/contextmenu/Item
 *
 * @module controls/sitemap/Item
 * @package com.pcsg.qui.js.controls.sitemap
 * @namespace QUI.controls.sitemap
 */

define('controls/sitemap/Item', [

    'controls/Control',
    'controls/contextmenu/Menu',
    'controls/contextmenu/Item',

    'css!controls/sitemap/Item.css'

], function(QUI_Control, QUI_ContextMenu, QUI_ContextMenuItem)
{
    "use strict";

    QUI.namespace( 'controls.sitemap' );

    /**
     * @class QUI.controls.sitemap.Item
     *
     * @fires onOpen [this]
     * @fires onClose [this]
     * @fires onClick [this, event]
     * @fires onContextMenu [this, event]
     * @fires onSelect [this]
     * @fires onDeSelect [this]
     *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.controls.sitemap.Item = new Class({

        Extends : QUI_Control,
        Type    : 'QUI.controls.sitemap.Item',

        Binds : [
            'toggle',
            'click',
            '$onChildDestroy',
            '$onSetAttribute'
        ],

        options : {
            value : '',
            text  : '',
            icon  : '',

            alt   : '',
            title : '',

            hasChildren : false,

            icon_opener_plus  : QUI.config('dir') +'controls/sitemap/images/plus.png',
            icon_opener_minus : QUI.config('dir') +'controls/sitemap/images/minus.png'
        },

        $Elm   : null,
        $items : [],

        initialize : function(options)
        {
            this.init( options );

            this.$Elm    = null;
            this.$Map    = null;
            this.$Opener = null;
            this.$Icons  = null;
            this.$Text   = null;

            this.$Children    = null;
            this.$ContextMenu = null;

            this.$items = [];

            this.addEvent( 'onSetAttribute', this.$onSetAttribute );

            this.addEvent('onDestroy', function(Item)
            {
                Item.clearChildren();

                if ( this.$Opener ) {
                    this.$Opener.destroy();
                }

                if ( this.$Icons ) {
                    this.$Icons.destroy();
                }

                if ( this.$Text ) {
                    this.$Text.destroy();
                }

                if ( this.$Children ) {
                    this.$Children.destroy();
                }

                if ( this.$ContextMenu ) {
                    this.$ContextMenu.destroy();
                }
            });
        },

        /**
         * Create the DOMNode of the Sitemap Item
         *
         * @method QUI.controls.sitemap.Item#create
         * @return {DOMNode}
         */
        create : function(Parent)
        {
            var i, len;

            this.$Elm = new Element('div.qui-sitemap-entry', {
                alt   : this.getAttribute('alt'),
                title : this.getAttribute('title'),
                'data-value' : this.getAttribute('value'),
                'data-quiid' : this.getId(),
                html  : '<div class="qui-sitemap-entry-opener"></div>' +
                        '<div class="qui-sitemap-entry-icon"></div>' +
                        '<div class="qui-sitemap-entry-text">###</div>' +
                        '<div class="qui-sitemap-entry-children"></div>',
                events :
                {
                    contextmenu : function(event)
                    {
                        if ( this.getMap() ) {
                            this.getMap().childContextMenu( this, event );
                        }

                        this.fireEvent( 'contextMenu', [ this, event ] );
                    }.bind(this)
                }
            });

            this.$Opener   = this.$Elm.getElement('.qui-sitemap-entry-opener');
            this.$Icons    = this.$Elm.getElement('.qui-sitemap-entry-icon');
            this.$Text     = this.$Elm.getElement('.qui-sitemap-entry-text');
            this.$Children = this.$Elm.getElement('.qui-sitemap-entry-children');

            // events
            this.$Opener.addEvents({
                click : this.toggle
            });

            this.$Text.addEvents({
                click : this.click
            });

            // ui
            this.$Children.setStyle( 'display', 'none' );


            if ( this.getAttribute( 'icon' ) )
            {
                this.$Icons.setStyle(
                    'background-image',
                    'url('+ this.getAttribute( 'icon' ) +')'
                );
            }

            if ( this.getAttribute( 'text' ) ) {
                this.$Text.set( 'html', this.getAttribute( 'text' ) );
            }

            len = this.$items.length;

            if ( len || this.hasChildren() )
            {
                this.$setOpener();

                for ( i = 0; i < len; i++ ) {
                    this.$items[i].inject( this.$Children );
                }
            }

            return this.$Elm;
        },

        /**
         * Return the text DOMNode
         *
         * @return {DOMNode|null}
         */
        getTextElm : function()
        {
            if ( this.$Text ) {
                return this.$Text;
            }

            return null;
        },

        /**
         * Add an Icon to the Icon Container
         * You can only add an icon if the main DOMNode are drawed
         *
         * @method QUI.controls.sitemap.Item#addIcon
         * @param {String} icon_url - URL of the Image
         * @return {this}
         */
        addIcon : function(icon_url)
        {
            if ( !this.$Icons ) {
                this.getElm();
            }

            var Img = this.$Icons.getElement( '[src="'+ icon_url +'"]' );

            if ( Img ) {
                return this;
            }

            new Element('img', {
                src    : icon_url,
                styles : {
                    position : 'absolute',
                    left     : 0,
                    top      : 0,
                    zIndex   : 10
                }
            }).inject( this.$Icons );

            return this;
        },

        /**
         * Remove an icon of the Icon Container
         *
         * @method QUI.controls.sitemap.Item#removeIcon
         * @param {String} icon_url - URL of the Image
         * @return {this}
         */
        removeIcon : function(icon_url)
        {
            if ( !this.$Icons ) {
                return this;
            }

            var Img = this.$Icons.getElement( '[src="'+ icon_url +'"]' );

            if ( Img ) {
                Img.destroy();
            }

            return this;
        },

        /**
         * Activate the Item. The inactive Icon would be destroy
         *
         * @method QUI.controls.sitemap.Item#activate
         * @return {this}
         */
        activate : function()
        {
            this.removeIcon(
                QUI.config('dir') + 'controls/sitemap/images/inactive.png'
            );

            return this;
        },

        /**
         * Deactivate the Item. Add inactive Icon
         *
         * @method QUI.controls.sitemap.Item#deactivate
         * @return {this}
         */
        deactivate : function()
        {
            this.addIcon(
                QUI.config('dir') + 'controls/sitemap/images/inactive.png'
            );

            return this;
        },

        /**
         * Add a Child
         *
         * @method QUI.controls.sitemap.Item#appendChild
         * @param {QUI.controls.sitemap.Item} Child
         * @return {this}
         */
        appendChild : function(Child)
        {
            this.$items.push( Child );
            this.$setOpener();

            if ( this.$Children )
            {
                Child.inject( this.$Children );

                var size = this.$Children.getSize();

                if ( size.x )
                {
                    var child_size = 10 +
                                     Child.$Opener.getSize().x +
                                     Child.$Icons.getSize().x +
                                     Child.$Text.getSize().x;

                    if ( child_size > size.x ) {
                        this.$Children.setStyle( 'width', child_size );
                    }
                }
            }

            Child.setParent( this );        // set the parent to the this
            Child.setMap( this.getMap() );  // set the parent to the Map

            Child.addEvents({
                onDestroy : this.$onChildDestroy
            });

            this.getMap().fireEvent( 'appendChild', [ this, Child ] );

            return this;
        },

        /**
         * Get the first Child if exists
         *
         * @method QUI.controls.sitemap.Item#firstChild
         * @return {QUI.controls.sitemap.Item || false}
         */
        firstChild : function()
        {
            return this.$items[0] || false;
        },

        /**
         * Have the Items childrens?
         * Observed the hasChildren Attribute
         *
         * @method QUI.controls.sitemap.Item#hasChildren
         * @return Bool
         */
        hasChildren : function()
        {
            if ( this.getAttribute( 'hasChildren' ) ) {
                return true;
            }

            return this.$items.length ? true : false;
        },

        /**
         * Returns the children items
         *
         * @method QUI.controls.sitemap.Item#getChildren
         * @return {Array}
         */
        getChildren : function()
        {
            return this.$items;
        },

        /**
         * Delete all children
         *
         * @method QUI.controls.sitemap.Item#clearChildren
         * @return {this}
         */
        clearChildren : function()
        {
            var i, len;
            var items = this.$items;

            for ( i = 0, len = items.length; i < len; i++ )
            {
                if ( items[ i ] ) {
                    items[ i ].destroy();
                }
            }

            this.$Children.set( 'html', '' );
            this.$items = [];

            return this;
        },

        /**
         * Get the number of children
         *
         * @method QUI.controls.sitemap.Item#countChildren
         * @return {Integer}
         */
        countChildren : function()
        {
            return this.$items.length;
        },

        /**
         * Remove the child from the list
         *
         * @method QUI.controls.sitemap.Item#countChildren
         *
         * @param {QUI.controls.sitemap.Item} Child
         * @return {this} self
         * @ignore
         */
        $removeChild : function(Child)
        {
            var items = [];

            for ( var i = 0, len = this.$items.length; i < len; i++ )
            {
                if ( this.$items[ i ].getId() !== Child.getId() ) {
                    items.push( this.$items[ i ] );
                }
            }

            this.$items = items;

            this.setAttribute( 'hasChildren', this.$items.length ? true : false );
            this.$setOpener();

            return this;
        },

        /**
         * Select the Item
         *
         * @method QUI.controls.sitemap.Item#select
         * @return {this} self
         */
        select : function()
        {
            this.fireEvent( 'select', [ this ] );

            if ( this.$Text ) {
                this.$Text.addClass( 'select' );
            }

            return this;
        },

        /**
         * Deselect the Item
         *
         * @method QUI.controls.sitemap.Item#deselect
         * @return {this}
         */
        deselect : function()
        {
            this.fireEvent( 'deSelect', [ this ] );

            if ( this.$Text ) {
                this.$Text.removeClass( 'select' );
            }

            return this;
        },

        /**
         * Normalite the item
         * no selection or highlighting
         *
         * @return {this}
         */
        normalize : function()
        {
            if ( this.$Text )
            {
                this.$Text.removeClass( 'select' );
                this.$Text.removeClass( 'holdBack' );
            }

            if ( this.$Opener ) {
                this.$Opener.removeClass( 'holdBack' );
            }

            if ( this.$Icons ) {
                this.$Icons.removeClass( 'holdBack' );
            }

            return this;
        },

        /**
         * the item is a little disappear
         *
         * @method QUI.controls.sitemap.Item#holdBack
         * @return {this} self
         */
        holdBack : function()
        {
            if ( this.$Text ) {
                this.$Text.addClass( 'holdBack' );
            }

            if ( this.$Opener ) {
                this.$Opener.addClass( 'holdBack' );
            }

            if ( this.$Icons ) {
                this.$Icons.addClass( 'holdBack' );
            }
        },

        /**
         * Klick the sitemap item
         *
         * @method QUI.controls.sitemap.Item#click
         * @param {Event} event - [optional -> event click]
         */
        click : function(event)
        {
            this.select();
            this.fireEvent( 'click', [this, event] );
        },

        /**
         * Opens the childrens
         *
         * @method QUI.controls.sitemap.Item#open
         * @return {this} self
         */
        open : function()
        {
            if ( !this.$Children ) {
                return this;
            }

            this.$Children.setStyle( 'display', '' );
            this.$setOpener();

            this.fireEvent( 'open', [ this ] );

            return this;
        },

        /**
         * Close the childrens
         *
         * @method QUI.controls.sitemap.Item#close
         * @return {this} self
         */
        close : function()
        {
            if ( !this.$Children ) {
                return this;
            }
            this.$Children.setStyle( 'display', 'none' );
            this.$setOpener();

            this.fireEvent( 'close', [this] );

            return this;
        },

        /**
         * Switch between open and close
         *
         * @method QUI.controls.sitemap.Item#toggle
         * @return {this} self
         */
        toggle : function()
        {
            if ( this.isOpen() )
            {
                this.close();
            } else
            {
                this.open();
            }

            return this;
        },

        /**
         * Is the Item open?
         *
         * @method QUI.controls.sitemap.Item#isOpen
         * @return {Bool}
         */
        isOpen : function()
        {
            if ( !this.$Children ) {
                return false;
            }

            return this.$Children.getStyle( 'display' ) == 'none' ? false : true;
        },

        /**
         * Create and return a contextmenu for the Element
         *
         * @method QUI.controls.sitemap.Item#getContextMenu
         * @return {QUI.controls.contextmenu.Menu} Menu
         */
        getContextMenu : function()
        {
            if ( this.$ContextMenu ) {
                return this.$ContextMenu;
            }

            var cm_name = this.getAttribute( 'name' ) || this.getId();

            this.$ContextMenu = new QUI.controls.contextmenu.Menu({
                name   : cm_name +'-contextmenu',
                events :
                {
                    onShow : function(Menu)
                    {
                        Menu.focus();
                    },
                    onBlur : function(Menu)
                    {
                        Menu.hide();
                    }
                }
            });

            this.$ContextMenu.inject( document.body );
            this.$ContextMenu.hide();

            return this.$ContextMenu;
        },

        /**
         * Get the map parent, if it is set
         *
         * @method QUI.controls.sitemap.Item#getMap
         * @return {QUI.controls.sitemap.Map|null} Map
         */
        getMap : function()
        {
            return this.$Map;
        },

        /**
         * Set the map parent
         *
         * @method QUI.controls.sitemap.Item#getMap
         *
         * @param {QUI.controls.sitemap.Map} Map
         * @return {this}
         */
        setMap : function(Map)
        {
            this.$Map = Map;

            return this;
        },

        /**
         * @method QUI.controls.sitemap.Item#$setOpener
         */
        $setOpener : function()
        {
            if ( !this.$Elm ) {
                return;
            }

            if ( this.hasChildren() === false )
            {
                this.$Opener.setStyle( 'background-image', '' );
                return;
            }

            if ( this.isOpen() )
            {
                this.$Opener.setStyle(
                    'background-image',
                    'url('+ this.getAttribute('icon_opener_minus') +')'
                );
            } else
            {
                this.$Opener.setStyle(
                    'background-image',
                    'url('+ this.getAttribute('icon_opener_plus') +')'
                );
            }
        },

        /**
         * event : on set attribute
         * change the DOMNode Element if some attributes changed
         *
         * @method QUI.controls.sitemap.Item#$onSetAttribute
         * @param {String} key - attribute name
         * @param {String} value - attribute value
         */
        $onSetAttribute : function(key, value)
        {
            if ( !this.$Elm ) {
                return;
            }

            if ( key == 'icon' )
            {
                this.$Icons.setStyle('background-image', 'url('+ value +')');
                return;
            }

            if ( key == 'text' )
            {
                this.$Text.set( 'html', value );

                var w = ( this.$Text.getSize().x ).toInt();

                if ( this.$Opener ) {
                    w = w + ( this.$Opener.getSize().x ).toInt();
                }

                if ( this.$Icons ) {
                    w = w + ( this.$Icons.getSize().x ).toInt();
                }

                this.$Elm.setStyle( 'width', w );
                return;
            }

            if ( key == 'value' )
            {
                this.$Elm.set( 'data-value', value );
                return;
            }
        },

        /**
         * event : children destroy
         *
         * @method QUI.controls.sitemap.Item#$onChildDestroy
         * @param {QUI.controls.sitemap.Item} Item
         */
        $onChildDestroy : function(Item)
        {
            this.$removeChild( Item );
        }
    });

    return QUI.controls.sitemap.Item;
});