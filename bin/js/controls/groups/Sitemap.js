/**
 * A sitemap that listet the groups
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @namespace QUI.controls.groups
 * @module controls/groups/Sitemap
 * @package com.pcsg.qui.js.controls.groups
 *
 * @events onItemClick [ this, {QUI.controls.sitemap.Item} ]
 * @events onItemDblClick [ this, {QUI.controls.sitemap.Item} ]
 *
 * @require controls/Control
 * @require controls/sitemap/Map
 * @require controls/sitemap/Item
 */

define('controls/groups/Sitemap', [

    'controls/Control',
    'controls/sitemap/Map',
    'controls/sitemap/Item'

], function(Control)
{
    QUI.namespace('controls.groups');

    /**
     * @class QUI.controls.groups.Sitemap
     *
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.controls.groups.Sitemap = new Class({

        Implements : [ Control ],
        Type       : 'QUI.controls.groups.Sitemap',

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
            this.init( options );

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

            this.$Map = new QUI.controls.sitemap.Map({
                name     : 'Group-Sitemap',
                multible : this.getAttribute( 'multible' )
            });

            // Firstchild
            this.$Map.appendChild(
                new QUI.controls.sitemap.Item({
                    Control : this,
                    name    : 1,
                    index   : 1,
                    value   : 1,
                    text    : '',
                    alt     : '',
                    icon    : URL_BIN_DIR +'images/loader.gif',
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
            QUI.Ajax.get('ajax_groups_root', function(result, Ajax)
            {
                var First = Ajax.getAttribute('First');

                First.setAttributes({
                    name    : result.name,
                    index   : result.id,
                    value   : result.id,
                    text    : result.name,
                    alt     : result.name,
                    icon    : URL_BIN_DIR +'16x16/group.png',
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
         * @param {QUI.controls.sitemap.Item} Parent
         */
        getChildren : function(Parent)
        {
            Parent.setAttribute( 'icon', URL_BIN_DIR +'images/loader.gif' );

            var Group = QUI.Groups.get( Parent.getAttribute('value') );

            Group.getChildren(function(result, Request)
            {
                var i, len, entry;

                var Parent  = Request.getAttribute( 'Parent' ),
                    Control = Request.getAttribute( 'Control' );

                Parent.clearChildren();

                for ( i = 0, len = result.length; i < len; i++ )
                {
                    entry = result[i];

                    Parent.appendChild(
                        new QUI.controls.sitemap.Item({
                            Control : Request.getAttribute('Control'),
                            name    : entry.name,
                            index   : entry.id,
                            value   : entry.id,
                            text    : entry.name,
                            alt     : entry.name,
                            icon    : URL_BIN_DIR +'16x16/group.png',
                            hasChildren : entry.hasChildren,
                            events :
                            {
                                onOpen     : Control.getChildren,
                                onClick    : Control.$onItemClick,
                                onDblClick : Control.$onItemDblClick
                            }
                        })
                    );
                }

                Parent.setAttribute( 'icon', URL_BIN_DIR +'16x16/group.png' );
            }, {
                Parent  : Parent,
                Control : this
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
         * @param {QUI.controls.sitemap.Item} Item
         */
        $onItemClick : function(Item)
        {
            this.fireEvent( 'onItemClick', [ this, Item ] );
        },

        /**
         * event : click on a sitemap item
         *
         * @param {QUI.controls.sitemap.Item} Item
         */
        $onItemDblClick : function(Item)
        {
            this.fireEvent( 'onItemDblClick', [ this, Item ] );
        }
    });

    return QUI.controls.groups.Sitemap;
});