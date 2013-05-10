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
    'controls/projects/Sitemap',
    'controls/sitemap/Filter',

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
            'hideSitemap',
            '$onCreate'
        ],

        options : {
            id    : 'project-site-search',
            icon  : URL_BIN_DIR +'16x16/search.png',
            title : 'Seiten suchen...'
        },

        initialize : function(options)
        {
            this.parent( options );

            this.$Project    = null;
            this.$ProjectMap = null;
            this.$MapFilter  = null;

            this.addEvents({
                onCreate : this.$onCreate
            });
        },


        /**
         * event: oncreate - panel creation
         */
        $onCreate : function()
        {
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
            this.getBody().set(
                'html',

                '<div class="qui-search-content">'+
                    '<form></form>' +
                '</div>'
            );
        },

        /**
         * Show the sitemap of a project
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
                    width : 285,
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

            Content.setStyles({
                width       : bsize.x - 350,
                marginLeft  : 300
            });

            Container.setStyles({
                height : bsize.y - 70
            });

            moofx( ProjectMap ).animate({
                left : 0
            }, {
                callback : function()
                {
                    //this.$createSitemap();
                    this.$resizeSheet();

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
                    });

                }.bind( this )
            });
        },

        /**
         * Hide the sitemap element
         */
        hideSitemap : function()
        {
            var Body      = this.getBody(),
                Container = Body.getElement( '.qui-search-sitemap' ),
                Content   = Body.getElement( '.qui-search-content' );

            if ( this.$ProjectMap )
            {
                this.$ProjectMap.clear();
                this.$ProjectMap.destroy();

                this.$ProjectMap = null;
            }

            moofx( Container ).animate({
                left : -350
            }, {
                callback : function(Container)
                {
                    var Body  = this.getBody(),
                        Items = Body.getElement('.qui-search-content');

                    Container.destroy();

                    Content.setStyles({
                        width      : '100%',
                        marginLeft : null
                    });

                    var Btn = this.getButtons( 'site-sitemap-button' );

                    if ( Btn ) {
                        Btn.setNormal();
                    }

                    this.$resizeSheet();

                }.bind( this, Container )
            });
        },

        /**
         * Draw the sitemap of a project
         *
         * @param {String} project - project name
         * @param {String} lang    - project language
         */
        $createSitemap : function(project, lang)
        {
            if ( this.$ProjectMap )
            {
                this.$ProjectMap.clear();
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
         * Resize the inner body of the panel
         */
        $resizeSheet : function()
        {

        }
    });

    return QUI.controls.projects.site.Search;
});