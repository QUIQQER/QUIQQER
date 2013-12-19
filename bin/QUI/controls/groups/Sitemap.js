/**
 * A sitemap that listet the groups
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/groups/Sitemap
 * @package com.pcsg.qui.js.controls.groups
 *
 * @events onItemClick [ this, {qui/controls/sitemap/Item} ]
 * @events onItemDblClick [ this, {qui/controls/sitemap/Item} ]
 *
 * @require controls/Control
 * @require controls/sitemap/Map
 * @require controls/sitemap/Item
 */

define('controls/groups/Sitemap', [

    'qui/controls/Control',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'Ajax',
    'Groups'

], function(QUIControl, QUISitemap, QUISitemapItem, Ajax, Groups)
{
    "use strict";

    /**
     * @class controls/groups/Sitemap
     *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIControl,
        Type    : 'controls/groups/Sitemap',

        Binds : [
            'getChildren',
            '$onItemClick',
            '$onDrawEnd'
        ],

        options : {
            multible : false
        },

        $Map       : null,
        $Container : null,

        initialize : function(options)
        {
            this.parent( options );

            this.$Map = null;
            this.addEvent( 'onDrawEnd', this.$onDrawEnd );
        },

        /**
         * Create the DomNode Element of the Control
         *
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Elm = new Element( 'div.qui-group-sitemap' );

            this.$Map = new QUISitemap({
                name     : 'Group-Sitemap',
                multible : this.getAttribute( 'multible' )
            });

            // Firstchild
            this.$Map.appendChild(
                new QUISitemapItem({
                    Control : this,
                    name    : 1,
                    index   : 1,
                    value   : 1,
                    text    : '',
                    alt     : '',
                    icon    : 'icon-refresh',
                    hasChildren : false,
                    events :
                    {
                        onOpen     : this.getChildren,
                        onClick    : this.$onItemClick,
                        onDblClick : this.$onItemDblClick
                    }
                })
            );

            this.$Map.inject( this.$Elm );

            return this.$Elm;
        },

        /**
         * the DOMNode is injected, then call the root group
         */
        $onDrawEnd : function()
        {
            // load first child
            Ajax.get('ajax_groups_root', function(result, Ajax)
            {
                var First = Ajax.getAttribute('First');

                First.setAttributes({
                    name    : result.name,
                    index   : result.id,
                    value   : result.id,
                    text    : result.name,
                    alt     : result.name,
                    icon    : 'icon-group',
                    hasChildren : result.hasChildren
                });

                First.open();
            }, {
                First : this.$Map.firstChild()
            });
        },

        /**
         * Display the children of the sitemap item
         *
         * @param {qui/controls/sitemap/Item} Parent
         */
        getChildren : function(Parent)
        {
            Parent.setAttribute( 'icon', 'icon-refresh' );

            var self  = this,
                Group = Groups.get( Parent.getAttribute('value') );

            Group.getChildren(function(result, Request)
            {
                var i, len, entry;

                Parent.clearChildren();

                for ( i = 0, len = result.length; i < len; i++ )
                {
                    entry = result[i];

                    Parent.appendChild(
                        new QUISitemapItem({
                            name    : entry.name,
                            index   : entry.id,
                            value   : entry.id,
                            text    : entry.name,
                            alt     : entry.name,
                            icon    : 'icon-group',
                            hasChildren : entry.hasChildren,
                            events :
                            {
                                onOpen     : self.getChildren,
                                onClick    : self.$onItemClick,
                                onDblClick : self.$onItemDblClick
                            }
                        })
                    );
                }

                Parent.setAttribute( 'icon', 'icon-group' );
            });
        },

        /**
         * Return the values of the selected sitemap items
         *
         * @return {Array}
         */
        getValues : function()
        {
            var i, len;

            var sels   = this.$Map.getSelectedChildren(),
                result = [];

            for ( i = 0, len = sels.length; i < len; i++ )
            {
                result.push(
                    sels[ i ].getAttribute( 'value' )
                );
            }

            return result;
        },

        /**
         * event : click on a sitemap item
         *
         * @param {qui/controls/sitemap/Item} Item
         */
        $onItemClick : function(Item)
        {
            this.fireEvent( 'onItemClick', [ this, Item ] );
        },

        /**
         * event : click on a sitemap item
         *
         * @param {qui/controls/sitemap/Item} Item
         */
        $onItemDblClick : function(Item)
        {
            this.fireEvent( 'onItemDblClick', [ this, Item ] );
        }
    });
});