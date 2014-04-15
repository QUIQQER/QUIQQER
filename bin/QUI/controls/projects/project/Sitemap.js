/**
 * Displays a Sitemap from a project
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires qui/controls/Control
 * @requires qui/controls/sitemap/Map
 *
 * @module controls/projects/project/Sitemap
 */

define('controls/projects/project/Sitemap', [

    'qui/controls/Control',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'qui/controls/contextmenu/Item',
    'qui/controls/contextmenu/Seperator',
    'Projects',
    'Ajax',
    'Clipboard'

], function(QUIControl, QUISitemap, QUISitemapItem, QUIContextmenuItem, QUIContextmenuSeperator, Projects, Ajax, Clipboard)
{
    "use strict";

    /**
     * A project sitemap
     *
     * @class controls/projects/project/Sitemap
     *
     * @fires onOpenBegin [Item, this]
     * @fires onOpenEnd [Item, this]
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIControl,
        Type    : 'controls/projects/project/Sitemap',

        Binds : [
            'onSiteChange',
            'onSiteCreate',
            'onSiteDelete',

            '$open',
            '$close'
        ],

        options : {
            name      : 'projects-site-panel',
            container : false,
            project   : false,
            lang      : false,
            id        : false,
            media     : false, // show the media in the sitemap, too
            multible  : false
        },

        initialize : function(options)
        {
            this.parent( options );

            this.$Elm = null;
            this.$Map = new QUISitemap({
                multible : this.getAttribute( 'multible' )
            });

            this.$Project = Projects.get(
                this.getAttribute( 'project' ),
                this.getAttribute( 'lang' )
            );

            var self = this;

            this.addEvent('onDestroy', function()
            {
                if ( self.$Map ) {
                    self.$Map.destroy();
                }

                self.$Project.removeEvents({
                    onSiteCreate     : self.onSiteCreate,
                    onSiteSave       : self.onSiteChange,
                    onSiteActivate   : self.onSiteChange,
                    onSiteDeactivate : self.onSiteChange,
                    onSiteDelete     : self.onSiteDelete
                });

            });

            // projects events
            this.$Project.addEvents({
                onSiteCreate     : this.onSiteCreate,
                onSiteSave       : this.onSiteChange,
                onSiteActivate   : this.onSiteChange,
                onSiteDeactivate : this.onSiteChange,
                onSiteDelete     : this.onSiteDelete
            });

            // copy and paste ids
            this.$cut  = false;
            this.$copy = false;
        },

        /**
         * Returns the qui/controls/sitemap/Map Control
         *
         * @method controls/projects/project/Sitemap#getMap
         * @return {qui/controls/sitemap/Map} Binded Map Object
         */
        getMap : function()
        {
            return this.$Map;
        },

        /**
         * Create the DOMNode of the sitemap
         *
         * @method controls/projects/project/Sitemap#create
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
         * @method controls/projects/project/Sitemap#open
         */
        open : function()
        {
            if ( !this.$Elm ) {
                return;
            }

            var self = this;


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
                    self.$Map.clearChildren();

                    self.$addSitemapItem(
                        self.$Map,
                        self.$parseArrayToSitemapitem( result )
                    );

                    self.$Map.firstChild().open();

                    // media
                    if ( self.getAttribute( 'media' ) )
                    {
                        self.$Map.appendChild(
                            new QUISitemapItem({
                                text   : 'Media',
                                icon   : 'icon-picture',
                                events :
                                {
                                    onClick : function()
                                    {
                                        require(['controls/projects/project/Panel'], function(Panel)
                                        {
                                            new Panel().openMediaPanel(
                                                self.getAttribute( 'project' )
                                            );
                                        });
                                    }
                                }
                            })
                        );
                    }
                });

                return;
            }

            this.$getSite(
                this.getAttribute( 'id' ),
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
         * Open the Sitemap to the specific id
         *
         * @method controls/projects/project/Sitemap#openSite
         * @param {Integer} id - Site ID
         */
        openSite : function(id)
        {
            if ( !this.$Map ) {
                return;
            }

            var children = this.$Map.getChildrenByValue( id ),
                self     = this;

            if ( children.length )
            {
                children[ 0 ].select();
                return;
            }

            // if not exist, search the path
            Ajax.get('ajax_site_path', function(result, Request)
            {
                var i, len, items;

                var Control = Request.getAttribute( 'Control' ),
                    Map     = Control.getMap();

                result.push( Request.getAttribute( 'id' ) );

                items = Map.getChildrenByValue( result.getLast() );

                if ( items.length )
                {
                    items[0].select();
                    return;
                }

                var open_event = function(SitemapItem, Control)
                {
                    var i, id, len, items;

                    if ( typeof self.$openids === 'undefined' ) {
                        return;
                    }

                    var ids = self.$openids,
                        Map = self.getMap();

                    for ( i = 0, len = ids.length; i < len; i++ )
                    {
                        id    = ( ids[ i ] ).toInt();
                        items = Map.getChildrenByValue( id );

                        if ( !items.length )
                        {
                            // open parent
                            items = Map.getChildrenByValue( ids[ i - 1 ] );

                            if ( items.length &&
                                 items[0].isOpen() === false )
                            {
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

                    items[0].select();

                    delete Control.$openids;

                    Control.removeEvent( 'onOpenEnd', open_event );
                };

                Control.$openids = result;
                Control.addEvent( 'onOpenEnd', open_event );

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
         * @method controls/projects/project/Sitemap#getSelectedChildren
         * @return {Array}
         */
        getSelectedChildren : function()
        {
            return this.getMap().getSelectedChildren();
        },

        /**
         * Get specific children
         *
         * @method controls/projects/project/Sitemap#getChildren
         * @param {String} selector
         * @return {Array} List of children
         */
        getChildren : function(selector)
        {
            return this.getMap().getChildren( selector );
        },

        /**
         * Sitemap filter, the user can search for certain items
         *
         * @method qui/controls/sitemap/Map#search
         * @param {String} search
         * @return {Array} List of found elements
         */
        search : function(search)
        {
            return this.getMap().search( search );
        },

        /**
         * If no id, the sitemap starts from the first child of the project
         *
         * @method controls/projects/project/Sitemap#getFirstChild
         * @param {Function} callback - callback function
         * @private
         * @ignore
         */
        $getFirstChild : function(callback)
        {
            Ajax.get('ajax_project_firstchild', function(result, Request)
            {
                if ( typeof callback !== 'undefined' ) {
                    callback( result, Request );
                }
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
         * @method controls/projects/project/Sitemap#$getSite
         * @param {Integer} id - Seiten ID
         * @param {Function} callback - call back function, if ajax is finish
         *
         * @private
         * @ignore
         */
        $getSite : function(id, callback)
        {
            Ajax.get('ajax_site_get', function(result, Request)
            {
                if ( typeof callback !== 'undefined' ) {
                    callback( result, Request );
                }
            }, {
                project  : this.getAttribute( 'project' ),
                lang     : this.getAttribute( 'lang' ),
                id       : id
            });
        },

        /**
         * Load the Children asynchron
         *
         * @method controls/projects/project/Sitemap#$loadChildren
         * @param {qui/controls/sitemap/Item} Item - Parent sitemap item
         * @param {Function} callback - callback function, if ajax is finish
         *
         * @ignore
         */
        $loadChildren : function(Item, callback)
        {
            var self = this;

            Item.clearChildren();

            Item.setAttribute( 'oicon', Item.getAttribute( 'icon' ) );
            Item.addIcon( 'icon-refresh icon-spin' );
            Item.removeIcon( Item.getAttribute( 'icon' ) );


            Ajax.get('ajax_site_getchildren', function(result, Request)
            {
                Item.clearChildren();

                for ( var i = 0, len = result.length; i < len; i++ )
                {
                    self.$addSitemapItem(
                        Item,
                        self.$parseArrayToSitemapitem( result[ i ] )
                    );
                }

                Item.addIcon( Item.getAttribute( 'oicon' ) );
                Item.removeIcon( 'icon-refresh' );

                if ( typeof callback !== 'undefined' ) {
                    callback( Item, Request );
                }

            }, {
                project : this.getAttribute( 'project' ),
                lang    : this.getAttribute( 'lang' ),
                id      : Item.getAttribute( 'value' ),
                params  : JSON.encode({
                    attributes : 'id,name,title,has_children,nav_hide,linked,active,icon'
                })
            });
        },

        /**
         * Parse a ajax result set to a sitemap item
         *
         * @method controls/projects/project/Sitemap#$parseArrayToSitemapitem
         * @param {Array} result
         * @param {qui/controls/sitemap/Item} Itm
         * @return {qui/controls/sitemap/Item}
         *
         * @private
         */
        $parseArrayToSitemapitem : function(result, Itm)
        {
            var self = this;

            if ( typeof Itm === 'undefined' ) {
                Itm = new QUISitemapItem();
            }

            Itm.setAttributes({
                name  : result.name,
                index : result.id,
                value : result.id,
                text  : result.title,
                alt   : result.name +'.html',
                icon  : result.icon || 'icon-file-alt',
                hasChildren : ( result.has_children ).toInt()
            });

            if ( result.nav_hide == '1' )
            {
                Itm.addIcon( URL_BIN_DIR +'16x16/navigation_hidden.png' );
            } else
            {
                Itm.removeIcon( URL_BIN_DIR +'16x16/navigation_hidden.png' );
            }

            if ( result.linked == '1' )
            {
                Itm.setAttribute( 'linked', true );
                Itm.addIcon( URL_BIN_DIR +'16x16/linked.png' );
            } else
            {
                Itm.setAttribute( 'linked', false );
                Itm.removeIcon( URL_BIN_DIR +'16x16/linked.png' );
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
            var ContextMenu = Itm.getContextMenu();

            ContextMenu.clearChildren().appendChild(
                    new QUIContextmenuItem({
                        name   : 'copy',
                        text   : 'kopieren',
                        icon   : 'icon-copy',
                        events :
                        {
                            onClick : function(ContextItem, event)
                            {
                                Clipboard.set({
                                    project  : self.getAttribute( 'project' ),
                                    lang     : self.getAttribute( 'lang' ),
                                    id       : Itm.getAttribute( 'value' ),
                                    Item     : Itm,
                                    copyType : 'copy'
                                });
                            }
                        }
                    })
                ).appendChild(
                    new QUIContextmenuItem({
                        name   : 'cut',
                        text   : 'ausschneiden',
                        icon   : 'icon-cut',
                        events :
                        {
                            onClick : function(ContextItem, event)
                            {
                                Clipboard.set({
                                    project  : self.getAttribute( 'project' ),
                                    lang     : self.getAttribute( 'lang' ),
                                    id       : Itm.getAttribute( 'value' ),
                                    Item     : Itm,
                                    copyType : 'cut'
                                });
                            }
                        }
                    })
                ).appendChild(
                    new QUIContextmenuSeperator()
                ).appendChild(
                    new QUIContextmenuItem({
                        disabled : true,
                        name   : 'paste',
                        text   : 'einfügen',
                        icon   : 'icon-paste',
                        events :
                        {
                            onClick : function(ContextItem, event) {
                                self.$pasteSite( Itm );
                            }
                        }
                    })
                ).appendChild(
                    new QUIContextmenuItem({
                        disabled : true,
                        name   : 'linked-paste',
                        text   : 'Verknüpfung einfügen',
                        icon   : 'icon-paste',
                        events :
                        {
                            onClick : function(ContextItem, event) {
                                self.$linkSite( Itm );
                            }
                        }
                    })
                );

            ContextMenu.addEvents({
                onShow : function(ContextMenu)
                {
                    var data   = Clipboard.get(),
                        Paste  = ContextMenu.getChildren( 'paste' ),
                        Linked = ContextMenu.getChildren( 'linked-paste' ),
                        Cut    = ContextMenu.getChildren( 'cut' );

                    Paste.disable();
                    Linked.disable();

                    if ( Itm.getAttribute( 'value' ) == 1 )
                    {
                        Cut.disable();
                    } else
                    {
                        Cut.enable();
                    }

                    if ( !data ) {
                        return;
                    }

                    if ( typeof data.copyType === 'undefined' ) {
                        return;
                    }

                    if ( data.copyType !== 'cut' && data.copyType !== 'copy' ) {
                        return;
                    }

                    Paste.enable();
                    Linked.enable();
                }
            });

            return Itm;
        },

        /**
         * Add the item to its parent<br />
         * set the control attributes to the child item
         *
         * @method controls/projects/project/Sitemap#$addSitemapItem
         *
         * @param {qui/controls/sitemap/Item} Parent
         * @param {qui/controls/sitemap/Item} Child
         *
         * @private
         * @ignore
         */
        $addSitemapItem : function(Parent, Child)
        {
            Child.setAttribute( 'Control', this );
            Child.addEvent( 'onOpen', this.$open );
            Child.addEvent( 'onClose', this.$close );

            Parent.appendChild( Child );
        },

        /**
         * Opens a Sitemap Item
         *
         * @method controls/projects/project/Sitemap#$open
         * @param {qui/controls/sitemap/Item} Item
         *
         * @private
         * @ignore
         */
        $open : function(Item)
        {
            var self = this;

            this.fireEvent( 'openBegin', [ Item, this ] );

            this.$loadChildren(Item, function(Item, Request) {
                self.fireEvent( 'openEnd', [ Item, self ] );
            });
        },

        /**
         * sitemap item close action
         *
         * @method controls/projects/project/Sitemap#$close
         * @param {qui/controls/sitemap/Item} Item
         *
         * @private
         * @ignore
         */
        $close : function(Item)
        {
            Item.clearChildren();
        },

        /**
         * Move a site to a new parent
         *
         * @param {qui/controls/sitemap/Item} ParentItem
         */
        $pasteSite : function(NewParentItem)
        {
            var data = Clipboard.get();

            if ( typeof data.Item === 'undefined' ) {
                return;
            }

            if ( typeof data.project === 'undefined' ||
                 typeof data.lang === 'undefined' ||
                 typeof data.id === 'undefined' )
            {
                return;
            }

            var self    = this,
                Project = Projects.get( data.project, data.lang ),
                Site    = Project.get( data.id ),
                Item    = data.Item;

            // move site
            if ( data.copyType === 'cut' )
            {
                Site.move( NewParentItem.getAttribute('value'), function()
                {
                    Item.destroy();

                    if ( !NewParentItem.isOpen() )
                    {
                        NewParentItem.open();
                    } else
                    {
                        self.$open( NewParentItem );
                    }
                });

                return;
            }

            Site.copy( NewParentItem.getAttribute('value'), function()
            {
                if ( !NewParentItem.isOpen() )
                {
                    NewParentItem.open();
                } else
                {
                    self.$open( NewParentItem );
                }
            });
        },

        /**
         * Link a site into a new parent
         *
         * @param {qui/controls/sitemap/Item} ParentItem
         */
        $linkSite : function(NewParentItem)
        {
            var data = Clipboard.get();

            if ( typeof data.Item === 'undefined' ) {
                return;
            }

            if ( typeof data.project === 'undefined' ||
                 typeof data.lang === 'undefined' ||
                 typeof data.id === 'undefined' )
            {
                return;
            }

            var self    = this,
                Project = Projects.get( data.project, data.lang ),
                Site    = Project.get( data.id ),
                Item    = data.Item;

            Site.linked( NewParentItem.getAttribute('value'), function()
            {
                if ( !NewParentItem.isOpen() )
                {
                    NewParentItem.open();
                } else
                {
                    self.$open( NewParentItem );
                }
            });
        },

        /**
         * Site event handling - if a site changes, the sitemap must change to
         */

        /**
         * event - onSiteActivate. onSiteDeactivate, onSiteSave
         *
         * @param {classes/projects/Project} Project - Project of the Site that are changed
         * @param {classes/projects/project/Site} Site - Site that are changed
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
         * @param {classes/projects/Project} Project - Project of the Site that are changed
         * @param {classes/projects/project/Site} Site - Site that create the child
         * @param {Integer} newid - new child id
         */
        onSiteCreate : function(Project, Site, newid)
        {
            if ( !this.$Map ) {
                return;
            }

            // refresh the parent
            var children = this.$Map.getChildrenByValue( Site.getId() );

            if ( !children.length ) {
                return;
            }

            for ( var i = 0, len = children.length; i < len; i++ )
            {
                if ( children[ i ].isOpen() )
                {
                    this.$loadChildren( children[ i ] );
                    return;
                }

                children[ i ].open();
            }
        },

        /**
         * event - on site delete
         *
         * @param {classes/projects/Project} Project - Project of the Site that are changed
         * @param {Integer} siteid - siteid that are deleted
         */
        onSiteDelete : function(Project, siteid)
        {
            if ( !this.$Map ) {
                return;
            }

            var children = this.$Map.getChildrenByValue( siteid );

            if ( !children.length ) {
                return;
            }

            for ( var i = 0, len = children.length; i < len; i++ ) {
                children[ i ].destroy();
            }
        }
    });
});
