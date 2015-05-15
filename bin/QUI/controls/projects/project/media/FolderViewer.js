
define('controls/projects/project/media/FolderViewer', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Seperator',
    'Projects',
    'Locale',
    'Ajax',

    'css!controls/projects/project/media/FolderViewer.css'

], function(QUI, QUIControl, QUILoader, QUIButton, QUISeperator, Projects, Locale, Ajax)
{
    "use strict";

    var lg = 'quiqqer/system';

    return new Class({

        Extends: QUIControl,
        Type: 'controls/projects/project/media/FolderViewer',

        Binds: [
            'preview',
            'diashow',
            '$onCreate',
            '$onInject'
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

            this.$Diashow = new QUIButton({
                text : 'Diashow',
                events : {
                    onClick : this.diashow
                }
            }).inject(this.$Buttons);

            new QUISeperator().inject(this.$Buttons);

            new QUIButton({
                text : 'Dateien hochladen'
            }).inject(this.$Buttons);

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
                Project = Projects.get( this.getAttribute('project')),
                Media = Project.getMedia();

            Media.get( this.getAttribute('folderId') ).done(function(Item)
            {
                if ( typeOf(Item) != 'classes/projects/project/media/Folder' )
                {
                    self.$Container.set('html', 'Dies ist kein Ordner');
                    self.Loader.hide();
                    return;
                }

                Item.getChildren(function(items)
                {
                    self.$Container.set( 'html', '' );

                    for ( var i = 0, len = items.length; i < len; i++ )
                    {
                        if ( items[i].type != 'image' ) {
                            continue;
                        }

                        self.$createImageItem( items[i] ).inject( self.$Container );
                    }

                    self.Loader.hide();
                });
            });
        },

        /**
         * opens the diashow
         */
        diashow : function()
        {
            var self = this;

            require([
                'package/quiqqer/gallery/bin/controls/Popup',
                'qui/utils/Elements'
            ], function(Diashow, ElementUtils)
            {
                var imageData = self.$Container.getElements(
                    '.qui-project-media-folderViewer-item'
                ).map(function(Elm)
                {
                    return {
                        src   : Elm.get( 'data-src' ),
                        title : Elm.title
                    };
                });

                new Diashow({
                    images : imageData,
                    zIndex : ElementUtils.getComputedZIndex( self.$Elm )
                }).showFirstImage();
            });
        },

        /**
         * create a domnode for the image data
         *
         * @param {Object} imageData
         */
        $createImageItem : function(imageData)
        {
            var imageSrc = URL_DIR + imageData.url;

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
                'data-src' : imageSrc
            });
        }

    });
});