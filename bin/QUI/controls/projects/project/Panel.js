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
 * @package com.pcsg.qui.js.controls.project
 * @namespace QUI.controls.project
 */

define('controls/projects/project/Panel', [

    'qui/controls/desktop/Panel',
    'Projects',
    'controls/projects/project/Sitemap',

    'qui/controls/buttons/Button',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'qui/controls/sitemap/Filter',

    'css!controls/projects/project/Panel.css'

], function(QUI_Panel, Projects, ProjectSitemap, QUIButton, QUISitemap, QUISitemapItem, QUISitemapFilter)
{
    "use strict";

    /**
     * @class controls/projects/project/Panel
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUI_Panel,
        Type    : 'controls/projects/project/Panel',

        Binds : [
            '$onCreate',
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
                title   : 'Projekte',
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
                onResize : this.$onResize
            });
        },

        /**
         * Create the project panel body
         *
         * @method QUI.controls.projects.Panel#$onCreate
         */
        $onCreate : function()
        {
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
                title  : 'Seiten suchen ...',
                alt    : 'Seiten suchen ...',
                events :
                {
                    onClick : function()
                    {
                        console.info( 'site search not implemented' );
                    }
                }
            }).inject( this.$Filter.getElm() );

            // add project
            var BtnAdd = new QUIButton({
                name     : 'add_projects',
                icon     : 'icon-plus',
                title    : 'Projekt hinzufügen',
                alt      : 'Projekt hinzufügen',
                events   :
                {
                    onClick : function(Btn, event)
                    {
                        event.stop();

                        require(['controls/projects/Manager'], function(Manager)
                        {
                            QUI.Workspace.appendPanel(
                                new Manager({
                                    events :
                                    {
                                        onCreate : function(Panel)
                                        {
                                            Panel.getCategoryBar()
                                                 .getElement( 'add_project' )
                                                 .click();
                                        }
                                    }
                                })
                            );
                        });
                    }
                }
            }).inject( this.getHeader(), 'top' );

            BtnAdd.getElm().removeClass( 'qui-button' );
            BtnAdd.getElm().addClass( 'button' );
            BtnAdd.getElm().addClass( 'btn-blue' );

            // title button
            this.$Button = new QUIButton({
                name   : 'projects',
                image  : 'icon-home',
                events :
                {
                    onClick : function(Btn, event)
                    {
                        if ( typeof event !== 'undefined' ) {
                            event.stop();
                        }

                        if ( Btn.isActive() )
                        {
                            var Content = this.getBody(),
                                List    = Content.getElement( '.project-list' );

                            moofx( List ).animate({
                                left : List.getSize().x * -1
                            }, {
                                callback : this.$Button.setNormal.bind( this.$Button )
                            });

                            return;
                        }

                        this.createList();

                    }.bind( this )
                }
            }).inject( this.getHeader(), 'top' );

            this.$Button.getElm().removeClass( 'qui-button' );
            this.$Button.getElm().addClass( 'button' );
            this.$Button.getElm().addClass( 'btn-blue' );

            // resize after insert
            (function()
            {
                this.resize();
                this.$Button.click();
            }).delay( 250, this );
        },

        /**
         * event : on panel resize
         */
        $onResize : function()
        {
            var Body      = this.getBody(),
                height    = this.getAttribute( 'height' ),
                Container = Body.getElement( '.project-container' );

            if ( Body.getSize().y ) {
                height = Body.getSize().y;
            }

            Container.setStyle( 'height', Body.getSize().y - 42 );
        },

        /**
         * Create the Project list for the Panel
         *
         * @method QUI.controls.projects.Panel#createList
         */
        createList : function()
        {
            var self = this;

            if ( this.$Map ) {
                this.$Map.destroy();
            }

            Projects.getList(function(result)
            {
                var i, l, langs, len,
                    scrollsize, Map, Project,
                    func_project_click, func_media_click;

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
                            text    : 'Media',
                            icon    : 'icon-picture',
                            project : i,
                            events  : {
                                onClick : func_media_click
                            }
                        })
                    );

                    List.appendChild( Map.create() );

                    Map.openAll();
                }

                Container.inject( List );


                moofx( List ).animate({
                    left : 0
                }, {
                    callback : function(time) {
                        self.$Button.setActive();
                    }
                });
            });
        },

        /**
         * Opens the selected Project and create a Project Sitemap in the Panel
         *
         * @method QUI.controls.projects.Panel#openProject
         */
        openProject : function()
        {
            var Content   = this.getBody(),
                List      = Content.getElement( '.project-list' ),
                Container = Content.getElement( '.project-content' ),

                Project = Projects.get(
                    this.getAttribute( 'project' ),
                    this.getAttribute( 'lang' )
                );

            moofx( List ).animate({
                left : List.getSize().x * -1
            });

            /*
            Project.addEvent('onSiteStatusEditEnd', function(Site)
            {
                if ( !this.Sitemap ) {
                    return;
                }

                var i, len;

                var id   = Site.getId(),
                    list = this.Sitemap.getChildren( '[data-value="'+ id +'"]' );

                for ( i = 0, len = list.length; i < len; i++)
                {
                    if ( Site.getAttribute( 'active' ) == 1 )
                    {
                        list[i].activate();
                    } else
                    {
                        list[i].deactivate();
                    }
                }

            }.bind( this ));
            */

            // create the project sitemap in the panel
            if ( this.$Map ) {
                this.$Map.destroy();
            }

            this.$Map = new ProjectSitemap({
                project : Project.getAttribute( 'name' ),
                lang    : Project.getAttribute( 'lang' )
            });

            this.$Sitemap = this.$Map.getMap();

            this.$Sitemap.addEvents({

                onChildClick : this.$openSitePanel,

                onChildContextMenu : function(Item, event)
                {
                    Item.getContextMenu()
                        .setTitle( Item.getAttribute( 'text' ) +' - '+ Item.getAttribute( 'value' ) )
                        .setPosition( event.page.x, event.page.y )
                        .show();

                    event.stop();
                }
            });

            this.$Filter.clearBinds();
            this.$Filter.bindSitemap( this.$Sitemap );

            this.$Map.inject( Container );
            this.$Map.open();

            // set the panel title
            this.getHeader().getElement( 'h2' ).set(
                'html',

                '<img src="'+ URL_BIN_DIR +'16x16/flags/'+ Project.getAttribute('lang') +'.png" ' +
                    'style="margin: 9px 5px 0 0; float: left;"' +
                ' />'+
                Project.getAttribute('name')
            );

            this.$Button.setNormal();
        },

        /**
         * Select an sitemap item by ID
         *
         * @method QUI.controls.projects.Panel#selectSitemapItemById
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
         * @method QUI.controls.projects.Panel#getSitemapItemsById
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
         * @method QUI.controls.projects.Panel#openSite
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
         * @method QUI.controls.projects.Panel#$openSitePanel
         * @param {QUI.controls.sitemap.Item} Item
         */
        $openSitePanel : function(Item)
        {
            var Conrol  = this,
                id      = Item.getAttribute( 'value' ),
                project = this.getAttribute( 'project' ),
                lang    = this.getAttribute( 'lang' );


            require([
                'qui/QUI',
                'controls/projects/project/site/Panel',
                'classes/projects/Project',
                'classes/projects/project/Site'
            ], function(QUI, SitePanel, Project, Site)
            {
                var n      = 'panel-'+ project +'-'+ lang +'-'+ id,
                    panels = QUI.Controls.get( n );


                if ( panels.length )
                {
                    panels[ 0 ].open();

                    // if a task exist, click it and open the instance
                    var Task = panels[ 0 ].getAttribute( 'Task' );

                    if ( Task && Task.getType() == 'qui/controls/taskbar/Task' ) {
                        panels[ 0 ].getAttribute( 'Task' ).click();
                    }

                    return;
                }

                panels = QUI.Controls.getByType( 'qui/controls/desktop/Tasks' );

                if ( !panels.length ) {
                    return;
                }

                var Project = QUI.Projects.get( project, lang ),
                    Site    = Project.get( id );

                panels[ 0 ].appendChild(
                    new SitePanel(Site, {
                        events :
                        {
                            onShow : function(Panel)
                            {
                                if ( Panel.getType() != 'QUI.controls.projects.site.Panel' ) {
                                    return;
                                }
                                // if it is a sitepanel
                                // set the item in the map active
                                Conrol.openSite( Panel.getSite().getId() );
                            }
                        }
                    })
                );
            });
        },

        /**
         * opens a media panel from a project
         *
         * @method QUI.controls.projects.Panel#$openSitePanel
         * @param {String} Project name
         */
        openMediaPanel : function(project)
        {
            var n      = 'panel-'+ project +'-media',
                panels = QUI.Controls.get( n );

            if ( panels.length )
            {
                panels[ 0 ].open();

                // if a task exist, click it and open the instance
                var Task = panels[ 0 ].getAttribute( 'Task' );

                if ( Task && Task.getType() == 'qui/controls/taskbar/Task' ) {
                    panels[ 0 ].getAttribute( 'Task' ).click();
                }

                return;
            }

            panels = QUI.Controls.getByType( 'qui/controls/desktop/Tasks' );

            if ( !panels.length ) {
                return;
            }


            require([
                'qui/QUI',
                'controls/projects/project/media/Panel',
                'classes/projects/Project'
            ], function(QUI, MediaPanel, Project, Site)
            {
                var Project = QUI.Projects.get( project ),
                    Media   = Project.getMedia();

                panels[ 0 ].appendChild(
                    new MediaPanel( Project.getMedia(), {

                    })
                );
            });
        }
    });
});