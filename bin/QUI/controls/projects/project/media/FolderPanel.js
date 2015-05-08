
/**
 * Media Folder Panel
 *
 * @module controls/projects/project/media/FolderPanel
 * @author www.pcsg.de (Henning Leutz)
 */

define('controls/projects/project/media/FolderPanel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Seperator',
    'qui/controls/windows/Confirm',
    'qui/controls/input/Range',
    'qui/utils/Form',
    'utils/Template',
    'Projects',
    'Locale',
    'Ajax',

    'css!controls/projects/project/media/FolderPanel.css'

], function(
    QUI,
    QUIPanel,
    QUIButton,
    QUIButtonSeperator,
    QUIConfirm,
    QUIRange,
    QUIFormUtils,
    Template,
    Projects,
    Locale,
    Ajax
) {
    "use strict";

    var lg = 'quiqqer/system';

    return new Class({

        Extends : QUIPanel,
        Type : 'controls/projects/project/media/FolderPanel',

        Binds : [
            '$onInject',
            'openDetails',
            'openEffects',
            'executeEffectsRecursive',
            '$refreshImageEffectFrame'
        ],

        options : {
            folderId : false,
            project : false
        },

        initialize : function(options)
        {
            this.parent( options );

            this.$Folder = null;
            this.$Media = null;
            this.$EffectPreview = null;
            this.$EffectBlur = null;
            this.$EffectBrightness = null;
            this.$EffectContrast = null;

            this.$loaded = false;

            this.addEvents({
                onInject : this.$onInject
            });
        },

        /**
         * @event : on panel inject
         */
        $onInject : function()
        {
            this.$load();
        },

        /**
         * load the folder data
         */
        $load : function()
        {
            this.Loader.show();

            if (this.$Folder || this.$loaded) {
                this.Loader.hide();
                return;
            }

            this.$loaded = true;

            var self = this,
                Project = Projects.get( this.getAttribute('project')),
                Media = Project.getMedia();

            Media.get(this.getAttribute('folderId')).done(function(Folder)
            {
                self.$Folder = Folder;
                self.$Media = Media;

                var title  = Project.getName() +'://'+ Folder.getAttribute( 'file' );

                self.setAttributes({
                    icon  : 'fa fa-folder-open-o icon-folder-open-alt',
                    title : title
                });

                self.refresh();

                self.$createCategories();
                self.$createButtons();

                self.getCategoryBar().firstChild().click();
            });
        },

        /**
         * Saves the folder
         *
         * @param {Function} [callback] - optional, callback function
         */
        save : function(callback)
        {
            if (!this.$Folder) {
                return;
            }

            var self = this;

            this.Loader.show();

            this.$unloadCategory();

            this.$Folder.save(function()
            {
                QUI.getMessageHandler(function(MH) {
                    MH.addSuccess(
                        Locale.get( lg, 'projects.project.site.media.folderPanel.message.save.success' )
                    );
                });

                if (typeof callback === 'function') {
                    callback();
                }

                self.Loader.hide();
            });
        },

        /**
         * Delete the files
         *
         * @method controls/projects/project/media/FilePanel#del
         */
        del : function()
        {
            var self = this;

            new QUIConfirm({
                icon     : 'fa fa-trash-o icon-trash',
                texticon : 'fa fa-trash-o icon-trash',
                maxWidth : 533,
                maxHeight : 300,
                title : Locale.get( 'quiqqer/system', 'projects.project.site.media.folderPanel.window.delete.title', {
                    folder : this.$Folder.getAttribute('file')
                }),

                text : Locale.get( 'quiqqer/system', 'projects.project.site.media.folderPanel.window.delete.text', {
                    folder : this.$Folder.getAttribute('file')
                }),

                information : Locale.get( 'quiqqer/system', 'projects.project.site.media.folderPanel.window.delete.information', {
                    folder : this.$Folder.getAttribute('file')
                } ),
                autoclose : false,
                events :
                {
                    onSubmit : function(Win)
                    {
                        Win.Loader.show();

                        self.$Folder.del(function()
                        {
                            self.close();
                            Win.close();
                        });
                    }
                }
            }).open();
        },

        /**
         * Oepn the folder details
         */
        openDetails : function()
        {
            this.$unloadCategory();

            var self   = this,
                Body   = this.getContent(),
                Folder = this.$Folder;

            Body.set( 'html', '' );

            this.Loader.show();

            Template.get('project_media_folder', function(result)
            {
                Body.set(
                    'html',
                    '<form>'+ result +'</form>'
                );

                QUIFormUtils.setDataToForm(
                    Folder.getAttributes(),
                    Body.getElement('form')
                );

                self.Loader.hide();
            });
        },

        /**
         * Open the folder effects
         */
        openEffects : function()
        {
            this.$unloadCategory();

            this.Loader.show();

            var self   = this,
                Body   = this.getContent(),
                Folder = this.$Folder;

            Body.set( 'html', '' );

            Template.get('project_media_effects', function(result)
            {
                Body.set(
                    'html',
                    '<form>' + result + '</form>'
                );

                var Effects = Folder.getEffects();
                var Greyscale = Body.getElement('[name="effect-greyscale"]');

                if ( !("blur" in Effects) ) {
                    Effects.blur = 0;
                }

                if ( !("brightness" in Effects) ) {
                    Effects.brightness = 0;
                }

                if ( !("contrast" in Effects) ) {
                    Effects.contrast = 0;
                }

                self.$EffectBlur = new QUIRange({
                    name: 'effect-blur',
                    value : Effects.blur,
                    min: 0,
                    max: 100,
                    events : {
                        onChange : self.$refreshImageEffectFrame
                    }
                }).inject( Body.getElement('.effect-blur') );

                self.$EffectBrightness = new QUIRange({
                    name: 'effect-brightness',
                    value : Effects.brightness,
                    min: -100,
                    max: 100,
                    events : {
                        onChange : self.$refreshImageEffectFrame
                    }
                }).inject( Body.getElement('.effect-brightness') );

                self.$EffectContrast = new QUIRange({
                    name: 'effect-contrast',
                    value : Effects.contrast,
                    min: -100,
                    max: 100,
                    events : {
                        onChange : self.$refreshImageEffectFrame
                    }
                }).inject( Body.getElement('.effect-contrast') );

                Greyscale.checked = Effects.greyscale || false;
                Greyscale.addEvent('change', self.$refreshImageEffectFrame);

                new QUIButton({
                    text : 'Effekte rekursiv anwenden',
                    styles : {
                        'float' : 'right',
                        marginBottom : 20
                    },
                    events : {
                        onClick : self.executeEffectsRecursive
                    }
                }).inject(
                    Body.getElement('.data-table'), 'after'
                );


                Folder.getChildren(function(children)
                {
                    var i, len;

                    for ( i = 0, len = children.length; i < len; i++ )
                    {
                        if ( children[i].type == 'image' )
                        {
                            self.$previewImageData = children[i];
                            break;
                        }
                    }

                    self.$refreshImageEffectFrame();
                    self.Loader.hide();
                });
            });
        },

        /**
         * Opens the confirm window for the resursive effect execution
         */
        executeEffectsRecursive : function()
        {
            if (!this.$Folder) {
                return;
            }

            var self = this;

            new QUIConfirm({
                title : 'Effekte rekursiv anwenden',
                maxWidth : 533,
                maxHeight : 300,
                text : 'Möchten Sie die Ordnereffekte rekursiv anwenden?',
                information : 'Alle Änderungen des Ordneres werden gespeichert. ' +
                            'Die Effekte des Ordners werden rekusriv auf alle <u>Bilder</u> und <u>Ordner</u> angewendet.',
                autoclose: false,
                events :
                {
                    onSubmit : function(Win)
                    {
                        Win.Loader.show();

                        self.save(function()
                        {
                            Ajax.post('ajax_media_folder_recursiveEffects', function()
                            {
                                Win.close();
                            }, {
                                folderId : self.$Folder.getId(),
                                project : self.$Folder.getMedia().getProject().getName()
                            });
                        });
                    }
                }
            }).open();
        },

        /**
         * create the action buttons of the panel
         */
        $createButtons : function()
        {
            var self = this;

            this.getButtonBar().clear();

            this.addButton(
                new QUIButton({
                    text      : Locale.get( lg, 'projects.project.site.media.filePanel.btn.save.text' ),
                    textimage : 'icon-save',
                    events    :
                    {
                        onClick : function() {
                            self.save();
                        }
                    }
                })
            ).addButton(
                new QUIButton({
                    alt : Locale.get( lg, 'projects.project.site.media.filePanel.btn.delete.text' ),
                    title : Locale.get( lg, 'projects.project.site.media.filePanel.btn.delete.text' ),
                    icon : 'fa fa-trash-o icon-trash',
                    events :
                    {
                        onClick : function() {
                            self.del();
                        }
                    },
                    styles : {
                        'float' : 'right'
                    }
                })
            );
        },

        /**
         * create the left categories of the panel
         */
        $createCategories : function()
        {
            this.getCategoryBar().clear();

            this.addCategory({
                text: Locale.get(lg, 'projects.project.site.media.filePanel.details.text'),
                name: 'details',
                icon: 'fa fa-folder-open-o icon-folder-open-alt',
                events: {
                    onActive: this.openDetails
                }
            });

            this.addCategory({
                text: Locale.get(lg, 'projects.project.site.media.filePanel.image.effects.text'),
                name: 'effects',
                icon: 'fa fa-magic icon-magic',
                events: {
                    onActive: this.openEffects
                }
            });
        },

        /**
         * Refresh the preview
         */
        $refreshImageEffectFrame : function()
        {
            if ( !this.$Media ) {
                return;
            }

            var PreviewParent = this.getContent().getElement('.preview-frame');

            if ( typeof this.$previewImageData === 'undefined' )
            {
                PreviewParent.set(
                    'html',
                    Locale.get( lg, 'projects.project.site.folder.has.no.images' )
                );

                return;
            }

            if ( !this.$EffectBlur ||
                 !this.$EffectBrightness ||
                 !this.$EffectContrast )
            {
                return;
            }

            var Image = PreviewParent.getElement('img');

            if ( !Image )
            {
                PreviewParent.set( 'html', '' );

                Image = new Element('img', {
                    src : URL_LIB_DIR +'QUI/Projects/Media/bin/effectPreview.php'
                }).inject( this.getContent().getElement( '.preview-frame' ) );
            }


            var fileId  = this.$previewImageData.id,
                project = this.$Media.getProject().getName(),
                Content = this.getContent();

            var Greyscale = Content.getElement('[name="effect-greyscale"]');
            var url = URL_LIB_DIR +'QUI/Projects/Media/bin/effectPreview.php?';

            url = url + Object.toQueryString({
                id         : fileId,
                project    : project,
                blur       : this.$EffectBlur.getValue(),
                brightness : this.$EffectBrightness.getValue(),
                contrast   : this.$EffectContrast.getValue(),
                greyscale  : Greyscale.checked ? 1 : 0,
                __nocache  : String.uniqueID()
            });

            Image.set( 'src', url );
        },

        /**
         * unload the category and set the data to the folder
         */
        $unloadCategory : function()
        {
            var Body = this.getContent();
            var Form = Body.getElement('form');

            if ( !Form || !Form.getParent() ) {
                return;
            }

            if ( !this.$Folder ) {
                return;
            }

            var data = QUIFormUtils.getFormData(Form);

            for ( var i in data )
            {
                if ( !data.hasOwnProperty(i) ) {
                    return;
                }

                // effects
                if ( i.match('effect-') )
                {
                    this.$Folder.setEffect( i.replace('effect-', ''), data[i] );
                    continue;
                }

                this.$Folder.setAttribute( i, data[i] );
            }
        }
    });
});
