
/**
 * Folder Viewer
 * Display images from a folder and offers a slideshow / diashow
 *
 * @module controls/projects/project/media/FolderViewer
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/loader/Loader
 * @require qui/controls/buttons/Button
 * @require qui/controls/buttons/Seperator
 * @require classes/request/Upload
 * @require Projects
 * @require Locale
 * @require css!controls/projects/project/media/FolderViewer.css
 */

define('controls/projects/project/media/FolderViewer', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Seperator',
    'classes/request/Upload',
    'controls/upload/Form',
    'Projects',
    'Locale',

    'css!controls/projects/project/media/FolderViewer.css'

], function(

    QUI,
    QUIControl,
    QUILoader,
    QUIButton,
    QUISeperator,
    RequestUpload,
    UploadForm,
    Projects,
    Locale

) {
    "use strict";

    var lg = 'quiqqer/system';

    return new Class({

        Extends: QUIControl,
        Type: 'controls/projects/project/media/FolderViewer',

        Binds: [
            'preview',
            'diashow',
            'openUpload',
            '$onCreate',
            '$onInject',
            '$onDrop'
        ],

        options : {
            project : '', // name of the project
            folderId : 1
        },

        initialize : function(options)
        {
            this.parent( options );

            this.Loader = null;
            this.$Buttons = null;
            this.$Container = null;
            this.$Diashow = null;

            this.$ButtonsDiashow = null;
            this.$ButtonsSeperator = null;
            this.$ButtonsUpload = null;

            this.$Folder = null;

            this.addEvents({
                onCreate : this.$onCreate,
                onInject : this.$onInject
            });
        },

        /**
         * event : on create
         */
        create : function()
        {
            this.$Elm = this.parent();
            this.$Elm.addClass('qui-project-media-folderViewer');

            this.$Elm.set({
                'data-quiid' : this.getId(),
                html : '<div class="qui-project-media-folderViewer-buttons"></div>' +
                       '<div class="qui-project-media-folderViewer-container"></div>'
            });

            this.Loader = new QUILoader().inject( this.$Elm );

            this.$Buttons = this.$Elm.getElement('.qui-project-media-folderViewer-buttons');
            this.$Container = this.$Elm.getElement('.qui-project-media-folderViewer-container');

            this.$ButtonsDiashow = new QUIButton({
                text      : Locale.get( lg, 'projects.project.media.folderviewer.btn.diashow' ),
                title     : Locale.get( lg, 'projects.project.media.folderviewer.btn.diashow' ),
                textimage : 'icon-play',
                events    : {
                    onClick : this.diashow
                },
                disabled : true
            }).inject(this.$Buttons);

            this.$ButtonsSeperator = new QUISeperator().inject(this.$Buttons);

            this.$ButtonsUpload = new QUIButton({
                text      : 'Dateien hochladen',
                title     : 'Dateien hochladen',
                textimage : 'icon-upload',
                events    : {
                    click : this.openUpload
                },
                disabled : true
            }).inject(this.$Buttons);


            this.$ButtonsDiashow.hide();
            this.$ButtonsSeperator.hide();

            // Upload events
            new RequestUpload([this.$Container], {

                onDragenter: function(event, Elm)
                {
                    if ( !Elm.hasClass( 'qui-project-media-folderViewer-container' )  ) {
                        Elm = Elm.getParent( 'qui-project-media-folderViewer-container' );
                    }

                    if ( !Elm || !Elm.hasClass('qui-project-media-folderViewer-container') ) {
                        return;
                    }

                    Elm.addClass( 'qui-media-drag' );
                    event.stop();
                },

                onDragleave: function(event, Elm)
                {
                    if ( Elm.hasClass( 'qui-project-media-folderViewer-container' ) ) {
                        Elm.removeClass( 'qui-media-drag' );
                    }
                },

                onDragend : function(event, Elm)
                {
                    if ( Elm.hasClass( 'qui-project-media-folderViewer-container' ) ) {
                        Elm.removeClass( 'qui-media-drag' );
                    }
                },

                onDrop : this.$onDrop
            });


            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject : function()
        {
            this.refresh();
        },

        /**
         * refresh the folder viewer
         */
        refresh : function()
        {
            this.Loader.show();

            var self = this,
                Project = Projects.get(this.getAttribute('project')),
                Media = Project.getMedia();

            Media.get(this.getAttribute('folderId')).done(function(Item)
            {
                if (typeOf(Item) != 'classes/projects/project/media/Folder')
                {
                    self.$Container.set(
                        'html',
                        Locale.get(lg, 'projects.project.media.folderviewer.no.folder')
                    );

                    self.Loader.hide();
                    return;
                }

                self.$Folder = Item;
                self.$ButtonsUpload.enable();

                Item.getChildren(function(items)
                {
                    self.$Container.set('html', '');

                    var images = 0;

                    if (items.length === 0) {

                        new Element('div', {
                            html : Locale.get(lg, 'projects.project.media.folderviewer.empty'),
                            styles : {
                                padding: 10
                            }
                        }).inject(self.$Container);
                    }

                    for (var i = 0, len = items.length; i < len; i++)
                    {
                        if (items[i].type != 'image') {
                            continue;
                        }

                        self.$createImageItem(items[i]).inject(self.$Container);
                        images++;
                    }

                    if (images >= 2) {
                        self.$ButtonsDiashow.show();
                        self.$ButtonsSeperator.show();

                        self.$ButtonsDiashow.enable();
                    } else {
                        self.$ButtonsDiashow.hide();
                        self.$ButtonsSeperator.hide();
                    }

                    self.Loader.hide();
                });
            });
        },

        /**
         * opens the diashow
         *
         * @param {String} [image] - optional, source of the image, whiche should be zoomed
         */
        diashow : function(image)
        {
            if (this.$Diashow)
            {
                if (typeOf(image) === 'string')
                {
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
            ], function(Diashow, ElementUtils)
            {
                var imageData = self.$Container.getElements(
                    '.qui-project-media-folderViewer-item'
                ).map(function(Elm)
                {
                    return {
                        src   : Elm.get('data-src'),
                        title : Elm.title,
                        short : Elm.get('data-short')
                    };
                });

                self.$Diashow = new Diashow({
                    images : imageData,
                    zIndex : ElementUtils.getComputedZIndex(self.$Elm)
                });


                if (typeOf(image) === 'string')
                {
                    self.$Diashow.showImage(image);
                    return;
                }

                self.$Diashow.showFirstImage();
            });
        },

        /**
         * Open upload
         */
        openUpload : function()
        {
            this.openSheet(function(Content, Sheet) {

                var Upload = new UploadForm({
                    multible     : true,
                    sendbutton   : true,
                    cancelbutton : true,
                    styles : {
                        height: '95%'
                    },
                    events : {
                        onCancel : function() {
                            Sheet.hide();
                        },
                        onComplete : function() {
                            Sheet.hide();
                            this.refresh();
                        }.bind(this)
                    }
                });

                Upload.setParam('onfinish', 'ajax_media_upload');
                Upload.setParam('project', this.getAttribute('project'));
                Upload.setParam('parentid', this.getAttribute('folderId'));
                Upload.setParam('extract', 0);

                Upload.inject(Content);

            }.bind(this), {
                buttons : false
            });
        },

        /**
         * create a domnode for the image data
         *
         * @param {Object} imageData - data of the image
         * @return {HTMLDivElement}
         */
        $createImageItem : function(imageData)
        {
            var self = this,
                imageSrc = URL_DIR + imageData.url;

            return new Element('div', {
                'class' : 'qui-project-media-folderViewer-item',
                html    : '<span class="qui-project-media-folderViewer-item-title">'+
                              imageData.name +
                          '</span>',
                alt     : imageData.name,
                title   : imageData.name,
                styles  : {
                    backgroundImage : 'url('+ imageSrc +'&maxwidth=80&maxheight=80&quiadmin=1)'
                },
                'data-src' : imageSrc +'&noresize=1',
                'data-short' : imageData.short,
                events :
                {
                    click : function() {
                        self.diashow( this.get('data-src') );
                    },

                    contextmenu : function(event) {
                        event.stop();
                    }
                }
            });
        },

        /**
         * OnDrop Event
         *
         * @param {DOMEvent} event         - DragDrop Event
         * @param {HTMLElement|File} Files - List of droped files
         */
        $onDrop : function(event, Files)
        {
            if (!Files.length) {
                return;
            }

            var self = this,
                size = this.$Elm.getSize();


            var Background = new Element('div', {
                'class' : 'qui-project-media-folderViewer-upload-background',
                styles : {
                    opacity : 0
                }
            }).inject( this.$Elm );

            var Message = new Element('div', {
                'class' : 'qui-project-media-folderViewer-upload-message',
                html : Locale.get(lg, 'projects.project.media.folderviewer.upload.message'),
                styles : {
                    opacity : 0
                }
            }).inject( this.$Elm );

            var close = function()
            {
                moofx( Background ).animate({
                    opacity : 0
                }, {
                    callback : function() {
                        Background.destroy();
                    }
                });

                moofx( Message ).animate({
                    opacity : 0,
                    top : top - 20
                }, {
                    callback : function() {
                        Message.destroy();
                    }
                });
            };

            var msgSize = Message.getSize(),
                top = ( size.y - msgSize.y ) / 2;

            Message.setStyles({
                left : ( size.x - msgSize.x ) / 2,
                top : top  - 20
            });

            new QUIButton({
                text : Locale.get(lg, 'projects.project.media.folderviewer.upload.btn.start'),
                styles : {
                    clear: 'both',
                    margin: '20px 0 0'
                },
                events :
                {
                    onClick : function()
                    {
                        close();

                        self.Loader.show();

                        self.$Folder.uploadFiles( Files, function() {
                            self.refresh();
                        });
                    }
                }
            }).inject( Message );

            new QUIButton({
                text : Locale.get(lg, 'projects.project.media.folderviewer.upload.btn.cancel'),
                styles : {
                    'float' : 'right',
                    margin: '20px 0 0'
                },
                events : {
                    onClick : close
                }
            }).inject( Message );


            moofx( Background ).animate({
                opacity : 0.6
            });

            moofx( Message ).animate({
                opacity : 1,
                top : top
            });
        }
    });
});
