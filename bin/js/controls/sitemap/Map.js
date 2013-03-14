/**
 * Sitemap Map
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/sitemap/Map
 * @package com.pcsg.qui.js.controls.sitemap
 * @namespace QUI.controls.sitemap
 */

define('controls/sitemap/Map', [

    'controls/Control',
    'controls/sitemap/Item',

    'css!controls/sitemap/Map.css'

], function(Control, Item)
{
    QUI.namespace('controls.sitemap');

    /**
     * @class QUI.controls.sitemap.Map
     * @fires onChildClick
     * @event onAppendChild [{QUI.controls.sitemap.Item}]
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.controls.sitemap.Map = new Class({

        Implements: [ Control ],
        Type      : 'QUI.controls.sitemap.Map',

        options : {
            multible : false // multible selection true or false
        },

        initialize : function(options)
        {
            this.init( options );

            this.$items = [];
            //this.$nodes = {};
            this.$sels  = {};

            this.addEvent('onAppendChild', function(Parent, Child)
            {
                //this.$nodes[ Slick.uidOf(Child.getElm()) ] = Child;

                Child.addEvents({

                    onClick : function(Item, event)
                    {
                        this.fireEvent( 'childClick', [Item, this] );
                        Item.select();

                    }.bind(this),

                    onSelect : function(Item)
                    {
                        if ( this.getAttribute('multible') === false ||
                             this.getAttribute('multible') && !event.control )
                        {
                            this.deselectAllChildren();
                        }

                        this.$addSelected( Item );

                    }.bind(this)
                });

            }.bind(this));


            this.addEvent('onDestroy', function()
            {
                this.clearChildren();
            }.bind( this ));
        },

        /**
         * Create the DOMNode of the Map
         *
         * @method QUI.controls.sitemap.Map#create
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element('div.qui-sitemap-map', {
                'data-quiid' : this.getId()
            });

            for ( var i = 0, len = this.$items.length; i < len; i++ ) {
                this.$items[i].inject( this.$Elm );
            }

            return this.$Elm;
        },

        /**
         * Get the first Child if exists
         *
         * @method QUI.controls.sitemap.Map#firstChild
         * @return {QUI.controls.sitemap.Item || false}
         */
        firstChild : function()
        {
            return this.$items[0] || false;
        },

        /**
         * Add a Child
         *
         * @method QUI.controls.sitemap.Map#appendChild
         * @param {QUI.controls.sitemap.Item} Itm
         * @return {this}
         */
        appendChild : function(Itm)
        {
            Itm.setParent( this );
            Itm.setMap( this );

            this.$items.push( Itm );

            if ( this.$Elm ) {
                Itm.inject( this.$Elm );
            }

            this.fireEvent('appendChild', [this, Itm]);

            return this;
        },

        /**
         * Clear all children
         *
         * @method QUI.controls.sitemap.Map#clearChildren
         * @return {this}
         */
        clearChildren : function()
        {
            for ( var i = 0, len = this.$items.length; i < len; i++ ) {
                this.$clearItem( this.$items[i] );
            }

            return this;
        },

        /**
         * Get all selected Items
         *
         * @method QUI.controls.sitemap.Map#getSelectedChildren
         * @return {Array}
         */
        getSelectedChildren : function()
        {
            var i;
            var result = [];

            for ( i in this.$sels ) {
                result.push( this.$sels[i] );
            }

            return result;
        },

        /**
         * Get specific children
         *
         * @method QUI.controls.sitemap.Map#getChildren
         *
         * @param {String} selector
         * @return {Array}
         */
        getChildren : function(selector)
        {
            selector = selector || '.qui-sitemap-entry';

            var i, len, quiid, Child;

            var children = this.$Elm.getElements( selector ),
                result   = [],
                Controls = QUI.Controls;


            if ( !children.length ) {
                return result;
            }

            for ( i = 0, len = children.length; i < len; i++ )
            {
                quiid = children[i].get('data-quiid');
                Child = Controls.getById( quiid );

                if ( Child ) {
                    result.push( Child );
                }
            }

            return result;
        },

        /**
         * Alias for getChildren
         *
         * @method QUI.controls.sitemap.Map#getChildren
         * @see QUI.controls.sitemap.Map#getChildren
         */
        getElements : function(selector)
        {
            return this.getChildren( selector );
        },

        /**
         * Get specific children by value
         *
         * @method QUI.controls.sitemap.Map#getChildren
         *
         * @param {String|Integer} value
         * @return {Array}
         */
        getChildrenByValue : function(value)
        {
            return this.getChildren( '[data-value="'+ value +'"]' );
        },

        /**
         * Deselected all selected Items
         *
         * @method QUI.controls.sitemap.Map#deselectAllChildren
         * @return {this}
         */
        deselectAllChildren : function()
        {
            for ( var i in this.$sels ) {
                this.$sels[i].deselect();
            }

            this.$sels = {};

            return this;
        },

        /**
         * Execute a {QUI.controls.sitemap.Item} contextMenu
         *
         * @method QUI.controls.sitemap.Map#childContextMenu
         * @fires onChildContextMenu {QUI.controls.sitemap.Item}
         * @param {QUI.controls.sitemap.Item} Itm
         * @return {this}
         */
        childContextMenu : function(Itm, event)
        {
            if ( typeof Itm === 'undefined' ) {
                return this;
            }

            this.fireEvent( 'childContextMenu', [this, Itm, event] );

            return this;
        },

        /**
         * Opens all children and children children
         *
         * @method QUI.controls.sitemap.Map#openAll
         * @return {this}
         */
        openAll : function()
        {
            for ( var i = 0, len = this.$items.length; i < len; i++ ) {
                this.$openItem( this.$items[i] );
            }

            return this;
        },

        /**
         * Clear a child item
         *
         * @method QUI.controls.sitemap.Map#$clearItem
         * @param {QUI.controls.sitemap.Item}
         * @return {this}
         * @ignore
         */
        $clearItem : function(Item)
        {
            if ( Item.hasChildren() === false ) {
                return this;
            }

            var i, len;
            var children = Item.getChildren();

            for ( i = 0, len = children.length; i < len; i++ )
            {
                if ( children[i].hasChildren() ) {
                    this.$clearItem( children[i] );
                }
            }

            Item.clearChildren();
            Item.destroy();

            return this;
        },

        /**
         * Opens a child item
         *
         * @method QUI.controls.sitemap.Map#$openItem
         * @param {QUI.controls.sitemap.Item}
         * @return {this}
         * @ignore
         */
        $openItem : function(Item)
        {
            Item.open();

            if ( Item.hasChildren() === false ) {
                return this;
            }

            var i, len;
            var children = Item.getChildren();

            for ( i = 0, len = children.length; i < len; i++ )
            {
                if ( children[i].hasChildren() ) {
                    this.$openItem( children[i] );
                }
            }

            return this;
        },

        /**
         * Remove the child from the list
         *
         * @method QUI.controls.sitemap.Item#countChildren
         *
         * @param {QUI.controls.sitemap.Item} Child
         * @return {this}
         * @ignore
         */
        $removeChild : function(Child)
        {
            var items = [];

            for ( var i = 0, len = this.$items.length; i < len; i++ )
            {
                if ( this.$items[i].getId() !== Child.getId() ) {
                    items.push( this.$items[i] );
                }
            }

            this.$items = items;

            return this;
        },

        /**
         * Adds an selected Item to the sels list
         *
         * @method QUI.controls.sitemap.Map#$addSelected
         * @param {QUI.controls.sitemap.Item}
         * @return {this}
         * @ignore
         */
        $addSelected : function(Item)
        {
            this.$sels[ Item.getId() ] = Item;

            return this;
        },

        /**
         * Remove an selected Item from the sels list
         *
         * @method QUI.controls.sitemap.Map#$removeSelected
         *
         * @param {QUI.controls.sitemap.Item}
         * @return {this}
         * @ignore
         */
        $removeSelected : function(Item)
        {
            if ( this.$sels[ Item.getId() ] )
            {
                this.$sels[ Item.getId() ].deselect();
                delete this.$sels[ Item.getId() ];
            }

            return this;
        },

        /**
         * Sitemap filter, the user can search for certain items
         *
         * @method QUI.controls.sitemap.Map#search
         *
         * @oaram {String} search
         */
        search : function(search)
        {
            search = search || '';

            var i, len, qid, Item;

            var list     = this.$Elm.getElements('.qui-sitemap-entry-text'),
                result   = [],
                Controls = QUI.Controls,

                regex    = new RegExp(search, "gi");

            for ( i = 0, len = list.length; i < len; i++ )
            {
                if ( list[i].get('text').match( regex ) )
                {
                    qid  = list[i].getParent().get('data-quiid');
                    Item = Controls.getById( qid );

                    if ( Item ) {
                        result.push( Item );
                    }
                }
            }

            return result;
        }
    });

    return QUI.controls.sitemap.Map;
});
