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
 * @requires controls/projects/project/media/PanelDOMEvents
 * @requires controls/projects/project/media/PanelContextMenu
 * @requires controls/upload/Form
 *
 * @event onDragDropComplete [this, event]
 *
 * @module controls/projects/project/media/Panel
 */

define('controls/projects/project/media/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'classes/projects/project/Media',
    'controls/projects/project/media/Sitemap',
    'classes/projects/project/media/panel/DOMEvents',
    'classes/projects/project/media/panel/ContextMenu',
    'qui/controls/breadcrumb/Item',

    //'controls/upload/Form',
    //'classes/request/Upload',

    'css!controls/projects/project/media/Panel.css'

], function(QUI, QUIPanel, Media, MediaSitemap, PanelDOMEvents, PanelContextMenu, BreadcrumbItem)
{
    "use strict";

    /**
     * A Media-Panel, opens the Media in an Apppanel
     *
     * @class QUI.controls.projects.media.Panel
     *
     * @param {QUI.classes.projects.Media} Media
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIPanel,
        Type    : 'controls/projects/project/media/Panel',

        Binds : [
            '$onCreate'
        ],

        options : {
            id         : 'projects-media-panel',
            container  : false,
            startid    : false,
            view       : 'symbols',    // available views are: symbols, details, preview
            fileid     : false,        // the current folder id
            breadcrumb : true,

            title : '',
            icon  : '',

            field : 'name',
            order : 'ASC',
            limit : 20,
            page  : 1
        },

        initialize : function(Media, options)
        {
            // defaults
            this.setAttribute( 'id', 'projects-media-panel' );
            this.setAttribute( 'name', 'projects-media-panel' );

            this.setAttribute(
                'title',
                Media.getProject().getName()
            );

            this.setAttribute( 'icon', 'icon-picture' );

//            var view = QUI.Storage.get( 'qui-media-panel-view' );
//
//            if ( view ) {
//                this.setAttribute('view', view);
//            }

            this.parent( options );

            this.$Map      = null;
            this.$Media    = Media;
            this.$File     = null;
            this.$children = [];
            this.$selected = [];

            this.$DOMEvents   = new PanelDOMEvents( this );
            this.$ContextMenu = new PanelContextMenu( this );

            this.addEvents({
                onCreate : this.$onCreate
            });
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
//        create : function()
//        {
//            var Panel = new QUI.controls.desktop.Panel({
//                id         : this.getAttribute('id'),
//                icon       : URL_BIN_DIR +'images/loader.gif',
//                tabbar     : false,
//                breadcrumb : true,
//                events : {
//                    onContextMenu : this.$ContextMenu.createPanelMenu.bind( this )
//                }
//            });
//
//            QUI.Controls.getByType( 'QUI.controls.desktop.Tasks' )[0].appendChild(
//                Panel
//            );
//
//            this.$Panel = Panel;
//            this.$Panel.Loader.show();
//            this.load();
//        },

        /**
         * Load the Media and the Tabs to the Panel
         *
         * @method QUI.controls.projects.media.Panel#load
         */
        $onCreate : function()
        {
            this.Loader.show();

            // blur event
            var self = this,
                Body = this.getContent();

            Body.addEvent('click', function() {
                self.unselectItems();
            });


            // buttons
            require([

                'qui/controls/buttons/Button',
                'qui/controls/buttons/Seperator',
                'qui/controls/contextmenu/Item'

            ], function(QUIButton, QUISeperator, ContextmenuItem)
            {
                self.addButton(
                    new QUIButton({
                        name    : 'left-sitemap-media-button',
                        image   : 'icon-sitemap',
                        alt     : 'Sitemap anzeigen',
                        title   : 'Sitemap anzeigen',
                        events  :
                        {
                            onClick : function(Btn)
                            {
                                if ( Btn.isActive() )
                                {
                                    self.hideSitemap();
                                    Btn.setNormal();
                                    return;
                                }

                                self.showSitemap();
                                Btn.setActive();
                            }
                        }
                    })
                );

                self.addButton(
                    new QUISeperator()
                );

                // views
                var View = new QUIButton({
                    textimage : 'icon-th',
                    text      : 'Ansicht: Symbolansicht',
                    change    : function(Item)
                    {
                        var Btn = Item.getAttribute('Button');

                        Btn.setAttribute('Active', Item);
                        Btn.setAttribute('text', 'Ansicht: '+ Item.getAttribute('text'));
                        Btn.setAttribute('textimage', Item.getAttribute('icon'));

                        self.setAttribute('view', Item.getAttribute('name'));
                        self.$view( self.$children );

                        Btn.getParent().resize();
                    }
                });

                View.appendChild(
                    new ContextmenuItem({
                        name   : 'symbols',
                        text   : 'Symbolansicht',
                        icon   : 'icon-th',
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
                    new ContextmenuItem({
                        name   : 'details',
                        text   : 'Detailsansicht',
                        icon   : 'icon-list-alt',
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
                    new ContextmenuItem({
                        name   : 'preview',
                        text   : 'Vorschau',
                        icon   : 'icon-eye-open',
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

                self.addButton( View );

                self.addButton(
                    new QUISeperator()
                );

                self.addButton(
                    new QUIButton({
                        name      : 'create_folder',
                        text      : 'Neuen Ordner erstellen',
                        textimage : 'icon-folder-open-alt',
                        events    :
                        {
                            onClick : function(Item) {
                                self.createFolder();
                            }
                        }
                    })
                );

                // Upload
                var Upload = new QUIButton({
                    textimage : 'icon-upload',
                    text      : 'Dateien hochladen',
                    Control   : this
                });

                Upload.appendChild(
                    new ContextmenuItem({
                        name   : 'upload_files',
                        text   : 'Dateien hochladen',
                        icon   : 'icon-file',
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
                    new ContextmenuItem({
                        name   : 'upload_archive',
                        text   : 'Archiv hochladen und entpacken',
                        icon   : 'icon-archive',
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

                self.addButton( Upload );


                //this.$Panel.Buttons.resize();

                if ( self.getAttribute('startid') )
                {
                    self.openID( self.getAttribute('startid') );
                    return;
                }

                self.openID( 1 );
            });

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
            var self    = this,
                Project = this.$Media.getProject();

            this.Loader.show();


            // set loader image
            this.setOptions({
                icon  : 'icon-spinner icon-spin',
                title : ' Media ('+ Project.getName() +')'
            });

            // this.refresh();

            // get the file object
            this.getMedia().get(fileid, function(MediaFile, Request)
            {
                // set media image to the panel
                self.setOptions({
                    icon  : 'icon-picture',
                    title : ' Media ('+ Project.getName() +')'
                });

                //self.refresh();
                self.$File = MediaFile;

                // if the MediaFile is no Folder
                if ( MediaFile.getType() !== 'classes/projects/project/media/Folder' )
                {
                    require([
                        'controls/projects/project/media/FilePanel'
                    ], function(FilePanel)
                    {
                        new FilePanel( MediaFile ).inject(
                            self.getParent()
                        );

                        self.Loader.hide();
                    });

                    return;
                }

                self.setAttribute( 'fileid', MediaFile.getId() );

                // load children
                MediaFile.getChildren(function(children, Request)
                {
                    self.$children = children;
                    self.$view( children );

                    // load breadcrumb
                    self.$File.getBreadcrumb(function(result, Request)
                    {
                        self.$createBreadCrumb( result );

                        // select active item, if map is open
                        if ( self.$Map ) {
                            self.$Map.selectFolder( MediaFile.getId() );
                        }

                        self.Loader.hide();
                    });
                });
            });
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

            var Body  = this.getContent(),
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
            var Body      = this.getContent(),
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
                    var Body  = this.getContent(),
                        Items = Body.getElement('.qui-media-content');

                    Container.destroy();

                    Items.setStyles({
                        width      : '100%',
                        marginLeft : null
                    });

                    var Btn = this.getButtons( 'left-sitemap-media-button' );

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

            this.openSheet(function(Sheet, Content, Buttons)
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
                    Media  : this.getMedia(),
                    Drops  : [Sheet],
                    Panel  : this,
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
            var Body      = this.getContent(),
                Container = Body.getElement( '.qui-media-sitemap' );

            if ( !Container ) {
                return;
            }

            var self    = this,
                Project = this.getMedia().getProject();

            this.$Map = new MediaSitemap({
                project : Project.getName(),
                lang    : Project.getLang(),
                id      : this.getAttribute( 'startid' ),
                events  :
                {
                    onItemClick : function(Item, Sitemap)
                    {
                        self.openID(
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
                var Breadcrumb = self.getBreadcrumb(),
                    Last       = Breadcrumb.lastChild();

                MapControl.selectFolder( Last.getAttribute( 'id' ) );

            });
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

            var self       = this,
                Breadcrumb = this.getBreadcrumb(),

                func_open = function(Item, event) {
                    self.openID( Item.getAttribute( 'id' ) );
                };

            Breadcrumb.clear();

            for ( i = 0, len = items.length; i < len; i++ )
            {
                Item = new BreadcrumbItem({
                    text : items[i].name,
                    id   : items[i].id
                });

                Item.addEvents({
                    onClick : func_open
                });

                if ( items[ i ].icon ) {
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
            var Body  = this.getContent(),
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
            var Body     = this.getContent(),
                Media    = this.$Media,
                Project  = Media.getProject(),
                droplist = [],
                project  = Project.getName(),

                Breadcrumb = this.Breadcrumb;

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

//            QUI.Storage.set(
//                'qui-media-panel-view',
//                this.getAttribute('view')
//            );


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
//            @todo upload
//            new QUI.classes.request.Upload(droplist, {
//
//                onDragenter: function(event, Elm, Upload)
//                {
//                    this.$dragEnter( event, Elm );
//
//                    event.stop();
//                }.bind( this ),
//
//                onDragend : function(event, Elm, Upload)
//                {
//                    this.$dragLeave( event, Elm );
//
//                    event.stop();
//                }.bind( this ),
//
//                onDrop : this.$viewOnDrop.bind( this )
//            });
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
                project  = Project.getName();

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
                project  = Project.getName();

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
                    this.getBreadcrumb().getChildren()
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
            var self = this;

            require(['qui/controls/windows/Prompt'], function(Prompt)
            {
                new Prompt({
                    title       : 'Neuer Ordner anlegen',
                    titleicon   : 'icon-folder-open-alt',
                    information : 'Geben Sie bitte den neuen Ordnernamen ein',
                    icon        : 'icon-folder-open-alt',
                    maxHeight   : 280,
                    maxWidth    : 500,
                    events      :
                    {
                        onSubmit : function(value, Win)
                        {
                            self.$File.createFolder(
                                value,
                                function(Folder, Request)
                                {
                                    if ( Folder ) {
                                        self.openID( Folder.getId() );
                                    }
                                }
                            );
                        }
                    }
                }).open()
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

            var self = this;

            self.Loader.show();

            Ajax.post('ajax_media_copy', function(result, Request)
            {
                self.Loader.hide();

                // we need no reload of the folder
            }, {
                project : this.$Media.getProject().getName(),
                to      : folderid,
                ids     : JSON.encode( ids )
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

            var self = this;

            self.Loader.show();

            Ajax.post('ajax_media_move', function(result, Request)
            {
                self.openID( self.getAttribute('fileid') );
            }, {
                project : this.$Media.getProject().getName(),
                to      : folderid,
                ids     : JSON.encode( ids )
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

            if ( this.getAttribute( '_mousedown' ) ) {
                return;
            }

            if ( this.getAttribute( '_stopdrag' ) ) {
                return;
            }

            this.setAttribute('_mousedown', true);

            var i, len, ElmSize;

            var self = this,
                mx   = event.page.x,
                my   = event.page.y,
                Elm  = event.target;

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
            this.$Drag.addEvent('mouseup', function() {
                self.$dragStop();
            });

            this.$Drag.focus();

            // mootools draging
            new Drag.Move(this.$Drag, {

                droppables : [ '[data-type="folder"]', '.media-drop' ].join(','),
                onComplete : this.$dragComplete.bind( this ),
                onDrop     : this.$drop.bind( this ),

                onEnter : function(element, Droppable) {
                    self.$dragEnter(false, Droppable);
                },

                onLeave : function(element, Droppable) {
                    self.$dragLeave(false, Droppable);
                }

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
                this.setAttribute( '_stopdrag', true );
                return;
            }

            this.setAttribute( '_mousedown', false );

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
        $dragComplete : function(event, Element, Droppable)
        {
            this.fireEvent( 'dragDropComplete', [ this, event ] );
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

                if ( typeof Control.highlight !== 'undefined' ) {
                    Control.highlight();
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

                if ( typeof Control.normalize !== 'undefined' ) {
                    Control.normalize();
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
});