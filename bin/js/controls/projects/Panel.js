/**
 * Displays a Project in a Panel
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires lib/Projects
 * @requires buttons/Button
 * @requires controls/projects/Sitemap
 *
 * @module controls/projects/Panel
 * @package com.pcsg.qui.js.controls.project
 * @namespace QUI.controls.project
 */

define('controls/projects/Panel', [

    'controls/desktop/Panel',
    'lib/Projects',
    'controls/projects/Sitemap',
    'controls/desktop/Panel',

    'controls/buttons/Button',
    'controls/sitemap/Map',
    'controls/sitemap/Item',
    'controls/sitemap/Filter',

    'css!controls/projects/Panel.css'

], function(QUI_Control, QUI_Projects, QUI_ProjectSitemap)
{
    QUI.namespace( 'controls.projects' );

    /**
     * @class QUI.controls.projects.Panel
     * @param {Object} options
     */
    QUI.controls.projects.Panel = new Class({

        Extends : QUI_Control,
        Type    : 'QUI.controls.projects.Panel',

        Binds : [
            '$onCreate',
            '$onResize'
        ],

        initialize : function(options)
        {
            // defaults
            this.setAttributes({
                name    : 'projects-panel',
                project : false,
                lang    : false
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
            this.$Filter = new QUI.controls.sitemap.Filter(null, {
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
            new QUI.controls.buttons.Button({
                image  : URL_BIN_DIR +'16x16/search.png',
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
            new QUI.controls.buttons.Button({
                name     : 'add_projects',
                image    : URL_BIN_DIR +'16x16/actions/edit_add.png',
                title    : 'Projekt hinzufügen',
                alt      : 'Projekt hinzufügen',
                events   :
                {
                    onClick : function(Btn, event)
                    {
                        event.stop();

                        require(['controls/projects/Manager'], function(Manager)
                        {
                            var Parent = QUI.Controls.get( 'content-panel' )[0];

                            Parent.appendChild(
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
                },
                styles : {
                    margin : '4px 0 0 4px'
                }
            }).inject( this.getHeader(), 'top' );

            // title button
            this.$Button = new QUI.controls.buttons.Button({
                name     : 'projects',
                image    : URL_BIN_DIR +'16x16/home.png',
                events   :
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
                },
                styles : {
                    margin : '4px 0 0 4px'
                }
            }).inject( this.getHeader(), 'top' );

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
            if ( this.$Map ) {
                this.$Map.destroy();
            }

            QUI.lib.Projects.getList(function(result, Ajax)
            {
                var i, l, langs, len,
                    scrollsize, Map, Project,
                    func_project_click, func_media_click;

                var Panel     = Ajax.getAttribute( 'Panel' ),
                    Content   = Panel.getBody(),
                    List      = Content.getElement( '.project-list' ),
                    Container = new Element( 'div' );

                List.set( 'html', '' );

                // click events
                func_project_click = function(Itm, event)
                {
                    var Panel = Itm.getAttribute( 'Panel' );

                    Panel.setAttribute( 'project', Itm.getAttribute( 'project' ) );
                    Panel.setAttribute( 'lang', Itm.getAttribute( 'lang' ) );

                    Panel.openProject();
                };

                func_media_click = function(Itm, event)
                {
                    QUI.lib.Projects.createMediaPanel(
                        Itm.getAttribute('project')
                    );
                };

                if ( Panel.$Filter ) {
                    Panel.$Filter.clearBinds();
                }


                // create
                for ( i in result )
                {
                    if ( !result[i].langs ) {
                        continue;
                    }

                    langs = result[i].langs.split( ',' );

                    if ( !Panel.$projectmaps[ i ] ) {
                        Panel.$projectmaps[ i ] = new QUI.controls.sitemap.Map();
                    }

                    Map = Panel.$projectmaps[ i ];
                    Map.clearChildren();

                    if ( Panel.$Filter ) {
                        Panel.$Filter.bindSitemap( Map );
                    }

                    Project = new QUI.controls.sitemap.Item({
                        text    : i,
                        icon    : URL_BIN_DIR +'16x16/home.png',
                        project : i,
                        lang    : result[i].default_lang,
                        Panel   : Panel,
                        events  : {
                            onClick : func_project_click
                        }
                    });

                    Map.appendChild( Project );

                    for ( l = 0, len = langs.length; l < len; l++ )
                    {
                        // project Lang
                        Project.appendChild(
                            new QUI.controls.sitemap.Item({
                                text    : langs[l],
                                icon    : URL_BIN_DIR +'16x16/flags/'+ langs[l] +'.png',
                                name    : 'project.'+ i +'.'+ langs[l],
                                project : i,
                                lang    : langs[l],
                                Panel   : Panel,
                                events  : {
                                    onClick : func_project_click
                                }
                            })
                        );
                    }

                    // Media
                    Project.appendChild(
                        new QUI.controls.sitemap.Item({
                            text    : 'Media',
                            icon    : URL_BIN_DIR +'16x16/media.png',
                            project : i,
                            Panel   : Panel,
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
                        this.$Button.setActive();
                    }.bind( Panel )
                });
            }, {
                Panel : this
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

                Project = QUI.lib.Projects.get(
                    this.getAttribute( 'project' ),
                    this.getAttribute( 'lang' )
                );

            moofx( List ).animate({
                left : List.getSize().x * -1
            });

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

            // create the project sitemap in the panel
            if ( this.$Map ) {
                this.$Map.destroy();
            }

            this.$Map = new QUI.controls.projects.Sitemap({
                project : Project.getAttribute( 'name' ),
                lang    : Project.getAttribute( 'lang' )
            });

            this.$Sitemap = this.$Map.getMap();

            this.$Sitemap.addEvents({

                onChildClick : function(Itm)
                {
                    QUI.lib.Projects.createSitePanel(
                        this.getAttribute( 'name' ),
                        this.getAttribute( 'lang' ),
                        Itm.getAttribute( 'value' )
                    );
                }.bind( Project ),

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
         *
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
         *
         * @param {Integer} id - ID from the wanted site
         */
        openSite : function(id)
        {
            if ( typeof this.$Map !== 'undefined' ) {
                this.$Map.openSite( id );
            }
        }
    });

    return QUI.controls.projects.Panel;
});