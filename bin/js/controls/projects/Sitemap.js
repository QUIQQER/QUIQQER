/**
 * Displays a Sitemap from a project
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires controls/sitemap/Map
 *
 * @module controls/projects/Sitemap
 * @package com.pcsg.qui.js.controls.project
 * @namespace QUI.controls.project
 */

define('controls/projects/Sitemap', [

    'controls/Control',
    'controls/sitemap/Map',
    'Projects'

], function(QUI_Control, QUI_Sitemap)
{
    "use strict";

    QUI.namespace( 'controls.projects' );

    /**
     * A project sitemap
     *
     * @class QUI.controls.projects.Sitemap
     *
     * @fires onOpenBegin [Item, this]
     * @fires onOpenEnd [Item, this]
     * @param {Object} options
     *
     * @memberof! <global>
     */
    QUI.controls.projects.Sitemap = new Class({

        Extends : QUI_Control,
        Type    : 'QUI.controls.projects.Sitemap',

        Binds : [
            'onSiteChange',
            'onSiteCreate'
        ],

        options : {
            name      : 'projects-site-panel',
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

            this.$Project = QUI.Projects.get(
                this.getAttribute( 'project' ),
                this.getAttribute( 'lang' )
            );

            this.addEvent('onDestroy', function()
            {
                if ( this.$Map ) {
                    this.$Map.destroy();
                }

            }.bind( this ));

            // projects events
            this.$Project.addEvents({
                onSiteCreate     : this.onSiteCreate,
                onSiteSave       : this.onSiteChange,
                onSiteActivate   : this.onSiteChange,
                onSiteDeactivate : this.onSiteChange
            });
        },

        /**
         * Returns the QUI.controls.sitemap.Map Control
         *
         * @method QUI.controls.projects.Sitemap#getMap
         * @return {QUI.controls.sitemap.Map} Binded Map Object
         */
        getMap : function()
        {
            return this.$Map;
        },

        /**
         * Create the DOMNode of the sitemap
         *
         * @method QUI.controls.projects.Sitemap#create
         * @return {DOMNode} Main DOM-Node Element
         */
        create : function()
        {
            if ( this.$Elm ) {
                return this.$Elm;
            }

            this.$Elm = this.$Map.create();

            return this.$Elm;
        },

        /**
         * Open the Map
         *
         * @method QUI.controls.projects.Sitemap#open
         */
        open : function()
        {
            if ( !this.$Elm ) {
                return;
            }

            // if an specific id must be open
            if ( typeof this.$openids !== 'undefined' && this.$Map.firstChild() )
            {
                var First = this.$Map.firstChild();

                if ( First.isOpen() )
                {
                    this.fireEvent( 'openEnd', [ First, this ] );
                    return;
                }

                First.open();
                return;
            }


            this.$Map.clearChildren();

            if ( this.getAttribute( 'id' ) === false )
            {
                this.$getFirstChild(function(result, Request)
                {
                    var Control = Request.getAttribute( 'Control' );

                    Control.$Map.clearChildren();
                    Control.$addSitemapItem(
                        Control.$Map,
                        Control.$parseArrayToSitemapitem( result )
                    );

                    Control.$Map.firstChild().open();
                });

                return;
            }

            this.$getSite(
                this.getAttribute( 'id' ),
                function(result, Request)
                {
                    var Control = Request.getAttribute( 'Control' );

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
         * Open the Sitemap to the specific id
         *
         * @method QUI.controls.projects.Sitemap#openSite
         * @param {Integer} id - Site ID
         */
        openSite : function(id)
        {
            QUI.Ajax.get('ajax_site_path', function(result, Request)
            {
                var i, len, items;

                var Control = Request.getAttribute( 'Control' ),
                    Map     = Control.getMap();

                result.push(
                    Request.getAttribute( 'id' )
                );

                items = Map.getChildrenByValue( result.getLast() );

                if ( items.length )
                {
                    items[0].select();
                    return;
                }

                Control.$openids = result;
                Control.addEvent( 'onOpenEnd', function(SitemapItem, Control)
                {
                    var i, len, items;

                    var ids = Control.$openids,
                        Map = Control.getMap();

                    for ( i = 0, len = ids.length; i < len; i++ )
                    {
                        items = Map.getChildrenByValue( ids[i] );

                        if ( !items.length )
                        {
                            // open parent
                            items = Map.getChildrenByValue( ids[i-1] );

                            if ( items.length && items[0].isOpen() === false ) {
                                items[0].open();
                            }

                            return;
                        }

                        if ( items[0].isOpen() === false )
                        {
                            items[0].open();
                            return;
                        }
                    }

                    items[0].click();

                    delete Control.$openids;
                });

                Control.open();

            }, {
                project : this.getAttribute( 'project' ),
                lang    : this.getAttribute( 'lang' ),
                id      : id,
                Control : this
            });
        },

        /**
         * Get all selected Items
         *
         * @method QUI.controls.projects.Sitemap#getSelectedChildren
         * @return {Array}
         */
        getSelectedChildren : function()
        {
            return this.getMap().getSelectedChildren();
        },

        /**
         * If no id, the sitemap starts from the first child of the project
         *
         * @method QUI.controls.projects.Sitemap#getFirstChild
         * @param {Function} callback - callback function
         * @private
         * @ignore
         */
        $getFirstChild : function(callback)
        {
            QUI.Ajax.get('ajax_project_firstchild', function(result, Request)
            {
                Request.getAttribute( 'onfinish' )( result, Request );
            }, {
                project  : this.getAttribute( 'project' ),
                lang     : this.getAttribute( 'lang' ),
                Control  : this,
                onfinish : callback
            });
        },

        /**
         * Get the attributes from a site
         *
         * @method QUI.controls.projects.Sitemap#$getSite
         * @param {Integer} id - Seiten ID
         * @param {Function} callback - call back function, if ajax is finish
         *
         * @private
         * @ignore
         */
        $getSite : function(id, callback)
        {
            QUI.Ajax.get('ajax_site_get', function(result, Request)
            {
                Request.getAttribute( 'onfinish' )(result, Request);
            }, {
                project  : this.getAttribute( 'project' ),
                lang     : this.getAttribute( 'lang' ),
                id       : id,
                Control  : this,
                onfinish : callback
            });
        },

        /**
         * Load the Children asynchron
         *
         * @method QUI.controls.projects.Sitemap#$loadChildren
         * @param {QUI.controls.sitemap.Item} Item - Parent sitemap item
         * @param {Function} callback - callback function, if ajax is finish
         *
         * @ignore
         */
        $loadChildren : function(Item, callback)
        {
            var Control = this;

            Item.clearChildren();
            Item.setAttribute( 'oicon', Item.getAttribute( 'icon' ) );
            Item.setAttribute( 'icon', URL_BIN_DIR +'images/loader.gif' );

            QUI.Ajax.get('ajax_site_getchildren', function(result, Request)
            {
                Item.clearChildren();

                for ( var i = 0, len = result.length; i < len; i++ )
                {
                    Control.$addSitemapItem(
                        Item,
                        Control.$parseArrayToSitemapitem( result[ i ] )
                    );
                }

                Item.setAttribute( 'icon', Item.getAttribute( 'oicon' ) );

            }, {
                project : this.getAttribute( 'project' ),
                lang    : this.getAttribute( 'lang' ),
                id      : Item.getAttribute( 'value' )
            });
        },

        /**
         * Parse a ajax result set to a sitemap item
         *
         * @method QUI.controls.projects.Sitemap#$loadChildren
         * @param {Array} result
         * @param {QUI.controls.sitemap.Item} Itm
         * @return {QUI.controls.sitemap.Item}
         *
         * @private
         */
        $parseArrayToSitemapitem : function(result, Itm)
        {
            if ( typeof Itm === 'undefined' ) {
                Itm = new QUI.controls.sitemap.Item();
            }

            Itm.setAttributes({
                name  : result.name,
                index : result.id,
                value : result.id,
                text  : result.title,
                alt   : result.name +'.html',
                icon  : URL_BIN_DIR +'16x16/page_white.png',
                hasChildren : ( result.has_children ).toInt()
            });

            if ( result.nav_hide == '1' ) {
                Itm.addIcon( URL_BIN_DIR +'16x16/navigation_hidden.png' );
            }

            if ( result.linked == '1' )
            {
                Itm.setAttribute( 'linked', true );
                Itm.addIcon( URL_BIN_DIR +'16x16/linked.png' );
            }

            if ( result.icon_16x16 ) {
                Itm.setAttribute( 'icon', result.icon_16x16 );
            }

            // Activ / Inactive
            if ( result.active.toInt() === 0 )
            {
                Itm.deactivate();
            } else
            {
                Itm.activate();
            }

            // contextmenu
            Itm.getContextMenu()
                .clearChildren()
                .appendChild(
                    new QUI.controls.contextmenu.Item({
                        name   : 'site-copy-'+ Itm.getId(),
                        text   : 'kopieren',
                        icon   : URL_BIN_DIR +'16x16/copy.png',
                        events :
                        {
                            onClick : function(Item, event)
                            {
                                console.info(Item);
                            }
                        }
                    })
                ).appendChild(
                    new QUI.controls.contextmenu.Item({
                        name   : 'site-paste-'+ Itm.getId(),
                        text   : 'einfügen',
                        icon   : URL_BIN_DIR +'16x16/paste.png',
                        events :
                        {
                            onClick : function(Item, event)
                            {
                                console.info(Item);
                            }
                        }
                    })
                ).appendChild(
                    new QUI.controls.contextmenu.Item({
                        name   : 'site-cut-'+ Itm.getId(),
                        text   : 'ausschneiden',
                        icon   : URL_BIN_DIR +'16x16/cut.png',
                        events :
                        {
                            onClick : function(Item, event)
                            {
                                console.info(Item);
                            }
                        }
                    })
                );

            return Itm;
        },

        /**
         * Add the item to its parent<br />
         * set the control attributes to the child item
         *
         * @method QUI.controls.projects.Sitemap#$addSitemapItem
         *
         * @param {QUI.controls.sitemap.Item} Parent
         * @param {QUI.controls.sitemap.Item} Child
         *
         * @private
         * @ignore
         */
        $addSitemapItem : function(Parent, Child)
        {
            Child.setAttribute( 'Control', this );
            Child.addEvent( 'onOpen', this.$open.bind( this ) );
            Child.addEvent( 'onClose', this.$close.bind( this ) );

            Parent.appendChild( Child );
        },

        /**
         * Opens a Sitemap Item
         *
         * @method QUI.controls.projects.Sitemap#$open
         * @param {QUI.controls.sitemap.Item} Item
         *
         * @private
         * @ignore
         */
        $open : function(Item)
        {
            this.fireEvent( 'openBegin', [ Item, this ] );

            this.$loadChildren(Item, function(Item, Request)
            {
                var Control = Request.getAttribute( 'Control' );

                Control.fireEvent( 'openEnd', [ Item, Control ] );
            });
        },

        /**
         * sitemap item close action
         *
         * @method QUI.controls.projects.Sitemap#$close
         * @param {QUI.controls.sitemap.Item} Item
         *
         * @private
         * @ignore
         */
        $close : function(Item)
        {
            Item.clearChildren();
        },

        /**
         * Site event handling - if a site changes, the sitemap must change to
         */

        /**
         * event - onSiteActivate. onSiteDeactivate, onSiteSave
         *
         * @param {QUI.classes.projects.Project} Project - Project of the Site that are changed
         * @param {QUI.classes.projects.Site} Site - Site that are changed
         */
        onSiteChange : function(Project, Site)
        {
            if ( !this.$Map ) {
                return;
            }

            var children = this.$Map.getChildrenByValue( Site.getId() );

            if ( !children.length ) {
                return;
            }

            var i, len, Item, params;

            for ( i = 0, len = children.length; i < len; i++ )
            {
                Item   = children[ i ];
                params = Site.getAttributes();

                params.active       = Site.isActive();
                params.has_children = Site.hasChildren() ? 1 : 0;

                this.$parseArrayToSitemapitem( params, Item );
            }
        },

        /**
         * event - onSiteCreate
         *
         * @param {QUI.classes.projects.Project} Project - Project of the Site that are changed
         * @param {QUI.classes.projects.Site} Site - Site that are changed
         */
        onSiteCreate : function(Project, Site)
        {
            console.log( Site.getId() );

        }
    });

    return QUI.controls.projects.Sitemap;
});
