/**
 * The type sitemap for the project
 *
 * The type sitemap displays / create a QUI.controls.sitemap.Map
 * with all available types for the project
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires controls/sitemap/Map
 * @requires controls/sitemap/Item
 *
 * @module controls/projects/TypeSitemap
 * @package com.pcsg.quiqqer
 */

define('controls/projects/TypeSitemap', [

    'qui/controls/Control',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'Ajax'

], function(QUIControl, QUISitemap, QUISitemapItem, Ajax)
{
    "use strict";

    /**
     * The type sitemap for the project
     *
     * The type sitemap displays / create a QUI.controls.sitemap.Map
     * with all available types for the project
     *
     * @class controls/projects/TypeSitemap
     *
     * @fires onItemClick
     * @fires onItemDblClick
     *
     * @param {DOMNode} Container
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIControl,
        Type    : 'controls/projects/TypeSitemap',

        options : {
            multible : false,
            project  : false
        },

        $Map       : null,
        $Container : null,

        initialize : function(options)
        {
            var self = this;

            this.parent( options );
        },

        /**
         * return the domnode
         *
         * @return {DOMNode}
         */
        create : function()
        {
            this.$Map = new QUISitemap({
                name     : 'Type-Sitemap',
                multible : this.getAttribute('multible')
            });

            // Firstchild
            var First = new QUISitemapItem({
                name    : 1,
                index   : 1,
                value   : 1,
                text    : 'Seitentypen',
                alt     : 'Seitentypen',
                icon    : 'icon-magic',
                hasChildren : false,
                events :
                {
                    onOpen : function(Itm) {
                        self.open();
                    }
                }
            });

            this.$Map.appendChild( First );

            return this.$Map.create();
        },

        /**
         * Opens the first child of the sitemap
         * Fetches the children asynchronously
         *
         * @method controls/projects/TypeSitemap#open
         */
        open : function()
        {
            var self = this;

            Ajax.get('ajax_project_types_get_list', function(result, Ajax)
            {
                var i, c, len, icon, types,
                    plugin, Plgn, type_icon,
                    func_itm_click;

                var First = self.$Map.firstChild();

                First.clearChildren();

                func_itm_click = function(Itm, event)
                {
                    Itm.open();

                    if ( Itm.firstChild() )
                    {
                        (function()
                        {
                            Itm.firstChild().click();
                        }).delay( 100 );
                    }
                };

                for ( i in result )
                {
                    plugin = result[i];
                    types  = plugin.types;
                    icon   = 'icon-magic';

                    if ( typeof types === 'undefined' ) {
                        continue;
                    }

                    if ( plugin.icon_16x16 ) {
                        icon = URL_OPT_DIR + plugin.icon_16x16;
                    }

                    Plgn = new QUISitemapItem({
                        name  : i,
                        value : i,
                        text  : plugin.name,
                        alt   : plugin.description,
                        icon  : icon,
                        hasChildren : true,

                        events : {
                            onClick : func_itm_click
                        }
                    });

                    First.appendChild( Plgn );


                    for ( c = 0, len = types.length; c < len; c++ )
                    {
                        type_icon = 'icon-magic';

                        if ( types[c].icon_16x16 ) {
                            type_icon = types[c].icon_16x16;
                        }

                        Plgn.appendChild(
                            new QUISitemapItem({
                                name  : i,
                                value : types[c].type,
                                text  : types[c].name,
                                alt   : types[c].description,
                                icon  : type_icon
                            })
                        );
                    }
                }

                // empty result
                if ( typeOf( result ) == 'array' )
                {
                    First.disable();

                    First.setAttribute(
                        'text',
                        'Es stehen keine Seitentypen zur Verfügung'
                    );
                }

                First.setAttribute( 'icon', 'icon-magic' );
                // First.setAttribute( 'icon', URL_BIN_DIR +'16x16/types.png' );
            }, {
                project : this.getAttribute( 'project' )
            });
        },

        /**
         * Get the selected Values
         *
         * @method controls/projects/TypeSitemap#getValues
         * @return {Array} Array of the selected values
         */
        getValues : function()
        {
            var i, len;

            var actives = this.$Map.getSelectedChildren(),
                result  = [];

            for ( i = 0, len = actives.length; i < len; i++ )
            {
                result.push(
                    actives[i].getAttribute( 'value' )
                );
            }

            return result;
        }
    });
});