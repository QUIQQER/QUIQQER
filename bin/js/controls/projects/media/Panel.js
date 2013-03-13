/**
 * Displays a Media in a Panel
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires controls/Control
 * @requires classes/projects/Media
 * @requires classes/request/Upload
 * @requires controls/projects/media/Sitemap
 * @requires controls/grid/Grid
 * @requires controls/projects/media/PanelDOMEvents
 * @requires controls/projects/media/PanelContextMenu
 * @requires controls/upload/Form
 *
 * @event onDragDropComplete [this, event]
 *
 * @module controls/projects/media/Panel
 * @package com.pcsg.qui.js.controls.project
 * @namespace QUI.controls.projects.media
 */

define('controls/projects/media/Panel', [

    'controls/Control',
    'classes/projects/Media',
    'classes/request/Upload',
    'controls/projects/media/Sitemap',
    'controls/grid/Grid',
    'controls/projects/media/PanelDOMEvents',
    'controls/projects/media/PanelContextMenu',
    'controls/upload/Form',

    'css!controls/projects/media/Panel.css'

], function(QUI_Control, QUI_Media, QUI_Upload, QUI_MediaSitemap)
{
    QUI.namespace( 'controls.projects.media' );

    /**
     * A Media-Panel, opens the Media in an Apppanel
     *
     * @class QUI.controls.projects.media.Panel
     *
     * @param {QUI.classes.projects.Media} Media
     * @param {Object} options
     */
    QUI.controls.projects.media.Panel = new Class({

        Implements : [ QUI_Control ],
        Type       : 'QUI.controls.projects.media.Panel',

        options : {
            id        : 'projects-media-panel',
            container : false,
            startid   : false,
            view      : 'symbols',    // available views are: symbols, details, preview
            fileid    : false,        // the current folder id

            title : '',
            icon  : '',

            field : 'name',
            order : 'ASC',
            limit : 20,
            page  : 1
        },

        initialize : function(Media, options)
        {
            // default id
            this.setAttribute( 'id', 'projects-media-panel' );
            this.setAttribute( 'name', 'projects-media-panel' );

            var view = QUI.Storage.get( 'qui-media-panel-view' );

            if ( view ) {
                this.setAttribute('view', view);
            }

            this.init( options );

            this.$Panel    = null;
            this.$Map      = null;
            this.$Media    = Media;
            this.$File     = null;
            this.$children = [];
            this.$selected = [];

            this.$DOMEvents   = new QUI.controls.projects.media.PanelDOMEvents( this );
            this.$ContextMenu = new QUI.controls.projects.media.PanelContextMenu( this );

            this.create();
        },

        /**
         * Close and destroy the media panel
         *
         * @method QUI.controls.projects.media.Panel#close
         */
        close : function()
        {
            this.destroy();
        },

        /**
         * Create the Media Panel
         * create a MUI.Apppanel and start the Media loading
         *
         * @method QUI.controls.projects.media.Panel#create
         */
        create : function()
        {
            var Panel = new QUI.controls.desktop.Panel({
                id         : this.getAttribute('id'),
                icon       : URL_BIN_DIR +'images/loader.gif',
                tabbar     : false,
                breadcrumb : true,
                events : {
                    onContextMenu : this.$ContextMenu.createPanelMenu.bind( this )
                }
            });

            QUI.Controls.get( 'content-panel' )[0].appendChild(
                Panel
            );

            this.$Panel = Panel;
            this.$Panel.Loader.show();
            this.load();
        },

        /**
         * Load the Media and the Tabs to the Panel
         *
         * @method QUI.controls.projects.media.Panel#load
         */
        load : function()
        {
            this.$Panel.Loader.show();

            // blur event
            var Body = this.$Panel.getBody();

            Body.addEvent('click', function()
            {
                this.unselectItems();
            }.bind( this ));


            // buttons
            this.$Panel.addButton(
                new QUI.controls.buttons.Button({
                    name    : 'left-sitemap-media-button',
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
                })
            );

            this.$Panel.addButton(
                new QUI.controls.buttons.Seperator()
            );

            // views
            var View = new QUI.controls.buttons.Button({
                textimage : URL_BIN_DIR +'16x16/view_icon.png',
                text      : 'Ansicht: Symbolansicht',
                Control   : this,
                change    : function(Item)
                {
                    var Btn     = Item.getAttribute('Button'),
                        Control = Btn.getAttribute('Control');

                    Btn.setAttribute('Active', Item);
                    Btn.setAttribute('text', 'Ansicht: '+ Item.getAttribute('text'));
                    Btn.setAttribute('textimage', Item.getAttribute('icon'));

                    Control.setAttribute('view', Item.getAttribute('name'));
                    Control.$view( Control.$children );

                    Btn.getParent().resize();
                }
            });

            View.appendChild(
                new QUI.controls.contextmenu.Item({
                    name   : 'symbols',
                    text   : 'Symbolansicht',
                    icon   : URL_BIN_DIR +'16x16/view_icon.png',
                    events :
                    {
                        onMouseDown : function(Item, event)
                        {
                            Item.getAttribute('Button')
                                .getAttribute('change')( Item );
                        }
                    }
                })
            ).appendChild(
                new QUI.controls.contextmenu.Item({
                    name   : 'details',
                    text   : 'Detailsansicht',
                    icon   : URL_BIN_DIR +'16x16/view_detailed.png',
                    events :
                    {
                        onMouseDown : function(Item, event)
                        {
                            Item.getAttribute('Button')
                                .getAttribute('change')( Item );
                        }
                    }
                })
            ).appendChild(
                new QUI.controls.contextmenu.Item({
                    name   : 'preview',
                    text   : 'Vorschau',
                    icon   : URL_BIN_DIR +'16x16/view_fullscreen.png',
                    events :
                    {
                        onMouseDown : function(Item, event)
                        {
                            Item.getAttribute('Button')
                                .getAttribute('change')( Item );
                        }
                    }
                })
            );

            this.$Panel.addButton( View );

            this.$Panel.addButton(
                new QUI.controls.buttons.Seperator()
            );

            this.$Panel.addButton(
                new QUI.controls.buttons.Button({
                    name      : 'create_folder',
                    text      : 'Neuen Ordner erstellen',
                    textimage : URL_BIN_DIR +'16x16/folder.png',
                    Control   : this,
                    events    :
                    {
                        onClick : function(Item) {
                            Item.getAttribute('Control').createFolder();
                        }
                    }
                })
            );

            // Upload
            var Upload = new QUI.controls.buttons.Button({
                textimage : URL_BIN_DIR +'16x16/up.png',
                text      : 'Dateien hochladen',
                Control   : this
            });

            Upload.appendChild(
                new QUI.controls.contextmenu.Item({
                    name   : 'upload_files',
                    text   : 'Dateien hochladen',
                    icon   : URL_BIN_DIR +'16x16/page.png',
                    events :
                    {
                        onMouseDown : function(Item, event)
                        {
                            Item.getAttribute('Button')
                                .getAttribute('Control')
                                .uploadFiles();
                        }
                    }
                })
            ).appendChild(
                new QUI.controls.contextmenu.Item({
                    name   : 'upload_archive',
                    text   : 'Archiv hochladen und entpacken',
                    icon   : URL_BIN_DIR +'16x16/archiv.png',
                    events :
                    {
                        onMouseDown : function(Item, event)
                        {
                            Item.getAttribute('Button')
                                .getAttribute('Control')
                                .uploadArchive();
                        }
                    }
                })
            );

            this.$Panel.addButton( Upload );


            //this.$Panel.Buttons.resize();

            if ( this.getAttribute('startid') )
            {
                this.openID( this.getAttribute('startid') );
                return;
            }

            this.openID( 1 );
        },

        unload : function()
        {

        },

        /**
         * Refresh the Panel
         *
         * @method QUI.controls.projects.media.Panel#openID
         */
        refresh : function()
        {
            if ( this.getAttribute( 'fileid' ) )
            {
                this.openID( this.getAttribute( 'fileid' ) );
                return;
            }

            this.openID( 1 );
        },

        /**
         * Opens the file and load the breadcrumb
         *
         * @method QUI.controls.projects.media.Panel#openID
         *
         * @param {Integer} fileid
         */
        openID : function(fileid)
        {
            this.$Panel.Loader.show();

            var Project = this.$Media.getProject();

            // set loader image
            this.$Panel.setOptions({
                icon  : URL_BIN_DIR +'images/loader.gif',
                title : ' Media ('+ Project.getName() +')'
            });

            this.$Panel.refresh();

            // get the file object
            this.$Media.get(fileid, function(MediaFile, Request)
            {
                // set media image to the panel
                this.$Panel.setOptions({
                    icon  : URL_BIN_DIR +'16x16/media.png',
                    title : ' Media ('+ Project.getName() +')'
                });

                this.$Panel.refresh();
                this.$File = MediaFile;

                // if the MediaFile is no Folder
                if ( MediaFile.getType() !== 'QUI.classes.projects.media.Folder' )
                {
                    MediaFile.openInPanel();

                    this.$Panel.Loader.hide();
                    return;
                }

                this.setAttribute('fileid', MediaFile.getId());

                // load children
                MediaFile.getChildren(function(children, Request)
                {
                    this.$children = children;
                    this.$view( children );

                    // load breadcrumb
                    this.$File.getBreadcrumb(function(result, Request)
                    {
                        this.$createBreadCrumb( result );

                        // select active item, if map is open
                        if ( this.$Map ) {
                            this.$Map.selectFolder( MediaFile.getId() );
                        }

                        this.$Panel.Loader.hide();

                    }.bind( this ));

                }.bind( this ));

            }.bind( this ));
        },

        /**
         * Return the Media object of the panel
         *
         * @return {QUI.classes.projects.Media} Media
         */
        getMedia : function()
        {
            return this.$Media;
        },

        /**
         * Return the current displayed media folder
         *
         * @return {QUI.classes.projects.media.Folder} Folder
         */
        getCurrentFile : function()
        {
            return this.$File;
        },

        /**
         * Create the left Sitemap for the panel and show it
         *
         * @method QUI.controls.projects.media.Panel#showSitemap
         */
        showSitemap : function()
        {
            var Container;

            var Body  = this.$Panel.getBody(),
                Items = Body.getElement('.qui-media-content');

            if ( !Body.getElement('.qui-media-sitemap') )
            {
                new Element('div', {
                    'class' : 'qui-media-sitemap shadow',
                    styles  : {
                        left     : -350,
                        position : 'absolute'
                    }
                }).inject( Body, 'top' );
            }

            Container = Body.getElement('.qui-media-sitemap');

            Items.setStyles({
                width       : Body.getSize().x - 350,
                marginLeft  : 300
            });

            moofx( Container ).animate({
                left : 0
            }, {
                callback : function()
                {
                    this.$createSitemap();
                    this.$resizeSheet();

                    new Element('div', {
                        'class' : 'qui-media-sitemap-handle columnHandle',
                        styles  : {
                            position : 'absolute',
                            top      : 0,
                            right    : 0,
                            height   : '100%',
                            width    : 4,
                            cursor   : 'pointer'
                        },
                        events :
                        {
                            click : function()
                            {
                                this.hideSitemap();
                            }.bind( this )
                        }
                    }).inject( Body.getElement('.qui-media-sitemap') );
                }.bind( this )
            });
        },

        /**
         * Hide the Sitemap
         *
         * @method QUI.controls.projects.media.Panel#hideSitemap
         */
        hideSitemap : function()
        {
            var Body      = this.$Panel.getBody(),
                Container = Body.getElement('.qui-media-sitemap');

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
                    var Body  = this.$Panel.getBody(),
                        Items = Body.getElement('.qui-media-content');

                    Container.destroy();

                    Items.setStyles({
                        width      : '100%',
                        marginLeft : null
                    });

                    var Btn = this.$Panel.getButtons( 'left-sitemap-media-button' );

                    if ( Btn ) {
                        Btn.setNormal();
                    }

                    this.$resizeSheet();

                }.bind( this, Container )
            });
        },

        /**
         * Opens the sheet with the upload dialog
         *
         * @method QUI.controls.projects.media.Panel#uploadFiles
         */
        uploadFiles : function()
        {
            this.$upload();
        },

        /**
         * Opens the sheet with the upload archive dialog
         *
         * @method QUI.controls.projects.media.Panel#uploadArchive
         */
        uploadArchive : function()
        {
            this.$upload( true );
        },

        /**
         * Upload sheet helper
         *
         * @method QUI.controls.projects.media.Panel#uploadArchive
         *
         * @param {Bool} extract - [optional] extrat = true => archiv upload,
         *                                    extrat = false => standard upload
         */
        $upload : function(extract)
        {
            extract = extract || false;

            this.$Panel.openSheet(function(Sheet, Content, Buttons)
            {
                var Parent;

                this.$resizeSheet();

                Content.set( 'html', '' );


                if ( extract )
                {
                    Parent = new Element('div.qui-media-upload', {
                        html : '<h2>Archiv Upload</h2>' +
                               '<p>Laden Sie Archiv Dateien in den Media Ordner hoch.</p>' +
                               '<p>Diese Archivdateien werden direkt entpackt.</p>'
                    }).inject( Content );

                } else
                {
                    Parent = new Element('div.qui-media-upload', {
                        html : '<h2>Datei Upload</h2>' +
                               '<p>Laden Sie Dateien in den Media Ordner hoch.</p>'
                    }).inject( Content );
                }

                // upload form
                var Form = new QUI.controls.upload.Form({
                    multible   : true,
                    sendbutton : true,
                    maxuploads : 5,
                    uploads    : 1,
                    styles     : {
                        margin : '20px 0 0',
                        float  : 'left',
                        clear  : 'both'
                    },
                    Media  : this,
                    Drops  : [Sheet],
                    Panel  : this.$Panel,
                    Sheet  : Sheet,
                    fileid : this.getAttribute('fileid'),
                    events :
                    {
                        onDragenter: function(event, Elm, Upload)
                        {
                            if ( !Elm.hasClass('pannelsheet-content')  ) {
                                Elm = Elm.getParent('pannelsheet-content');
                            }

                            if ( !Elm || !Elm.hasClass('pannelsheet-content') ) {
                                return;
                            }

                            Elm.addClass( 'qui-media-drag' );
                            event.stop();
                        },

                        onDragend : function(event, Elm, Upload)
                        {
                            if ( Elm.hasClass('qui-media-drag') ) {
                                Elm.removeClass( 'qui-media-drag' );
                            }
                        },

                        onBegin : function(Control) {
                            Control.getAttribute('Sheet').close();
                        },

                        onComplete : function(Control)
                        {
                            var i, len;
                            var panels = QUI.Controls.get('projects-media-panel');

                            for ( i = 0, len = panels.length; i < len; i++ ) {
                                panels[i].refresh();
                            }
                        }
                    }
                });

                Form.setParam('onfinish', 'ajax_media_upload');
                Form.setParam('project', this.$Media.getProject().getName());
                Form.setParam('parentid', this.getAttribute('fileid'));

                if ( extract )
                {
                    Form.setParam('extract', 1);
                } else
                {
                    Form.setParam('extract', 1);
                }

                Form.inject( Parent );

            }.bind( this ) );
        },

        /**
         * Download the file
         *
         * @method QUI.controls.projects.media.Panel#downloadFile
         * @param {Integer} fileid - ID of the file
         */
        downloadFile : function(fileid)
        {
            this.$Media.get(fileid, function(File) {
                File.download();
            });
        },

        /**
         * Create the Sitemap
         *
         * @method QUI.controls.projects.media.Panel#$createSitemap
         */
        $createSitemap : function()
        {
            var Body      = this.$Panel.getBody(),
                Container = Body.getElement( '.qui-media-sitemap' );

            if ( !Container ) {
                return;
            }

            var Project = this.$Media.getProject();

            this.$Map = new QUI.controls.projects.media.Sitemap({
                Panel   : this,
                project : Project.getAttribute( 'project' ),
                lang    : Project.getAttribute( 'lang' ),
                id      : this.getAttribute( 'startid' ),
                events :
                {
                    onItemClick : function(Item, Sitemap)
                    {
                        Sitemap.getAttribute( 'Panel' ).openID(
                            Item.getAttribute( 'value' )
                        );
                    }
                }
            });

            this.$Map.inject( Container );
            this.$Map.open();

            // open last breadcrumb item in the sitemap
            this.$Map.addEvent('onOpenEnd', function(Item, MapControl)
            {
                var Breadcrumb = this.$Panel.getBreadcrumb(),
                    Last       = Breadcrumb.lastChild();

                MapControl.selectFolder( Last.getAttribute( 'id' ) );

            }.bind( this ));
        },

        /**
         * Create the breadcrumb items for openID method
         *
         * @method QUI.controls.projects.media.Panel#$createBreadCrumb
         * @params {array} items
         */
        $createBreadCrumb : function(items)
        {
            var i, len, Item;

            var Breadcrumb = this.$Panel.getBreadcrumb(),
                func_open  = function(Item, event)
                {
                    this.openID( Item.getAttribute( 'id' ) );
                }.bind( this );

            Breadcrumb.clear();

            for ( i = 0, len = items.length; i < len; i++ )
            {
                Item = new QUI.controls.breadcrumb.Item({
                    text : items[i].name,
                    id   : items[i].id
                });

                Item.addEvents({
                    onClick : func_open
                });

                if (items[i].icon) {
                    Item.setAttribute('icon', items[i].icon);
                }

                Breadcrumb.appendChild( Item );
            }
        },

        /**
         * Resize the panel sheet, if the sheet exist
         *
         * @method QUI.controls.projects.media.Panel#$resizeSheet
         */
        $resizeSheet : function()
        {
            var Body  = this.$Panel.getBody(),
                Map   = Body.getElement('.qui-media-sitemap'),
                Sheet = Body.getElement('.pannelsheet');

            if ( !Sheet ) {
                return;
            }

            var PanelContent = Sheet.getElement('.pannelsheet-content'),
                PanelButtons = Sheet.getElement('.pannelsheet-buttons');


            if ( !Map )
            {
                var body_width = Body.getSize().x;

                Sheet.setStyles({
                    'width' : body_width,
                    'left'  : 0
                });

                PanelContent.setStyle('width', body_width);
                PanelButtons.setStyle('width', body_width);

                return;
            }

            var sheet_size = Sheet.getSize().x,
                map_size   = Map.getSize().x;

            Sheet.setStyles({
                'width' : sheet_size - map_size,
                'left'  : map_size
            });

            PanelContent.setStyle('width', sheet_size - map_size);
            PanelButtons.setStyle('width', sheet_size - map_size);
        },

        /**
         * list the children
         *
         * @method QUI.controls.projects.media.Panel#$viewSymbols
         * @params {array} children
         */
        $view : function(children)
        {
            var Body     = this.$Panel.getBody(),
                Media    = this.$Media,
                Project  = Media.getProject(),
                droplist = [],
                project  = Project.getAttribute('project'),

                Breadcrumb = this.$Panel.Breadcrumb;

            // create the media body
            var MediaBody;

            if ( !Body.getElement('.qui-media-content') )
            {
                MediaBody = new Element('div', {
                    'class' : 'qui-media-content box smooth'
                });

                MediaBody.inject( Body );
            }

            MediaBody = Body.getElement('.qui-media-content');
            MediaBody.set({
                'html'      : '',
                'data-id'   : this.getAttribute('fileid'),
                'data-type' : 'folder'
            });

            if ( this.$File ) {
                MediaBody.set('title', this.$File.getAttribute('title'));
            }

            QUI.Storage.set(
                'qui-media-panel-view',
                this.getAttribute('view')
            );


            switch ( this.getAttribute('view') )
            {
                case 'details':
                    droplist = this.$viewDetails( children, MediaBody );
                break;

                case 'preview':
                    droplist = this.$viewPreview( children, MediaBody );
                break;

                default:
                case 'symbols':
                    droplist = this.$viewSymbols( children, MediaBody );
            }

            droplist.push( MediaBody );


            // Upload events
            new QUI.classes.request.Upload(droplist, {

                onDragenter: function(event, Elm, Upload)
                {
                    this.$dragEnter( event, Elm );

                    event.stop();
                }.bind( this ),

                onDragend : function(event, Elm, Upload)
                {
                    this.$dragLeave( event, Elm );

                    event.stop();
                }.bind( this ),

                onDrop : this.$viewOnDrop.bind( this )
            });
        },

        /**
         * OnDrop Event
         *
         * @param {DOMEvent} event       - DragDrop Event
         * @param {Array|FileList} files - List of droped files
         * @param {DOMNode} Elm          - Droped Parent Element
         * @param {QUI.classes.request.Upload} Upload - Upload control
         */
        $viewOnDrop : function(event, files, Elm, Upload)
        {
            if ( !files.length ) {
                return;
            }

            if ( Elm.hasClass('qui-media-content') )
            {
                this.$ContextMenu.showDragDropMenu( files, Elm, event );

                /*
                this.$Media.get( this.getAttribute('fileid'), function(Item)
                {
                    Item.uploadFiles( files, function(File)
                    {
                        var i, len;

                        var params   = File.getAttribute('params'),
                            parentid = params.parentid,
                            project  = params.project,

                            panels   = QUI.Controls.get( 'projects-media-panel' );

                        for ( i = 0, len = panels.length; i < len; i++ ) {
                            panels[ i ].refresh();
                        }

                    } );
                });
                */

                return;
            }

            if ( !Elm.hasClass('qui-media-item') ) {
                Elm = Elm.getParent('.qui-media-item');
            }

            // drop on a file
            if ( !Elm || Elm.get('data-type') != 'folder' )
            {
                this.$ContextMenu.showDragDropMenu( files[0], Elm, event );
                return;
            }

            this.$ContextMenu.showDragDropMenu( files, Elm, event );
        },

        /**
         * list the children as symbol icons
         *
         * @method QUI.controls.projects.media.Panel#$viewSymbols
         * @params {array} children
         * @params {DOMNode} Container - Parent Container for the DOMNodes
         * @return {array} the drop-upload-list
         */
        $viewSymbols : function(children, Container)
        {
            var i, len, Elm, Child, func_context;

            var droplist = [],
                Media    = this.$Media,
                Project  = Media.getProject(),
                project  = Project.getAttribute('project');

            for ( i = 0, len = children.length; i < len; i++ )
            {
                if ( i === 0 && children[i].name === '..' ) {
                    continue;
                }

                Child = children[i];

                Elm = new Element('div', {
                    'data-id'      : Child.id,
                    'data-project' : project,
                    'data-type'    : Child.type,
                    'data-active'  : Child.active ? 1 : 0,
                    'data-error'   : Child.error ? 1 : 0,

                    'class' : 'qui-media-item box smooth',
                    html    : '<span class="title">'+ Child.name +'</span>',
                    alt     : Child.name,
                    title   : Child.name,

                    events  :
                    {
                        click       : this.$viewSymbolClick.bind(this),
                        mousedown   : this.$viewSymbolMouseDown.bind( this ),
                        mouseup     : this.$dragStop.bind( this ),
                        contextmenu : this.$ContextMenu.show.bind( this.$ContextMenu )
                    }
                });

                // if ( Child.type === 'folder' ) {
                droplist.push( Elm );
                // }

                if ( Child.active )
                {
                    Elm.addClass('qmi-active');
                } else
                {
                    Elm.addClass('qmi-deactive');
                }

                if ( Child.error )
                {
                    Elm.setStyles({
                        backgroundImage : 'url('+ URL_BIN_DIR +'48x48/file_broken.png)',
                        paddingLeft     : 20
                    });

                    QUI.MH.addError(
                        'File is broken #'+ Child.id +' '+ Child.name
                    );

                } else
                {
                    Elm.setStyles({
                        backgroundImage : 'url('+ Child.icon80x80 +')',
                        paddingLeft     : 20
                    });
                }

                Elm.inject( Container );
            }

            return droplist;
        },

        /**
         * list the children with preview icons
         * preview for images
         *
         * @method QUI.controls.projects.media.Panel#$viewSymbols
         * @params {array} children
         * @params {DOMNode} Container - Parent Container for the DOMNodes
         * @return {array} the drop-upload-list
         */
        $viewPreview : function(children, Container)
        {
            var i, len, url,
                Child, Elm, func_context;

            var droplist = [],
                Media    = this.$Media,
                Project  = Media.getProject(),
                project  = Project.getAttribute('project');

            for ( i = 0, len = children.length; i < len; i++ )
            {
                if ( i === 0 && children[i].name === '..' ) {
                    continue;
                }

                Child = children[i];

                Elm = new Element('div', {
                    'data-id'      : Child.id,
                    'data-project' : project,
                    'data-type'    : Child.type,
                    'data-active'  : Child.active ? 1 : 0,
                    'data-error'   : Child.error ? 1 : 0,

                    'class' : 'qui-media-item box smooth',
                    html    : '<span class="title">'+ Child.name +'</span>',
                    alt     : Child.name,
                    title   : Child.name,

                    events :
                    {
                        click       : this.$viewSymbolClick.bind( this ),
                        mousedown   : this.$viewSymbolMouseDown.bind( this ),
                        mouseup     : this.$dragStop.bind( this ),
                        contextmenu : this.$ContextMenu.show.bind( this.$ContextMenu )
                    }
                });

                droplist.push( Elm );

                Elm.setStyles({
                    backgroundImage : 'url('+ Child.icon80x80 +')',
                    paddingLeft     : 20
                });

                if ( Child.error )
                {
                    Elm.setStyles({
                        backgroundImage : 'url('+ URL_BIN_DIR +'48x48/file_broken.png)',
                        paddingLeft     : 20
                    });

                    QUI.MH.addError(
                        'File is broken #'+ Child.id +' '+ Child.name
                    );
                }

                if ( Child.type === 'image' && !Child.error )
                {
                    url = URL_DIR +'admin/bin/'+ Child.url;

                    url = url +'&maxheight=80';
                    url = url +'&maxwidth=80';

                    // because of the browser cache
                    if ( Child.e_date ) {
                        url = url +'&edate='+ Child.e_date.replace(/[^0-9]/g, '');
                    }

                    Elm.setStyles({
                        'backgroundImage'    : 'url('+ url +')',
                        'backgroundPosition' : 'center center'
                    });
                }

                if ( Child.active )
                {
                    Elm.addClass( 'qmi-active' );
                } else
                {
                    Elm.addClass( 'qmi-deactive' );
                }

                Elm.inject( Container );
            }

            return droplist;
        },

        /**
         * execute a click event on a target media item div
         *
         * @method QUI.controls.projects.media.Panel#$viewSymbolClick
         * @param {DOMEvent} event
         */
        $viewSymbolClick : function(event)
        {
            event.stopPropagation();

            var Target = event.target;

            if ( Target.nodeName == 'SPAN' ) {
                Target = Target.getParent('div');
            }

            if ( event.control )
            {
                this.$selected.push( Target );

                Target.addClass( 'selected' );

                return;
            }

            this.unselectItems();
            this.openID( Target.get('data-id') );
        },

        /**
         * execute a mousedown event on a target media item div
         *
         * @method QUI.controls.projects.media.Panel#$viewSymbolMouseDown
         * @param {DOMEvent} event
         */
        $viewSymbolMouseDown : function(event)
        {
            this.setAttribute( '_stopdrag', false );
            this.$dragStart.delay( 200, this, event ); // nach 0.1 Sekunden erst

            event.stop();
        },

        /**
         * execute a mouseup event on a target media item div
         *
         * @method QUI.controls.projects.media.Panel#$viewSymbolMouseUp
         * @param {DOMEvent} event
         */
        $viewSymbolMouseUp : function(event)
        {
            this.stopDrag( event );
        },

        /**
         * list the children as table
         *
         * @method QUI.controls.projects.media.Panel#$viewDetails
         *
         * @params {array} children
         * @params {DOMNode} Container - Parent Container for the DOMNodes
         * @return {array} the drop-upload-list
         */
        $viewDetails : function(children, Container)
        {
            Container.set('html', '');

            var GridContainer = new Element('div');
                GridContainer.inject( Container );

            var Grid = new QUI.controls.grid.Grid(GridContainer, {

                columnModel: [{
                    header    : '&nbsp;',
                    dataIndex : 'icon',
                    dataType  : 'image',
                    width     : 30
                }, {
                    header    : 'ID',
                    dataIndex : 'id',
                    dataType  : 'integer',
                    width     : 50
                }, {
                    header    : 'Name',
                    dataIndex : 'name',
                    dataType  : 'string',
                    width     : 150
                }, {
                    header    : 'Title',
                    dataIndex : 'title',
                    dataType  : 'stringr',
                    width     : 150
                }, {
                    header    : 'Größe',
                    dataIndex : 'size',
                    dataType  : 'string',
                    width     : 150
                }, {
                    header    : 'Erstellungs Datum',
                    dataIndex : 'cdate',
                    dataType  : 'date',
                    width     : 150
                }],

                pagination : false,
                filterInput: true,
                perPage    : this.getAttribute('limit'),
                page       : this.getAttribute('page'),
                sortOn     : this.getAttribute('field'),
                serverSort : true,
                showHeader : true,
                sortHeader : true,
                width      : Container.getSize().x - 100,
                height     : Container.getSize().y - 40,
                onrefresh  : function(me)
                {
                    var options = me.options;

                    this.setAttribute('field', options.sortOn);
                    this.setAttribute('order', options.sortBy);
                    this.setAttribute('limit', options.perPage);
                    this.setAttribute('page', options.page);

                    this.refresh();

                }.bind( this ),

                alternaterows     : true,
                resizeColumns     : true,
                selectable        : true,
                multipleSelection : true,
                resizeHeaderOnly  : true,

                Controle : this
            });

            Grid.addEvents({
                onClick : function(data, Item)
                {
                    var Grid     = data.target,
                        row      = data.row,
                        Controle = Grid.getAttribute('Controle');

                    Controle.openID( Grid.getDataByRow( row ).id );
                }
            });

            if ( children[0] && children[0].name !== '..' )
            {
                var breadcrumb_list = Array.clone(
                    this.$Panel.getBreadcrumb().getChildren()
                );

                if ( breadcrumb_list.length > 1 )
                {
                    var Last       = breadcrumb_list.pop(),
                        BeforeLast = breadcrumb_list.pop();

                    children.reverse();
                    children.push({
                        icon  : URL_BIN_DIR +'16x16/folder.png',
                        id    : BeforeLast.getAttribute('id'),
                        name  : '..',
                        title : BeforeLast.getAttribute('text')
                    });
                    children.reverse();
                }
            }

            Grid.setData({
                data : children
            });

            return [];
        },

        /**
         * Opens the create folder window
         *
         * @method QUI.controls.projects.media.Panel#createFolder
         */
        createFolder : function()
        {
            QUI.Windows.create('prompt', {
                title    : 'test',
                text     : 'Geben Sie bitte den neuen Ordnernamen ein',
                texticon : URL_BIN_DIR +'48x48/extensions/folder.png',
                icon     : URL_BIN_DIR +'16x16/folder.png',
                Control  : this,
                height   : 150,
                events   :
                {
                    onSubmit : function(value, Win)
                    {
                        var Control = Win.getAttribute('Control');

                        Control.$File.createFolder(
                            value,
                            function(Folder, Request)
                            {
                                this.openID( Folder.getId() );
                            }.bind( Control )
                        );
                    }
                }
            });
        },

        /**
         * Activate the media item from the DOMNode
         *
         * @method QUI.controls.projects.media.Panel#activateItem
         * @param {DOMNode} DOMNode
         *
         * @depricated this.$DOMEvents.activate
         */
        activateItem : function(DOMNode)
        {
            this.$DOMEvents.activate( [DOMNode] );
        },

        /**
         * Activate the media items
         *
         * @method QUI.controls.projects.media.Panel#activateItem
         * @param {Array} DOMNode List
         *
         * @depricated this.$DOMEvents.activate
         */
        activateItems : function(DOMNode)
        {
            this.$DOMEvents.activate( DOMNode );
        },

        /**
         * Deactivate the media item from the DOMNode
         *
         * @method QUI.controls.projects.media.Panel#deactivateItem
         * @param {DOMNode} DOMNode
         *
         * @depricated this.$DOMEvents.deactivate
         */
        deactivateItem : function(DOMNode)
        {
            this.$DOMEvents.deactivate( [DOMNode] );
        },

        /**
         * Deactivate the media item from the DOMNode
         *
         * @method QUI.controls.projects.media.Panel#deactivateItem
         * @param {DOMNode} DOMNode
         *
         * @depricated this.$DOMEvents.deactivate
         */
        deactivateItems : function(DOMNode)
        {
            this.$DOMEvents.deactivate( DOMNode );
        },

        /**
         * Delete the media item from the DOMNode
         *
         * @method QUI.controls.projects.media.Panel#deleteItem
         * @param {DOMNode} DOMNode
         */
        deleteItem : function(DOMNode)
        {
            this.$DOMEvents.del( [DOMNode] );
        },

        /**
         * Delete the media items
         *
         * @method QUI.controls.projects.media.Panel#deleteItems
         * @param {Array} DOMNode List
         */
        deleteItems : function(items)
        {
            this.$DOMEvents.del( items );
        },

        /**
         * Rename the folder
         *
         * @method QUI.controls.projects.media.Panel#renameItem
         * @param {DOMNode} DOMNode
         */
        renameItem : function(DOMNode)
        {
            this.$DOMEvents.rename( DOMNode );
        },

        /**
         * Opens the replace dialoge
         *
         * @method QUI.controls.projects.media.Panel#replaceItem
         * @param {DOMNode} DOMNode
         */
        replaceItem : function(DOMNode)
        {
            this.$DOMEvents.replace( DOMNode );
        },

        /**
         * Unselect all selected items
         */
        unselectItems : function()
        {
            if ( !this.$selected.length ) {
                return;
            }

            for ( var i = 0, len = this.$selected.length; i < len; i++ )
            {
                if ( !this.$selected[ i ] ) {
                    continue;
                }

                this.$selected[ i ].removeClass( 'selected' );
            }

            this.$selected = [];
        },


        /**
         * Return the selected Items
         *
         * @return {Array}
         */
        getSelectedItems : function()
        {
            return this.$selected;
        },

        /**
         * Copy Items to a folder
         *
         * @param {Integer} folderid - Folder which copy the files into
         * @param {Array} ids        - file ids
         */
        copyTo : function(folderid, ids)
        {
            if ( !ids.length ) {
                return;
            }

            this.$Panel.Loader.show();

            QUI.Ajax.post('ajax_media_copy', function(result, Request)
            {
                Request.getAttribute('Control')
                       .$Panel.Loader.hide();

                // we need no reload of the folder
            }, {
                project : this.$Media.getProject().getName(),
                to      : folderid,
                ids     : JSON.encode( ids ),
                Control : this
            });
        },

        /**
         * Move Items to a folder
         *
         * @param {Integer} folderid - Folder which copy the files into
         * @param {Array} ids        - file ids
         */
        moveTo : function(folderid, ids)
        {
            if ( !ids.length ) {
                return;
            }

            this.$Panel.Loader.show();

            QUI.Ajax.post('ajax_media_move', function(result, Request)
            {
                var Control = Request.getAttribute('Control');

                Control.openID(
                    Control.getAttribute('fileid')
                );

            }, {
                project : this.$Media.getProject().getName(),
                to      : folderid,
                ids     : JSON.encode( ids ),
                Control : this
            });
        },

        /**
         * DragDrop Methods
         */

        /**
         * Starts the Drag Drop
         *
         * @param {DOMEvent} event
         */
        $dragStart : function(event)
        {
            if ( event.rightClick ) {
                return;
            }

            if ( Browser.ie8 ) {
                return;
            }

            if ( this.getAttribute('_mousedown') ) {
                return;
            }

            if ( this.getAttribute('_stopdrag') ) {
                return;
            }

            this.setAttribute('_mousedown', true);

            var i, len, ElmSize;

            var mx  = event.page.x,
                my  = event.page.y,
                Elm = event.target;

            if ( !Elm.hasClass('qui-media-item') ) {
                Elm = Elm.getParent('.qui-media-item');
            }

            ElmSize = Elm.getSize();

            // create the shadow element
            this.$Drag = new Element('div', {
                'class' : 'box',
                styles : {
                    position   : 'absolute',
                    top        : my - 20,
                    left       : mx - 40,
                    zIndex     : 1000,
                    MozOutline : 'none',
                    outline    : 0,
                    color      : '#fff',
                    padding    : 10,
                    cursor     : 'pointer',

                    width  : ElmSize.x,
                    height : ElmSize.y,
                    background: 'rgba(0,0,0, 0.5)'
                }
            }).inject( document.body );

            if ( this.$selected.length > 1 ) {
                this.$Drag.set('html', this.$selected.length +' Elemente');
            }

            // set ids as data-ids
            var ids = [];

            for ( i = 0, len = this.$selected.length; i < len; i++ ) {
                ids.push( this.$selected[ i ].get('data-id') );
            }

            if ( !ids.length ) {
                ids.push( Elm.get('data-id') );
            }

            this.$Drag.set( 'data-ids', ids.join() );


            // set the drag&drop events to the shadow element
            this.$Drag.addEvent('mouseup', function()
            {
                this.$dragStop();
            }.bind( this ));

            this.$Drag.focus();

            // mootools draging
            new Drag.Move(this.$Drag, {

                droppables : [ '[data-type="folder"]', '.media-drop' ],
                onComplete : this.$dragComplete.bind( this ),
                onDrop     : this.$drop.bind( this ),

                onEnter : function(element, Droppable)
                {
                    this.$dragEnter(false, Droppable);
                }.bind(this),

                onLeave : function(element, Droppable)
                {
                    this.$dragLeave(false, Droppable);
                }.bind(this)

            }).start({
                page: {
                    x : mx,
                    y : my
                }
            });
        },

        /**
         * If the DragDrop was dropped to a droppable element
         *
         * @param {DOMNode} Element   - the dropabble element (media item div)
         * @param {DOMNode} Droppable - drop box element (folder)
         * @param {DOMEvent} event
         */
        $drop : function(Element, Droppable, event)
        {
            if ( !Droppable ) {
                return;
            }

            if ( Droppable.hasClass( 'media-drop' ) )
            {
                var Control = QUI.Controls.getById(
                    Droppable.get( 'data-quiid' )
                );

                if ( !Control ) {
                    return;
                }


                var items   = [],
                    ids     = Element.get( 'data-ids' ),
                    Media   = this.getMedia(),
                    Project = Media.getProject(),

                    lang    = Project.getLang(),
                    project = Project.getName();


                ids = ids.split( ',' );

                for ( var i = 0, len = ids.length; i < len; i++ )
                {
                    items.push({
                        id      : ids[ i ],
                        Media   : Media,
                        project : project,
                        url     : 'image.php?qui=1&id='+ ids[ i ] +'&project='+ project
                    });
                }

                Control.fireEvent( 'drop', [ items ] );

                return;
            }

            this.$ContextMenu.showDragDropMenu( Element, Droppable, event );
        },

        /**
         * Stops the Drag Drop
         *
         * @param {DOMEvent} event
         */
        $dragStop : function(event)
        {
            if ( Browser.ie8 ) {
                return;
            }

            // Wenn noch kein mousedown drag getätigt wurde
            // mousedown "abbrechen" und onclick ausführen
            if ( !this.getAttribute('_mousedown') )
            {
                this.setAttribute('_stopdrag', true);
                return;
            }

            this.setAttribute('_mousedown', false);

            if ( typeof this.$lastDroppable !== 'undefined' ) {
                this.$dragLeave( false, this.$lastDroppable );
            }

            if ( typeof this.$Drag !== 'undefined' || this.$Drag )
            {
                this.$Drag.destroy();
                this.$Drag = null;
            }

            this.unselectItems();
        },

        /**
         * if drag drop is complete
         *
         * @param {DOMEvent} event
         */
        $dragComplete : function(event)
        {
            this.fireEvent('dragDropComplete', [this, event]);
            this.$dragStop();
        },

        /**
         * on drag enter
         *
         * @param {DOMEvent} event
         * @param {DOMNode} Elm -> node for dropable
         */
        $dragEnter : function(event, Elm)
        {
            if ( !Elm ) {
                return;
            }

            if ( Elm.hasClass( 'media-drop' ) )
            {
                var Control = QUI.Controls.getById(
                    Elm.get( 'data-quiid' )
                );

                if ( !Control ) {
                    return;
                }

                Control.fireEvent( 'dragEnter' );

                return;
            }

            // Dragdrop to the main folder
            if ( Elm.hasClass( 'qui-media-content' ) )
            {
                if ( typeof this.$lastDroppable !== 'undefined' ) {
                    this.$dragLeave( event, this.$lastDroppable );
                }

                this.$lastDroppable = Elm;

                Elm.addClass( 'qui-media-content-ondragdrop' );

                return;
            }


            if ( !Elm.hasClass( 'qui-media-item' ) ) {
                Elm = Elm.getParent( '.qui-media-item' );
            }

            if ( typeof this.$lastDroppable !== 'undefined' ) {
                this.$dragLeave( event, this.$lastDroppable );
            }

            this.$lastDroppable = Elm;

            Elm.addClass( 'qui-media-item-ondragdrop' );
        },

        /**
         * on drag leave
         *
         * @param {DOMEvent} event
         * @param {DOMNode} Elm -> node for dropable
         */
        $dragLeave : function(event, Elm)
        {
            if ( !Elm ) {
                return;
            }

            if ( Elm.hasClass( 'media-drop' ) )
            {
                var Control = QUI.Controls.getById(
                    Elm.get( 'data-quiid' )
                );

                if ( !Control ) {
                    return;
                }

                Control.fireEvent( 'dragLeave' );

                return;
            }

            var Parent = Elm.getParent();

            if ( Parent &&
                 Parent.hasClass('qui-media-item-ondragdrop') &&
                 !Parent.hasClass('qui-media-content') )
            {
                return;
            }

            if ( Elm.hasClass('qui-media-content') )
            {
                Elm.removeClass('qui-media-content-ondragdrop');
                return;
            }

            if ( !Elm.hasClass('qui-media-item') ) {
                Elm = Elm.getParent('.qui-media-item');
            }

            if ( !Elm ) {
                return;
            }

            Elm.removeClass('qui-media-item-ondragdrop');
            Elm.removeClass('qui-media-content-ondragdrop');
        }

    });

    return QUI.controls.projects.media.Panel;
});