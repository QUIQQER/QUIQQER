/**
 * Displays a sitemap from a media
 *
 * @module controls/projects/project/media/Sitemap
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/Control
 * @require qui/controls/sitemap/Map
 * @require qui/controls/sitemap/Item
 * @require Ajax
 */

define([

    'qui/controls/Control',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'Ajax'

], function(QUIControl, QUISitemap, QUISitemapItem, Ajax)
{
    "use strict";

    /**
     * A media sitemap
     *
     * @class controls/projects/project/media/Sitemap
     *
     * @fires onOpenBegin [Item, Control]
     * @fires onOpenEnd [Item, Control]
     * @fires onItemClick [Item, Control]
     *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIControl,

        options : {
            name      : 'projects-media-sitemap',
            container : false,
            project   : false,
            lang      : false,
            id        : false
        },

        initialize : function(options)
        {
            this.parent( options );

            this.$Elm = null;
            this.$Map = new QUISitemap();
        },

        /**
         * Returns the qui/controls/sitemap/Map Control
         *
         * @method controls/projects/project/media/Sitemap#getMap
         *
         * @return {qui/controls/sitemap/Map}
         */
        getMap : function()
        {
            return this.$Map;
        },

        /**
         * Create the DOMNode of the sitemap
         *
         * @method controls/projects/project/media/Sitemap#create
         *
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = this.$Map.create();

            return this.$Elm;
        },

        /**
         * Open the Map
         *
         * @method controls/projects/project/media/Sitemap#open
         */
        open : function()
        {
            if ( !this.$Elm ) {
                return;
            }

            var self = this;

            this.$Map.clearChildren();

            this.$getItem(
                this.getAttribute('id') || 1,
                function(result, Request)
                {
                    self.$Map.clearChildren();

                    self.$addSitemapItem(
                        self.$Map,
                        self.$parseArrayToSitemapitem( result )
                    );

                    self.$Map.firstChild().open();
                }
            );
        },

        /**
         * Search the folder by value and select it
         * if the folder not exist
         * the sitemap search the parents and opens the path
         *
         * @method controls/projects/project/media/Sitemap#selectChildrenByValue
         * @param {Integer} fileid
         */
        selectFolder : function(fileid)
        {
            var list = this.getChildrenByValue( fileid );

            if ( list.length )
            {
                list.each(function(Itm)
                {
                    Itm.select();

                    if ( !Itm.isOpen() ) {
                        Itm.open();
                    }
                });

                return;
            }
        },

        /**
         * Search the a items by value and select the item
         *
         * @method controls/projects/project/media/Sitemap#selectChildrenByValue
         */
        selectChildrenByValue : function(value)
        {
            var items = this.$Map.getChildrenByValue( value );

            for ( var i = 0, len = items.length; i < len; i++ ) {
                items[ i ].select();
            }
        },

        /**
         * Get specific children by value
         *
         * @method controls/projects/project/media/Sitemap#getChildrenByValue
         *
         * @param {String|Integer} value
         * @return {Array}
         */
        getChildrenByValue : function(value)
        {
            return this.$Map.getChildrenByValue( value );
        },

        /**
         * Get all selected Items
         *
         * @method controls/projects/project/media/Sitemap#getSelectedChildren
         * @return {Array}
         */
        getSelectedChildren : function()
        {
            return this.$Map.getSelectedChildren();
        },

        /**
         * Get the attributes from a media item
         *
         * @method controls/projects/project/media/Sitemap#$getSite
         *
         * @param {Integer} id - Item ID
         * @param {Function} callback - call back function, if ajax is finish
         *
         * @private
         * @ignore
         */
        $getItem : function(id, callback)
        {
            Ajax.get('ajax_media_get', callback, {
                project : this.getAttribute('project'),
                lang    : this.getAttribute('lang'),
                fileid  : id
            });
        },

        /**
         * Parse a ajax result set to a sitemap item
         *
         * @method controls/projects/project/media/Sitemap#$parseArrayToSitemapitem
         *
         * @param {Array} result
         * @return {qui/controls/sitemap/Item}
         *
         * @private
         * @ignore
         */
        $parseArrayToSitemapitem : function(result)
        {
            var Itm;
            var file = result.file || result;

            Itm = new QUISitemapItem({
                name        : file.name,
                index       : file.id,
                value       : file.id,
                text        : file.name,
                icon        : file.icon,
                type        : file.type,
                Control     : this,
                hasChildren : file.hasSubfolders || false,

                events :
                {
                    onOpen : function(Item)
                    {
                        var Control  = Item.getAttribute('Control'),
                            children = Item.getAttribute('children');

                        Control.fireEvent( 'openBegin', [ Item, Control ] );

                        Item.clearChildren();

                        /*
                        if (children)
                        {
                            for (var i = 0, len = children.length; i < len; i++)
                            {
                                Control.$addSitemapItem(
                                    Item,
                                    Control.$parseArrayToSitemapitem({
                                        file : children[i]
                                    })
                                );
                            };

                            return;
                        };
                        */

                        // if children are false
                        Ajax.get('ajax_media_getsubfolders', function(result, Request)
                        {
                            var i, len;

                            var Control = Request.getAttribute('Control'),
                                Parent  = Request.getAttribute('Item');

                            for (i = 0, len = result.length; i < len; i++)
                            {
                                Control.$addSitemapItem(
                                    Parent,
                                    Control.$parseArrayToSitemapitem( result[i] )
                                );
                            }

                            Control.fireEvent('openEnd', [Parent, Control]);

                        }, {
                            project : Control.getAttribute('project'),
                            lang    : Control.getAttribute('lang'),
                            fileid  : Item.getAttribute('value'),
                            Item    : Item,
                            Control : Control
                        });
                    },

                    onClick : function(Itm, event)
                    {
                        Itm.getAttribute('Control').fireEvent('itemClick', [
                            Itm,
                            Itm.getAttribute('Control')
                        ]);
                    }
                }
            });

            if ( file.active === false ) {
                Itm.deactivate();
            }

            return Itm;
        },

        /**
         * Add the item to its parent
         * set the control attributes to the child item
         *
         * @method controls/projects/project/media/Sitemap#$addSitemapItem
         *
         * @param {qui/controls/sitemap/Item} Parent
         * @param {qui/controls/sitemap/Item} Child
         *
         * @private
         * @ignore
         */
        $addSitemapItem : function(Parent, Child)
        {
            if ( Child.getAttribute('type') !== 'folder' ) {
                return;
            }

            Child.setAttribute('Control', this);

            Parent.appendChild( Child );
        }
    });
});
