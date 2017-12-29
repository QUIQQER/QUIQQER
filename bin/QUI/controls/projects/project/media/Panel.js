/**
 * Displays a Media in a Panel
 *
 * @module controls/projects/project/media/Panel
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/desktop/Panel
 * @require classes/projects/project/Media
 * @require controls/projects/project/media/Sitemap
 * @require classes/projects/project/media/panel/DOMEvents
 * @require classes/projects/project/media/panel/ContextMenu
 * @require qui/controls/breadcrumb/Item
 * @require controls/grid/Grid
 * @require controls/upload/Form
 * @require classes/request/Upload
 * @require Ajax
 * @require Locale
 * @require utils/Media
 * @require Projects
 * @require css!controls/projects/project/media/Panel.css
 *
 * @event onDragDropComplete [this, event]
 * @event childClick [ this, imageData ]
 */
define('controls/projects/project/media/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'classes/projects/project/Media',
    'controls/projects/project/media/Sitemap',
    'classes/projects/project/media/panel/DOMEvents',
    'classes/projects/project/media/panel/ContextMenu',
    'qui/controls/breadcrumb/Item',
    'controls/grid/Grid',
    'controls/upload/Form',
    'classes/request/Upload',
    'Ajax',
    'Locale',
    'utils/Media',
    'Projects',

    'css!controls/projects/project/media/Panel.css'

], function () {
    "use strict";

    var lg = 'quiqqer/system';

    var QUI              = arguments[0],
        QUIPanel         = arguments[1],
        Media            = arguments[2],
        MediaSitemap     = arguments[3],
        PanelDOMEvents   = arguments[4],
        PanelContextMenu = arguments[5],
        BreadcrumbItem   = arguments[6],
        GridControl      = arguments[7],
        UploadForm       = arguments[8],
        RequestUpload    = arguments[9],
        Ajax             = arguments[10],
        Locale           = arguments[11],
        MediaUtils       = arguments[12],
        Projects         = arguments[13];

    /**
     * A Media-Panel, opens the Media in an Apppanel
     *
     * @class controls/projects/project/media/Panel
     *
     * @param {Object} Media - classes/projects/project/Media
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIPanel,
        Type   : 'controls/projects/project/media/Panel',

        Binds: [
            '$onCreate',
            '$viewOnDrop',
            '$itemEvent',
            '$onFilter',
            'unselectItems',
            '$onContextMenu'
        ],

        options: {
            id        : 'projects-media-panel',
            container : false,
            startid   : false,
            view      : 'symbols',    // available views are: symbols, details, preview
            fileid    : false,        // the current folder id
            breadcrumb: true,

            title: '',
            icon : '',

            field: 'name',
            order: 'ASC',
            limit: 20,
            page : 1,

            selectable          : false,    // is the media in the selectable mode (for popup or image inserts)
            selectable_types    : false,    // {Array} you can specified which types are selectable (folder, image, file, *)
            selectable_mimetypes: false,    // {Array} you can specified which mime types are selectable
            selectable_multiple : false     // multiple selection active? press ctrl / strg,
        },

        initialize: function (Media, options) {
            // defaults
            this.setAttribute('id', 'projects-media-panel');
            this.setAttribute('name', 'projects-media-panel');

            // bugfix for selectable_multiple / selectable_multible
            if (typeof options !== 'undefined' &&
                typeof options.selectable_multible !== 'undefined' &&
                typeof options.selectable_multiple === 'undefined'
            ) {
                options.selectable_multiple = options.selectable_multible;
                delete options.selectable_multible;
            }

            if (typeOf(Media) === 'object') {
                this.parent(options);
            }

            if (typeOf(Media) === 'classes/projects/project/Media') {
                this.setAttribute('title', Media.getProject().getName());
            }

            this.setAttribute('icon', 'fa fa-picture-o');
            this.parent(options);

            this.$Map    = null;
            this.$Media  = Media || null;
            this.$File   = null;
            this.$Filter = null;

            this.$children = [];
            this.$selected = [];

            this.$DOMEvents        = new PanelDOMEvents(this);
            this.$PanelContextMenu = new PanelContextMenu(this);

            this.addEvents({
                onCreate : this.$onCreate,
                onResize : this.$onResize,
                onDestroy: function () {
                    this.$Media.removeEvent('onItemRename', this.$itemEvent);
                    this.$Media.removeEvent('onItemActivate', this.$itemEvent);
                    this.$Media.removeEvent('onItemDeactivate', this.$itemEvent);
                    this.$Media.removeEvent('onItemRefresh', this.$itemEvent);
                    this.$Media.removeEvent('onItemSave', this.$itemEvent);
                    this.$Media.removeEvent('onItemDelete', this.$itemEvent);
                }.bind(this)
            });

            // media events
            if (typeOf(this.$Media) === 'classes/projects/project/Media') {
                this.$Media.addEvents({
                    onItemRename    : this.$itemEvent,
                    onItemActivate  : this.$itemEvent,
                    onItemDeactivate: this.$itemEvent,
                    onItemRefresh   : this.$itemEvent,
                    onItemSave      : this.$itemEvent,
                    onItemDelete    : this.$itemEvent
                });
            }
        },

        /**
         * Save the site panel to the workspace
         *
         * @method controls/projects/project/site/Panel#serialize
         * @return {Object} data
         */
        serialize: function () {
            return {
                attributes: this.getAttributes(),
                project   : this.$Media.getProject().getName(),
                type      : this.getType()
            };
        },

        /**
         * import the saved data form the workspace
         *
         * @method controls/projects/project/site/Panel#unserialize
         * @param {Object} data
         * @return {Object} this (controls/projects/project/site/Panel)
         */
        unserialize: function (data) {
            var Project = Projects.get(data.project);

            this.setAttributes(data.attributes);
            this.$Media = Project.getMedia();

            // media events
            this.$Media.addEvents({
                onItemRename    : this.$itemEvent,
                onItemActivate  : this.$itemEvent,
                onItemDeactivate: this.$itemEvent,
                onItemRefresh   : this.$itemEvent,
                onItemSave      : this.$itemEvent,
                onItemDelete    : this.$itemEvent
            });

            return this;
        },

        /**
         * Close and destroy the media panel
         *
         * @method controls/projects/project/media/Panel#close
         */
        close: function () {
            this.destroy();
        },

        /**
         * event: on resize
         */
        $onResize: function () {
            if (!this.getElm()) {
                return;
            }

            var Omnigrid  = this.getBody().getElement('.omnigrid');
            var Container = this.getBody().getElement('.qui-media-content');

            if (!Omnigrid) {
                return;
            }

            var Grid = QUI.Controls.getById(Omnigrid.get('data-quiid'));

            if (Grid) {
                Grid.setHeight(Container.getSize().y - 10);
            }
        },

        /**
         * Load the Media and the Tabs to the Panel
         *
         * @method controls/projects/project/media/Panel#load
         */
        $onCreate: function () {
            this.Loader.show();

            // blur event
            var self = this,
                Body = this.getContent();

            Body.addEvent('click', this.unselectItems);
            Body.addEvent('contextmenu', this.$onContextMenu);

            // buttons
            require([
                'qui/controls/buttons/Button',
                'qui/controls/buttons/Separator',
                'qui/controls/contextmenu/Item'
            ], function (QUIButton, QUISeparator, ContextmenuItem) {
                self.addButton(
                    new QUIButton({
                        name  : 'left-sitemap-media-button',
                        image : 'fa fa-sitemap',
                        alt   : Locale.get(lg, 'projects.project.site.media.panel.btn.sitemap.show'),
                        title : Locale.get(lg, 'projects.project.site.media.panel.btn.sitemap.show'),
                        events: {
                            onClick: function (Btn) {
                                if (Btn.isActive()) {
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
                    new QUISeparator()
                );

                // views
                var View = new QUIButton({
                    textimage: 'fa fa-th',
                    name     : 'view',
                    text     : '',
                    methods  : {
                        change: function (Item) {
                            var Btn = Item.getAttribute('Button');

                            var viewText = Locale.get(
                                lg, 'projects.project.site.media.panel.btn.view.title'
                            );

                            viewText = viewText + ' ' + Item.getAttribute('text');

                            Btn.setAttribute('Active', Item);
                            Btn.setAttribute('text', viewText);
                            Btn.setAttribute('textimage', Item.getAttribute('icon'));

                            self.setAttribute('view', Item.getAttribute('name'));
                            self.$view(self.$children);

                            Btn.getParent().resize();
                        }
                    }
                });

                View.appendChild(
                    new ContextmenuItem({
                        name  : 'symbols',
                        text  : Locale.get(lg, 'projects.project.site.media.panel.btn.view.symbols'),
                        icon  : 'fa fa-th',
                        events: {
                            onMouseDown: function (Item) {
                                View.change(Item);
                            }
                        }
                    })
                ).appendChild(
                    new ContextmenuItem({
                        name  : 'details',
                        text  : Locale.get(lg, 'projects.project.site.media.panel.btn.view.details'),
                        icon  : 'fa fa-list-alt',
                        events: {
                            onMouseDown: function (Item) {
                                View.change(Item);
                            }
                        }
                    })
                ).appendChild(
                    new ContextmenuItem({
                        name  : 'preview',
                        text  : Locale.get(lg, 'projects.project.site.media.panel.btn.view.preview'),
                        icon  : 'fa fa-eye',
                        events: {
                            onMouseDown: function (Item) {
                                View.change(Item);
                            }
                        }
                    })
                );

                self.addButton(View);

                View.getContextMenu(function (Menu) {
                    var Item = false,
                        view = QUI.Storage.get('qui-media-panel-view');

                    if (!view) {
                        view = self.getAttribute('view');
                    }

                    if (view) {
                        Item = Menu.getChildren(view);
                    }

                    if (!Item) {
                        Item = Menu.firstChild();
                    }

                    View.change(Item);
                });


                self.addButton(
                    new QUISeparator()
                );

                self.addButton(
                    new QUIButton({
                        name     : 'create_folder',
                        text     : Locale.get(lg, 'projects.project.site.media.panel.btn.create'),
                        textimage: 'fa fa-folder-open-o',
                        events   : {
                            onClick: function () {
                                self.createFolder();
                            }
                        }
                    })
                );

                // Upload
                var Upload = new QUIButton({
                    name     : 'upload',
                    textimage: 'fa fa-upload',
                    text     : Locale.get(lg, 'projects.project.site.media.panel.btn.upload')
                });

                Upload.appendChild(
                    new ContextmenuItem({
                        name  : 'upload_files',
                        text  : Locale.get(lg, 'projects.project.site.media.panel.btn.upload.files'),
                        icon  : 'fa fa-file',
                        events: {
                            onMouseDown: function () {
                                self.uploadFiles();
                            }
                        }
                    })
                ).appendChild(
                    new ContextmenuItem({
                        name  : 'upload_archive',
                        text  : Locale.get(lg, 'projects.project.site.media.panel.btn.upload.archive'),
                        icon  : 'fa fa-archive',
                        events: {
                            onMouseDown: function () {
                                self.uploadArchive();
                            }
                        }
                    })
                );

                self.addButton(Upload);

                self.$Filter = new Element('input', {
                    placeholder: 'Filter...',
                    styles     : {
                        'float' : 'right',
                        margin  : 10,
                        maxWidth: '100%',
                        width   : 200
                    },
                    events     : {
                        keyup: self.$onFilter
                    }
                });

                self.addButton(self.$Filter);

                // show project select
                if (self.getAttribute('isInPopup')) {
                    var Breadcrumb = self.getElm().getElement('.qui-panel-breadcrumb');

                    require([
                        'controls/projects/Select'
                    ], function (ProjectSelect) {
                        self.getBreadcrumb().getElm().setStyles({
                            clear: 'none'
                        });

                        Breadcrumb.setStyles({
                            paddingLeft: 0
                        });

                        new ProjectSelect({
                            langSelect : false,
                            emptyselect: false,
                            styles     : {
                                border      : 'none',
                                borderRight : '1px solid #dedede',
                                borderRadius: 0,
                                margin      : '4px 0 0',
                                paddingRight: 10,
                                width       : 'inherit'
                            },
                            events     : {
                                onChange: function (value) {
                                    if (self.$Media && self.$Media.getProject() &&
                                        self.$Media.getProject() === value) {
                                        return;
                                    }

                                    var Project = Projects.get(value);
                                    self.$Media = Project.getMedia();
                                    self.openID(1);
                                }
                            }
                        }).inject(Breadcrumb, 'top');
                    });
                }

                if (self.getAttribute('startid')) {
                    self.openID(self.getAttribute('startid'));
                    return;
                }

                // cached id?
                var Project    = self.$Media.getProject();
                var cacheMedia = Project.getName() + '-' + Project.getLang() + '-id';

                if (QUI.Storage.get(cacheMedia)) {
                    self.openID(QUI.Storage.get(cacheMedia));
                    return;
                }

                self.openID(1);
            });
        },

        /**
         * event on context menu
         *
         * @param {Event} event
         */
        $onContextMenu: function (event) {
            if (this.getAttribute('view') !== 'symbols' &&
                this.getAttribute('view') !== 'preview') {
                return;
            }

            event.stop();

            this.$PanelContextMenu.showMediaMenu(event);
        },

        unload: function () {

        },

        /**
         * Refresh the Panel
         *
         * @method controls/projects/project/media/Panel#openID
         */
        refresh: function () {
            if (this.getAttribute('fileid')) {
                this.openID(this.getAttribute('fileid'));
                return;
            }

            this.openID(1);
        },

        /**
         * Opens the file and load the breadcrumb
         *
         * @method controls/projects/project/media/Panel#openID
         * @param {Number} fileid
         */
        openID: function (fileid) {
            var self    = this,
                Project = this.$Media.getProject();

            this.Loader.show();

            if (this.$Filter) {
                this.$Filter.value = '';
            }

            // set loader image
            this.setOptions({
                icon : 'fa fa-spinner fa-spin',
                title: ' Media (' + Project.getName() + ')'
            });


            // set cache
            QUI.Storage.set(
                Project.getName() + '-' + Project.getLang() + '-id',
                fileid
            );

            this.setAttribute('startid', fileid);

            return new Promise(function (resolve) {

                // get the file object
                self.getMedia().get(fileid).then(function (MediaFile) {
                    // set media image to the panel
                    self.setOptions({
                        icon : 'fa fa-picture-o',
                        title: ' Media (' + Project.getName() + ')'
                    });

                    //self.refresh();
                    self.$File = MediaFile;

                    // if the MediaFile is no Folder
                    if (MediaFile.getType() !== 'classes/projects/project/media/Folder') {

                        require([
                            'controls/projects/project/media/FilePanel'
                        ], function (FilePanel) {
                            new FilePanel(MediaFile).inject(
                                self.getParent()
                            );

                            self.Loader.hide();
                        });

                        // open parent-id
                        MediaFile.getParentId().then(function (parentId) {
                            self.openID(parentId).then(resolve);
                        });

                        return;
                    }

                    self.setAttribute('fileid', MediaFile.getId());

                    // load children
                    MediaFile.getChildren(function (children) {
                        self.$children = children;
                        self.$view(children);

                        // load breadcrumb
                        self.$File.getBreadcrumb(function (result) {
                            self.$createBreadCrumb(result);

                            // select active item, if map is open
                            if (self.$Map) {
                                self.$Map.selectFolder(MediaFile.getId());
                            }

                            resolve();
                            self.Loader.hide();
                        });
                    }, {
                        order: self.getAttribute('field') + ' ' + self.getAttribute('order')
                    });
                }).catch(function () {
                    self.openID(1).then(resolve);
                });
            });
        },

        /**
         * Return the Media object of the panel
         *
         * @return {Object} Media - classes/projects/project/Media
         */
        getMedia: function () {
            return this.$Media;
        },

        /**
         * Return the Project object of the Media
         *
         * @return {Object} Project - classes/projects/Project
         */
        getProject: function () {
            return this.$Media.getProject();
        },

        /**
         * Return the current displayed media folder
         *
         * @return {Object} Folder - classes/projects/project/media/Folder
         */
        getCurrentFile: function () {
            return this.$File;
        },

        /**
         * Create the left Sitemap for the panel and show it
         *
         * @method controls/projects/project/media/Panel#showSitemap
         */
        showSitemap: function () {
            var Container;

            var self  = this,
                Body  = this.getContent(),
                Items = Body.getElement('.qui-media-content');

            if (!Body.getElement('.qui-media-sitemap')) {
                new Element('div', {
                    'class': 'qui-media-sitemap shadow',
                    styles : {
                        left    : -350,
                        position: 'absolute'
                    }
                }).inject(Body, 'top');
            }

            Container = Body.getElement('.qui-media-sitemap');

            Items.setStyles({
                width     : Body.getSize().x - 350,
                marginLeft: 300
            });

            moofx(Container).animate({
                left: 0
            }, {
                callback: function () {
                    self.$createSitemap();
                    self.$resizeSheet();

                    new Element('div', {
                        'class': 'qui-media-sitemap-handle columnHandle',
                        styles : {
                            position: 'absolute',
                            top     : 0,
                            right   : 0,
                            height  : '100%',
                            width   : 4,
                            cursor  : 'pointer'
                        },
                        events : {
                            click: function () {
                                self.hideSitemap();
                            }
                        }
                    }).inject(Body.getElement('.qui-media-sitemap'));
                }
            });
        },

        /**
         * Hide the Sitemap
         *
         * @method controls/projects/project/media/Panel#hideSitemap
         */
        hideSitemap: function () {
            var self      = this,
                Body      = this.getContent(),
                Container = Body.getElement('.qui-media-sitemap');

            if (this.$Map) {
                this.$Map.destroy();
                this.$Map = null;
            }

            moofx(Container).animate({
                left: -350
            }, {
                callback: function () {
                    var Body  = self.getContent(),
                        Items = Body.getElement('.qui-media-content');

                    Container.destroy();

                    Items.setStyles({
                        width     : '100%',
                        marginLeft: null
                    });

                    var Btn = self.getButtons('left-sitemap-media-button');

                    if (Btn) {
                        Btn.setNormal();
                    }

                    self.$resizeSheet();
                }
            });
        },

        /**
         * Opens the sheet with the upload dialog
         *
         * @method controls/projects/project/media/Panel#uploadFiles
         */
        uploadFiles: function () {
            this.$upload();
        },

        /**
         * Opens the sheet with the upload archive dialog
         *
         * @method controls/projects/project/media/Panel#uploadArchive
         */
        uploadArchive: function () {
            this.$upload(true);
        },

        /**
         * Upload sheet helper
         *
         * @method controls/projects/project/media/Panel#uploadArchive
         *
         * @param {Boolean} [extract] - (optional), extrat = true => archiv upload,
         *                                    extrat = false => standard upload
         */
        $upload: function (extract) {
            var self  = this,
                Sheet = this.createSheet({
                    buttons: false
                });

            self.fireEvent('uploadOpenBegin', [self, Sheet]);

            extract = extract || false;

            Sheet.addEvents({
                onClose: function (Sheet) {
                    Sheet.destroy();
                    self.fireEvent('uploadClose', [self]);
                },
                onOpen : function () {
                    var Parent;
                    var Content = Sheet.getBody();

                    Content.set({
                        html  : '',
                        styles: {
                            padding: 20
                        }
                    });


                    if (extract) {
                        Parent = new Element('div.qui-media-upload', {
                            html: Locale.get(lg, 'projects.project.site.media.panel.upload.extract.text')
                        }).inject(Content);

                    } else {
                        Parent = new Element('div.qui-media-upload', {
                            html: Locale.get(lg, 'projects.project.site.media.panel.upload.text')
                        }).inject(Content);
                    }

                    var height = Content.getSize().y -
                        Parent.getSize().y -
                        80; // 80 = content padding + form margin

                    // upload form
                    var Form = new UploadForm({
                        sendbutton  : true,
                        cancelbutton: true,
                        maxuploads  : 5,
                        styles      : {
                            float : 'left',
                            clear : 'both',
                            height: height,
                            margin: '20px 0 0'
                        },
                        fileid      : self.getAttribute('fileid'),
                        events      : {
                            onDragenter: function (event, Elm) {
                                if (!Elm.hasClass('qui-panel-sheet-body')) {
                                    Elm = Elm.getParent('qui-panel-sheet-body');
                                }

                                if (!Elm || !Elm.hasClass('qui-panel-sheet-body')) {
                                    return;
                                }

                                Elm.addClass('qui-media-drag');
                                event.stop();
                            },

                            onDragleave: function (event, Elm) {
                                if (Elm.hasClass('qui-panel-sheet-body')) {
                                    Elm.removeClass('qui-media-drag');
                                }
                            },

                            onDragend: function (event, Elm) {
                                if (Elm.hasClass('qui-panel-sheet-body')) {
                                    Elm.removeClass('qui-media-drag');
                                }
                            },

                            onBegin: function () {
                                Sheet.hide();
                            },

                            onCancel: function () {
                                Sheet.hide();
                            },

                            onComplete: function () {
                                var panels = QUI.Controls.get('projects-media-panel');

                                for (var i = 0, len = panels.length; i < len; i++) {
                                    panels[i].refresh();
                                }
                            }
                        }
                    });

                    Form.setParam('onfinish', 'ajax_media_upload');
                    Form.setParam('project', self.$Media.getProject().getName());
                    Form.setParam('parentid', self.getAttribute('fileid'));

                    if (extract) {
                        Form.setParam('extract', 1);
                    } else {
                        Form.setParam('extract', 0);
                    }

                    Form.inject(Content);

                    Sheet.focus();
                }
            });

            var showSheet = function () {
                Sheet.show().then(function () {
                    self.fireEvent('uploadOpen', [self, Sheet]);
                });
            };

            if (this.getAttribute('isInPopup')) {
                showSheet.delay(250);
                return;
            }

            showSheet();
        },

        /**
         * Download the file
         *
         * @method controls/projects/project/media/Panel#downloadFile
         * @param {Number} fileid - ID of the file
         */
        downloadFile: function (fileid) {
            this.$Media.get(fileid, function (File) {
                File.download();
            });
        },

        /**
         * Create the Sitemap
         *
         * @method controls/projects/project/media/Panel#$createSitemap
         */
        $createSitemap: function () {
            var Body      = this.getContent(),
                Container = Body.getElement('.qui-media-sitemap');

            if (!Container) {
                return;
            }

            var self    = this,
                Project = this.getMedia().getProject();

            this.$Map = new MediaSitemap({
                project: Project.getName(),
                lang   : Project.getLang(),
                id     : 1,
                events : {
                    onItemClick: function (Item) {
                        self.openID(
                            Item.getAttribute('value')
                        );
                    }
                }
            });

            this.$Map.inject(Container);
            this.$Map.open();

            // open last breadcrumb item in the sitemap
            this.$Map.addEvent('onOpenEnd', function (Item, MapControl) {
                var Breadcrumb = self.getBreadcrumb(),
                    Last       = Breadcrumb.lastChild();

                MapControl.selectFolder(Last.getAttribute('id'));
            });
        },

        /**
         * Create the breadcrumb items for openID method
         *
         * @method controls/projects/project/media/Panel#$createBreadCrumb
         * @params {array} items
         */
        $createBreadCrumb: function (items) {
            var i, len, Item;

            if (this.getAttribute('breadcrumb') === false) {
                return;
            }

            var self       = this,
                Breadcrumb = this.getBreadcrumb(),

                func_open  = function (Item) {
                    self.openID(Item.getAttribute('id'));
                };

            Breadcrumb.clear();

            for (i = 0, len = items.length; i < len; i++) {
                Item = new BreadcrumbItem({
                    text: items[i].name,
                    id  : items[i].id
                });

                Item.addEvents({
                    onClick: func_open
                });

                if (!(items[i].id === 1 && this.getAttribute('isInPopup')) && items[i].icon) {
                    Item.setAttribute('icon', items[i].icon);
                }

                Breadcrumb.appendChild(Item);
            }
        },

        /**
         * Resize the panel sheet, if the sheet exist
         *
         * @method controls/projects/project/media/Panel#$resizeSheet
         */
        $resizeSheet: function () {
            var Body  = this.getContent(),
                Map   = Body.getElement('.qui-media-sitemap'),
                Sheet = Body.getElement('.pannelsheet');

            if (!Sheet) {
                return;
            }

            var PanelContent = Sheet.getElement('.pannelsheet-content'),
                PanelButtons = Sheet.getElement('.pannelsheet-buttons');


            if (!Map) {
                var body_width = Body.getSize().x;

                Sheet.setStyles({
                    'width': body_width,
                    'left' : 0
                });

                PanelContent.setStyle('width', body_width);
                PanelButtons.setStyle('width', body_width);

                return;
            }

            var sheet_size = Sheet.getSize().x,
                map_size   = Map.getSize().x;

            Sheet.setStyles({
                'width': sheet_size - map_size,
                'left' : map_size
            });

            PanelContent.setStyle('width', sheet_size - map_size);
            PanelButtons.setStyle('width', sheet_size - map_size);
        },

        /**
         * List the children with the specific view
         *
         * @method controls/projects/project/media/Panel#$view
         * @params {array} children
         */
        $view: function (children) {
            var self     = this,
                Body     = this.getContent(),
                droplist = [];

            // create the media body
            var MediaBody;

            if (!Body.getElement('.qui-media-content')) {
                MediaBody = new Element('div', {
                    'class': 'qui-media-content box smooth'
                });

                MediaBody.inject(Body);
            }

            MediaBody = Body.getElement('.qui-media-content');
            MediaBody.set({
                'html'     : '',
                'data-id'  : this.getAttribute('fileid'),
                'data-type': 'folder'
            });

            QUI.Storage.set(
                'qui-media-panel-view',
                this.getAttribute('view')
            );


            switch (this.getAttribute('view')) {
                case 'details':
                    droplist = this.$viewDetails(children, MediaBody);
                    break;

                case 'preview':
                    droplist = this.$viewPreview(children, MediaBody);
                    break;

                default:
                case 'symbols':
                    droplist = this.$viewSymbols(children, MediaBody);
            }

            droplist.push(MediaBody);

            this.$onResize();

            // Upload events
            new RequestUpload(droplist, {
                onDragenter: function (event, Elm) {
                    self.$dragEnter(event, Elm);
                    event.stop();
                },

                onDragend: function (event, Elm) {
                    self.$dragLeave(event, Elm);
                    event.stop();
                },

                onDrop: this.$viewOnDrop
            });
        },

        /**
         * OnDrop Event
         *
         * @param {DOMEvent} event         - DragDrop Event
         * @param {HTMLElement|File} files - List of droped files
         * @param {HTMLElement} Elm        - Droped Parent Element
         */
        $viewOnDrop: function (event, files, Elm) {
            if (!files.length) {
                return;
            }

            if (Elm.hasClass('qui-media-content')) {
                this.$PanelContextMenu.showDragDropMenu(files, Elm, event);
                return;
            }

            if (!Elm.hasClass('qui-media-item')) {
                Elm = Elm.getParent('.qui-media-item');
            }

            // drop on a file
            if (!Elm || Elm.get('data-type') !== 'folder') {
                this.$PanelContextMenu.showDragDropMenu(files[0], Elm, event);
                return;
            }

            this.$PanelContextMenu.showDragDropMenu(files, Elm, event);
        },

        /**
         * list the children as symbol icons
         *
         * @method controls/projects/project/media/Panel#$viewSymbols
         * @params {Array} children
         * @params {HTMLElement} Container - Parent Container for the DOMNodes
         * @return {Array} the drop-upload-list
         */
        $viewSymbols: function (children, Container) {
            var i, len, Elm, Child;

            var droplist = [],
                Media    = this.$Media,
                Project  = Media.getProject(),
                project  = Project.getName();

            this.$Filter.setStyle('display', null);

            for (i = 0, len = children.length; i < len; i++) {
                if (i === 0 && children[i].name === '..') {
                    continue;
                }

                Child = children[i];

                Elm = new Element('div', {
                    'data-id'      : Child.id,
                    'data-project' : project,
                    'data-type'    : Child.type,
                    'data-active'  : Child.active ? 1 : 0,
                    'data-error'   : Child.error ? 1 : 0,
                    'data-mimetype': Child.mimetype,

                    'class': 'qui-media-item box smooth',
                    html   : '<span class="title">' + Child.name + '</span>',
                    alt    : Child.name,
                    title  : Child.name,

                    events: {
                        click      : this.$viewSymbolClick.bind(this),
                        dblclick   : this.$viewSymbolDblClick.bind(this),
                        mousedown  : this.$viewSymbolMouseDown.bind(this),
                        mouseup    : this.$dragStop.bind(this),
                        contextmenu: this.$PanelContextMenu.show.bind(this.$PanelContextMenu)
                    }
                });

                // if ( Child.type === 'folder' ) {
                droplist.push(Elm);
                // }

                if (Child.active) {
                    Elm.addClass('qmi-active');
                } else {
                    Elm.addClass('qmi-deactive');
                }

                if (Child.error) {
                    Elm.setStyles({
                        backgroundImage: 'url(' + URL_BIN_DIR + '48x48/file_broken.png)',
                        paddingLeft    : 20
                    });

                    QUI.getMessageHandler(function (MH) {
                        MH.addError(
                            'File is broken #' + Child.id + ' ' + Child.name
                        );
                    });
                } else {
                    Elm.setStyles({
                        backgroundImage: 'url(' + Child.icon80x80 + ')',
                        paddingLeft    : 20
                    });
                }

                Elm.inject(Container);
            }

            return droplist;
        },

        /**
         * list the children with preview icons
         * preview for images
         *
         * @method controls/projects/project/media/Panel#$viewSymbols
         * @params {Array} children
         * @params {HTMLElement} Container - Parent Container for the DOMNodes
         * @return {Array} the drop-upload-list
         */
        $viewPreview: function (children, Container) {
            var i, len, url, Child, Elm;

            var droplist = [],
                Media    = this.$Media,
                Project  = Media.getProject(),
                project  = Project.getName();

            this.$Filter.setStyle('display', null);

            for (i = 0, len = children.length; i < len; i++) {
                if (i === 0 && children[i].name === '..') {
                    continue;
                }

                Child = children[i];

                Elm = new Element('div', {
                    'data-id'      : Child.id,
                    'data-project' : project,
                    'data-type'    : Child.type,
                    'data-active'  : Child.active ? 1 : 0,
                    'data-error'   : Child.error ? 1 : 0,
                    'data-mimetype': Child.mimetype,

                    'class': 'qui-media-item box smooth',
                    html   : '<span class="title">' + Child.name + '</span>',
                    alt    : Child.name,
                    title  : Child.name,

                    events: {
                        click      : this.$viewSymbolClick.bind(this),
                        dblclick   : this.$viewSymbolDblClick.bind(this),
                        mousedown  : this.$viewSymbolMouseDown.bind(this),
                        mouseup    : this.$dragStop.bind(this),
                        contextmenu: this.$PanelContextMenu.show.bind(this.$PanelContextMenu)
                    }
                });

                droplist.push(Elm);

                Elm.setStyles({
                    backgroundImage: 'url(' + Child.icon80x80 + ')',
                    paddingLeft    : 20
                });

                if (Child.error) {
                    Elm.setStyles({
                        backgroundImage: 'url(' + URL_BIN_DIR + '48x48/file_broken.png)',
                        paddingLeft    : 20
                    });

                    QUI.getMessageHandler(function (MH) {
                        MH.addError(
                            'File is broken #' + this.id + ' ' + this.name
                        );
                    }.bind(Child));
                }

                if (Child.type === 'image' && !Child.error) {
                    url = URL_DIR + Child.url + '&quiadmin=1';
                    url = url + '&maxheight=80';
                    url = url + '&maxwidth=80';

                    // because of the browser cache
                    if (Child.e_date) {
                        url = url + '&edate=' + Child.e_date.replace(/[^0-9]/g, '');
                    }

                    Elm.setStyles({
                        'backgroundImage'   : 'url(' + url + ')',
                        'backgroundPosition': 'center center'
                    });
                }

                if (Child.active) {
                    Elm.addClass('qmi-active');
                } else {
                    Elm.addClass('qmi-deactive');
                }

                Elm.inject(Container);
            }

            return droplist;
        },

        /**
         * execute a click event on a target media item div
         *
         * @method controls/projects/project/media/Panel#$viewSymbolClick
         * @param {DOMEvent} event
         */
        $viewSymbolClick: function (event) {
            event.stopPropagation();
            event.stop();

            var Target = event.target;

            if (Target.nodeName === 'SPAN') {
                Target = Target.getParent('div');
            }

            if (!this.isItemSelectable(Target)) {
                return;
            }

            if (event.control || event.meta || this.getAttribute('selectable')) {
                if (!Target.hasClass('selected')) {
                    Target.addClass('selected');
                    this.$selected.push(Target);
                } else {
                    Target.removeClass('selected');
                    this.$selected.erase(Target);
                }

                var id      = Target.get('data-id'),
                    project = this.getProject().getName();


                var imageData = {
                    id     : id,
                    project: project,
                    url    : MediaUtils.getUrlByImageParams(id, project),
                    type   : Target.get('data-type')
                };

                this.fireEvent('childClick', [this, imageData]);
                return;
            }

            this.unselectItems();
            this.openID(Target.get('data-id'));
        },

        /**
         * execute a dbl click event on a target media item div
         *
         * @method controls/projects/project/media/Panel#$viewSymbolDblClick
         * @param {DOMEvent} event
         */
        $viewSymbolDblClick: function (event) {
            event.stop();

            this.$dragStop();
        },

        /**
         * execute a mousedown event on a target media item div
         *
         * @method controls/projects/project/media/Panel#$viewSymbolMouseDown
         * @param {DOMEvent} event
         */
        $viewSymbolMouseDown: function (event) {
            event.stop();

            this.setAttribute('_stopdrag', false);
            this.$dragStart.delay(200, this, event); // nach 0.1 Sekunden erst
        },

        /**
         * execute a mouseup event on a target media item div
         *
         * @method controls/projects/project/media/Panel#$viewSymbolMouseUp
         * @param {DOMEvent} event
         */
        $viewSymbolMouseUp: function (event) {
            this.stopDrag(event);
        },

        /**
         * list the children as table
         *
         * @method controls/projects/project/media/Panel#$viewDetails
         *
         * @params {Array} children
         * @params {DOMNode} Container - Parent Container for the DOMNodes
         * @return {Array} the drop-upload-list
         */
        $viewDetails: function (children, Container) {
            Container.set('html', '');

            var self          = this,
                GridContainer = new Element('div');

            GridContainer.inject(Container);

            this.$Filter.setStyle('display', 'none');

            var Grid = new GridControl(GridContainer, {

                columnModel: [{
                    header   : '&nbsp;',
                    dataIndex: 'icon',
                    dataType : 'image',
                    width    : 30
                }, {
                    header   : Locale.get(lg, 'id'),
                    dataIndex: 'id',
                    dataType : 'integer',
                    width    : 50
                }, {
                    header   : Locale.get(lg, 'name'),
                    dataIndex: 'name',
                    dataType : 'string',
                    width    : 150
                }, {
                    header   : Locale.get(lg, 'title'),
                    dataIndex: 'title',
                    dataType : 'string',
                    width    : 150
                }, {
                    header   : Locale.get(lg, 'c_date'),
                    dataIndex: 'c_date',
                    dataType : 'date',
                    width    : 150
                }, {
                    header   : Locale.get(lg, 'c_user'),
                    dataIndex: 'c_user',
                    dataType : 'string',
                    width    : 150
                }, {
                    header   : Locale.get(lg, 'e_date'),
                    dataIndex: 'e_date',
                    dataType : 'date',
                    width    : 150
                }, {
                    header   : Locale.get(lg, 'e_user'),
                    dataIndex: 'e_user',
                    dataType : 'string',
                    width    : 150
                }],

                pagination       : false,
                filterInput      : true,
                perPage          : this.getAttribute('limit'),
                page             : this.getAttribute('page'),
                sortOn           : this.getAttribute('field'),
                sortBy           : this.getAttribute('order'),
                serverSort       : true,
                showHeader       : true,
                sortHeader       : true,
                width            : Container.getSize().x - 100,
                height           : Container.getSize().y - 40,
                onrefresh        : function (me) {
                    var options = me.options;

                    self.setAttribute('field', options.sortOn);
                    self.setAttribute('order', options.sortBy);
                    self.setAttribute('limit', options.perPage);
                    self.setAttribute('page', options.page);

                    self.refresh();
                },
                alternaterows    : true,
                resizeColumns    : true,
                selectable       : true,
                multipleSelection: true,
                resizeHeaderOnly : true
            });

            Grid.addEvents({
                onClick: function (data) {
                    var Grid    = data.target,
                        row     = data.row,
                        rowData = Grid.getDataByRow(row);

                    if (self.getAttribute('selectable') &&
                        self.isItemSelectable(rowData) &&
                        rowData.type !== 'folder' // folder must be openable
                    ) {
                        var GridData = Grid.getDataByRow(row),
                            id       = GridData.id,
                            project  = self.getProject().getName();

                        var imageData = {
                            id     : id,
                            project: project,
                            url    : MediaUtils.getUrlByImageParams(id, project),
                            type   : ''
                        };

                        self.fireEvent('childClick', [self, imageData]);
                        return;
                    }

                    self.openID(Grid.getDataByRow(row).id);
                }
            });

            if (children[0] && children[0].name !== '..') {
                var breadcrumb_list = Array.clone(
                    this.getBreadcrumb().getChildren()
                );

                if (breadcrumb_list.length > 1) {
                    var Last       = breadcrumb_list.pop(),
                        BeforeLast = breadcrumb_list.pop();

                    children.reverse();

                    children.push({
                        icon : 'fa fa-level-up',
                        id   : BeforeLast.getAttribute('id'),
                        name : '..',
                        title: BeforeLast.getAttribute('text')
                    });

                    children.reverse();
                }
            }

            Grid.setData({
                data: children
            });

            return [];
        },

        /**
         * Opens the create folder window
         *
         * @method controls/projects/project/media/Panel#createFolder
         */
        createFolder: function () {
            var self = this;

            require(['qui/controls/windows/Prompt'], function (Prompt) {
                new Prompt({
                    title      : Locale.get(lg, 'projects.project.site.folder.create.title'),
                    titleicon  : 'fa fa-folder-open-o',
                    information: Locale.get(lg, 'projects.project.site.folder.create.information'),
                    icon       : 'fa fa-folder-open-o',
                    maxHeight  : 400,
                    maxWidth   : 600,
                    autoclose  : false,
                    events     : {
                        onSubmit: function (value, Win) {
                            Win.Loader.show();

                            self.$File.createFolder(value).then(function (Folder) {
                                if (typeOf(Folder) === 'classes/projects/project/media/Folder') {
                                    self.openID(Folder.getId());
                                    Win.close();
                                }
                            }).catch(function (Exception) {
                                // nicht erlaubte zeichen
                                if (Exception.getCode() === 702) {
                                    Win.close();
                                    self.createFolderReplaceName(value);
                                }
                            });
                        }
                    }
                }).open();
            });
        },

        /**
         *
         *
         * @method controls/projects/project/media/Panel#createFolderReplaceName
         * @param {String} name
         */
        createFolderReplaceName: function (name) {
            var self = this;

            require(['qui/controls/windows/Confirm'], function (Confirm) {
                new Confirm({
                    title    : Locale.get(lg, 'projects.project.site.folder.createNewName.title'),
                    text     : Locale.get(lg, 'projects.project.site.folder.createNewName.text'),
                    icon     : 'fa fa-folder-open-o',
                    texticon : 'fa fa-folder-open-o',
                    maxHeight: 400,
                    maxWidth : 600,
                    autoclose: false,
                    events   : {
                        onOpen  : function (Win) {
                            Win.Loader.show();

                            Ajax.get('ajax_media_folder_stripName', function (newName) {

                                Win.setAttribute('newName', newName);

                                Win.setAttribute(
                                    'information',
                                    Locale.get(lg, 'projects.project.site.folder.createNewName.information', {
                                        newName: newName
                                    })
                                );

                                Win.Loader.hide();
                            }, {
                                name: name
                            });
                        },
                        onSubmit: function (Win) {
                            Win.Loader.show();

                            var value = Win.getAttribute('newName');

                            self.$File.createFolder(value).then(function (Folder) {
                                if (typeOf(Folder) === 'classes/projects/project/media/Folder') {
                                    self.openID(Folder.getId());
                                }

                                Win.close();
                            }).catch(function () {
                                Win.close();
                            });
                        }
                    }
                }).open();
            });
        },

        /**
         * Activate the media item from the DOMNode
         *
         * @method controls/projects/project/media/Panel#activateItem
         * @param {HTMLElement} DOMNode
         *
         * @deprecated this.$DOMEvents.activate
         */
        activateItem: function (DOMNode) {
            this.$DOMEvents.activate([DOMNode]);
        },

        /**
         * Activate the media items
         *
         * @method controls/projects/project/media/Panel#activateItem
         * @param {Array} DOMNode - List
         *
         * @deprecated this.$DOMEvents.activate
         */
        activateItems: function (DOMNode) {
            this.$DOMEvents.activate(DOMNode);
        },

        /**
         * Deactivate the media item from the DOMNode
         *
         * @method controls/projects/project/media/Panel#deactivateItem
         * @param {Array} DOMNode - List
         *
         * @deprecated this.$DOMEvents.deactivate
         */
        deactivateItem: function (DOMNode) {
            this.$DOMEvents.deactivate([DOMNode]);
        },

        /**
         * Deactivate the media item from the DOMNode
         *
         * @method controls/projects/project/media/Panel#deactivateItem
         * @param {Array} DOMNode - List
         *
         * @deprecated this.$DOMEvents.deactivate
         */
        deactivateItems: function (DOMNode) {
            this.$DOMEvents.deactivate(DOMNode);
        },

        /**
         * Delete the media item from the DOMNode
         *
         * @method controls/projects/project/media/Panel#deleteItem
         * @param {Array|NodeList|HTMLElement} DOMNode - list
         */
        deleteItem: function (DOMNode) {
            this.$DOMEvents.del([DOMNode]);
        },

        /**
         * Delete the media items
         *
         * @method controls/projects/project/media/Panel#deleteItems
         * @param {Array|NodeList} items List
         */
        deleteItems: function (items) {
            this.$DOMEvents.del(items);
        },

        /**
         * Opens the move dialog for the nodes
         *
         * @method controls/projects/project/media/Panel#deleteItem
         * @param {Array|NodeList|HTMLElement} DOMNode - list
         */
        moveItem: function (DOMNode) {
            this.$DOMEvents.move([DOMNode]);
        },

        /**
         * Opens the move dialog for the nodes
         *
         * @method controls/projects/project/media/Panel#deleteItem
         * @param {Array|NodeList|HTMLElement} Nodes - list
         */
        moveItems: function (Nodes) {
            this.$DOMEvents.move(Nodes);
        },

        /**
         * Rename the folder
         *
         * @method controls/projects/project/media/Panel#renameItem
         * @param {HTMLElement} DOMNode
         */
        renameItem: function (DOMNode) {
            this.$DOMEvents.rename(DOMNode);
        },

        /**
         * Opens the replace dialoge
         *
         * @method controls/projects/project/media/Panel#replaceItem
         * @param {HTMLElement} DOMNode
         */
        replaceItem: function (DOMNode) {
            this.$DOMEvents.replace(DOMNode);
        },

        /**
         * Unselect all selected items
         */
        unselectItems: function () {
            if (!this.$selected.length) {
                return;
            }

            for (var i = 0, len = this.$selected.length; i < len; i++) {
                if (!this.$selected[i]) {
                    continue;
                }

                this.$selected[i].removeClass('selected');
            }

            this.$selected.length = 0;
        },

        /**
         * Return the selected Items
         *
         * @return {Array}
         */
        getSelectedItems: function () {
            return this.$selected;
        },

        /**
         * Is the item selectable
         *
         * @param {Object|HTMLElement} Item
         * @return {Boolean}
         */
        isItemSelectable: function (Item) {
            // selectable
            var selectableTypes     = this.getAttribute('selectable_types'),
                selectableMimeTypes = this.getAttribute('selectable_mimetypes');

            if (!selectableTypes && !selectableMimeTypes) {
                return true;
            }

            if (typeOf(selectableTypes) !== 'array') {
                selectableTypes = ['*'];
            }

            if (typeOf(selectableMimeTypes) !== 'array') {
                selectableMimeTypes = ['*'];
            }

            var allTypes = selectableTypes.contains('*'),
                allMimes = selectableMimeTypes.contains('*');

            if (allTypes && allMimes) {
                return true;
            }


            var elmtype  = '',
                mimeType = '';

            if (typeOf(Item) === 'element') {
                elmtype  = Item.get('data-type');
                mimeType = Item.get('data-mimetype');
            } else {
                elmtype  = Item.type;
                mimeType = Item.mimetype;
            }

            if (elmtype === 'folder') {
                return true;
            }


            var mimeTypeFound = selectableMimeTypes.contains(mimeType),
                typeFound     = selectableTypes.contains(elmtype);

            // if all mime types allowed and the allowed type is correct
            if (allMimes && typeFound) {
                return true;
            }

            // if all types allowed and the allowed mime_type is correct
            if (allTypes && mimeTypeFound) {
                return true;
            }

            return (typeFound && mimeTypeFound);
        },

        /**
         * Copy Items to a folder
         *
         * @param {Number} folderid - Folder which copy the files into
         * @param {Array} ids        - file ids
         */
        copyTo: function (folderid, ids) {
            if (!ids.length) {
                return;
            }

            var self = this;

            self.Loader.show();

            Ajax.post('ajax_media_copy', function () {
                self.Loader.hide();

                // we need no reload of the folder
            }, {
                project: this.$Media.getProject().getName(),
                to     : folderid,
                ids    : JSON.encode(ids)
            });
        },

        /**
         * Move Items to a folder
         *
         * @param {Number} folderid - Folder which copy the files into
         * @param {Array} ids        - file ids
         */
        moveTo: function (folderid, ids) {
            if (!ids.length) {
                return Promise.resolve();
            }

            var self = this;

            return new Promise(function (resolve) {
                self.Loader.show();

                Ajax.post('ajax_media_move', function () {
                    self.openID(self.getAttribute('fileid')).then(resolve);
                }, {
                    project: self.$Media.getProject().getName(),
                    to     : folderid,
                    ids    : JSON.encode(ids)
                });
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
        $dragStart: function (event) {
            if (event.rightClick) {
                return;
            }

            if (Browser.ie8) {
                return;
            }

            if (this.getAttribute('_mousedown')) {
                return;
            }

            if (this.getAttribute('_stopdrag')) {
                return;
            }

            this.setAttribute('_mousedown', true);

            var i, len, ElmSize;

            var self = this,
                mx   = event.page.x,
                my   = event.page.y,
                Elm  = event.target;

            if (!Elm.hasClass('qui-media-item')) {
                Elm = Elm.getParent('.qui-media-item');
            }

            ElmSize = Elm.getSize();

            // create the shadow element
            this.$Drag = new Element('div', {
                'class': 'box',
                styles : {
                    position  : 'absolute',
                    top       : my - 20,
                    left      : mx - 40,
                    zIndex    : 1000,
                    MozOutline: 'none',
                    outline   : 0,
                    color     : '#fff',
                    padding   : 10,
                    cursor    : 'pointer',

                    width     : ElmSize.x,
                    height    : ElmSize.y,
                    background: 'rgba(0,0,0, 0.5)'
                }
            }).inject(document.body);

            if (this.$selected.length > 1) {
                this.$Drag.set('html', this.$selected.length + ' Elemente');
            }

            // set ids as data-ids
            var ids = [];

            for (i = 0, len = this.$selected.length; i < len; i++) {
                ids.push(this.$selected[i].get('data-id'));
            }

            if (!ids.length) {
                ids.push(Elm.get('data-id'));
            }

            this.$Drag.set('data-ids', ids.join());


            // set the drag&drop events to the shadow element
            this.$Drag.addEvent('mouseup', function () {
                self.$dragStop();
            });

            this.$Drag.focus();

            // mootools draging
            new Drag.Move(this.$Drag, {

                droppables: ['[data-type="folder"]', '.media-drop'].join(','),
                onComplete: this.$dragComplete.bind(this),
                onDrop    : this.$drop.bind(this),

                onEnter: function (element, Droppable) {
                    self.$dragEnter(false, Droppable);
                },

                onLeave: function (element, Droppable) {
                    self.$dragLeave(false, Droppable);
                }

            }).start({
                page: {
                    x: mx,
                    y: my
                }
            });
        },

        /**
         * If the DragDrop was dropped to a droppable element
         *
         * @param {HTMLElement} Element   - the dropabble element (media item div)
         * @param {HTMLElement} Droppable - drop box element (folder)
         * @param {DOMEvent} event
         */
        $drop: function (Element, Droppable, event) {
            if (!Droppable) {
                return;
            }

            if (Droppable.hasClass('media-drop')) {
                var Control = QUI.Controls.getById(
                    Droppable.get('data-quiid')
                );

                if (!Control) {
                    return;
                }


                var items   = [],
                    ids     = Element.get('data-ids'),
                    Media   = this.getMedia(),
                    Project = Media.getProject(),
                    project = Project.getName();

                ids = ids.split(',');

                for (var i = 0, len = ids.length; i < len; i++) {
                    items.push({
                        id     : ids[i],
                        Media  : Media,
                        project: project,
                        url    : 'image.php?qui=1&id=' + ids[i] + '&project=' + project
                    });
                }

                Control.fireEvent('drop', [items]);

                return;
            }

            this.$PanelContextMenu.showDragDropMenu(Element, Droppable, event);
        },

        /**
         * Stops the Drag Drop
         */
        $dragStop: function () {
            if (Browser.ie8) {
                return;
            }

            (function () {
                if (typeof this.$Drag !== 'undefined' && this.$Drag) {
                    this.$Drag.destroy();
                    this.$Drag = null;
                }
            }).delay(200, this);

            // Wenn noch kein mousedown drag getätigt wurde
            // mousedown "abbrechen" und onclick ausführen
            if (!this.getAttribute('_mousedown')) {
                this.setAttribute('_stopdrag', true);
                return;
            }

            this.setAttribute('_mousedown', false);

            if (typeof this.$lastDroppable !== 'undefined') {
                this.$dragLeave(false, this.$lastDroppable);
            }

            if (typeof this.$Drag !== 'undefined' || this.$Drag) {
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
        $dragComplete: function (event) {
            this.fireEvent('dragDropComplete', [this, event]);
            this.$dragStop();
        },

        /**
         * on drag enter
         *
         * @param {DOMEvent} event
         * @param {HTMLElement} Elm -> node for dropable
         */
        $dragEnter: function (event, Elm) {
            if (!Elm) {
                return;
            }

            if (Elm.hasClass('media-drop')) {
                var Control = QUI.Controls.getById(
                    Elm.get('data-quiid')
                );

                if (!Control) {
                    return;
                }

                if (typeof Control.highlight !== 'undefined') {
                    Control.highlight();
                }

                Control.fireEvent('dragEnter');

                return;
            }

            // Dragdrop to the main folder
            if (Elm.hasClass('qui-media-content')) {
                if (typeof this.$lastDroppable !== 'undefined') {
                    this.$dragLeave(event, this.$lastDroppable);
                }

                this.$lastDroppable = Elm;

                Elm.addClass('qui-media-content-ondragdrop');

                return;
            }


            if (!Elm.hasClass('qui-media-item')) {
                Elm = Elm.getParent('.qui-media-item');
            }

            if (typeof this.$lastDroppable !== 'undefined') {
                this.$dragLeave(event, this.$lastDroppable);
            }

            this.$lastDroppable = Elm;

            Elm.addClass('qui-media-item-ondragdrop');
        },

        /**
         * on drag leave
         *
         * @param {DOMEvent} event
         * @param {HTMLElement} Elm -> node for dropable
         */
        $dragLeave: function (event, Elm) {
            if (!Elm) {
                return;
            }

            if (Elm.hasClass('media-drop')) {
                var Control = QUI.Controls.getById(
                    Elm.get('data-quiid')
                );

                if (!Control) {
                    return;
                }

                if (typeof Control.normalize !== 'undefined') {
                    Control.normalize();
                }

                Control.fireEvent('dragLeave');

                return;
            }

            var Parent = Elm.getParent();

            if (Parent &&
                Parent.hasClass('qui-media-item-ondragdrop') && !Parent.hasClass('qui-media-content')) {
                return;
            }

            if (Elm.hasClass('qui-media-content')) {
                Elm.removeClass('qui-media-content-ondragdrop');
                return;
            }

            if (!Elm.hasClass('qui-media-item')) {
                Elm = Elm.getParent('.qui-media-item');
            }

            if (!Elm) {
                return;
            }

            Elm.removeClass('qui-media-item-ondragdrop');
            Elm.removeClass('qui-media-content-ondragdrop');
        },

        /**
         * Item events
         */

        /**
         * Item event
         *
         * @param {Object} Media - qui/classes/projects/Media
         * @param {Object} Item - qui/classes/projects/media/Item
         */
        $itemEvent: function (Media, Item) {
            if (typeOf(Item) === 'string' || typeOf(Item) === 'number') {
                var self = this;
                Media.get(Item).then(function (File) {
                    self.$itemEvent(Media, File);
                });
                return;
            }

            var Content = this.getContent();
            var Node    = Content.getElement('[data-id="' + Item.getId() + '"]');

            if (!Node) {
                return;
            }

            if (Node.get('data-project') !== Media.getProject().getName()) {
                return;
            }

            Node.removeClass('qmi-active');
            Node.removeClass('qmi-deactive');

            Node.set({
                alt          : Item.getAttribute('name'),
                title        : Item.getAttribute('title'),
                'data-active': Item.isActive() ? 1 : 0
            });

            if (Item.isActive()) {
                Node.addClass('qmi-active');
            } else {
                Node.addClass('qmi-deactive');
            }

            Node.getElement('span').set('html', Item.getAttribute('name'));

            var itemId = Item.getId();

            for (var i = 0, len = this.$children.length; i < len; i++) {
                if (this.$children[i].id != itemId) {
                    continue;
                }

                this.$children[i].active   = Item.isActive();
                this.$children[i].e_date   = Item.getAttribute('e_date');
                this.$children[i].name     = Item.getAttribute('name');
                this.$children[i].priority = Item.getAttribute('priority');
                this.$children[i].short    = Item.getAttribute('short');
                this.$children[i].title    = Item.getAttribute('title');
                break;
            }
        },

        /**
         * event: on filter
         */
        $onFilter: function () {
            if (this.$filterDelay) {
                clearTimeout(this.$filterDelay);
            }

            var self = this;

            this.$filterDelay = function () {
                var i, len, Child, Title;
                var children = self.getContent().getElements('.qui-media-item');

                var value = String(self.$Filter.value).toLowerCase();

                for (i = 0, len = children.length; i < len; i++) {
                    Child = children[i];
                    Title = Child.getElement('.title');

                    if (Title.get('text').toLowerCase().match(value)) {
                        Child.setStyle('display', null);
                        continue;
                    }

                    Child.setStyle('display', 'none');
                }
            }.delay(100);
        }
    });
});
