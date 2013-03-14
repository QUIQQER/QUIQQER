/**
 * Package Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module controls/packages/Panel
 * @package com.pcsg.qui.js.controls.packages
 * @namespace QUI.controls.packages
 */

define('controls/packages/Panel', [

    'controls/desktop/Panel',

    'css!controls/packages/Panel.css'

],function(QUI_Panel)
{
    QUI.namespace( 'controls.packages' );

    /**
     * @class QUI.controls.packages.Panel
     *
     * @param {Object} options - QDOM panel params
     *
     * @memberof! <global>
     */
    QUI.controls.packages.Panel = new Class({

        Extends : QUI.controls.desktop.Panel,
        Type    : 'QUI.controls.packages.Panel',

        Binds : [
            '$onCreate',
            '$onResize',
            '$onRefresh',
            '$serverGridClick',
            '$serverGridBlur',

            'loadUpdates',
            'unloadUpdates',
            'checkUpdates',
            'uploadUpdates',
            '$clickUpdateBtn',

            'loadPackages',
            'unloadPackages',

            'loadServers',
            'unloadServer',

            'loadSearch',
            'unloadSearch',
            'startSearch',

            'dialogAddServer',
            'dialogInstall'
        ],

        options : {
            name  : 'packages-panel',
            field : 'name',
            order : 'DESC',
            limit : 20,
            page  : 1,
            type  : ''
        },

        initialize : function(options)
        {
            this.parent( options );

            // defaults
            this.setAttribute( 'title',
                QUI.Locale.get(
                    'quiqqer/system',
                    'packages.panel.title'
                )
            );

            this.setAttribute( 'icon', URL_BIN_DIR +'16x16/packages.png' );

            // init
            this.init( options );

            this.$Grid       = null;
            this.$PluginGrid = null;
            this.$UpdateGrid = null;
            this.$ServerGrid = null;
            this.$SearchGrid = null;

            this.$packages = {};

            this.addEvents({
                onCreate  : this.$onCreate,
                onResize  : this.$onResize,
                onRefresh : this.$onRefresh
            });
        },

        /**
         * event: on panel create
         */
        $onCreate : function()
        {
            this.addCategory({
                name : 'updates',
                text : QUI.Locale.get(
                    'quiqqer/system',
                    'packages.category.updates'
                ),
                image  : URL_BIN_DIR +'32x32/update.png',
                events : {
                    onActive : this.loadUpdates,
                    onNormal : this.unloadUpdates
                }
            });

            this.addCategory({
                name : 'plugins',
                text : QUI.Locale.get(
                    'quiqqer/system',
                    'packages.category.plugins'
                ),
                image  : URL_BIN_DIR +'32x32/plugins.png',
                events :
                {
                    onActive : function()
                    {
                        this.setAttribute( 'type', 'quiqqer-plugin' );
                        this.loadPackages();
                    }.bind( this ),

                    onNormal : this.unloadPackages
                }
            });

            this.addCategory({
                name : 'packages',
                text : QUI.Locale.get(
                    'quiqqer/system',
                    'packages.category.packages'
                ),
                image  : URL_BIN_DIR +'32x32/packages.png',
                events :
                {
                    onActive : function()
                    {
                        this.setAttribute( 'type', '' );
                        this.loadPackages();
                    }.bind( this ),

                    onNormal : this.unloadPackages
                }
            });

            this.addCategory({
                name : 'server',
                text : QUI.Locale.get(
                    'quiqqer/system',
                    'packages.category.server'
                ),
                image  : URL_BIN_DIR +'32x32/filesystems/server.png',
                events : {
                    onActive : this.loadServers,
                    onNormal : this.unloadServer
                }
            });

            this.addCategory({
                name : 'search',
                text : QUI.Locale.get(
                    'quiqqer/system',
                    'packages.category.search'
                ),
                image  : URL_BIN_DIR +'32x32/actions/find.png',
                events : {
                    onActive : this.loadSearch,
                    onNormal : this.unloadSearch
                }
            });

            this.getCategoryBar().firstChild().click();
        },

        /**
         * event: on panel resize
         */
        $onResize : function()
        {
            var Form, Title, Information, height;

            var Body = this.getBody(),
                size = Body.getSize();

            if ( this.$Grid )
            {
                this.$Grid.setHeight( size.y -40 );
                this.$Grid.setWidth( size.x -40 );
            }

            if ( this.$PluginGrid )
            {
                this.$PluginGrid.setHeight( size.y -40 );
                this.$PluginGrid.setWidth( size.x -40 );
            }

            if ( this.$UpdateGrid )
            {
                Title       = Body.getElement( 'h1' );
                Information = Body.getElement( '.description' );

                if ( !Title || !Information) {
                    return;
                }

                height = ( Title.getSize().y + Information.getSize().y );
                height = Body.getSize().y - height - 80;

                this.$UpdateGrid.setHeight( height );
                this.$UpdateGrid.setWidth( size.x - 40 );
            }

            if ( this.$SearchGrid )
            {
                Title       = Body.getElement( 'h1' );
                Information = Body.getElement( '.description' );
                Form        = Body.getElement( 'form' );

                if ( !Form || !Title || !Information) {
                    return;
                }

                height = Title.getSize().y +
                         Information.getSize().y +
                         Form.getSize().y;

                height = Body.getSize().y - height - 100;

                this.$SearchGrid.setHeight( height );
                this.$SearchGrid.setWidth( size.x - 40 );
            }
        },

        /**
         * event: on panel refresh
         */
        $onRefresh : function()
        {

        },

    /**
     * Update methods
     */

        /**
         * load the update category
         */
        loadUpdates : function()
        {
            this.Loader.show();

            var Body = this.getBody().set( 'html', '' ),

                Container = new Element('div', {
                    'class' : 'qui-packages-panel-update',
                    styles : {
                        width : '100%'
                    }
                }).inject( Body );


            var Title = new Element('h1', {
                'html' : QUI.Locale.get(
                    'quiqqer/system',
                    'packages.grid.update.title'
                )
            }).inject( Body, 'top' );

            var Information = new Element('div.description', {
                'html' : QUI.Locale.get(
                    'quiqqer/system',
                    'packages.grid.update.information'
                )
            }).inject( Title, 'after' );

        // Grid
            this.$UpdateGrid = new QUI.controls.grid.Grid(Container, {

                columnModel : [{
                    header : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.grid.update.title.updatebtn'
                    ),
                    dataIndex : 'update',
                    dataType  : 'button',
                    width     : 50
                }, {
                    header : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.grid.update.title.package'
                    ),
                    dataIndex : 'package',
                    dataType  : 'string',
                    width     : 150
                }, {
                    header : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.grid.update.title.version.from'
                    ),
                    dataIndex : 'from',
                    dataType  : 'string',
                    width     : 200
                }, {
                    header : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.grid.update.title.version.to'
                    ),
                    dataIndex : 'to',
                    dataType  : 'string',
                    width     : 200
                }],

                buttons : [{
                    text : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.grid.update.btn.start'
                    ),
                    textimage : URL_BIN_DIR +'16x16/update.png',
                    Control   : this,
                    events    : {
                        onClick : this.checkUpdates
                    }
                }, {
                    text : QUI.Locale.get(
                            'quiqqer/system',
                            'packages.grid.update.btn.upload'
                        ),
                        textimage : URL_BIN_DIR +'16x16/actions/up.png',
                        Control   : this,
                        events    : {
                            onClick : this.uploadUpdates
                        }
                    }],

                height : 200
            });

            this.resize();
            this.Loader.hide();
        },

        /**
         * unload the update, destroy the update grid
         */
        unloadUpdates : function()
        {
            if ( this.$UpdateGrid )
            {
                this.$UpdateGrid.destroy();
                this.$UpdateGrid = null;
            }
        },

        /**
         * Check if updates exist
         *
         * @param {Function} callback - callback function
         */
        checkUpdates : function(Btn)
        {
            Btn.setAttribute( 'textimage', URL_BIN_DIR +'images/loader.gif' );

            QUI.Ajax.get('ajax_system_update_check', function(result, Request)
            {
                var Btn     = Request.getAttribute( 'Btn' ),
                    Control = Request.getAttribute( 'Control' );

                Btn.setAttribute(
                    'textimage',
                    URL_BIN_DIR +'16x16/update.png'
                );

                if ( !result.length )
                {
                    Control.Loader.hide();
                    return;
                }

                var entry,
                    data = [];

                for ( var i = 0, len = result.length; i < len; i++ )
                {
                    entry = result[i];

                    data.push({
                        'package' : entry['package'],
                        'from'    : entry.from,
                        'to'      : entry.to,
                        'update'  : {
                            'package' : entry['package'],
                            image     : URL_BIN_DIR +'16x16/update.png',
                            events    : {
                                onClick : Control.$clickUpdateBtn
                            }
                        }
                    });
                }

                Control.$UpdateGrid.setData({
                    data : data
                });

                Control.Loader.hide();

            }, {
                Btn     : Btn,
                Control : this
            });
        },

        /**
         * Opens the upload field for update uploading
         */
        uploadUpdates : function()
        {
            var Win = new QUI.controls.windows.Upload({
                title : QUI.Locale.get(
                    'quiqqer/system',
                    'packages.grid.update.btn.upload'
                ),

                server_finish : 'ajax_system_update_byfile',

                events :
                {
                    onDrawEnd : function(Win) {
                        Win.getForm().setParam( 'extract', false );
                    }
                }
            }).create();
        },

        /**
         * Update a package or the entire quiqqer system
         *
         * @param {Fucction} callback - Callback function
         * @param {String|false} pkg - [optional] package name
         *
         */
        update : function(callback, pkg)
        {
           QUI.Ajax.post('ajax_system_update', function(result, Request)
           {
               if ( Request.getAttribute( 'oncomplete' ) ) {
                   Request.getAttribute( 'oncomplete' )( result, Request );
               }
           }, {
               'package'  : pkg || false,
               oncomplete : callback
           });
        },

        /**
         * Update button click
         *
         * @param {QUI.controls.button.Button} Btn
         */
        $clickUpdateBtn : function(Btn)
        {
            Btn.setAttribute( 'image', URL_BIN_DIR +'images/loader.gif' );

            this.update(function(result, Request)
            {
                Btn.setAttribute( 'image', URL_BIN_DIR +'16x16/actions/apply.png' );

            }, Btn.getAttribute( 'package' ) );
        },

    /**
     * Package list methods
     */

        /**
         * Package Category
         */
        loadPackages : function()
        {
            this.Loader.show();
            this.getBody().set( 'html', '' );

            var Body = this.getBody(),
                size = Body.getSize(),

                Container = new Element('div', {
                    styles : {
                        width  : size.x - 40,
                        height : size.y - 40
                    }
                }).inject( Body );


            var Grid = new QUI.controls.grid.Grid(Container, {
                columnModel : [{
                    header : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.grid.title.name'
                    ),
                    dataIndex : 'name',
                    dataType  : 'string',
                    width     : 150
                }, {
                    header : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.grid.title.version'
                    ),
                    dataIndex : 'version',
                    dataType  : 'string',
                    width     : 100
                }, {
                    header : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.grid.title.desc'
                    ),
                    dataIndex : 'description',
                    dataType  : 'String',
                    width     : 200
                }, {
                    header : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.grid.title.homepage'
                    ),
                    dataIndex : 'homepage',
                    dataType  : 'string',
                    width     : 100
                }, {
                    header : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.grid.title.type'
                    ),
                    dataIndex : 'type',
                    dataType  : 'string',
                    width     : 100
                }, {
                    header    : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.grid.title.lastupdate'
                    ),
                    dataIndex : 'time',
                    dataType  : 'string',
                    width     : 150
                }],
                pagination : true,
                filterInput: true,
                perPage    : this.getAttribute( 'limit' ),
                page       : this.getAttribute( 'page' ),
                sortOn     : this.getAttribute( 'field' ),
                serverSort : true,
                showHeader : true,
                sortHeader : true,
                width      : size.x - 40,
                height     : size.y - 40,
                onrefresh  : function(me)
                {
                    var options = me.options;

                    this.setAttribute( 'field', options.sortOn );
                    this.setAttribute( 'order', options.sortBy );
                    this.setAttribute( 'limit', options.perPage );
                    this.setAttribute( 'page', options.page );

                    this.refreshPackageList();

                }.bind( this ),

                alternaterows     : true,
                resizeColumns     : true,
                selectable        : true,
                multipleSelection : true,
                resizeHeaderOnly  : true,

                accordion : true,
                accordionLiveRenderer : function(data)
                {
                    var Grid    = data.grid,
                        Parent  = data.parent,
                        row     = data.row,
                        rowdata = Grid.getDataByRow( row );

                    Parent.set(
                        'html',
                        '<img src="'+ URL_BIN_DIR +'images/loader.gif" style="margin: 10px;" />'
                    );

                    this.getPackage( rowdata.name, function(result, Request)
                    {
                        var pkg;
                        var str = '<div class="qui-packages-panel-package-info">' +
                                  '<h1>'+ result.name +'</h1>' +
                                  '<div class="package-description">'+ result.description +'</div>';

                        if ( result.require )
                        {
                            str = str +'<div class="package-require">';
                            str = str +'<h2>'+ QUI.Locale.get(
                                'quiqqer/system',
                                'packages.grid.dependencies'
                            ) +'</h2>';

                            for ( pkg in result.require ) {
                                str = str + pkg +': '+ result.require[ pkg ] +'<br />';
                            }

                            str = str +'</div>';
                        }

                        if ( result.dependencies && result.dependencies.length )
                        {
                            str = str +'<div class="package-require">';
                            str = str +'<h2>'+ QUI.Locale.get(
                                'quiqqer/system',
                                'packages.grid.other.package.dependencies',
                                { pkg : result.name }
                            ) +'</h2>';

                            str = str + result.dependencies.join( ',' ) +'<br />';
                            str = str +'</div>';
                        }

                        str = str +'</div>';


                        Parent.set( 'html', str );
                    });
                }.bind( this )
            });

            if ( this.getAttribute( 'type' ) == 'quiqqer-plugin' )
            {
                this.$PluginGrid = Grid;
            } else
            {
                this.$Grid = Grid;
            }

            Grid.refresh();
        },

        /**
         * unload the packages, destroy the package grid
         */
        unloadPackages : function(Btn)
        {
            if ( this.$Grid && Btn.getAttribute( 'name' ) == 'packages' )
            {
                this.$Grid.destroy();
                this.$Grid = null;
            }

            if ( this.$PluginGrid && Btn.getAttribute( 'name' ) == 'plugins' )
            {
                this.$PluginGrid.destroy();
                this.$PluginGrid = null;
            }
        },

        /**
         * Refresh the packagelist
         */
        refreshPackageList : function()
        {
            this.Loader.show();

            QUI.Ajax.get('ajax_system_packages_list', function(result, Request)
            {
                var Control = Request.getAttribute( 'Control' );

                if ( Control.$Grid ) {
                    Control.$Grid.setData( result );
                }

                Control.Loader.hide();
            }, {
                Control : this,
                params  : JSON.encode({
                    limit : this.getAttribute( 'limit' ),
                    page  : this.getAttribute( 'page' ),
                    type  : this.getAttribute( 'type' )
                })
            });
        },

        /**
         * Return the data of one package
         *
         * @param {String} pkg        - Package name
         * @param {Function} onfinish - callback function
         */
        getPackage : function(pkg, onfinish)
        {
            if ( this.$packages[ pkg ] )
            {
                onfinish( this.$packages[ pkg ] );
                return;
            }

            QUI.Ajax.get('ajax_system_packages_get', function(result, Request)
            {
                var pkg     = Request.getAttribute( 'package' ),
                    Control = Request.getAttribute( 'Control' );

                Control.$packages[ pkg ] = result;

                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }
            }, {
                'onfinish' : onfinish,
                'package'  : pkg,
                Control    : this
            });
        },

    /**
     * Search methods
     */

        /**
         * Opens the package search
         */
        loadSearch : function()
        {
            this.Loader.show();
            this.getBody().set( 'html', '' );

            var Body = this.getBody(),
                size = Body.getSize(),

                Container = new Element('div', {
                    'class' : 'qui-packages-panel-grid-container',
                    styles : {
                        width  : size.x - 40,
                        height : size.y - 100
                    }
                }).inject( Body ),

                Form = new Element('form', {
                    name    : 'qui-package-search',
                    'class' : 'qui-packages-panel-search',
                    'html'  : '<input type="text" name="search" placeholder="search..." />',
                    events  :
                    {
                        submit : function(event)
                        {
                            this.startSearch();
                            event.stop();
                        }.bind( this )
                    }
                }).inject( Body, 'top' ),

                Title = new Element('h1', {
                    html : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.search.title'
                    )
                }).inject( Body, 'top' ),

                Information = new Element('div.description', {
                    html : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.search.description'
                    )
                }).inject( Title, 'after' );


            // search grid
            this.$SearchGrid = new QUI.controls.grid.Grid(Container, {
                columnModel : [{
                    header : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.search.grid.title.btn'
                    ),
                    dataIndex : 'install',
                    dataType  : 'button',
                    width     : 40
                },{
                    header : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.search.grid.title.package'
                    ),
                    dataIndex : 'package',
                    dataType  : 'string',
                    width     : 200
                }, {
                    header : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.search.grid.title.description'
                    ),
                    dataIndex : 'description',
                    dataType  : 'string',
                    width     : 400
                }],
                pagination : true,
                filterInput: true,
                showHeader : true,
                sortHeader : true,
                width      : Container.getSize().x,
                height     : 200,
                onrefresh  : function(me)
                {

                }
            });

            // search button
            new QUI.controls.buttons.Button({
                text   : 'suchen',
                events : {
                    onClick : this.startSearch
                }
            }).inject( Form );


            Form.getElement( 'input' ).focus();

            this.resize();
            this.Loader.hide();
        },

        /**
         * unload the search, destroy the search grid
         */
        unloadSearch : function()
        {
            if ( this.$SearchGrid )
            {
                this.$SearchGrid.destroy();
                this.$SearchGrid = null;
            }
        },

        /**
         * Start the package search, read the input field and display the results
         */
        startSearch : function()
        {
            var Search = this.getBody().getElement( 'input[name="search"]' );

            if ( !Search ) {
                return;
            }

            this.Loader.show();

            var Control = this;

            this.search(Search.value, function(result, Request)
            {
                for ( var i = 0, len = result.data.length; i < len; i++ )
                {
                    if ( result.data[ i ].isInstalled ) {
                        continue;
                    }

                    result.data[ i ].install = {
                        'package' : result.data[ i ]['package'],
                        image     : URL_BIN_DIR +'16x16/apps/kpackage.png',
                        title     : QUI.Locale.get(
                            'quiqqer/system',
                            'packages.search.grid.setup.btn.title',
                            {'package' : result.data[ i ]['package'] }
                        ),
                        alt : QUI.Locale.get(
                            'quiqqer/system',
                            'packages.search.grid.setup.btn.alt',
                            {'package' : result.data[ i ]['package'] }
                        ),
                        events : {
                            onClick : Control.dialogInstall
                        }
                    };
                }


                if ( this.$SearchGrid ) {
                    this.$SearchGrid.setData( result );
                }

                this.Loader.hide();

            }.bind( this ));
        },

        /**
         * Start the search
         *
         * @param {String} str
         * @param {Function} callback
         */
        search : function(str, callback)
        {
            QUI.Ajax.get('ajax_system_packages_search', function(result, Request)
            {
                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }
            }, {
                str      : str,
                onfinish : callback
            });
        },

        /**
         * Dialog : Package install?
         */
        dialogInstall : function(Btn)
        {
            new QUI.controls.windows.Submit({
                title : QUI.Locale.get(
                    'quiqqer/system',
                    'packages.server.win.install.package.title'
                ),
                icon : URL_BIN_DIR +'16x16/apps/kpackage.png',
                text : QUI.Locale.get(
                    'quiqqer/system',
                    'packages.server.win.install.package.text',
                    { 'package' : Btn.getAttribute('package') }
                ),

                texticon  : URL_BIN_DIR +'48x48/apps/kpackage.png',
                Control   : this,
                'package' : Btn.getAttribute('package'),

                ok_button :
                {
                    textimage : URL_BIN_DIR +'16x16/apps/kpackage.png',
                    text      : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.server.win.install.submit.btn'
                    )
                },

                events  :
                {
                    onDrawEnd : function(Win)
                    {
                        Win.Loader.show();

                        QUI.Ajax.get('ajax_system_packages_get', function(result, Request)
                        {
                            var Win = Request.getAttribute( 'Win' ),
                                req = result.require.join( ', ' );

                            var html = '<p>'+ result.description +'</p>' +
                                       '<p>&nbsp;</p>'+
                                       '<p>Version: '+ result.versions +'</p>' +
                                       '<p>Require: '+ ( req || '---' ) +'</p>';

                            Win.setAttribute( 'information', html );
                            Win.Loader.hide();

                        }, {
                            'package' : Win.getAttribute( 'package' ),
                            Win       : Win
                        });
                    },

                    onSubmit : function(Win)
                    {

                    }
                }
            }).create();
        },

    /**
     * Server Methods
     */

        /**
         * Load the Server-Management
         */
        loadServers : function()
        {
            this.Loader.show();
            this.getBody().set( 'html', '' );

            var Body = this.getBody(),
                size = Body.getSize(),

                Container = new Element('div', {
                    styles : {
                        width  : size.x - 40,
                        height : size.y - 40
                    }
                }).inject( Body );


            this.$ServerGrid = new QUI.controls.grid.Grid(Container, {
                columnModel : [{
                    header : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.server.grid.title.status'
                    ),
                    dataIndex : 'status',
                    dataType  : 'button',
                    width     : 50
                }, {
                    header : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.server.grid.title.server'
                    ),
                    dataIndex : 'server',
                    dataType  : 'string',
                    width     : 400
                }, {
                    header : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.server.grid.title.type'
                    ),
                    dataIndex : 'type',
                    dataType  : 'string',
                    width     : 100
                }],
                buttons : [{
                    text : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.btn.add.server'
                    ),
                    textimage : URL_BIN_DIR +'16x16/filesystems/server.png',
                    events : {
                        onClick : this.dialogAddServer
                    }
                }, {
                    text : QUI.Locale.get(
                        'quiqqer/system',
                        'packages.btn.del.server'
                    ),
                    name : 'delServers',
                    textimage : URL_BIN_DIR +'16x16/filesystems/trashcan_empty.png',
                    disabled  : true,
                    events :
                    {
                        onClick : function(Btn)
                        {
                            var server = [],
                                data   = this.$ServerGrid.getSelectedData();

                            for ( var i = 0, len = data.length; i < len; i++ ) {
                                server.push( data[ i ].server );
                            }

                            this.dialogRemoveServer( server );

                        }.bind( this )
                    }
                }],
                pagination : false,
                filterInput: true,
                showHeader : true,
                sortHeader : true,
                width      : size.x - 40,
                height     : size.y - 40,
                onrefresh  : function(me)
                {
                    QUI.Ajax.get('ajax_system_packages_server_list', function(result, Request)
                    {
                        var i, len, alt, title, icon;
                        var Control = Request.getAttribute( 'Control' );

                        var server_click = function(Btn)
                        {
                            var Control = Btn.getAttribute( 'Control' ),
                                server  = Btn.getAttribute( 'server' );

                            if ( !server ) {
                                return;
                            }

                            Btn.setAttribute( 'image', URL_BIN_DIR +'loader.gif' );

                            if ( Btn.getAttribute( 'status' ) )
                            {
                                Control.deactivateServer( server );
                            } else
                            {
                                Control.activateServer( server );
                            }
                        };


                        for ( i = 0, len = result.length; i < len; i++ )
                        {
                            alt = QUI.Locale.get(
                                'quiqqer/system',
                                'packages.server.grid.btn.activate.title'
                            );

                            title = QUI.Locale.get(
                                'quiqqer/system',
                                'packages.server.grid.btn.activate.title'
                            );

                            icon = URL_BIN_DIR +'16x16/actions/apply.png';

                            if ( result[ i ].active === 0 )
                            {
                                icon  = URL_BIN_DIR +'16x16/actions/cancel.png';

                                alt = QUI.Locale.get(
                                    'quiqqer/system',
                                    'packages.server.grid.btn.deactivate.title'
                                );

                                title = QUI.Locale.get(
                                    'quiqqer/system',
                                    'packages.server.grid.btn.deactivate.title'
                                );
                            }

                            result[ i ].status = {
                                name    : 'server-active-status',
                                title   : title,
                                alt     : alt,
                                icon    : icon,
                                server  : result[ i ].server,
                                status  : result[ i ].active,
                                Control : Control,
                                events  : {
                                    onClick : server_click
                                }
                            };
                        }


                        Control.$ServerGrid.setData({
                            data : result
                        });

                        Control.Loader.hide();
                    }, {
                        Control : this
                    });

                }.bind( this ),

                alternaterows     : true,
                resizeColumns     : true,
                resizeHeaderOnly  : true
            });

            this.$ServerGrid.addEvents({
                onClick : this.$serverGridClick
            });

            this.$ServerGrid.refresh();
        },

        /**
         * unload the search, destroy the search grid
         */
        unloadServer : function()
        {
            if ( this.$ServerGrid )
            {
                this.$ServerGrid.destroy();
                this.$ServerGrid = null;
            }
        },

        /**
         * Opens the Dialog for Server Adding
         */
        dialogAddServer : function()
        {
            new QUI.controls.windows.Submit({
                title : QUI.Locale.get(
                    'quiqqer/system',
                    'packages.server.win.add.title'
                ),
                icon : URL_BIN_DIR +'16x16/filesystems/server.png',
                text : QUI.Locale.get(
                    'quiqqer/system',
                    'packages.server.win.add.text'
                ),

                information : '<form class="qui-packages-panel-addserver">' +
                                  '<input type="text" name="server" value="" placeholder="Server" />' +
                                  '<select name="types">' +
                                      '<option value="composer">composer</option>' +
                                      '<option value="vcs">vcs</option>' +
                                      '<option value="pear">pear</option>' +
                                      '<option value="package">package</option>' +
                                  '</select>' +
                              '</form>',

                autoclose : false,
                Control   : this,
                events    :
                {
                    onDrawEnd : function(Win)
                    {
                        var Form = Win.getBody().getElement( 'form' );

                        if ( !Form ) {
                            return;
                        }

                        Form.addEvent('submit', function(event)
                        {
                            event.stop();
                            Win.submit();
                        });

                        Form.getElement( 'input' ).focus();
                    },

                    onSubmit : function(Win)
                    {
                        var Form    = Win.getBody().getElement( 'form' ),
                            Control = Win.getAttribute( 'Control' );

                        if ( !Form ) {
                            return;
                        }

                        var Input  = Form.getElement( 'input' ),
                            Select = Form.getElement( 'select' );

                        Control.addServer(
                            Input.value,
                            {
                                type : Select.value
                            }
                        );

                        Win.close();
                    }
                }
            }).create();
        },

        /**
         * Opens the remove server dialog
         *
         * @param {Array} list - server list
         */
        dialogRemoveServer : function(list)
        {
            new QUI.controls.windows.Submit({
                title : QUI.Locale.get(
                    'quiqqer/system',
                    'packages.server.win.remove.title'
                ),
                icon : URL_BIN_DIR +'16x16/filesystems/trashcan_empty.png',
                text : QUI.Locale.get(
                    'quiqqer/system',
                    'packages.server.win.remove.text'
                ),
                texticon    : URL_BIN_DIR +'32x32/filesystems/trashcan_empty.png',
                information : list.join( '<br />' ) +
                              '<p>&nbsp;</p>'+
                              '<p>'+
                                  QUI.Locale.get(
                                      'quiqqer/system',
                                      'packages.server.win.remove.information'
                                  ) +
                              '</p>',
                autoclose : false,
                Control   : this,
                list      : list,
                events    :
                {
                    onSubmit : function(Win)
                    {
                        Win.Loader.show();

                        QUI.Ajax.post(
                            'ajax_system_packages_server_remove',
                            function(result, Request)
                            {
                                var Win     = Request.getAttribute( 'Win' ),
                                    Control = Win.getAttribute( 'Control' );

                                Win.close();

                                if ( Control.$ServerGrid ) {
                                    Control.$ServerGrid.refresh();
                                }

                            }, {
                                server : JSON.encode( Win.getAttribute( 'list' ) ),
                                Win    : Win
                            }
                        );
                    }
                }
            }).create();
        },

        /**
         * event: server grid click
         *
         * @param {Object} data - grid event data
         */
        $serverGridClick : function(data)
        {
            var len = data.target.selected.length,
                Del = this.$ServerGrid.getAttribute( 'buttons' ).delServers;

            if ( len === 0 )
            {
                Del.disable();
                return;
            }

            Del.enable();

            data.evt.stop();
        },

        /**
         * Activate the server in the server list
         *
         * @param {String} server - server name
         */
        activateServer : function(server)
        {
            QUI.Ajax.post('ajax_system_packages_server_status', function(result, Request)
            {
                var Control = Request.getAttribute( 'Control' );

                if ( Control.$ServerGrid ) {
                    Control.$ServerGrid.refresh();
                }

            }, {
                Control : this,
                server  : server,
                status  : 1
            });
        },

        /**
         * Deactivate the server in the server list
         *
         * @param {String} server - server name
         */
        deactivateServer : function(server)
        {
            QUI.Ajax.post('ajax_system_packages_server_status', function(result, Request)
            {
                var Control = Request.getAttribute( 'Control' );

                if ( Control.$ServerGrid ) {
                    Control.$ServerGrid.refresh();
                }

            }, {
                Control : this,
                server  : server,
                status  : 0
            });
        },

        /**
         * Add a server to the update server list
         *
         * @param {String} server - server name
         */
        addServer : function(server, params)
        {
            QUI.Ajax.post('ajax_system_packages_server_add', function(result, Request)
            {
                var Control = Request.getAttribute( 'Control' );

                if ( Control.$ServerGrid ) {
                    Control.$ServerGrid.refresh();
                }

            }, {
                Control : this,
                server  : server,
                params  : JSON.encode( params )
            });
        }
    });

    return QUI.controls.packages.Panel;
});