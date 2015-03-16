
/**
 * Package Manager / System Update
 *
 * @module controls/packages/Panel
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require Locale
 * @require Ajax
 * @require classes/packages/Manager
 * @require controls/grid/Grid
 * @require controls/packages/DetailWindow
 * @require qui/controls/desktop/Panel
 * @require qui/controls/windows/Confirm
 * @require qui/controls/windows/Popup
 * @require qui/controls/buttons/Button
 * @require css!controls/packages/Panel.css
 */
define('controls/packages/Panel', [

    'qui/QUI',
    'Locale',
    'Ajax',
    'classes/packages/Manager',
    'controls/grid/Grid',
    'controls/packages/DetailWindow',
    'qui/controls/desktop/Panel',
    'qui/controls/windows/Confirm',
    'qui/controls/windows/Popup',
    'qui/controls/buttons/Button',

    'css!controls/packages/Panel.css'

],function(QUI, Locale, Ajax, PackageManager, Grid, DetailWindow, QUIPanel, QUIConfirm, QUIWindow, QUIButton)
{
    "use strict";

    var lg = 'quiqqer/system';

    /**
     * @class controls/packages/Panel
     *
     * @param {Object} options - QDOM panel params
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIPanel,
        Type    : 'controls/packages/Panel',

        Binds : [
            '$onCreate',
            '$onResize',
            '$serverGridClick',
            '$serverGridBlur',
            'setup',

            'executeCompleteUpdate',
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
            'dialogInstall',

            'loadHealth',
            'unloadHealth',

            'loadPHPInfo'
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
                Locale.get( lg, 'packages.panel.title' )
            );

            this.setAttribute( 'icon', URL_BIN_DIR +'16x16/quiqqer.png' );

            // init
            this.$Grid       = null;
            this.$ModuleGrid = null;
            this.$UpdateGrid = null;
            this.$ServerGrid = null;
            this.$SearchGrid = null;
            this.$HealthGrid = null;

            this.$Manager  = new PackageManager();
            this.$packages = {};

            this.addEvents({
                onCreate  : this.$onCreate,
                onResize  : this.$onResize
            });
        },

        /**
         * event: on panel create
         */
        $onCreate : function()
        {
            this.addCategory({
                name   : 'updates',
                text   : Locale.get( lg, 'packages.category.updates' ),
                image  : 'icon-refresh',
                events : {
                    onActive : this.loadUpdates,
                    onNormal : this.unloadUpdates
                }
            });

            this.addCategory({
                name   : 'modules',
                text   : Locale.get( lg, 'packages.category.modules' ),
                image  : 'icon-puzzle-piece',
                events :
                {
                    onActive : function()
                    {
                        this.setAttribute( 'page', 1 );
                        this.setAttribute( 'type', 'quiqqer-module' );

                        this.loadPackages();

                    }.bind( this ),

                    onNormal : this.unloadPackages
                }
            });

            this.addCategory({
                name   : 'packages',
                text   : Locale.get( lg, 'packages.category.packages' ),
                image  : 'icon-puzzle-piece',
                events :
                {
                    onActive : function()
                    {
                        this.setAttribute( 'page', 1 );
                        this.setAttribute( 'type', '' );

                        this.loadPackages();
                    }.bind( this ),

                    onNormal : this.unloadPackages
                }
            });

            this.addCategory({
                name   : 'server',
                text   : Locale.get( lg, 'packages.category.server' ),
                image  : 'icon-building',
                events : {
                    onActive : this.loadServers,
                    onNormal : this.unloadServer
                }
            });

            this.addCategory({
                name   : 'search',
                text   : Locale.get( lg, 'packages.category.search' ),
                image  : 'icon-search',
                events : {
                    onActive : this.loadSearch,
                    onNormal : this.unloadSearch
                }
            });

            this.addCategory({
                name   : 'health',
                text   : Locale.get( lg, 'packages.category.system.health' ),
                image  : 'icon-medkit',
                events : {
                    onActive : this.loadHealth,
                    onNormal : this.unloadHealth
                }
            });

            this.addCategory({
                name   : 'phpInfo',
                text   : Locale.get( lg, 'packages.category.system.phpInfo' ),
                image  : 'icon-info-sign',
                events : {
                    onActive : this.loadPHPInfo
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

            var Body = this.getContent(),
                size = Body.getSize();

            if ( this.$Grid )
            {
                this.$Grid.setHeight( size.y  -50 );
                this.$Grid.setWidth( size.x - 50 );
            }

            if ( this.$ModuleGrid )
            {
                this.$ModuleGrid.setHeight( size.y - 0 );
                this.$ModuleGrid.setWidth( size.x - 0 );
            }

            if ( this.$UpdateGrid )
            {
                Title       = Body.getElement( 'h1' );
                Information = Body.getElement( '.description' );

                if ( !Title || !Information ) {
                    return;
                }

                height = ( Title.getSize().y + Information.getSize().y );
                height = Body.getSize().y - height - 110;

                this.$UpdateGrid.setHeight( height );
                this.$UpdateGrid.setWidth( size.x - 50 );
                this.$UpdateGrid.resize();
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
                this.$SearchGrid.resize();
            }
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

            var self = this,
                Body = this.getBody().set( 'html', '' ),

                Container = new Element('div', {
                    'class' : 'qui-packages-panel-update',
                    styles : {
                        width : '100%'
                    }
                }).inject( Body );


            var Title = new Element('h1', {
                html : Locale.get( lg, 'packages.grid.update.title' )
            }).inject( Body, 'top' );

            new Element('div.description', {
                html : Locale.get( lg, 'packages.grid.update.information' )
            }).inject( Title, 'after' );

        // Grid
            this.$UpdateGrid = new Grid(Container, {

                columnModel : [{
                    header    : Locale.get( lg, 'packages.grid.update.title.updatebtn' ),
                    dataIndex : 'update',
                    dataType  : 'button',
                    width     : 60
                }, {
                    header    : Locale.get( lg, 'packages.grid.update.title.package' ),
                    dataIndex : 'package',
                    dataType  : 'string',
                    width     : 150
                }, {
                    header    : Locale.get( lg, 'packages.grid.update.title.version.from' ),
                    dataIndex : 'from',
                    dataType  : 'string',
                    width     : 200
                }, {
                    header    : Locale.get( lg, 'packages.grid.update.title.version.to' ),
                    dataIndex : 'to',
                    dataType  : 'string',
                    width     : 200
                }],

                buttons : [{
                    text      : Locale.get( lg, 'packages.grid.update.btn.execute' ),
                    textimage : 'icon-double-angle-down',
                    events : {
                        onClick : this.executeCompleteUpdate
                    }
                }, {
                    text      : Locale.get( lg, 'packages.grid.update.btn.start' ),
                    textimage : 'icon-refresh',
                    events : {
                        onClick : this.checkUpdates
                    }
                }, {
                    type : 'seperator'
                }, {
                    text      : Locale.get( lg, 'packages.grid.update.btn.upload' ),
                    textimage : 'icon-upload',
                    events : {
                        onClick : this.uploadUpdates
                    }
                }, {
                    text      : Locale.get( lg, 'packages.grid.update.btn.setup' ),
                    textimage : 'icon-hdd',
                    events :
                    {
                        onClick : function(Btn) {
                            self.setup( false, Btn );
                        }
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
         * Execute a system setup
         *
         * @param {String} [pkg] - (optional), Package name, if no package name given, complete setup are executed
         * @param {Object} [Btn] - (optional), qui/controls/buttons/Button
         */
        setup : function(pkg, Btn)
        {
            var self = this;

            if ( typeof Btn !== 'undefined' )
            {
                if ( Btn.getAttribute( 'textimage' ) ) {
                    Btn.setAttribute( 'textimage', 'icon-refresh icon-spin' );
                }

                if ( Btn.getAttribute( 'icon' ) ) {
                    Btn.setAttribute( 'icon', 'icon-refresh icon-spin' );
                }
            }


            QUI.getMessageHandler(function(MH)
            {
                // #Locale message
                MH.addLoading('Setup wird durchgeführt', function(Loading)
                {
                    self.$Manager.setup( pkg, function()
                    {
                        if ( typeof Btn === 'undefined' ) {
                            return;
                        }

                        if ( Btn.getAttribute( 'textimage' ) ) {
                            Btn.setAttribute( 'textimage', 'icon-hdd' );
                        }

                        if ( Btn.getAttribute( 'icon' ) ) {
                            Btn.setAttribute( 'icon', 'icon-hdd' );
                        }

                        Loading.finish( Locale.get( lg, 'message.setup.successfull' ) );
                    });
                });
            });
        },

        /**
         * Execute a system update
         *
         * @param {Object} Btn - qui/controls/buttons/Button
         */
        executeCompleteUpdate : function(Btn)
        {
            var self = this;

            new QUIConfirm({
                title     : Locale.get( lg, 'packages.update.window.title' ),
                maxHeight : 200,
                maxWidth  : 550,
                texticon  : 'icon-double-angle-down fa fa-angle-double-down',
                text      : Locale.get( lg, 'packages.update.window.text'),
                events :
                {
                    onSubmit : function()
                    {
                        Btn.setAttribute( 'textimage', 'icon-refresh icon-spin' );

                        self.$Manager.update(function() {
                            Btn.setAttribute( 'textimage', 'icon-double-angle-down' );
                        });
                    }
                }
            }).open();
        },

        /**
         * Check if updates exist
         *
         * @param {Object} Btn - qui/controls/buttons/Button
         */
        checkUpdates : function(Btn)
        {
            var self = this;

            Btn.setAttribute( 'textimage', 'icon-refresh icon-spin' );

            this.$Manager.checkUpdate(function(result)
            {
                Btn.setAttribute( 'textimage', 'icon-refresh' );

                if ( !result || !result.length )
                {
                    self.Loader.hide();
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
                            image     : 'icon-retweet',
                            events    : {
                                onClick : self.$clickUpdateBtn
                            }
                        }
                    });
                }

                self.$UpdateGrid.setData({
                    data : data
                });

                self.Loader.hide();
            });
        },

        /**
         * Opens the upload field for update uploading
         */
        uploadUpdates : function()
        {
            var self = this;

            new QUIConfirm({
                title     : Locale.get( lg, 'packages.grid.update.btn.upload' ),
                maxHeight : 500,
                maxWidth  : 600,
                autoclose : false,
                events :
                {
                    onOpen : function(Win)
                    {
                        Win.Loader.show();

                        require(['controls/upload/Form'], function(UploadForm)
                        {
                            var Form = new UploadForm({
                                maxuploads : 1000,
                                multible   : true,
                                sendbutton : false,
                                styles     : {
                                    clear  : 'both',
                                    float  : 'left',
                                    margin : 0,
                                    width  : '100%'
                                },
                                Drops  : [],
                                events :
                                {
                                    onDragenter: function(event, Elm)
                                    {
                                        Elm.addClass( 'highlight' );
                                        event.stop();
                                    },

                                    onComplete : function()
                                    {
                                        Win.close();
                                        self.updateWithLocalServer();
                                    }
                                }
                            });

                            Form.setParam( 'onfinish', 'ajax_system_packages_upload_package' );
                            Form.setParam( 'extract', false );

                            Form.inject( Win.getContent() );

                            Win.setAttribute( 'Form', Form );
                            Win.Loader.hide();
                        });
                    },

                    onSubmit : function(Win)
                    {
                        var Form = Win.getAttribute( 'Form' );

                        if ( !Form )
                        {
                            Win.close();
                            return;
                        }

                        Form.submit();
                        Win.close();
                    }
                }
            }).open();
        },

        /**
         * Update a package or the entire quiqqer system
         *
         * @param {Function} callback - Callback function
         * @param {String|Boolean} [pkg] - (optional), package name
         */
        update : function(callback, pkg)
        {
            this.$Manager.update( pkg, callback );
        },

        /**
         * Execute an update wiith the local update server
         */
        updateWithLocalServer : function()
        {
            var self = this;

            QUI.getMessageHandler(function(MH)
            {
                MH.addLoading('Setup wird durchgeführt...', function(Loading)
                {
                    self.$Manager.updateWithLocalServer(function() {
                        Loading.finish( Locale.get( lg, 'message.setup.successfull' ) );
                    });
                });
            });
        },

        /**
         * Update button click
         *
         * @param {Object} Btn - qui/controls/buttons/Button
         */
        $clickUpdateBtn : function(Btn)
        {
            Btn.setAttribute( 'image', 'icon-refresh icon-spin' );

            this.$Manager.update(function()
            {
                Btn.setAttribute( 'image', 'icon-ok' );

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

            var self = this,
                Body = this.getBody(),
                size = Body.getSize(),

                Container = new Element('div', {
                    styles : {
                        width  : size.x - 40,
                        height : size.y - 40
                    }
                }).inject( Body );


            var GridObj = new Grid(Container, {
                columnModel : [{
                    header    : ' ',
                    dataIndex : '_',
                    dataType  : 'string',
                    width     : 30
                }, {
                    header    : ' ',
                    dataIndex : 'setup',
                    dataType  : 'button',
                    width     : 60
                }, {
                    header    : Locale.get( lg, 'packages.grid.title.name' ),
                    dataIndex : 'name',
                    dataType  : 'string',
                    width     : 150
                }, {
                    header    : Locale.get( lg, 'packages.grid.title.version' ),
                    dataIndex : 'version',
                    dataType  : 'string',
                    width     : 100
                }, {
                    header    : Locale.get( lg, 'packages.grid.title.desc' ),
                    dataIndex : 'description',
                    dataType  : 'String',
                    width     : 200
                }, {
                    header    : Locale.get( lg, 'packages.grid.title.homepage' ),
                    dataIndex : 'homepage',
                    dataType  : 'string',
                    width     : 100
                }, {
                    header    : Locale.get( lg, 'packages.grid.title.type' ),
                    dataIndex : 'type',
                    dataType  : 'string',
                    width     : 100
                }, {
                    header    : Locale.get( lg, 'packages.grid.title.lastupdate' ),
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

                    self.setAttribute( 'field', options.sortOn );
                    self.setAttribute( 'order', options.sortBy );
                    self.setAttribute( 'limit', options.perPage );
                    self.setAttribute( 'page', options.page );

                    self.refreshPackageList();
                },

                alternaterows     : true,
                resizeColumns     : true,
                selectable        : true,
                multipleSelection : true,
                resizeHeaderOnly  : true,

                accordion             : true,
                openAccordionOnClick  : false,
                accordionLiveRenderer : function(data)
                {
                    var GridObj = data.grid,
                        Parent  = data.parent,
                        row     = data.row,
                        rowdata = GridObj.getDataByRow( row );

                    Parent.set(
                        'html',
                        '<img src="'+ URL_BIN_DIR +'images/loader.gif" style="margin: 10px;" />'
                    );

                    self.getPackage( rowdata.name, function(result)
                    {
                        var pkg;
                        var str = '<div class="qui-packages-panel-package-info">' +
                                  '<h1>'+ result.name +'</h1>' +
                                  '<div class="package-description">'+ result.description +'</div>';

                        if ( result.require )
                        {
                            str = str +'<div class="package-require">';
                            str = str +'<h2>'+
                                Locale.get( lg, 'packages.grid.dependencies' ) +
                            '</h2>';

                            for ( pkg in result.require )
                            {
                                if ( result.require.hasOwnProperty( pkg ) ) {
                                    str = str + pkg + ': ' + result.require[pkg] + '<br />';
                                }
                            }

                            str = str +'</div>';
                        }

                        if ( result.dependencies && result.dependencies.length )
                        {
                            str = str +'<div class="package-require">';
                            str = str +'<h2>'+
                                Locale.get( lg, 'packages.grid.other.package.dependencies', {
                                    pkg : result.name
                                }) +
                            '</h2>';

                            str = str + result.dependencies.join( ',' ) +'<br />';
                            str = str +'</div>';
                        }

                        str = str +'</div>';


                        Parent.set( 'html', str );
                    });
                }
            });

            if ( this.getAttribute( 'type' ) == 'quiqqer-library' )
            {
                this.$ModuleGrid = GridObj;

            } else
            {
                this.$Grid = GridObj;
            }

            GridObj.refresh();

            GridObj.addEvent('onDblClick', function(event) {
                self.openPackageDetails( GridObj.getDataByRow( event.row ).name );
            });
        },

        /**
         * unload the packages, destroy the package grid
         *
         * @param {Object} Btn - qui/controls/buttons/Button
         */
        unloadPackages : function(Btn)
        {
            this.setAttribute( 'page', 1 );

            if ( this.$Grid && Btn.getAttribute( 'name' ) == 'packages' )
            {
                this.$Grid.destroy();
                this.$Grid = null;
            }

            if ( this.$ModuleGrid && Btn.getAttribute( 'name' ) == 'modules' )
            {
                this.$ModuleGrid.destroy();
                this.$ModuleGrid = null;
            }
        },

        /**
         * Refresh the packagelist
         */
        refreshPackageList : function()
        {
            this.Loader.show();

            var self = this;

            Ajax.get('ajax_system_packages_list', function(result)
            {
                var i, alt, len, pkg, entry;
                var GridObj = null;

                if ( self.getAttribute( 'type' ) == 'quiqqer-module' )
                {
                    GridObj = self.$ModuleGrid;

                } else
                {
                    GridObj = self.$Grid;
                }

                var onSlickSetup = function(Btn)
                {
                    self.setup(
                        Btn.getAttribute( 'pkg' ),
                        Btn
                    );
                };

                for ( i = 0, len = result.data.length; i < len; i++ )
                {
                    entry = result.data[ i ];

                    pkg = result.data[ i ].name;

                    alt = Locale.get( lg, 'packages.btn.execute.setup.alt', {
                        pkg : pkg
                    });

                    result.data[ i ].setup = {
                        icon   : 'icon-hdd',
                        pkg    : pkg,
                        alt    : alt,
                        title  : alt,
                        events : {
                            onClick : onSlickSetup
                        }
                    };
                }

                if ( GridObj ) {
                    GridObj.setData( result );
                }

                self.Loader.hide();
            }, {
                params : JSON.encode({
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
            this.$Manager.getPackage( pkg, onfinish );
        },

        /**
         * Open package detail window
         */
        openPackageDetails : function(pkg)
        {
            new DetailWindow({
                'package' : pkg
            }).open();
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

            var self = this,
                Body = this.getBody(),
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
                    'html'  : '<input type="text" name="search" placeholder="search..." />',  // #Locale -> placeholder
                    events  :
                    {
                        submit : function(event)
                        {
                            self.startSearch();
                            event.stop();
                        }
                    }
                }).inject( Body, 'top' ),

                Title = new Element('h1', {
                    html : Locale.get( lg, 'packages.search.title' )
                }).inject( Body, 'top' );


            new Element('div.description', {
                html : Locale.get( lg, 'packages.search.description' )
            }).inject( Title, 'after' );


            // search grid
            this.$SearchGrid = new Grid(Container, {
                columnModel : [{
                    header    : Locale.get( lg, 'packages.search.grid.title.btn' ),
                    dataIndex : 'install',
                    dataType  : 'button',
                    width     : 60
                }, {
                    header    : Locale.get( lg, 'packages.search.grid.title.package' ),
                    dataIndex : 'package',
                    dataType  : 'string',
                    width     : 200
                }, {
                    header    : Locale.get( lg, 'packages.search.grid.title.description' ),
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
                onrefresh  : function() {
                    self.startSearch();
                }
            });

            // search button
            new QUIButton({
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

            var self     = this,
                onResult = function(result)
                {
                    for ( var i = 0, len = result.data.length; i < len; i++ )
                    {
                        if ( result.data[ i ].isInstalled ) {
                            continue;
                        }

                        result.data[ i ].install = {
                            'package' : result.data[ i ]['package'],
                            image : 'icon-download',
                            title : Locale.get( lg, 'packages.search.grid.setup.btn.title', {
                                'package' : result.data[ i ]['package']
                            }),
                            alt : Locale.get( lg, 'packages.search.grid.setup.btn.alt', {
                                'package' : result.data[ i ]['package']
                            }),
                            events : {
                                onClick : self.dialogInstall
                            }
                        };
                    }


                    if ( self.$SearchGrid ) {
                        self.$SearchGrid.setData( result );
                    }

                    self.Loader.hide();
                };

            this.search(
                Search.value,
                onResult,
                this.$SearchGrid.options.page,
                this.$SearchGrid.options.perPage
            );
        },

        /**
         * Start the search
         *
         * @param {String} str
         * @param {Function} callback
         * @param {Number} [start] - (optional)
         * @param {Number} [max] - (optional)
         */
        search : function(str, callback, start, max)
        {
            if ( typeof start === 'undefined' ) {
                start = 1;
            }

            if ( typeof max === 'undefined' ) {
                max = 1;
            }

            Ajax.get('ajax_system_packages_search', function(result, Request)
            {
                if ( typeof callback !== 'undefined' ) {
                    callback( result, Request );
                }
            }, {
                str  : str,
                from : start,
                max  : max
            });
        },

        /**
         * Dialog : Package install?
         *
         * @param {Object} Btn - qui/controls/buttons/Button
         */
        dialogInstall : function(Btn)
        {
            var pkg  = Btn.getAttribute( 'package' ),
                self = this;

            new QUIConfirm({
                title : Locale.get( lg, 'packages.server.win.install.package.title' ),
                icon : 'icon-download',
                text : Locale.get( lg, 'packages.server.win.install.package.text', {
                    'package' : pkg
                }),
                texticon  : 'icon-download',
                Control   : this,
                autoclose : false,

                ok_button : {
                    textimage : 'icon-download',
                    text : Locale.get( lg, 'packages.server.win.install.submit.btn' )
                },

                events  :
                {
                    onDrawEnd : function(Win)
                    {
                        Win.Loader.show();

                        Ajax.get('ajax_system_packages_get', function(result)
                        {
                            if ( typeof result.require === 'undefined' &&
                                 typeof result.description === 'undefined' )
                            {
                                Win.setAttribute( 'information', '' );
                                Win.Loader.hide();

                                return;
                            }

                            var req = result.require.join( ', ' );

                            var html = '<p>'+ result.description +'</p>' +
                                       '<p>&nbsp;</p>'+
                                       '<p>Version: '+ result.versions +'</p>' +
                                       '<p>Require: '+ ( req || '---' ) +'</p>';

                            Win.setAttribute( 'information', html );
                            Win.Loader.hide();

                        }, {
                            'package' : pkg
                        });
                    },

                    onSubmit : function(Win)
                    {
                        Win.Loader.show();

                        Ajax.get('ajax_system_packages_install', function()
                        {
                            Win.close();
                            self.startSearch();
                        }, {
                            'package' : pkg
                        });
                    }
                }
            }).open();
        },

    /**
     * Server Methods
     */

        /**
         * Load the Server-Management
         */
        loadServers : function()
        {
            var self = this;

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


            this.$ServerGrid = new Grid(Container, {
                columnModel : [{
                    header    : Locale.get( lg, 'packages.server.grid.title.status' ),
                    dataIndex : 'status',
                    dataType  : 'button',
                    width     : 60
                }, {
                    header    : Locale.get( lg, 'packages.server.grid.title.server' ),
                    dataIndex : 'server',
                    dataType  : 'string',
                    width     : 400
                }, {
                    header    : Locale.get( lg, 'packages.server.grid.title.type' ),
                    dataIndex : 'type',
                    dataType  : 'string',
                    width     : 100
                }],
                buttons : [{
                    text      : Locale.get( lg, 'packages.btn.add.server' ),
                    textimage : 'icon-plus',
                    events : {
                        onClick : this.dialogAddServer
                    }
                }, {
                    text      : Locale.get( lg, 'packages.btn.del.server' ),
                    name      : 'delServers',
                    textimage : 'icon-trash',
                    disabled  : true,
                    events :
                    {
                        onClick : function()
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
                onrefresh  : function()
                {
                    Ajax.get('ajax_system_packages_server_list', function(result)
                    {
                        var i, len, alt, title, icon;

                        var server_click = function(Btn)
                        {
                            var server = Btn.getAttribute( 'server' );

                            if ( !server ) {
                                return;
                            }

                            Btn.setAttribute( 'icon', 'icon-refresh icon-spin' );

                            if ( Btn.getAttribute( 'status' ) )
                            {
                                self.deactivateServer( server );
                            } else
                            {
                                self.activateServer( server );
                            }
                        };


                        for ( i = 0, len = result.length; i < len; i++ )
                        {
                            alt   = Locale.get( lg, 'packages.server.grid.btn.activate.title' );
                            title = Locale.get( lg, 'packages.server.grid.btn.activate.title' );

                            icon = 'icon-ok';

                            if ( result[ i ].active === 0 )
                            {
                                icon = 'icon-remove';

                                alt   = Locale.get( lg, 'packages.server.grid.btn.deactivate.title' );
                                title = Locale.get( lg, 'packages.server.grid.btn.deactivate.title' );
                            }

                            result[ i ].status = {
                                name    : 'server-active-status',
                                title   : title,
                                alt     : alt,
                                icon    : icon,
                                server  : result[ i ].server,
                                status  : result[ i ].active,
                                events  : {
                                    onClick : server_click
                                }
                            };
                        }


                        self.$ServerGrid.setData({
                            data : result
                        });

                        self.Loader.hide();
                    });
                },
                alternaterows    : true,
                resizeColumns    : true,
                resizeHeaderOnly : true
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
            var self = this;

            new QUIConfirm({
                title : Locale.get( lg, 'packages.server.win.add.title' ),
                icon  : 'icon-building',
                text  : Locale.get( lg, 'packages.server.win.add.text' ),
                information : '<form class="qui-packages-panel-addserver">' +
                                  '<input type="text" name="server" value="" placeholder="Server" />' +
                                  '<select name="types">' +
                                      '<option value="composer">composer</option>' +
                                      '<option value="vcs">vcs</option>' +
                                      '<option value="pear">pear</option>' +
                                      '<option value="package">package</option>' +
                                      '<option value="artifact">artifact</option>' +
                                  '</select>' +
                              '</form>',

                autoclose : false,
                events    :
                {
                    onDrawEnd : function(Win)
                    {
                        var Form = Win.getContent().getElement( 'form' );

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
                        var Form = Win.getContent().getElement( 'form' );

                        if ( !Form ) {
                            return;
                        }

                        var Input  = Form.getElement( 'input' ),
                            Select = Form.getElement( 'select' );

                        self.addServer( Input.value, {
                            type : Select.value
                        });

                        Win.close();
                    }
                }
            }).open();
        },

        /**
         * Opens the remove server dialog
         *
         * @param {Array} list - server list
         */
        dialogRemoveServer : function(list)
        {
            var self = this;

            new QUIConfirm({
                title : Locale.get( lg, 'packages.server.win.remove.title' ),
                icon : 'icon-trash',
                text : Locale.get( lg, 'packages.server.win.remove.text' ),
                texticon    : 'icon-trash',
                information : list.join( '<br />' ) +
                              '<p>&nbsp;</p>'+
                              '<p>'+
                                  Locale.get( lg, 'packages.server.win.remove.information' ) +
                              '</p>',
                autoclose : false,
                list : list,
                events :
                {
                    onSubmit : function(Win)
                    {
                        Win.Loader.show();

                        Ajax.post( 'ajax_system_packages_server_remove', function()
                        {
                            Win.close();

                            if ( self.$ServerGrid ) {
                                self.$ServerGrid.refresh();
                            }

                        }, {
                            server : JSON.encode( Win.getAttribute( 'list' ) )
                        });
                    }
                }
            }).open();
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
            var self = this;

            Ajax.post('ajax_system_packages_server_status', function()
            {
                if ( self.$ServerGrid ) {
                    self.$ServerGrid.refresh();
                }

            }, {
                server : server,
                status : 1
            });
        },

        /**
         * Deactivate the server in the server list
         *
         * @param {String} server - server name
         */
        deactivateServer : function(server)
        {
            var self = this;

            Ajax.post('ajax_system_packages_server_status', function()
            {
                if ( self.$ServerGrid ) {
                    self.$ServerGrid.refresh();
                }

            }, {
                server : server,
                status : 0
            });
        },

        /**
         * Add a server to the update server list
         *
         * @param {String} server - server name
         * @param {Object} params - server params
         */
        addServer : function(server, params)
        {
            var self = this;

            Ajax.post('ajax_system_packages_server_add', function()
            {
                if ( self.$ServerGrid ) {
                    self.$ServerGrid.refresh();
                }

            }, {
                server : server,
                params : JSON.encode( params )
            });
        },

    /**
     * Health methods
     */

        /**
         * load the system health category
         */
        loadHealth : function()
        {
            var self = this;

            this.Loader.show();

            this.getBody().set(
                'html',

                Locale.get( lg, 'packages.systemhealth.text' )
            );

            var Body = this.getBody(),
                size = Body.getSize(),

                Container = new Element('div', {
                    styles : {
                        width     : size.x - 40,
                        height    : size.y - 200,
                        marginTop : 20
                    }
                }).inject( Body );

            this.$HealthGrid = new Grid(Container, {

                columnModel : [{
                    header    : '&nbsp;',
                    dataIndex : 'health',
                    dataType  : 'button',
                    width     : 60
                }, {
                    header    : Locale.get( lg, 'packages.grid.update.title.package' ),
                    dataIndex : 'name',
                    dataType  : 'string',
                    width     : 250
                }],

                buttons : [{
                    text      : Locale.get( lg, 'packages.grid.healthcheck.systemcheck' ),
                    textimage : 'icon-play',
                    events    :
                    {
                        click : function() {
                            self.systemHealthCheck();
                        }
                    }
                }],

                height : size.y - 200
            });

            Ajax.get('ajax_system_packages_list', function(result)
            {
                var i, alt, len, pkg, entry;

                var startHealthCheck = function(Btn)
                {
                    self.packageHealthCheck(
                        Btn.getAttribute( 'pkg' )
                    );
                };

                for ( i = 0, len = result.data.length; i < len; i++ )
                {
                    entry = result.data[ i ];
                    pkg   = entry.name;

                    alt = Locale.get( lg, 'packages.btn.execute.health.alt', {
                        pkg : pkg
                    });

                    result.data[ i ].health = {
                        icon   : 'icon-play',
                        pkg    : pkg,
                        alt    : alt,
                        title  : alt,
                        events : {
                            onClick : startHealthCheck
                        }
                    };
                }

                if ( self.$HealthGrid ) {
                    self.$HealthGrid.setData( result );
                }

                self.Loader.hide();

            }, {
                params : JSON.encode({
                    limit : this.getAttribute( 'limit' ),
                    page  : this.getAttribute( 'page' ),
                    type  : ''
                })
            });
        },

        /**
         * unload the system health category
         */
        unloadHealth : function()
        {
            if ( this.$HealthGrid )
            {
                this.$HealthGrid.destroy();
                this.$HealthGrid = null;
            }
        },

        /**
         * start the system health check
         */
        systemHealthCheck : function()
        {
            var self = this;

            this.Loader.show();

            Ajax.get('ajax_system_health_system', function(result)
            {
                self.$openHealthCheckSheet( result );
                self.Loader.hide();
            });
        },

        /**
         * start the health check for a package
         *
         * @param {String} pkg - name of the package
         */
        packageHealthCheck : function(pkg)
        {
            var self = this;

            this.Loader.show();

            Ajax.get('ajax_system_health_package', function(result)
            {
                if ( result ) {
                    self.$openHealthCheckSheet( result );
                }

                self.Loader.hide();
            }, {
                pkg : pkg
            });
        },

        /**
         * display the result sheet for a health check
         *
         * @param {Object} result - health check result
         */
        $openHealthCheckSheet : function(result)
        {
            this.createSheet({
                title  : 'Healthcheck Ergebnis',
                icon   : 'icon-health',
                events :
                {
                    onOpen : function(Sheet)
                    {
                        var i, icon, html;

                        var c        = 0,
                            sumOk    = 0,
                            sumError = 0,
                            sumNotFo = 0,
                            Content  = Sheet.getContent();

                        var iconOK       = '<span class="icon-ok"></span>',
                            iconError    = '<span class="icon-exclamation"></span>',
                            iconNotFound = '<span class="icon-question"></span>';

                        Content.setStyles({
                            overflow : 'auto',
                            padding  : 20
                        });

                        Content.addClass( 'packages-health-sumSheet' );

                        html = '<table class="data-table"><thead>' +
                                    '<tr>' +
                                    '<th colspan="2">Zusammenfassung</th>' + // #Locale
                                '</tr>' +
                                '</thead>' +
                                '<tbody>' +
                                    '<tr class="odd">' +
                                        '<td>'+ iconOK +'</td>' +
                                        '<td class="sum-ok"></td>' +
                                    '</tr>'+
                                    '<tr class="even">' +
                                        '<td>'+ iconError +'</td>' +
                                        '<td class="sum-error"></td>' +
                                    '</tr>'+
                                    '<tr class="odd">' +
                                        '<td>'+ iconNotFound +'</td>' +
                                        '<td class="sum-notFound"></td>' +
                                    '</tr>'+
                                '</tbody></table>'+
                                '<table class="data-table"><thead>' +
                                '<tr>' +
                                    '<th colspan="2">Ergebnis</th>' +
                                '</tr>' +
                                '</thead>' +
                                '<tbody>';


                        for ( i in result )
                        {
                            if ( !result.hasOwnProperty( i ) ) {
                                continue;
                            }

                            icon = '';

                            if ( result[ i ] === -1 )
                            {
                                icon = iconError;
                                sumError++;

                            } else if ( result[ i ] === 1 )
                            {
                                icon = iconOK;
                                sumOk++;

                            } else if ( result[ i ] === 0 )
                            {
                                icon = iconNotFound;
                                sumNotFo++;
                            }

                            html = html +
                                   '<tr class="'+ (c % 2 ? 'even' : 'odd') +'">' +
                                       '<td>'+ icon +'</td>' +
                                       '<td>'+ i +'</td>' +
                                   '</tr>';

                            c++;
                        }

                        html = html + '</tbody></table>';

                        Content.set( 'html', html );


                        Content.getElement( '.sum-ok' ).set( 'html', sumOk +' Dateien ok.' );
                        Content.getElement( '.sum-error' ).set( 'html', sumError +' Dateien fehlerhaft.' );
                        Content.getElement( '.sum-notFound' ).set( 'html', sumNotFo +' Dateien konnten nicht geprüft werden.' );
                    }
                }
            }).show();
        },


    /**
     * PHP Info
     */

        /**
         * Shows the php info
         */
        loadPHPInfo : function()
        {
            var self = this,
                Body = this.getContent();

            this.Loader.show();

            Body.set( 'html', '' );

            Ajax.get('ajax_system_phpinfo', function(result)
            {
                Body.set( 'html', result );
                self.Loader.hide();
            });
        }

    });
});