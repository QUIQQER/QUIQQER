/**
 * a site search
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/projects/site/Search
 * @package com.pcsg.qui.js.controls.projects.site
 * @namespace QUI.controls.projects.site
 */

define('controls/projects/site/Search', [

    'controls/desktop/Panel',
    'controls/buttons/Select',
    'controls/buttons/Button',
    'controls/projects/Sitemap',
    'controls/sitemap/Filter',
    'controls/grid/Grid',

    'css!controls/projects/site/Search.css'

], function(QUI_Control)
{
    "use strict";

    QUI.namespace( 'controls.projects.site' );

    /**
     * @class QUI.controls.projects.site.Search
     *
     * @memberof! <global>
     */
    QUI.controls.projects.site.Search = new Class({

        Extends : QUI_Control,
        Type    : 'QUI.controls.projects.site.Search',

        Binds : [
            'search',
            'hideSitemap',
            '$onCreate',
            '$onResize',
            '$onDestroy'
        ],

        options : {
            id    : 'project-site-search',
            icon  : URL_BIN_DIR +'16x16/search.png',
            title : 'Seiten suchen...',

            project : null,

            fields  : {
                id      : true,
                name    : true,
                title   : true,
                short   : false,
                content : false
            }
        },

        initialize : function(options)
        {
            this.parent( options );

            this.$ProjectMap = null;
            this.$MapFilter  = null;
            this.$Grid       = null;

            this.addEvents({
                onCreate  : this.$onCreate,
                onResize  : this.$onResize,
                onDestroy : this.$onDestroy
            });
        },

        /**
         * event: oncreate - panel creation
         *
         * @method QUI.controls.projects.site.Search#$onCreate
         */
        $onCreate : function()
        {
            var Control = this;

            // buttons
            this.addButton({
                name    : 'site-sitemap-button',
                image   : URL_BIN_DIR +'16x16/view_tree.png',
                alt     : 'Sitemap anzeigen',
                title   : 'Sitemap anzeigen',
                Control : this,
                events  :
                {
                    onClick : function(Btn)
                    {
                        if ( Btn.isActive() )
                        {
                            Btn.getAttribute('Control').hideSitemap();
                            Btn.setNormal();
                            return;
                        }

                        Btn.getAttribute('Control').showSitemap();
                        Btn.setActive();
                    }
                }
            });


            // create the search form & body
            QUI.Template.get('project_site_search', function(result, Request)
            {
                var Body = Control.getBody();

                Body.set( 'html', result );

                // form element
                QUI.Utils.setDataToForm(
                    Control.getAttribute( 'fields' ),
                    Body.getElement( 'form' )
                );

                Body.getElement( 'form' ).addEvent('submit', function(event)
                {
                    Control.search();
                    event.stop();
                });

                Body.getElement( 'input' ).addEvent('change', function(event)
                {
                    if ( this.type != 'checkbox' ) {
                        return;
                    }

                    var fields = Control.getAttribute( 'fields' );

                    fields[ this.get( 'name' ) ] = this.checked ? true : false;

                    Control.setAttribute( 'fields', fields );
                });

                Body.getElement( 'input[name="qui-search-string"]' ).focus();

                // project lang
                var Select = new QUI.controls.buttons.Select({
                    name   : 'project_select',
                    styles : {
                        width : 100
                    },
                    events :
                    {
                        onChange : function(value, Select) {
                            Control.setAttribute( 'project', value );
                        }
                    }
                }).inject(
                    Body.getElement( '.qui-search-elements' )
                );

                QUI.Projects.getList(function(result, Request)
                {
                    var i, len, project, langs;

                    for ( project in result )
                    {
                        langs = result[ project ].langs.split(',');

                        for ( i = 0, len = langs.length; i < len; i++ )
                        {
                            Select.appendChild(
                                project +' ('+ langs[ i ] +')',
                                project +':'+ langs[ i ],
                                URL_BIN_DIR +'16x16/flags/'+ langs[ i ] +'.png'
                            );
                        }
                    }

                    Select.setValue(
                        Select.firstChild().getAttribute( 'value' )
                    );
                });

                // search button
                new QUI.controls.buttons.Button({
                    textimage : URL_BIN_DIR +'16x16/search.png',
                    text   : 'suchen',
                    events : {
                        onClick : Control.search
                    }
                }).inject(
                    Body.getElement( '.qui-search-elements' )
                );

                // set the sizes of the elements
                Control.resize();

                // result table
                Control.$Grid = new QUI.controls.grid.Grid(
                    Body.getElement( '.qui-search-results' ),
                    {
                        columnModel : [{
                            header    : 'ID',
                            dataIndex : 'id',
                            dataType  : 'string',
                            width     : 50
                        }, {
                            header    : 'Name',
                            dataIndex : 'name',
                            dataType  : 'string',
                            width     : 150
                        }, {
                            header    : 'Titel',
                            dataIndex : 'title',
                            dataType  : 'string',
                            width     : 150
                        }],
                        pagination : true
                    }
                );

                Control.$Grid.addEvents({
                    onDblClick : function(event)
                    {
                        if ( !Control.getAttribute( 'project' ) ) {
                            return;
                        }

                        var data = Control.$Grid.getDataByRow( event.row );

                        Control.openSitePanel(
                            Control.getAttribute( 'project' ).split( ':' )[0],
                            Control.getAttribute( 'project' ).split( ':' )[1],
                            data.id
                        );
                    }
                });
            });
        },

        /**
         * Start the search
         */
        search : function()
        {
            var Control = this,
                Body    = this.getBody();

            if ( !this.getAttribute( 'project' ) ) {
                return;
            }

            if ( !Body ) {
                return;
            }

            this.Loader.show();


            var Search  = Body.getElement( '[name="qui-search-string"]' ),
                options = Body.getElements( 'fieldset input' ),

                project = this.getAttribute( 'project' ).split(':')[0],
                lang    = this.getAttribute( 'project' ).split(':')[1];

            var i, len;
            var fields = [];

            for ( i = 0, len = options.length; i < len; i++ )
            {
                if ( options[ i ].checked ) {
                    fields.push( options[ i ].name );
                }
            }

            QUI.Ajax.get('ajax_project_sites_search', function(result, Request)
            {
                Control.$Grid.setData( result );
                Control.Loader.hide();

            }, {
                project : project,
                lang    : lang,
                search  : Search.value,
                params  : JSON.encode({
                    fields : fields
                })
            });
        },

        /**
         * Opens the panel a Site
         *
         * @method QUI.controls.projects.site.Search#openSitePanel
         * @param {String} project - Name of the Project
         * @param {String} lang - Language of the Project
         * @param {Integer} id - Site-ID
         */
        openSitePanel : function(project, lang, id)
        {
            require([
                'controls/projects/site/Panel',
                'classes/projects/Project',
                'classes/projects/Site'
            ], function(QUI_SitePanel, QUI_Site)
            {
                var n      = 'panel-'+ project +'-'+ lang +'-'+ id,
                    panels = QUI.Controls.get( n );


                if ( panels.length )
                {
                    panels[ 0 ].open();

                    // if a task exist, click it and open the instance
                    var Task = panels[ 0 ].getAttribute( 'Task' );

                    if ( Task && Task.getType() == 'QUI.controls.taskbar.Task' ) {
                        panels[ 0 ].getAttribute( 'Task' ).click();
                    }

                    return;
                }

                panels = QUI.Controls.getByType( 'QUI.controls.desktop.Tasks' );

                if ( !panels.length ) {
                    return;
                }

                var Project = QUI.Projects.get( project, lang ),
                    Site    = Project.get( id );

                panels[ 0 ].appendChild(
                    new QUI_SitePanel( Site )
                );
            });
        },

        /**
         * Show the sitemap of a project
         *
         * @method QUI.controls.projects.site.Search#showSitemap
         */
        showSitemap : function()
        {
            var Container, ProjectMap;

            var Body    = this.getBody(),
                Content = Body.getElement( '.qui-search-content' ),
                bsize   = Body.getSize();

            if ( !Body.getElement( '.qui-search-sitemap' ) )
            {
                new Element('div', {
                    'class' : 'qui-search-sitemap shadow',
                    html    : '<div class="qui-search-sitemap-handle columnHandle"></div>' +
                              '<div class="qui-search-sitemap-container"></div>' +
                              '<div class="qui-search-sitemap-filter"></div>',
                    styles  : {
                        left     : -350,
                        position : 'absolute'
                    }
                }).inject( Body, 'top' );
            }

            ProjectMap = Body.getElement( '.qui-search-sitemap' );
            Container  = Body.getElement( '.qui-search-sitemap-container' );

            // select box
            var Select = new QUI.controls.buttons.Select({
                name   : 'project_select',
                styles : {
                    width : 285
                },
                events :
                {
                    onChange : function(value, Select)
                    {
                        var data = value.split( ':' );

                        this.$createSitemap(
                             data[0],
                             data[1]
                        );

                    }.bind( this )
                }
            }).inject( ProjectMap, 'top' );

            // filter
            this.$MapFilter = new QUI.controls.sitemap.Filter(null, {
                styles : {
                    background : '#F2F2F2'
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
            }).inject( Body.getElement( '.qui-search-sitemap-filter' ) );

            this.$MapFilter.getInput().setStyle( 'width', 284 );

            // elements
            Body.getElement( '.qui-search-sitemap-handle' ).set({
                styles  : {
                    position : 'absolute',
                    top      : 0,
                    right    : 0,
                    height   : '100%',
                    width    : 4,
                    cursor   : 'pointer'
                },
                events : {
                    click : this.hideSitemap
                }
            });

            moofx( ProjectMap ).animate({
                left : 0
            }, {
                callback : function()
                {
                    //this.$createSitemap();
                    this.resize();

                    // project dropdown
                    QUI.Projects.getList(function(result, Request)
                    {
                        var i, len, project, langs;

                        for ( project in result )
                        {
                            langs = result[ project ].langs.split(',');

                            for ( i = 0, len = langs.length; i < len; i++ )
                            {
                                Select.appendChild(
                                    project +' ('+ langs[ i ] +')',
                                    project +':'+ langs[ i ],
                                    URL_BIN_DIR +'16x16/flags/'+ langs[ i ] +'.png'
                                );
                            }
                        }

                        Select.setValue(
                            Select.firstChild().getAttribute( 'value' )
                        );
                    });

                }.bind( this )
            });
        },

        /**
         * Hide the sitemap element
         *
         * @method QUI.controls.projects.site.Search#hideSitemap
         */
        hideSitemap : function()
        {
            var Body      = this.getBody(),
                Container = Body.getElement( '.qui-search-sitemap' ),
                Content   = Body.getElement( '.qui-search-content' );

            if ( this.$ProjectMap )
            {
                this.$ProjectMap.destroy();
                this.$ProjectMap = null;
            }

            moofx( Container ).animate({
                left : -350
            }, {
                callback : function(Container)
                {
                    var Body  = this.getBody(),
                        Items = Body.getElement( '.qui-search-content' );

                    Container.destroy();

                    var Btn = this.getButtons( 'site-sitemap-button' );

                    if ( Btn ) {
                        Btn.setNormal();
                    }

                    this.resize();

                }.bind( this, Container )
            });
        },

        /**
         * Draw the sitemap of a project
         *
         * @method QUI.controls.projects.site.Search#$createSitemap
         * @param {String} project - project name
         * @param {String} lang    - project language
         */
        $createSitemap : function(project, lang)
        {
            if ( this.$ProjectMap )
            {
                this.$ProjectMap.destroy();
                this.$ProjectMap = null;
            }

            this.$ProjectMap = new QUI.controls.projects.Sitemap({
                project : project,
                lang    : lang
            }).inject(
                this.getBody().getElement( '.qui-search-sitemap-container' )
            );

            this.$ProjectMap.open();

            if ( this.$MapFilter ) {
                this.$MapFilter.bindSitemap( this.$ProjectMap );
            }
        },

        /**
         * event : on panel resize
         * Resize all elements
         *
         * @method QUI.controls.projects.site.Search#$onResize
         */
        $onResize : function()
        {
            var Body = this.getBody();

            if ( !Body ) {
                return;
            }

            var Results   = Body.getElement( '.qui-search-results' ),
                Sitemap   = Body.getElement( '.qui-search-sitemap' ),
                Content   = Body.getElement( '.qui-search-content' ),
                Container = Body.getElement( '.qui-search-sitemap-container' ),

                bsize = Body.getSize();

            if ( !Container )
            {
                Content.setStyles({
                    width      : '100%',
                    marginLeft : null
                });
            } else
            {
                Content.setStyles({
                    width       : bsize.x - 340,
                    marginLeft  : 300
                });

                Sitemap.setStyles({
                    height : bsize.y
                });

                Container.setStyles({
                    height : bsize.y - 70
                });
            }

            if ( this.$Grid ) {
                this.$Grid.setHeight( bsize.y - 200 );
            }
        },

        /**
         * event : on panel destroy
         *
         * @method QUI.controls.projects.site.Search#$onDestroy
         */
        $onDestroy : function()
        {
            if ( this.$ProjectMap ) {
                this.$ProjectMap.destroy();
            }

            if ( this.$MapFilter ) {
                this.$MapFilter.destroy();
            }

            if ( this.$Grid ) {
                this.$Grid.destroy();
            }
        }
    });

    return QUI.controls.projects.site.Search;
});