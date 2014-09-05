/**
 * Displays a Project in a Panel
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires Projects
 * @requires buttons/Button
 * @requires controls/projects/Sitemap
 *
 * @module controls/projects/Panel
 */

define('controls/projects/project/Panel', [

    'qui/controls/desktop/Panel',
    'Projects',
    'controls/projects/project/Sitemap',
    'utils/Panels',

    'qui/controls/buttons/Button',
    'qui/controls/buttons/Seperator',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'qui/controls/sitemap/Filter',

    'Locale',

    'css!controls/projects/project/Panel.css'

], function()
{
    "use strict";

    // classes
    var QUIPanel           = arguments[ 0 ],
        Projects           = arguments[ 1 ],
        ProjectSitemap     = arguments[ 2 ],
        PanelUtils         = arguments[ 3 ],

        QUIButton          = arguments[ 4 ],
        QUIButtonSeperator = arguments[ 5 ],
        QUISitemap         = arguments[ 6 ],
        QUISitemapItem     = arguments[ 7 ],
        QUISitemapFilter   = arguments[ 8 ],

        Locale             = arguments[ 9 ];

    /**
     * @class controls/projects/project/Panel
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIPanel,
        Type    : 'controls/projects/project/Panel',

        Binds : [
            '$onCreate',
            '$onInject',
            '$onResize',
            '$openSitePanel'
        ],

        initialize : function(options)
        {
            // defaults
            this.setAttributes({
                name    : 'projects-panel',
                project : false,
                lang    : false,
                title   : Locale.get(
                    'quiqqer/system',
                    'projects.project.panel.title'
                ),
                icon    : 'icon-home'
            });

            this.parent( options );

            this.$Map         = null;
            this.$opensites   = null;
            this.$projectmaps = {};
            this.$Filter      = null;
            this.$Button      = null;

            this.addEvents({
                onCreate : this.$onCreate,
                onInject : this.$onInject,
                onResize : this.$onResize
            });
        },

        /**
         * Create the project panel body
         *
         * @method controls/projects/project/Panel#$onCreate
         */
        $onCreate : function()
        {
            var self = this;

            this.getBody().set(
                'html',

                '<div class="project-container">' +
                    '<div class="project-content"></div>' +
                    '<div class="project-list"></div>' +
                '</div>' +
                '<div class="project-search"></div>'
            );

            var Content    = this.getBody(),
                Body       = Content.getParent(),
                List       = Content.getElement( '.project-list' ),
                Container  = Content.getElement( '.project-container' ),
                ProjectCon = Content.getElement( '.project-content' );

            Container.setStyles({
                height : '100%'
            });

            List.setStyles({
                left : -300
            });

            // draw filter
            this.$Filter = new QUISitemapFilter(null, {
                styles : {
                    paddingLeft : 30,
                    background  : '#F2F2F2'
                },
                events :
                {
                    onFilter : function(Filter, result)
                    {
                        if ( !result.length )
                        {
                            new Fx.Scroll( this ).toTop();
                            return;
                        }

                        new Fx.Scroll( this ).toElement(
                            result[ 0 ].getElm()
                        );

                    }.bind( Container )
                }
            }).inject( Content.getElement( '.project-search' ) );

            // site search
            new QUIButton({
                icon   : 'icon-search',
                title  : Locale.get('quiqqer/system', 'projects.project.panel.open.search'),
                alt    : Locale.get('quiqqer/system', 'projects.project.panel.open.search'),
                events :
                {
                    onClick : function()
                    {
                        require([
                            'controls/projects/project/site/Search',
                            'utils/Panels'
                        ], function(Search, PanelUtils)
                        {
                            PanelUtils.openPanelInTasks( new Search() );
                        });
                    }
                }
            }).inject( this.$Filter.getElm() );

            new QUIButtonSeperator().inject( this.getHeader(), 'top' );

            // title button
            this.$Button = new QUIButton({
                name   : 'projects',
                image  : 'icon-circle-arrow-left',
                events :
                {
                    onClick : function(Btn, event)
                    {
                        if ( typeof event !== 'undefined' ) {
                            event.stop();
                        }

                        if ( Btn.isActive() )
                        {
                            var Content = self.getBody(),
                                List    = Content.getElement( '.project-list' ),
                                first   = null;

                            // get the first projects map
                            for ( first in self.$projectmaps ) {
                                break;
                            }

                            // select the first languag of the project
                            self.$projectmaps[ first ].firstChild().firstChild().click();

                            return;
                        }

                        self.createList();
                    }
                }
            }).inject( this.getHeader(), 'top' );

            this.$Button.getElm().removeClass( 'qui-button' );
            this.$Button.getElm().addClass( 'button' );
            this.$Button.getElm().addClass( 'btn-blue' );
        },

        /**
         * event : on inject
         */
        $onInject : function()
        {
            var self = this;

            this.Loader.show();

            // resize after insert
            (function()
            {
                self.resize();
                self.Loader.show();

                Projects.getList(function(result)
                {
                    if ( Object.getLength( result ) > 1 )
                    {
                        self.$Button.click();
                        return;
                    }

                    for ( var key in result )
                    {
                        self.setAttribute( 'project', key );
                        self.setAttribute( 'lang', result[ key ].default_lang );
                        break;
                    }

                    self.openProject();
                    self.Loader.hide();
                });

            }).delay( 250 );
        },

        /**
         * event : on panel resize
         */
        $onResize : function()
        {
            var Body      = this.getBody(),
                height    = this.getAttribute( 'height' ),
                Container = Body.getElement( '.project-container' ),
                Search    = Body.getElement( '.project-search' );

            var height = Body.getComputedSize().height;

            if ( !height ) {
                return;
            }

            Container.setStyle(
                'height',
                height - Search.getComputedSize().totalHeight
            );
        },

        /**
         * Create the Project list for the Panel
         *
         * @method controls/projects/project/Panel#createList
         */
        createList : function()
        {
            this.Loader.show();

            var self = this;

            if ( this.$Map ) {
                this.$Map.destroy();
            }

            Projects.getList(function(result)
            {
                var i, l, langs, len,
                    scrollsize, Map, Project,
                    func_project_click, func_media_click, func_trash_click;

                var Content   = self.getContent(),
                    List      = Content.getElement( '.project-list' ),
                    Container = new Element( 'div' );

                List.set( 'html', '' );

                // click events
                func_project_click = function(Itm, event)
                {
                    self.setAttribute( 'project', Itm.getAttribute( 'project' ) );
                    self.setAttribute( 'lang', Itm.getAttribute( 'lang' ) );

                    self.openProject();
                };

                func_media_click = function(Itm, event)
                {
                    self.openMediaPanel(
                        Itm.getAttribute( 'project' )
                    );
                };

                func_trash_click = function()
                {
                    PanelUtils.openTrashPanel();
                };

                if ( self.$Filter ) {
                    self.$Filter.clearBinds();
                }


                // create
                for ( i in result )
                {
                    if ( !result[i].langs ) {
                        continue;
                    }

                    langs = result[i].langs.split( ',' );

                    if ( typeof self.$projectmaps[ i ] === 'undefined' ||
                         !self.$projectmaps[ i ] )
                    {
                        self.$projectmaps[ i ] = new QUISitemap();
                    }

                    Map = self.$projectmaps[ i ];
                    Map.clearChildren();

                    if ( self.$Filter ) {
                        self.$Filter.bindSitemap( Map );
                    }

                    Project = new QUISitemapItem({
                        text    : i,
                        icon    : 'icon-home',
                        project : i,
                        lang    : result[i].default_lang,
                        events  : {
                            onClick : func_project_click
                        }
                    });

                    Map.appendChild( Project );

                    for ( l = 0, len = langs.length; l < len; l++ )
                    {
                        // project Lang
                        Project.appendChild(
                            new QUISitemapItem({
                                text    : langs[l],
                                icon    : URL_BIN_DIR +'16x16/flags/'+ langs[l] +'.png',
                                name    : 'project.'+ i +'.'+ langs[l],
                                project : i,
                                lang    : langs[l],
                                events  : {
                                    onClick : func_project_click
                                }
                            })
                        );
                    }

                    // Media
                    Project.appendChild(
                        new QUISitemapItem({
                            text    : Locale.get('quiqqer/system', 'projects.project.panel.media'),
                            icon    : 'icon-picture',
                            project : i,
                            events  : {
                                onClick : func_media_click
                            }
                        })
                    );

                    // Media
                    Project.appendChild(
                        new QUISitemapItem({
                            text    : Locale.get('quiqqer/system', 'projects.project.panel.tash'),
                            icon    : 'icon-trash',
                            project : i,
                            events  : {
                                onClick : func_trash_click
                            }
                        })
                    );


                    List.appendChild( Map.create() );

                    Map.openAll();
                }

                Container.inject( List );


                List.setStyle( 'display', null );

                moofx( List ).animate({
                    left : 0,

                }, {
                    callback : function(time)
                    {
                        self.$Button.setActive();
                        self.Loader.hide();
                    }
                });
            });
        },

        /**
         * Opens the selected Project and create a Project Sitemap in the Panel
         *
         * @method controls/projects/project/Panel#openProject
         */
        openProject : function()
        {
            var Content   = this.getBody(),
                List      = Content.getElement( '.project-list' ),
                Container = Content.getElement( '.project-content' ),
                lang      = this.getAttribute( 'lang' ),

                Project = Projects.get(
                    this.getAttribute( 'project' ),
                    this.getAttribute( 'lang' )
                );

            moofx( List ).animate({
                left : List.getSize().x * -1
            }, {
                callback : function() {
                    List.setStyle( 'display', 'none' );
                }
            });

            Container.set(
                'html',
                '<h2>'+ this.getAttribute('project') + '</h2>'
            );

            Container.getElement( 'h2' ).setStyles({
                margin : '20px 0 0 20px',
                background : 'url('+ URL_BIN_DIR +'16x16/flags/'+ lang +'.png) no-repeat left center',
                padding : '0 0 0 20px'
            });

            // create the project sitemap in the panel
            if ( this.$Map ) {
                this.$Map.destroy();
            }

            this.$Map = new ProjectSitemap({
                project : Project.getAttribute( 'name' ),
                lang    : Project.getAttribute( 'lang' ),
                media   : true
            });

            this.$Sitemap = this.$Map.getMap();

            this.$Sitemap.addEvents({
                onChildClick       : this.$openSitePanel,
                onChildContextMenu : function(Item, MapItem, event)
                {
                    var title = MapItem.getAttribute( 'text' ) +' - '+
                                MapItem.getAttribute( 'value' );

                    MapItem.getContextMenu()
                           .setTitle( title )
                           .setPosition(
                               event.page.x,
                               event.page.y
                           )
                           .show();

                    event.stop();
                }
            });

            this.$Filter.clearBinds();
            this.$Filter.bindSitemap( this.$Sitemap );

            this.$Map.inject( Container );
            this.$Map.open();

            this.$Map.getElm().setStyles({
                margin : '10px 20px'
            });

            this.$Button.setNormal();
        },

        /**
         * Select an sitemap item by ID
         *
         * @method controls/projects/project/Panel#selectSitemapItemById
         *
         * @param {Integer} id - the site id
         * @return {this}
         */
        selectSitemapItemById : function(id)
        {
            if ( typeof this.$Sitemap === 'undefined' ) {
                return this;
            }

            var i, len;
            var children = this.getSitemapItemsById( id );

            for ( i = 0, len = children.length; i < len; i++ ) {
                children[ i ].select();
            }

            return this;
        },

        /**
         * Get all sitemap items by the id
         *
         * @method controls/projects/project/Panel#getSitemapItemsById
         * @return {Array}
         */
        getSitemapItemsById : function(id)
        {
            if ( typeof this.$Sitemap === 'undefined' ) {
                return [];
            }

            return this.$Sitemap.getChildrenByValue( id );
        },

        /**
         * Opens a site in the panel<br />
         * Opens the sitemap and open the site panel
         *
         * @method controls/projects/project/Panel#openSite
         * @param {Integer} id - ID from the wanted site
         */
        openSite : function(id)
        {
            if ( typeof this.$Map !== 'undefined' ) {
                this.$Map.openSite( id );
            }
        },

        /**
         * event: click on sitemap item -> opens a site panel
         *
         * @method controls/projects/project/Panel#$openSitePanel
         * @param {qui/controls/sitemap/Item} Item
         */
        $openSitePanel : function(Item)
        {
            var self    = this,
                id      = ( Item.getAttribute( 'value' ) ).toInt(),
                project = this.getAttribute( 'project' ),
                lang    = this.getAttribute( 'lang' );

            if ( !id ) {
                return;
            }

            PanelUtils.openSitePanel(project, lang, id, function(Panel)
            {
                Panel.addEvents({
                    onShow : function(Panel)
                    {
                        if ( Panel.getType() != 'controls/projects/project/site/Panel' ) {
                            return;
                        }
                        // if it is a sitepanel
                        // set the item in the map active
                        self.openSite( Panel.getSite().getId() );
                    }
                });
            });
        },

        /**
         * opens a media panel from a project
         *
         * @method controls/projects/project/Panel#$openSitePanel
         * @param {String} Project name
         */
        openMediaPanel : function(project)
        {
            PanelUtils.openMediaPanel( project );
        }
    });
});