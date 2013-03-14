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
 * @package com.pcsg.qui.js.classes.projects
 * @namespace QUI.classes.projects
 */

define('controls/projects/TypeSitemap', [

    'controls/Control',
    'controls/sitemap/Map',
    'controls/sitemap/Item'

], function(Control, QUI_Item, QUI_SitemapItem)
{
    QUI.namespace( 'controls.projects' );

    /**
     * @class QUI.controls.projects.TypeSitemap
     *
     * @fires onItemClick
     * @fires onItemDblClick
     *
     * @param {DOMNode} Container
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.controls.projects.TypeSitemap = new Class({

        Implements : [ Control ],
        Type       : 'QUI.controls.projects.TypeSitemap',

        options : {
            multible : false,
            project  : false
        },

        $Map       : null,
        $Container : null,

        initialize : function(Container, options)
        {
            this.init( options );

            this.$Container = Container;

            this.$Map = new QUI.controls.sitemap.Map({
                name     : 'Type-Sitemap',
                multible : this.getAttribute('multible')
            });

            // Firstchild
            var First = new QUI.controls.sitemap.Item({
                Control : this,
                name    : 1,
                index   : 1,
                value   : 1,
                text    : 'Seitentypen',
                alt     : 'Seitentypen',
                icon    : URL_BIN_DIR +'images/loader.gif',
                hasChildren : false,
                events :
                {
                    onOpen : function(Itm) {
                        Itm.getAttribute('Control').open();
                    }
                }
            });

            this.$Map.appendChild( First );
            this.$Map.inject( this.$Container );

            this.$Map.getElm().setStyle( 'margin', 0 );

            First.open();
        },

        /**
         * Opens the first child of the sitemap
         * Fetches the children asynchronously
         *
         * @method QUI.controls.projects.TypeSitemap#open
         */
        open : function()
        {
            QUI.Ajax.get('ajax_project_types_get_list', function(result, Ajax)
            {
                var i, c, len, icon, types,
                    plugin, Plgn, type_icon,
                    func_itm_click;

                var First = Ajax.getAttribute('Control').$Map.firstChild();

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


                for ( i in result)
                {
                    plugin = result[i];
                    types  = plugin.types;
                    icon   = URL_BIN_DIR + '16x16/types.png';

                    if ( plugin.icon_16x16 ) {
                        icon = URL_OPT_DIR + plugin.icon_16x16;
                    }

                    Plgn = new QUI.controls.sitemap.Item({
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
                        type_icon = URL_BIN_DIR +'16x16/types.png';

                        if ( types[c].icon_16x16 ) {
                            type_icon = types[c].icon_16x16;
                        }

                        Plgn.appendChild(
                            new QUI.controls.sitemap.Item({
                                name  : i,
                                value : types[c].type,
                                text  : types[c].name,
                                alt   : types[c].description,
                                icon  : type_icon
                            })
                        );
                    }
                }

                First.setAttribute( 'icon', URL_BIN_DIR +'16x16/types.png' );
            }, {
                Control : this,
                project : this.getAttribute( 'project' )
            });
        },

        /**
         * Get the selected Values
         *
         * @method QUI.controls.projects.TypeSitemap#getValues
         * @return {Array}
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

    return QUI.controls.projects.TypeSitemap;
});