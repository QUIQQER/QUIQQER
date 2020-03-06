/**
 * Folder Viewer
 * Display images from a folder and offers a slideshow / diashow
 *
 * @module controls/projects/project/media/FolderViewer
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onFolderCreated [self, newFolder]
 */
define('controls/projects/project/media/FolderViewer', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Separator',
    'qui/controls/contextmenu/Menu',
    'qui/utils/String',
    'classes/request/Upload',
    'classes/projects/project/media/panel/ContextMenu',
    'classes/projects/project/media/panel/DOMEvents',
    'controls/upload/Form',
    'utils/Panels',
    'Projects',
    'Locale',
    'Ajax',

    'css!controls/projects/project/media/FolderViewer.css'

], function (QUI,
             QUIControl,
             QUILoader,
             QUIButton,
             QUISeparator,
             QUIContextmenu,
             QUIStringUtils,
             RequestUpload,
             MediaContextMenu,
             DOMEvents,
             UploadForm,
             PanelUtils,
             Projects,
             QUILocale,
             QUIAjax) {
    "use strict";

    var lg                = 'quiqqer/quiqqer';
    var HIDE_HIDDEN_FILES = 1; // 1 = hide all hidden files, 0 = show all hidden files

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/projects/project/media/FolderViewer',

        Binds: [
            'preview',
            'diashow',
            'openUpload',
            'openInMedia',
            'openFolder',
            '$onInject',
            '$onDestroy',
            '$onDrop',
            '$onContextMenu',
            '$itemEvent',
            '$itemHideStatusChange'
        ],

        options: {
            project      : false, // name of the project, // {string} parent project if the folder not exists
            folderId     : false, // id of the folder
            Parent       : false, // {Object} parent folder if the folder not exists
            folderUrl    : false,
            parentId     : false, // {number} parent id if the folder not exists
            filetype     : ['image'], // types : image, file, folder
            createMessage: QUILocale.get('quiqqer/quiqqer', 'folderviewer.create.folder'),
            newFolderName: false,
            autoactivate : false // activate files after the upload
        },

        initialize: function (options) {
            this.parent(options);

            this.Loader     = null;
            this.$Buttons   = null;
            this.$Container = null;
            this.$Diashow   = null;

            this.$ButtonsDiashow   = null;
            this.$ButtonsSeparator = null;
            this.$ButtonsUpload    = null;
            this.$selected         = [];

            this.$Folder      = null;
            this.$ContextMenu = null;
            this.$DOMEvents   = null;

            this.addEvents({
                onInject : this.$onInject,
                onDestroy: this.$onDestroy
            });


            if (!this.getAttribute('project') && this.getAttribute('folderUrl')) {
                var folderUrl = this.getAttribute('folderUrl'),
                    params    = QUIStringUtils.getUrlParams(folderUrl);

                if ("project" in params) {
                    this.setAttribute('project', params.project);
                }

                if ("id" in params) {
                    this.setAttribute('folderId', params.id);
                }
            }

            if (!this.getAttribute('project')) {
                this.setAttribute('project', QUIQQER_PROJECT.name);
            }

            var Project = Projects.get(this.getAttribute('project'));
            var Media   = Project.getMedia();

            Media.addEvents({
                onItemRename    : this.$itemEvent,
                onItemActivate  : this.$itemEvent,
                onItemDeactivate: this.$itemEvent,
                onItemRefresh   : this.$itemEvent,
                onItemSave      : this.$itemEvent,
                onItemDelete    : this.$itemEvent,
                onItemsHide     : this.$itemHideStatusChange,
                onItemsVisible  : this.$itemHideStatusChange
            });
        },

        /**
         * event : on create
         */
        create: function () {
            this.$Elm = this.parent();
            this.$Elm.addClass('qui-project-media-folderViewer');

            this.$Elm.set({
                'data-quiid': this.getId(),
                html        : '' +
                    '<div class="qui-project-media-folderViewer-buttons"></div>' +
                    '<div class="qui-project-media-folderViewer-container"></div>'
            });

            this.Loader            = new QUILoader().inject(this.$Elm);
            this.$PanelContextMenu = new MediaContextMenu(this);
            this.$DOMEvents        = new DOMEvents(this);

            // buttons
            this.$Buttons   = this.$Elm.getElement('.qui-project-media-folderViewer-buttons');
            this.$Container = this.$Elm.getElement('.qui-project-media-folderViewer-container');

            this.$ButtonsDiashow = new QUIButton({
                text     : QUILocale.get(lg, 'projects.project.media.folderviewer.btn.diashow'),
                title    : QUILocale.get(lg, 'projects.project.media.folderviewer.btn.diashow'),
                textimage: 'fa fa-play',
                name     : 'diashow',
                events   : {
                    onClick: this.diashow
                },
                disabled : true
            }).inject(this.$Buttons);

            this.$ButtonsSeparator = new QUISeparator().inject(this.$Buttons);

            this.$ButtonsUpload = new QUIButton({
                text     : QUILocale.get(lg, 'projects.project.site.media.panel.btn.upload'),
                title    : QUILocale.get(lg, 'projects.project.site.media.panel.btn.upload'),
                textimage: 'fa fa-upload',
                name     : 'upload',
                events   : {
                    click: this.openUpload
                },
                disabled : true
            }).inject(this.$Buttons);

            this.$MediaSettings = new QUIButton({
                name      : 'settings',
                menuCorner: 'topRight',
                icon      : 'fa fa-gear',
                styles    : {
                    'float': 'right',
                    width  : 50
                }
            }).inject(this.$Buttons);

            this.$MediaSettings.getContextMenu(function (Menu) {
                Menu.addEvent('onShow', function () {
                    var pos   = Menu.getElm().getPosition();
                    var width = Menu.getElm().getSize().x;

                    var newMenuPos = this.$MediaSettings.getElm().getPosition().x - width + 60;

                    Menu.setPosition(newMenuPos, pos.y);
                }.bind(this));
            }.bind(this));

            this.$MediaSettings.appendChild({
                name  : 'hiddenView',
                text  : QUILocale.get('quiqqer/quiqqer', 'media.panel.view.hiddenItems.show'),
                icon  : 'fa fa-eye',
                events: {
                    onMouseDown: function (Item) {
                        HIDE_HIDDEN_FILES = !HIDE_HIDDEN_FILES;

                        if (HIDE_HIDDEN_FILES) {
                            Item.setAttribute('text', QUILocale.get('quiqqer/quiqqer', 'media.panel.view.hiddenItems.show'));
                            Item.setAttribute('icon', 'fa fa-eye');
                        } else {
                            Item.setAttribute('text', QUILocale.get('quiqqer/quiqqer', 'media.panel.view.hiddenItems.hide'));
                            Item.setAttribute('icon', 'fa fa-eye-slash');
                        }

                        this.refresh();
                    }.bind(this)
                }
            });

            this.$MediaFolder = new QUIButton({
                name  : 'folder-properties',
                icon  : 'fa fa-folder',
                title : QUILocale.get(lg, 'properties'),
                styles: {
                    'float': 'right',
                    width  : 50
                },
                events: {
                    onClick: this.openFolder
                }
            }).inject(this.$Buttons);

            this.$MediaOpen = new QUIButton({
                name  : 'open-folder',
                icon  : 'fa fa-picture-o',
                title : QUILocale.get(lg, 'projects.project.site.btn.open_in_media.text'),
                styles: {
                    'float': 'right',
                    width  : 50
                },
                events: {
                    onClick: this.openInMedia
                }
            }).inject(this.$Buttons);

            this.$ButtonsDiashow.hide();
            this.$ButtonsSeparator.hide();

            // Upload events
            new RequestUpload([this.$Container], {
                onDragenter: function (event, Elm) {
                    if (!Elm.hasClass('qui-project-media-folderViewer-container')) {
                        Elm = Elm.getParent('qui-project-media-folderViewer-container');
                    }

                    if (!Elm || !Elm.hasClass('qui-project-media-folderViewer-container')) {
                        return;
                    }

                    Elm.addClass('qui-media-drag');
                    event.stop();
                },
                onDragleave: function (event, Elm) {
                    if (Elm.hasClass('qui-project-media-folderViewer-container')) {
                        Elm.removeClass('qui-media-drag');
                    }
                },
                onDragend  : function (event, Elm) {
                    if (Elm.hasClass('qui-project-media-folderViewer-container')) {
                        Elm.removeClass('qui-media-drag');
                    }
                },
                onDrop     : this.$onDrop
            });

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.refresh();
        },

        /**
         * event: on destroy
         */
        $onDestroy: function () {
            if (this.$ContextMenu) {
                this.$ContextMenu.destroy();
            }

            var Project = Projects.get(this.getAttribute('project')),
                Media   = Project.getMedia();

            Media.removeEvent('onItemRename', this.$itemEvent);
            Media.removeEvent('onItemActivate', this.$itemEvent);
            Media.removeEvent('onItemDeactivate', this.$itemEvent);
            Media.removeEvent('onItemRefresh', this.$itemEvent);
            Media.removeEvent('onItemSave', this.$itemEvent);
            Media.removeEvent('onItemDelete', this.$itemEvent);
            Media.removeEvent('onItemsHide', this.$itemHideStatusChange);
            Media.removeEvent('onItemsVisible', this.$itemHideStatusChange);
        },

        /**
         * refresh the folder viewer
         */
        refresh: function () {
            if (!this.getAttribute('project') && this.getAttribute('folderUrl') ||
                !this.getAttribute('folderId') && this.getAttribute('folderUrl')) {
                var folderUrl = this.getAttribute('folderUrl'),
                    params    = QUIStringUtils.getUrlParams(folderUrl);

                if ("project" in params) {
                    this.setAttribute('project', params.project);
                }

                if ("id" in params) {
                    this.setAttribute('folderId', params.id);
                }
            }

            if (!this.getAttribute('project')) {
                return this.showCreateFolder();
            }

            this.hideCreateFolder();
            this.Loader.show();

            var self    = this,
                Project = Projects.get(this.getAttribute('project')),
                Media   = Project.getMedia();

            if (!this.getAttribute('folderId')) {
                return this.showCreateFolder().then(function () {
                    self.Loader.hide();
                });
            }

            Media.get(this.getAttribute('folderId')).then(function (Item) {
                var allowedTypes = self.getAttribute('filetype');

                if (typeOf(Item) !== 'classes/projects/project/media/Folder') {
                    self.$Container.set(
                        'html',
                        QUILocale.get(lg, 'projects.project.media.folderviewer.no.folder')
                    );

                    self.$ButtonsDiashow.disable();
                    self.Loader.hide();
                    return;
                }

                if (allowedTypes && !allowedTypes.contains('image')) {
                    self.$ButtonsDiashow.disable();
                } else {
                    self.$ButtonsDiashow.enable();
                }

                self.$Folder = Item;
                self.$ButtonsUpload.enable();

                Item.getChildren(false, {
                    showHiddenFiles: !HIDE_HIDDEN_FILES
                }).then(function (Result) {
                    var items = Result.data;

                    self.$Container.set('html', '');

                    var files = 0;

                    for (var i = 0, len = items.length; i < len; i++) {
                        if (!allowedTypes.contains(items[i].type)) {
                            continue;
                        }

                        self.$createImageItem(items[i]).inject(self.$Container);
                        files++;
                    }

                    if (files >= 2) {
                        self.$ButtonsDiashow.show();
                        self.$ButtonsSeparator.show();
                        self.$ButtonsDiashow.enable();
                    } else {
                        self.$ButtonsDiashow.hide();
                        self.$ButtonsSeparator.hide();
                    }

                    if (files === 0) {
                        var message = QUILocale.get(
                            lg,
                            'projects.project.media.folderviewer.empty'
                        );

                        if (!allowedTypes.contains('image')) {
                            message = QUILocale.get(
                                lg,
                                'projects.project.media.fileviewer.empty'
                            );
                        }

                        new Element('div', {
                            html  : message,
                            styles: {
                                padding: 10
                            }
                        }).inject(self.$Container);
                    }

                    self.Loader.hide();
                });
            }).catch(function (err) {
                console.error('debug error', err);
                return self.showCreateFolder();
            });
        },

        /**
         * opens the diashow
         *
         * @param {String} [image] - optional, source of the image, whiche should be zoomed
         */
        diashow: function (image) {
            if (this.$Diashow) {
                if (typeOf(image) === 'string') {
                    this.$Diashow.showImage(image);
                    return;
                }

                this.$Diashow.showFirstImage();
                return;
            }


            var self = this;

            require([
                'package/quiqqer/diashow/bin/Diashow',
                'qui/utils/Elements'
            ], function (Diashow, ElementUtils) {
                var imageData = self.$Container.getElements(
                    '.qui-project-media-folderViewer-item'
                ).map(function (Elm) {
                    return {
                        src  : Elm.get('data-src'),
                        title: Elm.title,
                        short: Elm.get('data-short')
                    };
                });

                if (!self.$Diashow) {
                    self.$Diashow = new Diashow({
                        images: imageData,
                        zIndex: ElementUtils.getComputedZIndex(self.$Elm),
                        events: {
                            onClose: function () {
                                // workaround close bug
                                self.$Diashow.destroy();
                                self.$Diashow = null;
                            }
                        }
                    });
                }

                if (typeOf(image) === 'string') {
                    self.$Diashow.showImage(image);
                    return;
                }

                self.$Diashow.showFirstImage();
            });
        },

        /**
         * Open upload
         */
        openUpload: function () {
            this.openSheet(function (Content, Sheet) {

                var Upload = new UploadForm({
                    multiple    : true,
                    sendbutton  : true,
                    cancelbutton: true,
                    styles      : {
                        height: '95%'
                    },
                    events      : {
                        onCancel  : function () {
                            Sheet.fireEvent('close');
                        },
                        onComplete: function (Form, File, result) {
                            if (this.getAttribute('autoactivate') && result && "url" in result) {
                                this.$autoActivate(result.url).then(function () {
                                    Sheet.fireEvent('close');
                                });
                                return;
                            }

                            Sheet.fireEvent('close');
                            this.refresh();
                        }.bind(this)
                    }
                });

                Upload.setParam('onfinish', 'ajax_media_upload');
                Upload.setParam('project', this.getAttribute('project'));
                Upload.setParam('parentid', this.getAttribute('folderId'));
                Upload.setParam('extract', 0);

                Upload.getElm().setStyles({
                    opacity: 0,
                    left   : -20
                });

                Upload.inject(Content);

                moofx(Upload.getElm()).animate({
                    left   : 0,
                    opacity: 1
                }, {
                    duration: 200
                });

            }.bind(this), {
                buttons: false
            });
        },

        /**
         * create a domnode for the image data
         *
         * @param {Object} imageData - data of the image
         * @return {Element}
         */
        $createImageItem: function (imageData) {
            var cursor   = 'zoom-in',
                self     = this,
                imageSrc = URL_DIR + imageData.url,
                dataSrc  = imageSrc + '&noresize=1';

            imageSrc = imageSrc + '&maxwidth=80&maxheight=80&quiadmin=1';

            if (imageData.type === 'file') {
                imageSrc = imageData.icon80x80;
                dataSrc  = imageData.icon80x80;
                cursor   = 'default';
            }

            var Container = new Element('div', {
                'class'        : 'qui-project-media-folderViewer-item',
                html           : '<div class="qui-project-media-folderViewer-item-image"></div>' +
                    '<span class="qui-project-media-folderViewer-item-title">' +
                    imageData.name +
                    '</span>',
                alt            : imageData.name,
                title          : imageData.name,
                styles         : {
                    cursor: cursor
                },
                'data-src'     : dataSrc,
                'data-short'   : imageData.short,
                'data-id'      : imageData.id,
                //'data-project' : project,
                'data-type'    : imageData.type,
                'data-active'  : imageData.active ? 1 : 0,
                'data-error'   : imageData.error ? 1 : 0,
                'data-mimetype': imageData.mimetype,
                'data-hidden'  : imageData.isHidden ? 1 : 0,
                events         : {
                    click: function () {
                        if (imageData.type === 'image') {
                            self.diashow(this.get('data-src'));
                        }
                    },

                    contextmenu: this.$onContextMenu
                }
            });

            if (imageData.active) {
                Container.addClass('qui-project-media-folderViewer-item-active');
            } else {
                Container.addClass('qui-project-media-folderViewer-item-inactive');
            }

            if (imageData.isHidden) {
                if (HIDE_HIDDEN_FILES) {
                    Container.addClass('qui-media-item-hidden__hide');
                } else {
                    Container.addClass('qui-media-item-hidden__show');
                }
            }

            var IC = Container.getElement(
                '.qui-project-media-folderViewer-item-image'
            );

            IC.setStyle('opacity', 0);
            IC.setStyle('display', 'inline');

            require([
                'image!' + imageSrc
            ], function (Image) {
                var IC = this.getElement(
                    '.qui-project-media-folderViewer-item-image'
                );

                IC.setStyle('backgroundImage', 'url(' + Image.src + ')');

                moofx(IC).animate({
                    opacity: 1
                }, {
                    duration: 200
                });

            }.bind(Container), function (err) {
                console.error(err);
            }.bind(Container));

            return Container;
        },

        /**
         * OnDrop Event
         *
         * @param {DOMEvent} event         - DragDrop Event
         * @param {HTMLElement|File} Files - List of droped files
         */
        $onDrop: function (event, Files) {
            if (!Files.length) {
                return;
            }

            var self = this,
                size = this.$Elm.getSize();


            var Background = new Element('div', {
                'class': 'qui-project-media-folderViewer-upload-background',
                styles : {
                    opacity: 0
                }
            }).inject(this.$Elm);

            var Message = new Element('div', {
                'class': 'qui-project-media-folderViewer-upload-message',
                html   : QUILocale.get(lg, 'projects.project.media.folderviewer.upload.message'),
                styles : {
                    opacity: 0
                }
            }).inject(this.$Elm);

            var close = function () {
                moofx(Background).animate({
                    opacity: 0
                }, {
                    callback: function () {
                        Background.destroy();
                    }
                });

                moofx(Message).animate({
                    opacity: 0,
                    top    : top - 20
                }, {
                    callback: function () {
                        Message.destroy();
                    }
                });
            };

            var msgSize = Message.getSize(),
                top     = (size.y - msgSize.y) / 2;

            Message.setStyles({
                left: (size.x - msgSize.x) / 2,
                top : top - 20
            });

            new QUIButton({
                text  : QUILocale.get(lg, 'projects.project.media.folderviewer.upload.btn.start'),
                styles: {
                    clear : 'both',
                    margin: '20px 0 0'
                },
                events: {
                    onClick: function () {
                        close();

                        self.Loader.show();

                        self.$Folder.uploadFiles(Files).then(function (Img) {
                            if (!self.getAttribute('autoactivate')) {
                                self.refresh();
                                return;
                            }

                            self.$autoActivate(Img.url);
                        });
                    }
                }
            }).inject(Message);

            new QUIButton({
                text  : QUILocale.get(lg, 'projects.project.media.folderviewer.upload.btn.cancel'),
                styles: {
                    'float': 'right',
                    margin : '20px 0 0'
                },
                events: {
                    onClick: close
                }
            }).inject(Message);


            moofx(Background).animate({
                opacity: 0.6
            });

            moofx(Message).animate({
                opacity: 1,
                top    : top
            });
        },

        /**
         * Show the create folder dialog
         *
         * @return {Promise}
         */
        showCreateFolder: function () {
            var self = this;

            if (self.getElm().getElement('.folder-missing-container')) {
                return Promise.resolve();
            }

            return new Promise(function (resolve, reject) {
                require(['controls/projects/project/media/CreateFolder'], function (CreateFolder) {
                    self.$Buttons.setStyle('display', 'none');
                    self.$Container.setStyle('display', 'none');

                    if (self.getElm().getElement('.folder-missing-container')) {
                        return resolve();
                    }

                    var Container = new Element('div', {
                        'class': 'create-folder-container',
                        html   : self.getAttribute('createMessage'),
                        styles : {
                            background: '#fff',
                            fontSize  : 14,
                            fontStyle : 'italic',
                            height    : '100%',
                            padding   : 20,
                            position  : 'absolute',
                            textAlign : 'center',
                            width     : '100%'
                        }
                    }).inject(self.getElm());

                    new QUIButton({
                        text  : QUILocale.get(lg, 'projects.project.media.folderviewer.createNewFolderButton'),
                        styles: {
                            'float'  : 'none',
                            marginTop: 10
                        },
                        events: {
                            onClick: function () {
                                new CreateFolder({
                                    newFolderName: self.getAttribute('newFolderName'),
                                    Parent       : self.getAttribute('Parent'),
                                    events       : {
                                        onSubmit: function (CF, Item) {
                                            self.fireEvent('folderCreated', [self, Item]);
                                        }
                                    }
                                }).open();
                            }
                        }
                    }).inject(Container);

                    resolve();
                }, reject);
            });
        },

        /**
         * Hide the create folder dialog
         */
        hideCreateFolder: function () {
            this.getElm().getElements('.create-folder-container').destroy();
            this.$Buttons.setStyle('display', null);
            this.$Container.setStyle('display', null);
        },

        /**
         * open the folder
         */
        openInMedia: function () {
            var project  = this.getAttribute('project');
            var folderId = this.getAttribute('folderId');

            PanelUtils.openMediaPanel(project, {
                fileid: folderId
            });
        },

        /**
         * open the folder details
         */
        openFolder: function () {
            var project  = this.getAttribute('project');
            var folderId = this.getAttribute('folderId');

            PanelUtils.openMediaItemPanel(project, folderId);
        },

        /**
         * activate a image
         *
         * @param {String} url
         * @returns {Promise}
         */
        $autoActivate: function (url) {
            return new Promise(function (resolve) {
                // activate the file
                var params = QUIStringUtils.getUrlParams(url);

                QUIAjax.post('ajax_media_activate', function () {
                    this.refresh();
                    resolve();
                }.bind(this), {
                    project: params.project,
                    fileid : params.id
                });
            }.bind(this));
        },

        /**
         * event: on context menu
         *
         * @param event
         */
        $onContextMenu: function (event) {
            if (typeOf(event) !== 'domevent') {
                return;
            }

            event.stop();

            var Target = event.target;

            if (!Target.hasClass('qui-project-media-folderViewer-item')) {
                Target = Target.getParent('.qui-project-media-folderViewer-item');
            }

            this.$selected = [Target];
            this.$PanelContextMenu.show(event);
        },

        // media panel pretending

        /**
         * Return the content node -> elm
         *
         * @return {HTMLElement}
         */
        getContent: function () {
            return this.getElm();
        },

        /**
         * Return all selected items
         *
         * @return {Array}
         */
        getSelectedItems: function () {
            return this.$selected;
        },

        /**
         * Return the panel contextmenu
         *
         * @method qui/controls/desktop/Panel#getContextMenu
         * @return {qui/controls/contextmenu/Menu}
         */
        getContextMenu: function () {
            if (this.$ContextMenu) {
                return this.$ContextMenu;
            }

            // context menu
            this.$ContextMenu = new QUIContextmenu({
                title : this.getAttribute('title'),
                events: {
                    blur: function (Menu) {
                        Menu.hide();
                    }
                }
            });

            this.$ContextMenu.inject(document.body);

            return this.$ContextMenu;
        },

        /**
         * Return the media
         *
         * @return {*}
         */
        getMedia: function () {
            var Project = Projects.get(this.getAttribute('project'));

            return Project.getMedia();
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
         * Download the file
         *
         * @method controls/projects/project/media/Panel#downloadFile
         * @param {Number} fileid - ID of the file
         */
        downloadFile: function (fileid) {
            this.getMedia().get(fileid, function (File) {
                File.download();
            });
        },

        //endregion

        /**
         * event: media item change
         */
        $itemEvent: function () {
            this.refresh();
        },

        /**
         * event: media item hidden status change
         */
        $itemHideStatusChange: function () {
            this.refresh();
        }
    });
});
