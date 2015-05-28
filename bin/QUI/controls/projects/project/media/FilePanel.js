
/**
 * Displays a Media in a Panel
 *
 * @module controls/projects/project/media/FilePanel
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requir qui/QUI
 * @requir qui/controls/desktop/Panel
 * @requir classes/projects/project/media/panel/DOMEvents
 * @requir qui/controls/buttons/Button
 * @requir qui/controls/buttons/Seperator
 * @requir qui/controls/windows/Confirm
 * @requir utils/Template
 * @requir qui/utils/Form
 * @requir utils/Controls
 * @requir Locale
 * @requir css!controls/projects/project/media/FilePanel.css
 */

define('controls/projects/project/media/FilePanel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'classes/projects/project/media/panel/DOMEvents',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Seperator',
    'qui/controls/buttons/Select',
    'qui/controls/windows/Confirm',
    'qui/controls/input/Range',
    'utils/Template',
    'qui/utils/Form',
    'utils/Controls',
    'controls/projects/project/media/Input',
    'Locale',
    'Projects',

    'css!controls/projects/project/media/FilePanel.css'

], function()
{
    "use strict";

    var lg = 'quiqqer/system';

    var QUI				   = arguments[ 0 ],
        QUIPanel           = arguments[ 1 ],
        PanelDOMEvents     = arguments[ 2 ],
        QUIButton          = arguments[ 3 ],
        QUIButtonSeperator = arguments[ 4 ],
        QUISelect          = arguments[ 5 ],
        QUIConfirm         = arguments[ 6 ],
        QUIRange           = arguments[ 7 ],
        Template           = arguments[ 8 ],
        FormUtils          = arguments[ 9 ],
        ControlUtils       = arguments[ 10 ],
        MediaInput         = arguments[ 11 ],
        Locale             = arguments[ 12 ],
        Projects           = arguments[ 13 ];

    /**
     * A Media-Panel, opens the Media in an Desktop Panel
     *
     * @class controls/projects/project/media/FilePanel
     *
     * @param {Object} File - classes/projects/media/File
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QUIPanel,
        Type    : 'controls/projects/project/media/FilePanel',

        Binds : [
            'openDetails',
            'openImageEffects',
            'openPreview',
            '$onInject',
            '$unloadCategory',
            '$refreshImageEffectFrame'
        ],

        options : {
            fileId  : false,
            project : false
        },

        initialize : function(File, options)
        {
            var self = this;

            this.$__injected = false;

            this.$DOMEvents        = new PanelDOMEvents( this );
            this.$EffectPreview    = null;
            this.$EffectBlur       = null;
            this.$EffectBrightness = null;
            this.$EffectContrast   = null;
            this.$EffectWatermark  = null;

            this.$ButtonActiv   = null;
            this.$ButtonDetails = null;
            this.$ButtonEffects = null;
            this.$ButtonPreview = null;

            this.addEvents({
                onInject : this.$onInject,
                onDestroy : function()
                {
                    self.$ButtonDetails.destroy();

                    if (self.$ButtonEffects) {
                        self.$ButtonEffects.destroy();
                    }

                    if (self.$ButtonPreview) {
                        self.$ButtonPreview.destroy();
                    }

                    self.$ButtonActiv = null;
                }
            });

            if ( typeOf( File ) === 'object' )
            {
                this.parent( File );
                return;
            }


            this.$File = File;
            this.$Media = this.$File.getMedia();

            // default id
            this.setAttribute( 'fileId', File.getId() );

            this.setAttribute(
                'id',
                'projects-media-file-panel-' + File.getId()
            );

            this.setAttribute(
                'name',
                'projects-media-file-panel-' + File.getId()
            );

            this.setAttribute(
                'project',
                this.$Media.getProject().getName()
            );


            this.parent( options );
        },

        /**
         * Return the Media object of the panel
         *
         * @method controls/projects/project/media/FilePanel#getMedia
         * @return {Object} Media (classes/projects/project/Media)
         */
        getMedia : function()
        {
            return this.$Media;
        },

        /**
         * Return the Project object of the Media
         *
         * @return {Object} Project (classes/projects/Project)
         */
        getProject : function()
        {
            return this.$Media.getProject();
        },

        /**
         * Close and destroy the panel
         *
         * @method controls/projects/project/media/FilePanel#close
         */
        close : function()
        {
            this.destroy();
        },

        /**
         * @event : on panel inject
         */
        $onInject : function()
        {
            if (this.$__injected) {
                return;
            }

            var self = this;

            this.Loader.show();

            this.load(function()
            {
                self.$createTabs();
                self.$createButtons();

                self.$File.addEvents({
                    onSave : function() {
                        self.refresh();
                    }
                });

                self.openDetails();
                self.$__injected = true;
            });
        },

        /**
         * Load the image data, and set the image data to the panel
         *
         * @method controls/projects/project/media/FilePanel#load
         * @param {Function} [callback] - callback function, optional
         */
        load : function(callback)
        {
            var self = this;

            if ( !this.$File )
            {
                var Project = Projects.get( this.getAttribute('project') );

                this.$Media = Project.getMedia();
                this.$Media.get( this.getAttribute('fileId') ).done(function(File)
                {
                    self.$File = File;

                    self.load( callback );
                });

                return;
            }

            var File = this.$File,
                icon = 'fa fa-picture-o icon-picture';

            if ( File.getAttribute( 'type' ) == 'image' ) {
                icon = URL_BIN_DIR +'16x16/extensions/image.png';
            }

            this.setAttributes({
                icon  : icon,
                title : File.getAttribute( 'file' )
            });

            this.$refresh();

            if (typeof callback === 'function') {
                callback();
            }
        },

        /**
         * Unload the panel
         *
         * @method controls/projects/project/media/FilePanel#unload
         */
        unload : function()
        {

        },

        /**
         * Refresh the panel
         *
         * @method controls/projects/project/media/FilePanel#refresh
         */
        refresh : function()
        {
            var self = this;

            this.Loader.show();

            this.$File.refresh().then(function() {
                self.load();
            });
        },

        /**
         * Return the file objectwhich is linked to the panel
         *
         * @method controls/projects/project/media/FilePanel#load
         * @return {classes/project/media/Item} File
         */
        getFile : function()
        {
            return this.$File;
        },

        /**
         * Saves the files
         *
         * @method controls/projects/project/media/FilePanel#save
         */
        save : function()
        {
            var self = this;

            this.Loader.show();

            this.$unloadCategory();

            this.getFile().save(function()
            {
                QUI.getMessageHandler(function(MH) {
                    MH.addSuccess(
                        Locale.get( lg, 'projects.project.site.media.folderPanel.message.save.success' )
                    );
                });

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

                title : Locale.get( 'quiqqer/system', 'projects.project.site.media.filePanel.window.delete.title', {
                    file : this.$File.getAttribute('file')
                }),

                text : Locale.get( 'quiqqer/system', 'projects.project.site.media.filePanel.window.delete.text', {
                    file : this.$File.getAttribute('file')
                }),

                information : Locale.get('quiqqer/system', 'projects.project.site.media.filePanel.window.delete.information', {
                    file : this.$File.getAttribute('file')
                }),

                maxWidth : 533,
                maxHeight : 300,

                autoclose   : false,
                events :
                {
                    onSubmit : function(Win)
                    {
                        Win.Loader.show();

                        self.getFile().del(function()
                        {
                           self.close();
                           Win.close();
                        });
                    }
                }
            }).open();
        },

        /**
         * Activate the file
         *
         * @method controls/projects/project/media/FilePanel#activate
         */
        activate : function()
        {
            this.getButtonBar()
                .getElement( 'status' )
                .setAttribute( 'textimage', 'icon-spinner icon-spin' );

            this.$File.activate( this.refresh.bind( this ) );
        },

        /**
         * Deactivate the file
         *
         * @method controls/projects/project/media/FilePanel#activate
         */
        deactivate : function()
        {
            this.getButtonBar()
                .getElement( 'status' )
                .setAttribute( 'textimage', 'icon-spinner icon-spin' );

            this.$File.deactivate( this.refresh.bind( this ) );
        },

        /**
         * Open the replace Dialog for the File
         *
         * @method controls/projects/project/media/FilePanel#replace
         */
        replace : function()
        {
            this.$DOMEvents.replace( this.getBody() );
        },

        /**
         * Create the Buttons for the Panel
         * Such like Save, Delete
         *
         * @method controls/projects/project/media/FilePanel#$createTabs
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
                    text      : Locale.get( lg, 'projects.project.site.media.filePanel.btn.replace.text' ),
                    textimage : 'icon-upload',
                    events    :
                    {
                        onClick : function() {
                            self.replace();
                        }
                    }
                })
            ).addButton(
                new QUIButtonSeperator()
            );


            if ( this.$File.isActive() )
            {
                this.addButton(
                    new QUIButton({
                        name      : 'status',
                        text      : Locale.get( lg, 'projects.project.site.media.filePanel.btn.deactivate.text' ),
                        textimage : 'icon-remove',
                        Control   : this,
                        events    :
                        {
                            onClick : function() {
                                self.deactivate();
                            }
                        }
                    })
                );

            } else
            {
                this.addButton(
                    new QUIButton({
                        name      : 'status',
                        text      : Locale.get( lg, 'projects.project.site.media.filePanel.btn.activate.text' ),
                        textimage : 'icon-remove',
                        Control   : this,
                        events    :
                        {
                            onClick : function() {
                                self.activate();
                            }
                        }
                    })
                );
            }

            this.addButton(
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
         * Create the Tabs for the Panel
         * Such like Preview and Details Tab
         *
         * @method controls/projects/project/media/FilePanel#$createTabs
         */
        $createTabs : function()
        {
            this.getCategoryBar().clear();

            this.$ButtonDetails = new QUIButton({
                text   : Locale.get( lg, 'projects.project.site.media.filePanel.details.text' ),
                name   : 'details',
                icon   : 'fa fa-file-o icon-file-alt',
                events : {
                    onClick : this.openDetails
                }
            });

            this.addCategory( this.$ButtonDetails );

            // image
            if ( this.$File.getType() != 'classes/projects/project/media/Image' ) {
                return;
            }

            this.$ButtonEffects = new QUIButton({
                text   : Locale.get( lg, 'projects.project.site.media.filePanel.image.effects.text' ),
                name   : 'imageEffects',
                icon   : 'fa fa-magic icon-magic',
                events : {
                    onClick : this.openImageEffects
                }
            });

            this.$ButtonPreview = new QUIButton({
                text    : Locale.get( lg, 'projects.project.site.media.filePanel.preview.text' ),
                name    : 'preview',
                icon    : 'fa fa-eye icon-eye-open',
                events  : {
                    onClick : this.openPreview
                }
            });

            this.addCategory( this.$ButtonEffects );
            this.addCategory( this.$ButtonPreview );
        },

        /**
         * unload the category and set the data to the file
         */
        $unloadCategory : function()
        {
            if ( !this.$ButtonActiv || this.$__injected === false ) {
                return;
            }

            var Body = this.getContent();
            var Form = Body.getElement('form');

            if ( typeOf(Form) !== 'element' ) {
                return;
            }

            var data = FormUtils.getFormData(Form),
                File = this.getFile();

            for ( var i in data )
            {
                if ( !data.hasOwnProperty( i ) ) {
                    return;
                }


                // effects
                if ( i.match('effect-') )
                {
                    File.setEffect( i.replace('effect-', ''), data[ i ] );
                    continue;
                }


                if ( "file_name" == i ) {
                    File.setAttribute( 'name', data[ i ] );
                }

                if ( "file_title" == i ) {
                    File.setAttribute( 'title', data[ i ] );
                }

                if ( "file_alt" == i ) {
                    File.setAttribute( 'alt', data[ i ] );
                }

                if ( "file_short" == i ) {
                    File.setAttribute( 'short', data[ i ] );
                }
            }
        },

        /**
         * Opens the detail tab
         *
         * @method controls/projects/project/media/FilePanel#$createTabs
         */
        openDetails : function()
        {
            if ( this.$ButtonDetails.isActive() ) {
                return;
            }

            this.Loader.show();
            this.$unloadCategory();

            this.$ButtonActiv = this.$ButtonDetails;
            this.$ButtonActiv.setActive();


            var self = this,
                Body = this.getContent(),
                File = this.$File;

            Body.set( 'html', '' );

            Template.get('project_media_file', function(result)
            {
                var Body = self.getContent();

                Body.set(
                  'html',
                  '<form>'+ result +'</form>'
                );

                ControlUtils.parse( Body.getElement( 'form' ), function()
                {
                    var dimension = '';

                    if ( File.getAttribute( 'image_width' ) &&
                         File.getAttribute( 'image_height' ) )
                    {
                        dimension = File.getAttribute( 'image_width' ) +
                                    ' x '+
                                    File.getAttribute( 'image_height' );
                    }

                    // set data to form
                    FormUtils.setDataToForm({
                            file_id        : File.getId(),
                            file_name      : File.getAttribute( 'name' ),
                            file_title     : File.getAttribute( 'title' ),
                            file_alt       : File.getAttribute( 'alt' ),
                            file_short     : File.getAttribute( 'short' ),
                            file_file      : File.getAttribute( 'file' ),
                            file_path      : File.getAttribute( 'path' ),
                            file_type      : File.getAttribute( 'type' ),
                            file_edate     : File.getAttribute( 'e_date' ),
                            file_url       : File.getAttribute( 'cache_url' ),
                            file_dimension : dimension,
                            file_md5       : File.getAttribute( 'md5hash' ),
                            file_sha1      : File.getAttribute( 'sha1hash' ),
                            file_size      : File.getAttribute( 'filesize' )
                        },
                        Body.getElement( 'form' )
                    );

                    new QUIButton({
                        name   : 'download_file',
                        image  : 'icon-download',
                        title  : Locale.get( lg, 'projects.project.site.media.filePanel.btn.downloadFile.title' ),
                        alt    : Locale.get( lg, 'projects.project.site.media.filePanel.btn.downloadFile.alt' ),
                        events :
                        {
                            onClick : function() {
                                self.getFile().download();
                            }
                        },
                        styles : {
                            'float' : 'right'
                        }
                    }).inject(
                        Body.getElement('input[name="file_url"]'),
                        'after'
                    );

                    self.Loader.hide();
                });
            });
        },

        /**
         * oben the preview of the image
         */
        openPreview : function()
        {
            if ( this.$ButtonPreview.isActive() ) {
                return;
            }

            this.$unloadCategory();

            this.$ButtonActiv = this.$ButtonPreview;
            this.$ButtonActiv.setActive();


            var Body = this.getContent();

            Body.set( 'html', '' );

            var url = URL_DIR + this.$File.getAttribute( 'url' );

            if ( url.match('image.php') ) {
                url = url +'&noresize=1';
            }

            new Element('img', {
                src    : url,
                styles : {
                    maxWidth : '100%'
                }
            }).inject( Body );
        },

        /**
         * Opens the image effects with preview
         */
        openImageEffects : function()
        {
            if ( this.$ButtonEffects.isActive() ) {
                return;
            }

            this.Loader.show();
            this.$unloadCategory();

            this.$ButtonActiv = this.$ButtonEffects;
            this.$ButtonActiv.setActive();

            var self = this,
                Content = this.getContent();

            Content.set( 'html', '' );

            Template.get('project_media_effects', function(result)
            {
                var WatermarkInput;
                var Effects = self.getFile().getEffects();

                Content.set(
                    'html',
                    '<form>'+ result +'</form>'
                );

                var WatermarkPosition = Content.getElement('[name="effect-watermark_position"]'),
                    Watermark = Content.getElement('.effect-watermark'),
                    WatermarkCell = Content.getElement('.effect-watermark-cell'),
                    WatermarkRow = Content.getElement('.effect-watermark-row');

                self.$EffectWatermark = Content.getElement('[name="effect-watermark"]');

                self.$EffectPreview = new Element('img', {
                    src : URL_LIB_DIR +'QUI/Projects/Media/bin/effectPreview.php'
                }).inject( Content.getElement( '.preview-frame' ) );


                var Greyscale = Content.getElement('[name="effect-greyscale"]');

                if ( !("blur" in Effects) ) {
                    Effects.blur = 0;
                }

                if ( !("brightness" in Effects) ) {
                    Effects.brightness = 0;
                }

                if ( !("contrast" in Effects) ) {
                    Effects.contrast = 0;
                }

                if ( !("watermark" in Effects) ) {
                    Effects.watermark = false;
                }

                if ( !("watermark_position" in Effects) ) {
                    Effects.watermark_position = false;
                }


                self.$EffectBlur = new QUIRange({
                    name: 'effect-blur',
                    value : Effects.blur,
                    min: 0,
                    max: 100,
                    events : {
                        onChange : self.$refreshImageEffectFrame
                    }
                }).inject( Content.getElement('.effect-blur') );

                self.$EffectBrightness = new QUIRange({
                    name: 'effect-brightness',
                    value : Effects.brightness,
                    min: -100,
                    max: 100,
                    events : {
                        onChange : self.$refreshImageEffectFrame
                    }
                }).inject( Content.getElement('.effect-brightness') );

                self.$EffectContrast = new QUIRange({
                    name: 'effect-contrast',
                    value : Effects.contrast,
                    min: -100,
                    max: 100,
                    events : {
                        onChange : self.$refreshImageEffectFrame
                    }
                }).inject( Content.getElement('.effect-contrast') );


                Greyscale.checked = Effects.greyscale || false;
                Greyscale.addEvent('change', self.$refreshImageEffectFrame);

                WatermarkPosition.value = Effects.watermark_position || '';
                WatermarkPosition.addEvent( 'change', self.$refreshImageEffectFrame );


                // watermark
                var Select = new QUISelect({
                    menuWidth : 300,
                    styles : {
                        width : 260
                    },
                    events :
                    {
                        onChange : function(value)
                        {
                            if ( value == 'default' || value === '' )
                            {
                                WatermarkRow.setStyle('display', 'none');

                                if (WatermarkInput) {
                                    WatermarkInput.clear();
                                }

                                self.$EffectWatermark.value = value;
                                self.$refreshImageEffectFrame();
                                return;
                            }

                            WatermarkRow.setStyle('display', null);
                            self.$refreshImageEffectFrame();
                        }
                    }
                }).inject( Watermark );

                Select.appendChild(
                    Locale.get(lg, 'projects.project.site.media.folderPanel.no.watermark'),
                    '',
                    'fa fa-remove icon-remove'
                );

                Select.appendChild(
                    Locale.get(lg, 'projects.project.site.media.folderPanel.project.watermark'),
                    'default',
                    'fa fa-home icon-home'
                );

                Select.appendChild(
                    Locale.get(lg, 'projects.project.site.media.folderPanel.own.watermark'),
                    'own',
                    'fa fa-picture-o icon-picture'
                );

                WatermarkInput = new MediaInput({
                    styles : {
                        clear : 'both',
                        'float' : 'left',
                        marginTop : 10
                    },
                    events :
                    {
                        onChange : function(Input, value) {
                            self.$EffectWatermark.value = value;
                            self.$refreshImageEffectFrame();
                        }
                    }
                }).inject( WatermarkCell );

                WatermarkInput.setProject( self.getProject() );

                if ( Effects.watermark === '' )
                {
                    Select.setValue('');

                } else if ( Effects.watermark.toString().match('image.php') )
                {
                    Select.setValue( 'own' );
                    WatermarkInput.setValue( Effects.watermark );
                } else
                {
                    Select.setValue('default');
                }


                self.$refreshImageEffectFrame();
                self.Loader.hide();
            });
        },

        /**
         * Refresh the effect preview image
         */
        $refreshImageEffectFrame : function()
        {
            if (!this.$EffectBlur ||
                !this.$EffectBrightness ||
                !this.$EffectContrast)
            {
                return;
            }

            var File    = this.getFile(),
                fileId  = File.getId(),
                project = this.getProject().getName(),
                Content = this.getContent(),
                WatermarkPosition = Content.getElement('[name="effect-watermark_position"]');

            var Greyscale = Content.getElement('[name="effect-greyscale"]');
            var url = URL_LIB_DIR +'QUI/Projects/Media/bin/effectPreview.php?';

            url = url + Object.toQueryString({
                id         : fileId,
                project    : project,
                blur       : this.$EffectBlur.getValue(),
                brightness : this.$EffectBrightness.getValue(),
                contrast   : this.$EffectContrast.getValue(),
                greyscale  : Greyscale.checked ? 1 : 0,
                watermark  : this.$EffectWatermark.value,
                watermark_position : WatermarkPosition.value,
                __nocache  : String.uniqueID()
            });

            this.$EffectPreview.set( 'src', url );
        }
    });
});