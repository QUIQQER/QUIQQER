
/**
 * The type sitemap for the project
 *
 * The type sitemap displays / create a qui/controls/sitemap/Map
 * with all available types for the project
 *
 * @module controls/projects/TypeSitemap
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/Control
 * @require qui/controls/sitemap/Map
 * @require qui/controls/sitemap/Item
 * @require Ajax
 * @require Locale
 *
 * @event onLoad [ self ]
 */

define('controls/projects/TypeSitemap', [

    'qui/controls/Control',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'Ajax',
    'Locale'

], function(QUIControl, QUISitemap, QUISitemapItem, Ajax, Locale)
{
    "use strict";

    /**
     * The type sitemap for the project
     *
     * The type sitemap displays / create a qui/controls/sitemap/Map
     * with all available types for the project
     *
     * @class controls/projects/TypeSitemap
     *
     * @fires onItemClick
     * @fires onItemDblClick
     *
     * @param {HTMLElement} Container
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIControl,
        Type    : 'controls/projects/TypeSitemap',

        options : {
            multible : false,
            project  : false,
            pluginsSelectable : false
        },

        Binds : [
            'open'
        ],

        initialize : function(options)
        {
            this.$Map       = null;
            this.$Container = null;
            this.$load      = false;

            this.parent( options );
        },

        /**
         * return the domnode
         *
         * @return {HTMLElement}
         */
        create : function()
        {
            this.$Map = new QUISitemap({
                name     : 'Type-Sitemap',
                multible : this.getAttribute('multible')
            });

            // Firstchild
            var First = new QUISitemapItem({
                name  : 1,
                index : 1,
                value : 1,
                text  : Locale.get( 'quiqqer/system', 'projects.typesitemap.firstChild' ),
                alt   : Locale.get( 'quiqqer/system', 'projects.typesitemap.firstChild' ),
                icon  : 'icon-magic',
                hasChildren : false
            });

            this.$Map.appendChild( First );
            this.open();

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
            if ( this.$load ) {
                return;
            }

            this.$load = true;

            var self  = this,
                First = self.$Map.firstChild();

            First.removeIcon( 'icon-magic' );
            First.addIcon( 'icon-spin icon-refresh' );

            Ajax.get('ajax_project_types_get_list', function(result)
            {
                First = self.$Map.firstChild();
                First.clearChildren();
                First.disable();

                First.removeIcon( 'icon-spin' );
                First.addIcon( 'icon-magic' );

                // empty result
                if ( typeOf( result ) == 'array' )
                {
                    First.setAttribute(
                        'text',
                        Locale.get( 'quiqqer/system', 'projects.typesitemap.message.no.types' )
                    );

                    return;
                }

                var c, i, len, data, icon, Plugin;

                var func_itm_click = function(Itm)
                {
                    Itm.open();

                    if ( self.getAttribute( 'pluginsSelectable' ) ) {
                        return;
                    }

                    if ( Itm.firstChild() )
                    {
                        (function() {
                            Itm.firstChild().click();
                        }).delay( 100 );
                    }
                };

                // create the map
                for ( i in result )
                {
                    if ( !result.hasOwnProperty( i ) ) {
                        continue;
                    }

                    if ( i == 'standard' )
                    {
                        new QUISitemapItem({
                            name  : i,
                            value : i,
                            text  : i,
                            alt   : i,
                            icon  : result[ i ].icon,
                            hasChildren : false
                        }).inject( First );

                        continue;
                    }

                    Plugin = new QUISitemapItem({
                        name  : i,
                        value : i,
                        text  : Locale.get( i, 'package.title' ),
                        alt   : i,
                        icon  : 'icon-puzzle-piece',
                        hasChildren : true,

                        events : {
                            onClick : func_itm_click
                        }
                    });

                    First.appendChild( Plugin );


                    for ( c = 0, len = result[ i ].length; c < len; c++ )
                    {
                        icon = 'icon-magic';
                        data = result[ i ][ c ];

                        if ( data.icon ) {
                            icon = data.icon;
                        }

                        new QUISitemapItem({
                            name  : i,
                            value : data.type,
                            text  : data.text || data.type.split(':')[1],
                            alt   : data.type,
                            icon  : icon
                        }).inject( Plugin );
                    }

                    self.fireEvent( 'load', [ self ] );
                }

                First.open();

            }, {
                project : JSON.encode({
                    name : this.getAttribute( 'project' )
                })
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
                    actives[ i ].getAttribute( 'value' )
                );
            }

            return result;
        }
    });
});