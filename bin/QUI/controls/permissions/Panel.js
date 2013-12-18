/**
 * Comment here
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/permissions/Panel
 * @package com.pcsg.qui.js.controls.permissions
 * @namespace QUI.controls.permissions
 */

define('controls/permissions/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'utils/permissions/Utils',
    'qui/utils/Object',
    'Locale',
    'Ajax',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Seperator',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',

    'css!controls/permissions/Panel.css'

], function(QUI, Panel, Utils, ObjectUtils, Locale, Ajax, QUIButton, QUIButtonSeperator, Sitemap, SitemapItem)
{
    "use strict";

    /**
     * @class QUI.controls.permissions.Panel
     *
     * @param {Object} options - QDOM panel params
     * @param {QUI.classes.groups.Group|QUI.classes.users.User} Bind - [optional]
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : Panel,
        Type    : 'controls/permissions/Panel',

        Binds : [
            '$onCreate',
            '$onResize',
            '$onRefresh',
            '$onSitemapItemClick',
            '$onFormElementChange',
            'addPermission',
            'delPermission',
            'openSearch',
            'save'
        ],

        initialize : function(options, Bind)
        {
            // defaults
            this.setAttribute( 'title',
                Locale.get(
                    'quiqqer/system',
                    'permissions.panel.title'
                )
            );

            this.setAttribute( 'icon', 'icon-gears' );

            // init
            this.parent( options );

            this.$Bind   = Bind || null;
            this.$Map    = null;
            this.$rights = {};

            this.$bindpermissions = {};
            this.$Container       = null;

            this.addEvents({
                onCreate  : this.$onCreate,
                onResize  : this.$onResize,
                onRefresh : this.$onRefresh
            });
        },

        /**
         * Set the object for which the rights are
         *
         * @param {QUI.classes.groups.Group|QUI.classes.users.User|
         *         QUI.classes.projects.Site|QUI.classes.projects.Project} Bind
         */
        setBind : function(Bind)
        {
            this.Loader.show();
            this.$Bind = Bind;

            if ( !this.$Bind )
            {
                this.refresh();
                return;
            }

            this.Loader.show();

            var params = {
                id : Bind.getId()
            };

            switch ( Bind.getType() )
            {
                case 'classes/projects/Project':
                    params.project = Bind.getName();
                break;

                case 'classes/projects/Site':
                    var Project = Bind.getProject();

                    params.project = Project.getName();
                    params.lang    = Project.getLang();
                break;
            }

            // @todo load the rights of the object
            Ajax.get('ajax_permissions_get', function(result, Request)
            {
                var Control = Request.getAttribute( 'Control' );

                Control.$bindpermissions = result;
                Control.refresh();
            }, {
                params  : JSON.encode( params ),
                btype   : Bind.getType(),
                Control : this
            });
        },

        /**
         * Opens the search for groups / users
         *
         * @method QUI.controls.permissions.Panel#openSearch
         */
        openSearch : function()
        {
            var Sheet = this.createSheet(),
                Body  = this.getBody(),
                self  = this;

            if ( Body.getElement( '.qui-permissions-sitemap' ) ) {
                this.hideSitemap();
            }

            Sheet.addEvent('onOpen', function(Sheet)
            {
                var Container = new Element('div.qui-permissions-select-sheet', {
                    html   : '<h1>'+
                                 Locale.get(
                                     'quiqqer/system',
                                     'permissions.panel.select.group.title'
                                 ) +
                             '</h1>' +
                             '<div class="buttons"></div>'+
                             '<div class="search"></div>',
                    styles : {
                        margin : '50px auto 0',
                        width  : 612
                    }
                }).inject( Sheet.getBody() );

                var Buttons = Container.getElement( '.buttons' ),
                    btnList = [];


                btnList.push(
                    new QUIButton({
                        text : Locale.get(
                            'quiqqer/system',
                            'permissions.panel.btn.select.user'
                        ),
                        icon    : 'icon-user',
                        styles  : {
                            width : 200
                        },
                        events :
                        {
                            onClick : function(Btn) {
                                self.$loadUserSearch( Sheet );
                            }
                        }
                    }).inject( Buttons )
                );

                btnList.push(
                    new QUIButton({
                        text : Locale.get(
                            'quiqqer/system',
                            'permissions.panel.btn.select.group'
                        ),
                        icon   : 'icon-group',
                        events :
                        {
                            onClick : function(Btn) {
                                self.$loadGroupSearch( Sheet );
                            }
                        }
                    }).inject( Buttons )
                );

                btnList.push(
                    new QUIButton({
                        text : Locale.get(
                            'quiqqer/system',
                            'permissions.panel.btn.select.site'
                        ),
                        icon   : 'icon-file-alt',
                        events :
                        {
                            onClick : function(Btn)
                            {
                                /*
                                Btn.getAttribute( 'Control' ).$loadSiteSearch(
                                    Btn.getAttribute( 'Sheet' )
                                );
                                */
                            }
                        }
                    }).inject( Buttons )
                );

                btnList.push(
                    new QUIButton({
                        text : Locale.get(
                            'quiqqer/system',
                            'permissions.panel.btn.select.project'
                        ),
                        icon   : 'icon-home',
                        events :
                        {
                            onClick : function(Btn) {
                                self.$loadProjectSearch( Sheet );
                            }
                        }
                    }).inject( Buttons )
                );

                btnList.push(
                    new QUIButton({
                        text : Locale.get(
                            'quiqqer/system',
                            'permissions.panel.btn.select.manage'
                        ),
                        icon   : 'icon-gears',
                        events :
                        {
                            onClick : function(Btn)
                            {
                                Sheet.hide();

                                self.setBind( null );
                                self.getButtons( 'permissions-sitemap' ).click();
                            }
                        }
                    }).inject( Buttons )
                );

                var i, len, Elm;

                for ( i = 0, len = btnList.length; i < len; i++ )
                {
                    Elm = btnList[ i ].getElm();

                    Elm.removeClass( 'qui-button' );
                    Elm.addClass( 'button' );
                    Elm.addClass( 'btn-rosy' );
                    Elm.setStyles({
                        margin : '0 10px 10px 0',
                        width  : 190
                    });
                }

                self.Loader.hide();
            });

            Sheet.addEvent('onClose', function()
            {
                self.refresh();
                self.showSitemap();
            });

            Sheet.show();
        },

        /**
         * Load the group control, to select a group
         *
         * @method QUI.controls.permissions.Panel#$loadGroupSearch
         * @param {QUI.controls.desktop.panels.Sheet} Sheet
         */
        $loadGroupSearch : function(Sheet)
        {
            var Body   = Sheet.getBody(),
                Search = Body.getElement( '.search' );

            Search.set( 'html', '' );

            require(['controls/groups/Input'], function(Input)
            {
                Search.set(
                    'html',

                    '<h2>' +
                        QUI.Locale.get(
                            'quiqqer/system',
                            'permissions.panel.select.group.title'
                        ) +
                    '</h2>'
                );

                var GroupSearch = new Input({
                    max      : 1,
                    multible : false,
                    styles   : {
                        margin : '0 auto',
                        width  : 200
                    },
                    events :
                    {
                        onAdd : function(GroupSearch, groupid)
                        {
                            this.setBind( QUI.Groups.get( groupid ) );
                            //this.getButtons( 'permissions-sitemap' ).click();

                            Sheet.hide();
                            GroupSearch.close();

                        }.bind( this )
                    }
                }).inject( Search );

                GroupSearch.focus();
            }.bind( this ));
        },

        /**
         * Load the group control, to select a group
         *
         * @method QUI.controls.permissions.Panel#$loadUserSearch
         * @param {QUI.controls.desktop.panels.Sheet} Sheet
         */
        $loadUserSearch : function(Sheet)
        {
            var Body   = Sheet.getBody(),
                Search = Body.getElement( '.search' );

            Search.set( 'html', '' );

            require(['controls/users/Input'], function(Input)
            {
                Search.set(
                    'html',

                    '<h2>' +
                        QUI.Locale.get(
                            'quiqqer/system',
                            'permissions.panel.select.user.title'
                        ) +
                    '</h2>'
                );

                var UserSearch = new Input({
                    max      : 1,
                    multible : false,
                    styles   : {
                        margin : '0 auto',
                        width  : 200
                    },
                    events :
                    {
                        onAdd : function(UserSearch, userid)
                        {
                            this.setBind( QUI.Users.get( userid ) );
                            // this.getButtons( 'permissions-sitemap' ).click();

                            Sheet.hide();
                            UserSearch.close();

                        }.bind( this )
                    }
                }).inject( Search );

                UserSearch.focus();
            }.bind( this ));
        },

        /**
         * Load the project control, to select a project
         *
         * @method QUI.controls.permissions.Panel#$loadProjectSearch
         * @param {QUI.controls.desktop.panels.Sheet} Sheet
         */
        $loadProjectSearch : function(Sheet)
        {
            var Body   = Sheet.getBody(),
                Search = Body.getElement( '.search' );

            Search.set( 'html', '' );

            require(['controls/projects/Input'], function(Input)
            {
                Search.set(
                    'html',

                    '<h2>' +
                        QUI.Locale.get(
                            'quiqqer/system',
                            'permissions.panel.select.project.title'
                        ) +
                    '</h2>'
                );

                var ProjectSearch = new Input({
                    max      : 1,
                    multible : false,
                    styles   : {
                        margin : '0 auto',
                        width  : 200
                    },
                    events :
                    {
                        onAdd : function(UserSearch, project, lang)
                        {
                            this.setBind( QUI.Projects.get( project, lang ) );

                            Sheet.hide();
                            ProjectSearch.close();

                        }.bind( this )
                    }
                }).inject( Search );

                ProjectSearch.focus();
            }.bind( this ));
        },

        /**
         * Opens the add permission dialog
         *
         * @method QUI.controls.permissions.Panel#addPermission
         */
        addPermission : function()
        {
            new QUI.controls.windows.Prompt({
                title : QUI.Locale.get(
                    'quiqqer/system',
                    'permissions.panel.window.add.title'
                ),
                icon : URL_BIN_DIR +'16x16/add.png',
                text : QUI.Locale.get(
                    'quiqqer/system',
                    'permissions.panel.window.add.text'
                ),

                information : QUI.Locale.get(
                    'quiqqer/system',
                    'permissions.panel.window.add.information'
                ),

                autoclose : false,
                width     : 600,
                Control   : this,

                events :
                {
                    onDrawEnd : function(Win)
                    {
                        var Body    = Win.getBody(),
                            Input   = Body.getElement( 'input' ),
                            Control = Win.getAttribute( 'Control' );

                        Input.setStyles({
                            width   : 200,
                            'float' : 'left'
                        });

                        var Area = new Element('select', {
                            name : 'area',
                            html : '<option value="">Recht für Benutzer und Gruppen</option>'+
                                   '<option value="site">Seiten Zugriffsrecht</option>' +
                                   '<option value="media">Media Zugriffsrecht</option>',
                            styles : {
                                width   : 150,
                                margin  : '10px 5px 10px 10px',
                                'float' : 'left'
                            }
                        }).inject( Input, 'after' );

                        var Type = new Element('select', {
                            name : 'type',
                            html : '<option value="bool" selected="selected">bool</option>' +
                                   '<option value="string">string</option>' +
                                   '<option value="int">int</option>' +
                                   '<option value="group">group</option>' +
                                   '<option value="groups">groups</option>' +
                                   '<option value="user">user</option>' +
                                   '<option value="users">users</option>' +
                                   '<option value="array">array</option>',
                            styles : {
                                width   : 80,
                                margin  : '10px 5px',
                                'float' : 'left'
                            }
                        }).inject( Area, 'after' );

                        Body.getElement( '.information' ).setStyle( 'clear', 'both' );

                        if ( !Control.$Map ) {
                            return;
                        }

                        var sels = Control.$Map.getSelectedChildren();

                        if ( sels[ 0 ] )
                        {
                            Win.getInput().focus();
                            Win.setValue( sels[ 0 ].getAttribute( 'value' ) +'.' );
                        }
                    },

                    onSubmit : function(value, Win)
                    {
                        Win.Loader.show();

                        QUI.Ajax.post('ajax_permissions_add', function(result, Request)
                        {
                            if ( result )
                            {
                                Request.getAttribute( 'Win' ).close();
                                Request.getAttribute( 'Control' ).$createSitemap();
                            }
                        }, {
                            permission     : value,
                            area           : Win.getBody().getElement( '[name="area"]' ).value,
                            permissiontype : Win.getBody().getElement( '[name="type"]' ).value,
                            Win            : Win,
                            Control        : Win.getAttribute( 'Control' ),
                            onError : function(Exception, Request)
                            {
                                QUI.MH.addException( Exception );

                                Request.getAttribute( 'Win' ).Loader.hide();
                            }
                        });
                    }
                }

            }).create();
        },

        /**
         * Opens the dialog for delete a permission
         *
         * @method QUI.controls.permissions.Panel#delPermission
         * @param {QUI.controls.buttons.Button|String} right
         */
        delPermission : function(right)
        {
            if ( typeOf( right ) == 'QUI.controls.buttons.Button' ) {
                right = right.getAttribute( 'value' );
            }

            if ( !right ) {
                return;
            }

            new QUI.controls.windows.Submit({
                title : QUI.Locale.get(
                    'quiqqer/system',
                    'permissions.panel.window.delete.title'
                ),
                text  : QUI.Locale.get(
                    'quiqqer/system',
                    'permissions.panel.window.delete.text',
                    {
                        right : right
                    }
                ),
                information : QUI.Locale.get(
                    'quiqqer/system',
                    'permissions.panel.window.delete.information',
                    {
                        right : right
                    }
                ),
                icon      : URL_BIN_DIR +'16x16/cancel.png',
                texticon  : URL_BIN_DIR +'32x32/permissions.png',
                autoclose : false,
                width     : 450,
                height    : 200,
                right     : right,
                Control   : this,
                events :
                {
                    onSubmit : function(Win)
                    {
                        Win.Loader.show();

                        QUI.Ajax.post('ajax_permissions_delete', function(result, Request)
                        {
                            Request.getAttribute( 'Win' ).close();
                            Request.getAttribute( 'Control' ).$createSitemap();

                        }, {
                            permission : Win.getAttribute( 'right' ),
                            Control    : Win.getAttribute( 'Control' ),
                            Win        : Win
                        });
                    }
                }
            }).create();
        },

        /**
         * Save all permissions
         *
         * @method QUI.controls.permissions.Panel#save
         */
        save : function()
        {
            if ( !this.$Bind ) {
                return;
            }

            if ( this.getButtons( 'permissions-save' ) )
            {
                this.getButtons( 'permissions-save' ).setAttribute(
                    'textimage',
                    URL_BIN_DIR +'images/loader.gif'
                );
            }

            var params = {
                id : this.$Bind.getId()
            };

            switch ( this.$Bind.getType() )
            {
                case 'QUI.classes.projects.Project':
                    params.project = this.$Bind.getName();
                break;

                case 'QUI.classes.projects.Site':
                    var Project = this.$Bind.getProject();

                    params.project = Project.getName();
                    params.lang    = Project.getLang();
                break;
            }


            QUI.Ajax.post('ajax_permissions_save', function(result, Request)
            {
                var Control = Request.getAttribute( 'Control' );

                if ( Control.getButtons( 'permissions-save' ) )
                {
                    Control.getButtons( 'permissions-save' ).setAttribute(
                        'textimage',
                        URL_BIN_DIR +'16x16/save.png'
                    );
                }

            }, {
                params      : JSON.encode( params ),
                btype       : this.$Bind.getType(),
                permissions : JSON.encode( this.$bindpermissions ),
                Control     : this
            });
        },

        /**
         * event: on create
         * create the panel body
         *
         * @method QUI.controls.permissions.Panel#$onCreate
         */
        $onCreate : function()
        {
            this.Loader.show();

            // title - header info
            new Element( 'span.bind-info' ).inject( this.getHeader() );

            // create the main container
            this.$Container = new Element('div', {
                'class' : 'qui-permissions-content box smooth'
            }).inject( this.getBody() );

            this.addButton({
                name    : 'permissions-sitemap',
                image   : 'icon-sitemap',
                alt     : Locale.get(
                    'quiqqer/system',
                    'permissions.panel.btn.sitemap.alt'
                ),
                title   : Locale.get(
                    'quiqqer/system',
                    'permissions.panel.btn.sitemap.title'
                ),
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

            this.addButton({
                name    : 'permissions-select',
                image   : 'icon-gears',
                alt     : Locale.get(
                    'quiqqer/system',
                    'permissions.panel.btn.select.open.alt'
                ),
                title   : Locale.get(
                    'quiqqer/system',
                    'permissions.panel.btn.select.open.alt'
                ),
                Control : this,
                events  : {
                    onClick : this.openSearch
                }
            });

            this.addButton( new QUIButtonSeperator() );

            this.addButton({
                name      : 'permissions-add',
                textimage : 'icon-plus',
                disabled  : true,

                alt : Locale.get(
                    'quiqqer/system',
                    'permissions.panel.btn.add.alt'
                ),
                title : Locale.get(
                    'quiqqer/system',
                    'permissions.panel.btn.add.title'
                ),
                text : Locale.get(
                    'quiqqer/system',
                    'permissions.panel.btn.add.text'
                ),
                Control : this,
                events  : {
                    onClick : this.addPermission
                }
            });

            this.addButton({
                name      : 'permissions-save',
                textimage : 'icon-save',
                disabled  : true,

                alt : Locale.get(
                    'quiqqer/system',
                    'permissions.panel.btn.save.alt'
                ),
                title : Locale.get(
                    'quiqqer/system',
                    'permissions.panel.btn.save.title'
                ),
                text : Locale.get(
                    'quiqqer/system',
                    'permissions.panel.btn.save.text'
                ),
                Control : this,
                events  : {
                    onClick : this.save
                }
            });

            // show the object selection
            if ( !this.$Bind )
            {
                this.openSearch.delay( 200, this );
                return;
            }

            this.setBind( this.$Bind );
            this.getButtons( 'permissions-sitemap' ).click();
        },

        /**
         * event: on resize
         *
         * @method QUI.controls.permissions.Panel#$onResize
         */
        $onResize : function()
        {
            var Body = this.getBody(),
                size = Body.getSize();

            if ( this.$Map )
            {
                this.$Container.setStyles({
                    width       : size.x - 360,
                    marginLeft  : 300
                });

            } else
            {
                this.$Container.setStyles({
                    width       : size.x - 40,
                    marginLeft  : 0
                });
            }
        },

        /**
         * event: on panel refresh
         * eq: refresh the buttons
         *
         * @method QUI.controls.permissions.Panel#$onRefresh
         */
        $onRefresh : function()
        {
            var BtnSave = this.getButtons( 'permissions-save' ),
                BtnAdd  = this.getButtons( 'permissions-add' );

            if ( !this.$Bind )
            {
                BtnSave.disable();
                BtnAdd.enable();

                this.Loader.hide();
                return;
            }

            BtnSave.enable();
            BtnAdd.disable();

            var Title = this.getHeader(),
                Info  = Title.getElement( '.bind-info' ),
                title = '<span>: </span>';

            // user
            switch ( this.$Bind.getType() )
            {
                case 'QUI.classes.users.User':
                    title = title + '<span class="user">'+
                        this.$Bind.getId() +
                        ' - '+
                        this.$Bind.getAttribute( 'username' ) +
                    '</span>';
                break;

                case 'QUI.classes.groups.Group':
                    title = title + '<span class="group">'+
                        this.$Bind.getId() +
                        ' - ' +
                        this.$Bind.getAttribute( 'name' ) +
                    '</span>';
                break;

                case 'QUI.classes.projects.Site':
                    title = title + '<span class="site">'+
                        this.$Bind.getAttribute( 'name' ) +
                        ' - #'+ this.$Bind.getId() +
                    '</span>';
                break;
            }

            Info.set( 'html', title );

            this.showSitemap();
            this.Loader.hide();
        },

        /**
         * Sitemap methods
         */

        /**
         * Show the permission sitemap (list)
         *
         * @method QUI.controls.permissions.Panel#showSitemap
         */
        showSitemap : function()
        {
            var Container;

            var Body    = this.getBody(),
                Content = this.$Container;

            Content.set( 'html', '' );

            if ( !Body.getElement( '.qui-permissions-sitemap' ) )
            {
                new Element('div', {
                    'class' : 'qui-permissions-sitemap shadow',
                    styles  : {
                        left     : -350,
                        position : 'absolute'
                    }
                }).inject( Body, 'top' );
            }

            Container = Body.getElement( '.qui-permissions-sitemap' );

            moofx( Container ).animate({
                left : 0
            }, {
                callback : function()
                {
                    this.$createSitemap();
                    this.resize();

                    new Element('div', {
                        'class' : 'qui-permissions-sitemap-handle columnHandle',
                        styles  : {
                            position : 'absolute',
                            top      : 0,
                            right    : 0,
                            height   : '100%',
                            width    : 4,
                            cursor   : 'pointer'
                        },
                        events : {
                            click : this.hideSitemap.bind( this )
                        }
                    }).inject(
                        Body.getElement( '.qui-permissions-sitemap' )
                    );

                    var SitemapBtn = this.getButtons( 'permissions-sitemap' );

                    if ( !SitemapBtn.isActive() ) {
                        SitemapBtn.setActive();
                    }

                    this.Loader.hide();

                }.bind( this )
            });
        },

        /**
         * Hide the permission sitemap (list)
         *
         * @method QUI.controls.permissions.Panel#hideSitemap
         */
        hideSitemap : function()
        {
            var Body      = this.getBody(),
                Container = Body.getElement( '.qui-permissions-sitemap' );

            if ( this.$Map )
            {
                this.$Map.destroy();
                this.$Map = null;
            }

            moofx( Container ).animate({
                left : -350
            }, {
                callback : function(Container)
                {
                    var Body  = this.getBody(),
                        Items = this.$Container;

                    Container.destroy();

                    Items.setStyles({
                        width      : '100%',
                        marginLeft : null
                    });

                    var Btn = this.getButtons( 'permissions-sitemap' );

                    if ( Btn ) {
                        Btn.setNormal();
                    }

                    this.resize();

                }.bind( this, Container )
            });
        },

        /**
         * Creates the permission map
         *
         * @method QUI.controls.permissions.Panel#$createSitemap
         */
        $createSitemap : function()
        {
            if ( this.$Map ) {
                this.$Map.destroy();
            }

            this.$Map = new Sitemap();

            this.$Map.appendChild(
                new SitemapItem({
                    text   : 'Rechte',
                    icon   : 'icon-gears',
                    value  : '',
                    events : {
                        onClick : this.$onSitemapItemClick
                    }
                })
            );

            this.$Map.inject(
                this.getBody().getElement( '.qui-permissions-sitemap' )
            );

            Ajax.get('ajax_permissions_list', function(result, Request)
            {
                var arr, right;

                var tmp     = {},
                    Control = Request.getAttribute( 'Control' ),
                    Map     = Control.$Map;

                Control.$rights = result;

                if ( !Map ) {
                    return;
                }

                for ( right in result )
                {
                    arr = right.split( '.' );
                    arr.pop(); // drop the last element

                    if ( arr.length ) {
                        ObjectUtils.namespace( arr.join( '.' ), tmp );
                    }
                }

                // create the children
                Control.$appendSitemapItemTo( Map.firstChild(), '', tmp );

                //Map.firstChild().open();
                Map.openAll();
                Map.firstChild().click();

            }, {
                Control : this
            });
        },

        /**
         * Recursive append item helper for sitemap
         *
         * @method QUI.controls.permissions.Panel#$appendSitemapItemTo
         * @param {QUI.controls.sitemap.Item} Parent
         * @param {String} name
         * @param {Object} params
         */
        $appendSitemapItemTo : function(Parent, name, params)
        {
            var right, Item, _name;

            for ( right in params )
            {
                if ( name.length )
                {
                    _name = name +'.'+ right;
                } else
                {
                    _name = right;
                }

                Item = new SitemapItem({
                    icon  : 'icon-gears',
                    value : _name,
                    text  : Locale.get( 'locale/permissions', _name +'._title' ),
                    events : {
                        onClick : this.$onSitemapItemClick
                    }
                });

                Parent.appendChild( Item );

                this.$appendSitemapItemTo( Item, _name, params[ right ] );
            }
        },

        /**
         * event : on sitemap item click
         *
         * @method QUI.controls.permissions.Panel#$onSitemapItemClick
         * @param {QUI.controls.sitemap.Item} Item
         */
        $onSitemapItemClick : function(Item)
        {
            this.Loader.show();

            var list, right, Elm;

            var i     = 0,
                len   = 0,
                val   = Item.getAttribute( 'value' ) +'.';

            this.$Container.set( 'html', '' );

            var Table = new Element('table', {
                'class' : 'data-table',
                html    : '<tr><th>'+ Item.getAttribute( 'text' ) +'</th></tr>'
            });


            // maybe to php?
            for ( right in this.$rights )
            {
                if ( val == '.' && right.match( /\./ ) ) {
                    continue;
                }

                if ( val == '.' && !right.match( /\./ ) )
                {
                    this.$createPermissionRow(
                        this.$rights[ right ],
                        i,
                        Table
                    );

                    i++;

                    continue;
                }

                if ( !right.match( val ) ) {
                    continue;
                }

                if ( right.replace( val, '' ).match( /\./ ) ) {
                    continue;
                }

                this.$createPermissionRow(
                    this.$rights[ right ],
                    i,
                    Table
                );

                i++;
            }

            // no rights
            if ( i === 0 )
            {
                new Element('tr', {
                    'class' : 'odd',
                    html    : '<td>'+ Locale.get(
                                'quiqqer/system',
                                'permissions.panel.message.no.rights'
                            ) +
                        '</td>'
                }).inject( Table );
            }

            Table.inject( this.$Container );

            this.$Container.getElements( 'input' ).addEvent(
                'change',
                this.$onFormElementChange
            );

            var perms = this.$bindpermissions;

            // set form values
            list = this.$Container.getElements( 'input' );

            for ( i = 0, len = list.length; i < len; i++ )
            {
                Elm = list[ i ];

                if ( typeof perms[ Elm.name ] === 'undefined' ) {
                    continue;
                }

                if ( Elm.type == 'checkbox' )
                {
                    if ( perms[ Elm.name ] == 1 ) {
                        Elm.checked = true;
                    }

                    continue;
                }

                Elm.value = perms[ Elm.name ];
            }

            // parse controls
            //QUI.controls.Utils.parse( Table );
            console.info( '@todo QUI.controls.Utils.parse( Table );' );

            this.Loader.hide();
        },

        /**
         * event: if a form element triggered its onchange event
         *
         * @method QUI.controls.permissions.Panel#$onFormElementChange
         */
        $onFormElementChange : function(event)
        {
            var Target = event.target;

            if ( Target.type == 'checkbox' )
            {
                this.$bindpermissions[ Target.name ] = Target.checked ? 1 : 0;
                return;
            }

            this.$bindpermissions[ Target.name ] = Target.value;
        },

        /**
         * Create the controls in the rows of the permission tables
         *
         * @method QUI.controls.permissions.Panel#$createPermissionRow
         * @param {String} right - right name
         * @param {Integer} i - row counter
         * @param {DOMNode }Table - <table> Node Element
         */
        $createPermissionRow : function(right, i, Table)
        {
            var list, Node, Row, Elm;

            Row = new Element('tr', {
                'class' : i % 2 ? 'even' : 'odd',
                html    : '<td></td>'
            });

            Node = Utils.parse( right );

            // first we disable all
            Node.addClass( 'disabled' );

            // than, we enable only for the binded area
            if ( this.$Bind )
            {
                switch ( this.$Bind.getType() )
                {
                    case 'QUI.classes.projects.Site':
                        Node.getElements( 'input[data-area="site"]' )
                            .getParent()
                            .removeClass( 'disabled' );
                    break;

                    case 'QUI.classes.projects.Project':
                        Node.getElements( 'input[data-area="project"]' )
                            .getParent()
                            .removeClass( 'disabled' );
                    break;
                }
            }

            // edit modus
            if ( !this.$Bind )
            {
                Node.getElements( 'label' ).set({
                    styles : {
                        marginLeft : 10,
                        lineHeight : 24
                    }
                });

                // only user rights can be deleted
                if ( right.src == 'user' )
                {
                    new QUIButton({
                        icon   : URL_BIN_DIR +'16x16/cancel.png',
                        title  : QUI.Locale.get(
                            'quiqqer/system',
                            'permissions.panel.btn.delete.right.alt',
                            {
                                right : right.name
                            }
                        ),
                        alt : QUI.Locale.get(
                            'quiqqer/system',
                            'permissions.panel.btn.delete.right.title',
                            {
                                right : right.name
                            }
                        ),
                        value  : right.name,
                        events : {
                            onClick : this.delPermission
                        }
                    }).inject( Node, 'top' );
                }
            }

            Node.inject( Row.getElement( 'td' ) );
            Row.inject( Table );
        }

    });
});