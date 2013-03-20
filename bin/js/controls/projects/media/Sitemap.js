/**
 * Displays a sitemap from a media
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires controls/sitemap/Map
 *
 * @module controls/projects/media/Sitemap
 * @package com.pcsg.qui.js.controls.projects.media
 * @namespace QUI.controls.projects.media
 */

define('controls/projects/media/Sitemap', [

    'controls/Control',
    'controls/sitemap/Map'

], function(QUI_Control, QUI_Sitemap)
{
    "use strict";

    QUI.namespace( 'controls.projects.media' );

    /**
     * A media sitemap
     *
     * @class QUI.controls.projects.media.Sitemap
     *
     * @fires onOpenBegin [Item, Control]
     * @fires onOpenEnd [Item, Control]
     * @fires onItemClick [Item, Control]
     *
     * @param {Object} options
     */
    QUI.controls.projects.media.Sitemap = new Class({

        Implements : [QUI_Control],

        options : {
            name      : 'projects-media-sitemap',
            container : false,
            project   : false,
            lang      : false,
            id        : false
        },

        initialize : function(options)
        {
            this.init( options );

            this.$Elm = null;
            this.$Map = new QUI.controls.sitemap.Map();
        },

        /**
         * Returns the QUI.controls.sitemap.Map Control
         *
         * @method QUI.controls.projects.media.Sitemap#getMap
         *
         * @return {QUI.controls.sitemap.Map}
         */
        getMap : function()
        {
            return this.$Map;
        },

        /**
         * Create the DOMNode of the sitemap
         *
         * @method QUI.controls.projects.media.Sitemap#create
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
         * @method QUI.controls.projects.media.Sitemap#open
         */
        open : function()
        {
            if ( !this.$Elm ) {
                return;
            }

            this.$Map.clearChildren();

            this.$getItem(
                this.getAttribute('id') || 1,
                function(result, Request)
                {
                    var Control = Request.getAttribute('Control');

                    Control.$Map.clearChildren();
                    Control.$addSitemapItem(
                        Control.$Map,
                        Control.$parseArrayToSitemapitem( result )
                    );

                    Control.$Map.firstChild().open();
                }
            );
        },

        /**
         * Search the folder by value and select it
         * if the folder not exist
         * the sitemap search the parents and opens the path
         *
         * @method QUI.controls.projects.media.Sitemap#selectChildrenByValue
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
         * @method QUI.controls.projects.media.Sitemap#selectChildrenByValue
         */
        selectChildrenByValue : function(value)
        {
            var items = this.$Map.getChildrenByValue( value );

            for ( var i = 0, len = items.length; i < len; i++ ) {
                items[i].select();
            }
        },

        /**
         * Get specific children by value
         *
         * @method QUI.controls.projects.media.Sitemap#getChildrenByValue
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
         * @method QUI.controls.projects.media.Sitemap#getSelectedChildren
         * @return {Array}
         */
        getSelectedChildren : function()
        {
            return this.$Map.getSelectedChildren();
        },

        /**
         * Get the attributes from a media item
         *
         * @method QUI.controls.projects.media.Sitemap#$getSite
         *
         * @param {Integer} id - Item ID
         * @param {Function} callback - call back function, if ajax is finish
         *
         * @private
         * @ignore
         */
        $getItem : function(id, callback)
        {
            QUI.Ajax.get('ajax_media_get', function(result, Request)
            {
                Request.getAttribute('onfinish')(result, Request);
            }, {
                project  : this.getAttribute('project'),
                lang     : this.getAttribute('lang'),
                fileid   : id,
                Control  : this,
                onfinish : callback
            });
        },

        /**
         * Parse a ajax result set to a sitemap item
         *
         * @method QUI.controls.projects.media.Sitemap#$parseArrayToSitemapitem
         *
         * @param {Array} result
         * @return {QUI.controls.sitemap.Item}
         *
         * @private
         * @ignore
         */
        $parseArrayToSitemapitem : function(result)
        {
            var Itm;
            var file = result.file || result;

            Itm = new QUI.controls.sitemap.Item({
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

                        Control.fireEvent('openBegin', [Item, Control]);

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
                        QUI.Ajax.get('ajax_media_getsubfolders', function(result, Request)
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
         * @method QUI.controls.projects.media.Sitemap#$addSitemapItem
         *
         * @param {QUI.controls.sitemap.Item} Parent
         * @param {QUI.controls.sitemap.Item} Child
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

    return QUI.controls.projects.media.Sitemap;
});
