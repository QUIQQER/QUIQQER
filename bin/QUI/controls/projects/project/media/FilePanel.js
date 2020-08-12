/**
 * Displays a Media in a Panel
 *
 * @module controls/projects/project/media/FilePanel
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/projects/project/media/FilePanel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/loader/Loader',
    'classes/projects/project/media/panel/DOMEvents',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Separator',
    'qui/controls/buttons/Select',
    'qui/controls/windows/Confirm',
    'qui/controls/input/Range',
    'utils/Template',
    'qui/utils/Form',
    'qui/utils/String',
    'utils/Controls',
    'utils/Media',
    'controls/projects/project/media/Input',
    'Locale',
    'Projects',

    'css!controls/projects/project/media/FilePanel.css'

], function () {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    var QUI                = arguments[0],
        QUIPanel           = arguments[1],
        QUILoader          = arguments[2],
        PanelDOMEvents     = arguments[3],
        QUIButton          = arguments[4],
        QUIButtonSeparator = arguments[5],
        QUISelect          = arguments[6],
        QUIConfirm         = arguments[7],
        QUIRange           = arguments[8],
        Template           = arguments[9],
        FormUtils          = arguments[10],
        StringUtils        = arguments[11],
        ControlUtils       = arguments[12],
        MediaUtils         = arguments[13],
        MediaInput         = arguments[14],
        Locale             = arguments[15],
        Projects           = arguments[16];

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

        Extends: QUIPanel,
        Type   : 'controls/projects/project/media/FilePanel',

        Binds: [
            'toggleStatus',
            'openDetails',
            'openImageEffects',
            'openPreview',
            'openPermissions',
            'refresh',
            '$onInject',
            '$unloadCategory',
            '$refreshImageEffectFrame',
            '$onFileActivate',
            '$onFileDeactivate'
        ],

        options: {
            fileId : false,
            project: false
        },

        initialize: function (File, options) {
            var self = this;

            this.$__injected = false;

            this.$DOMEvents        = new PanelDOMEvents(this);
            this.$EffectPreview    = null;
            this.$EffectLoader     = null;
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
                onDestroy: function () {
                    if (self.$ButtonDetails) {
                        self.$ButtonDetails.destroy();
                    }

                    if (self.$ButtonEffects) {
                        self.$ButtonEffects.destroy();
                    }

                    if (self.$ButtonPreview) {
                        self.$ButtonPreview.destroy();
                    }

                    self.$ButtonActiv = null;
                }
            });

            if (typeOf(File) === 'object') {
                this.parent(File);
                return;
            }


            this.$File  = File;
            this.$Media = this.$File.getMedia();

            // default id
            this.setAttribute('fileId', File.getId());

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


            this.parent(options);
        },

        /**
         * Return the Media object of the panel
         *
         * @method controls/projects/project/media/FilePanel#getMedia
         * @return {Object} Media (classes/projects/project/Media)
         */
        getMedia: function () {
            return this.$Media;
        },

        /**
         * Return the Project object of the Media
         *
         * @return {Object} Project (classes/projects/Project)
         */
        getProject: function () {
            return this.$Media.getProject();
        },

        /**
         * Close and destroy the panel
         *
         * @method controls/projects/project/media/FilePanel#close
         */
        close: function () {
            this.destroy();
        },

        /**
         * @event : on panel inject
         */
        $onInject: function () {
            if (this.$__injected) {
                return;
            }

            var self = this;

            this.Loader.show();

            this.load(function () {
                self.$createTabs();
                self.$createButtons();

                self.$File.addEvents({
                    onSave      : function () {
                        self.refresh();
                    },
                    onActivate  : self.$onFileActivate,
                    onDeactivate: self.$onFileDeactivate
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
        load: function (callback) {
            var self = this;

            if (!this.$File) {
                var Project = Projects.get(this.getAttribute('project'));

                this.$Media = Project.getMedia();
                this.$Media.get(this.getAttribute('fileId')).done(function (File) {
                    self.$File = File;
                    self.load(callback);
                });

                return;
            }

            var File = this.$File,
                icon = 'fa fa-picture-o';

            if (File.getAttribute('type') === 'image') {
                icon = URL_BIN_DIR + '16x16/extensions/image.png';
            }

            this.setAttributes({
                icon : icon,
                title: File.getAttribute('file')
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
        unload: function () {

        },

        /**
         * Refresh the panel
         *
         * @method controls/projects/project/media/FilePanel#refresh
         *
         * @return Promise
         */
        refresh: function () {
            this.Loader.show();
            this.fireEvent('refresh', [this]);

            return this.$File.refresh().then(function () {
                this.load();
                this.$refresh();
                this.Loader.hide();
            }.bind(this)).catch(function (Exception) {
                console.error(Exception);
            });
        },

        /**
         * Return the file objectwhich is linked to the panel
         *
         * @method controls/projects/project/media/FilePanel#load
         * @return {classes/project/media/Item} File
         */
        getFile: function () {
            return this.$File;
        },

        /**
         * Saves the files
         *
         * @method controls/projects/project/media/FilePanel#save
         * @return Promise
         */
        save: function () {
            var self = this;

            this.Loader.show();

            this.$unloadCategory();

            var File = this.getFile();

            File.save(function () {
                // Update the (maybe truncated) filename
                var NameInput   = self.getContent().getElement('input[name=file_name]');

                if (NameInput) {
                    NameInput.value = File.getAttribute('name');
                }

                self.Loader.hide();
            }).catch(function (Exception) {
                console.error(Exception);
                self.Loader.hide();
            });
        },

        /**
         * Delete the files
         *
         * @method controls/projects/project/media/FilePanel#del
         */
        del: function () {
            var self = this;

            new QUIConfirm({
                icon    : 'fa fa-trash-o',
                texticon: 'fa fa-trash-o',

                title: Locale.get('quiqqer/system', 'projects.project.site.media.filePanel.window.delete.title', {
                    file: this.$File.getAttribute('file')
                }),

                text: Locale.get('quiqqer/system', 'projects.project.site.media.filePanel.window.delete.text', {
                    file: this.$File.getAttribute('file')
                }),

                information: Locale.get('quiqqer/system', 'projects.project.site.media.filePanel.window.delete.information', {
                    file: this.$File.getAttribute('file')
                }),

                maxWidth : 533,
                maxHeight: 300,

                autoclose: false,
                events   : {
                    onSubmit: function (Win) {
                        Win.Loader.show();

                        self.getFile().del(function () {
                            self.close();
                            Win.close();
                        });
                    }
                }
            }).open();
        },

        /**
         * Toggle the file status to active or deactive
         *
         * @method controls/projects/project/media/FilePanel#activate
         */
        toggleStatus: function () {
            if (this.$File.isActive()) {
                this.deactivate();

            } else {
                this.activate();
            }
        },

        /**
         * Activate the file
         *
         * @method controls/projects/project/media/FilePanel#activate
         */
        activate: function () {
            this.getButtonBar()
                .getElement('status')
                .setAttribute('textimage', 'fa fa-spinner fa-spin');

            this.$File.activate();
        },

        /**
         * Deactivate the file
         *
         * @method controls/projects/project/media/FilePanel#activate
         */
        deactivate: function () {
            this.getButtonBar()
                .getElement('status')
                .setAttribute('textimage', 'fa fa-spinner fa-spin');

            this.$File.deactivate();
        },

        /**
         * Open the replace Dialog for the File
         *
         * @method controls/projects/project/media/FilePanel#replace
         */
        replace: function () {
            this.$DOMEvents.replace(
                new Element('div', {
                    'data-id': this.$File.getId(),
                    title    : this.$File.getAttribute('title')
                })
            );
        },

        /**
         * Create the Buttons for the Panel
         * Such like Save, Delete
         *
         * @method controls/projects/project/media/FilePanel#$createTabs
         */
        $createButtons: function () {
            var self = this;

            this.getButtonBar().clear();

            // permissions
            if (parseInt(QUIQQER_CONFIG.permissions.media)) {
                new QUIButton({
                    image : 'fa fa-shield',
                    name  : 'permissions',
                    alt   : Locale.get('quiqqer/quiqqer', 'projects.project.site.media.filePanel.permissions'),
                    title : Locale.get('quiqqer/quiqqer', 'projects.project.site.media.filePanel.permissions'),
                    styles: {
                        'border-left-width' : 1,
                        'border-right-width': 1,
                        'float'             : 'right',
                        width               : 40
                    },
                    events: {
                        onClick: this.openPermissions
                    }
                }).inject(this.getHeader());
            }

            this.addButton(
                new QUIButton({
                    name     : 'save',
                    text     : Locale.get(lg, 'projects.project.site.media.filePanel.btn.save.text'),
                    textimage: 'fa fa-save',
                    events   : {
                        onClick: function () {
                            self.save();
                        }
                    }
                })
            ).addButton(
                new QUIButton({
                    'name'   : 'upload',
                    text     : Locale.get(lg, 'projects.project.site.media.filePanel.btn.replace.text'),
                    textimage: 'fa fa-upload',
                    events   : {
                        onClick: function () {
                            self.replace();
                        }
                    }
                })
            ).addButton(
                new QUIButtonSeparator()
            );


            if (this.$File.isActive()) {
                this.addButton(
                    new QUIButton({
                        name     : 'status',
                        text     : Locale.get(lg, 'projects.project.site.media.filePanel.btn.deactivate.text'),
                        textimage: 'fa fa-remove',
                        events   : {
                            onClick: this.toggleStatus
                        }
                    })
                );

            } else {
                this.addButton(
                    new QUIButton({
                        name     : 'status',
                        text     : Locale.get(lg, 'projects.project.site.media.filePanel.btn.activate.text'),
                        textimage: 'fa fa-ok',
                        events   : {
                            onClick: this.toggleStatus
                        }
                    })
                );
            }

            this.addButton(
                new QUIButton({
                    name  : 'delete',
                    alt   : Locale.get(lg, 'projects.project.site.media.filePanel.btn.delete.text'),
                    title : Locale.get(lg, 'projects.project.site.media.filePanel.btn.delete.text'),
                    icon  : 'fa fa-trash-o',
                    events: {
                        onClick: function () {
                            self.del();
                        }
                    },
                    styles: {
                        'float': 'right'
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
        $createTabs: function () {
            this.getCategoryBar().clear();

            this.$ButtonDetails = new QUIButton({
                text  : Locale.get(lg, 'projects.project.site.media.filePanel.details.text'),
                name  : 'details',
                icon  : 'fa fa-file-o',
                events: {
                    onClick: this.openDetails
                }
            });

            this.addCategory(this.$ButtonDetails);

            // image
            if (this.$File.getType() !== 'classes/projects/project/media/Image') {
                return;
            }


            this.$ButtonEffects = new QUIButton({
                text  : Locale.get(lg, 'projects.project.site.media.filePanel.image.effects.text'),
                name  : 'imageEffects',
                icon  : 'fa fa-magic',
                events: {
                    onClick: this.openImageEffects
                }
            });

            this.$ButtonPreview = new QUIButton({
                text  : Locale.get(lg, 'projects.project.site.media.filePanel.preview.text'),
                name  : 'preview',
                icon  : 'fa fa-eye',
                events: {
                    onClick: this.openPreview
                }
            });

            this.addCategory(this.$ButtonEffects);
            this.addCategory(this.$ButtonPreview);


            if (this.$File.getAttribute('mime_type') === 'image/svg+xml') {
                this.$ButtonEffects.getElm().setStyle('display', 'none');
            }
        },

        /**
         * unload the category and set the data to the file
         */
        $unloadCategory: function () {
            if (!this.$ButtonActiv || this.$__injected === false) {
                return;
            }

            var Body = this.getContent();
            var Form = Body.getElement('form');

            if (typeOf(Form) !== 'element') {
                return;
            }

            var data = FormUtils.getFormData(Form),
                File = this.getFile();

            for (var i in data) {
                if (!data.hasOwnProperty(i)) {
                    return;
                }


                // effects
                if (i.match('effect-')) {
                    File.setEffect(i.replace('effect-', ''), data[i]);
                    continue;
                }

                if ("file_name" === i) {
                    File.setAttribute('name', data[i]);
                }

                if ("file_priority" === i) {
                    File.setAttribute('priority', data[i]);
                }
            }

            if (typeof Form.elements.file_title !== 'undefined') {
                File.setAttribute('title', Form.elements.file_title.value);
            }

            if (typeof Form.elements.file_short !== 'undefined') {
                File.setAttribute('short', Form.elements.file_short.value);
            }

            if (typeof Form.elements.file_alt !== 'undefined') {
                File.setAttribute('alt', Form.elements.file_alt.value);
            }
        },

        /**
         * Opens the detail tab
         *
         * @method controls/projects/project/media/FilePanel#$createTabs
         */
        openDetails: function () {
            if (this.$ButtonDetails.isActive()) {
                return;
            }

            this.Loader.show();
            this.$unloadCategory();

            this.$ButtonActiv = this.$ButtonDetails;
            this.$ButtonActiv.setActive();


            var self = this,
                Body = this.getContent(),
                File = this.$File;

            Body.set('html', '');

            Template.get('project_media_file', function (result) {
                var Body = self.getContent();

                Body.set('html', '<form>' + result + '</form>');

                var Form = Body.getElement('form');

                Form.elements.file_title.value = File.getAttribute('title');
                Form.elements.file_alt.value   = File.getAttribute('alt');
                Form.elements.file_short.value = File.getAttribute('short');

                ControlUtils.parse(Form).then(function () {
                    return QUI.parse(Form);
                }).then(function () {
                    var dimension = '';

                    if (File.getAttribute('image_width') &&
                        File.getAttribute('image_height')) {
                        dimension = File.getAttribute('image_width') +
                            ' x ' +
                            File.getAttribute('image_height');
                    }

                    // set data to form
                    FormUtils.setDataToForm(
                        {
                            file_id       : File.getId(),
                            file_name     : File.getAttribute('name'),
                            file_file     : File.getAttribute('file'),
                            file_path     : File.getAttribute('path'),
                            file_type     : File.getAttribute('type'),
                            file_edate    : File.getAttribute('e_date'),
                            file_url      : File.getAttribute('cache_url'),
                            file_dimension: dimension,
                            file_md5      : File.getAttribute('md5hash'),
                            file_sha1     : File.getAttribute('sha1hash'),
                            file_size     : StringUtils.formatBytes(
                                File.getAttribute('filesize')
                            ),
                            file_priority : File.getAttribute('priority')
                        },
                        Form
                    );

                    MediaUtils.bindCheckMediaName(
                        Body.getElement('[name="file_name"]')
                    );

                    new QUIButton({
                        name  : 'download_file',
                        image : 'fa fa-download',
                        title : Locale.get(lg, 'projects.project.site.media.filePanel.btn.downloadFile.title'),
                        alt   : Locale.get(lg, 'projects.project.site.media.filePanel.btn.downloadFile.alt'),
                        events: {
                            onClick: function () {
                                self.getFile().download();
                            }
                        },
                        styles: {
                            'float': 'right'
                        }
                    }).inject(
                        Body.getElement('input[name="file_url"]'),
                        'after'
                    );

                    Body.getElements('[data-qui="controls/lang/InputMultiLang"]').forEach(function (Node) {
                        var Instance = QUI.Controls.getById(Node.get('data-quiid'));

                        if (!Instance) {
                            return;
                        }

                        if (Instance.isLoaded()) {
                            Instance.open();
                        } else {
                            Instance.addEvent('load', function () {
                                Instance.open();
                            });
                        }
                    });

                    self.Loader.hide();
                });
            });
        },

        /**
         * oben the preview of the image
         */
        openPreview: function () {
            if (this.$ButtonPreview.isActive()) {
                return;
            }

            this.$unloadCategory();

            this.$ButtonActiv = this.$ButtonPreview;
            this.$ButtonActiv.setActive();


            var Body = this.getContent();

            Body.set('html', '');

            var url = URL_DIR + this.$File.getAttribute('url');

            if (url.match('image.php')) {
                url = url + '&noresize=1';
            }

            new Element('img', {
                src   : url,
                styles: {
                    maxWidth: '100%'
                }
            }).inject(Body);
        },

        /**
         * Opens the image effects with preview
         */
        openImageEffects: function () {
            if (this.$ButtonEffects.isActive()) {
                return;
            }

            this.Loader.show();
            this.$unloadCategory();

            this.$ButtonActiv = this.$ButtonEffects;
            this.$ButtonActiv.setActive();

            var self    = this,
                Content = this.getContent();

            Content.set('html', '');

            Template.get('project_media_effects', function (result) {
                var WatermarkInput;
                var Effects = self.getFile().getEffects();

                Content.set(
                    'html',
                    '<form>' + result + '</form>'
                );

                var WatermarkPosition = Content.getElement('[name="effect-watermark_position"]'),
                    Watermark         = Content.getElement('.effect-watermark'),
                    WatermarkRatio    = Content.getElement('[name="effect-watermark_ratio"]'),
                    WatermarkCell     = Content.getElement('.effect-watermark-cell'),
                    WatermarkRow      = Content.getElement('.effect-watermark-row');

                self.$EffectWatermark = Content.getElement('[name="effect-watermark"]');

                self.$EffectPreview = new Element('img', {
                    src: URL_LIB_DIR + 'QUI/Projects/Media/bin/effectPreview.php'
                }).inject(Content.getElement('.preview-frame'));

                self.$EffectLoader = new QUILoader().inject(Content.getElement('.preview-frame'));

                var Form      = Content.getElement('form');
                var Greyscale = Content.getElement('[name="effect-greyscale"]');

                if (!("blur" in Effects)) {
                    Effects.blur = 0;
                }

                if (!("brightness" in Effects)) {
                    Effects.brightness = 0;
                }

                if (!("contrast" in Effects)) {
                    Effects.contrast = 0;
                }

                if (!("watermark" in Effects)) {
                    Effects.watermark = false;
                }

                if (!("watermark_position" in Effects)) {
                    Effects.watermark_position = false;
                }

                if (!("watermark_ratio" in Effects)) {
                    Effects.watermark_ratio = false;
                }

                new Element('input', {
                    name: 'effect-blur',
                    type: 'hidden'
                }).inject(Form);

                new Element('input', {
                    name: 'effect-brightness',
                    type: 'hidden'
                }).inject(Form);

                new Element('input', {
                    name: 'effect-contrast',
                    type: 'hidden'
                }).inject(Form);


                self.$EffectBlur = new QUIRange({
                    name     : 'effect-blur',
                    min      : 0,
                    max      : 100,
                    start    : [0],
                    step     : 1,
                    connect  : false,
                    Formatter: function (value) {
                        return parseInt(value.from) + ' - ' + parseInt(value.to);
                    },
                    events   : {
                        onChange: self.$refreshImageEffectFrame
                    }
                }).inject(Content.getElement('.effect-blur'));

                self.$EffectBrightness = new QUIRange({
                    name   : 'effect-brightness',
                    value  : Effects.brightness,
                    min    : -100,
                    max    : 100,
                    start  : [0],
                    connect: false,
                    events : {
                        onChange: self.$refreshImageEffectFrame
                    }
                }).inject(Content.getElement('.effect-brightness'));

                self.$EffectContrast = new QUIRange({
                    name   : 'effect-contrast',
                    value  : Effects.contrast,
                    min    : -100,
                    max    : 100,
                    start  : [0],
                    connect: false,
                    events : {
                        onChange: self.$refreshImageEffectFrame
                    }
                }).inject(Content.getElement('.effect-contrast'));

                self.$EffectBlur.setValue(Effects.blur);
                self.$EffectBrightness.setValue(Effects.brightness);
                self.$EffectContrast.setValue(Effects.contrast);


                Greyscale.checked = Effects.greyscale || false;
                Greyscale.addEvent('change', self.$refreshImageEffectFrame);

                WatermarkPosition.value = Effects.watermark_position || '';
                WatermarkPosition.addEvent('change', self.$refreshImageEffectFrame);

                WatermarkRatio.value = Effects.watermark_ratio || '';
                WatermarkRatio.addEvent('change', self.$refreshImageEffectFrame);

                // watermark
                var Select = new QUISelect({
                    menuWidth: 300,
                    styles   : {
                        width: 260
                    },
                    events   : {
                        onChange: function (value) {
                            if (value === 'default' || value === '') {
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
                }).inject(Watermark);

                Select.appendChild(
                    Locale.get(lg, 'projects.project.site.media.folderPanel.no.watermark'),
                    '',
                    'fa fa-remove'
                );

                Select.appendChild(
                    Locale.get(lg, 'projects.project.site.media.folderPanel.project.watermark'),
                    'default',
                    'fa fa-home'
                );

                Select.appendChild(
                    Locale.get(lg, 'projects.project.site.media.folderPanel.own.watermark'),
                    'own',
                    'fa fa-picture-o'
                );

                WatermarkInput = new MediaInput({
                    styles: {
                        clear    : 'both',
                        'float'  : 'left',
                        marginTop: 10
                    },
                    events: {
                        onChange: function (Input, value) {
                            self.$EffectWatermark.value = value;
                            self.$refreshImageEffectFrame();
                        }
                    }
                }).inject(WatermarkCell);

                WatermarkInput.setProject(self.getProject());

                if (Effects.watermark === '') {
                    Select.setValue('');

                } else if (Effects.watermark.toString().match('image.php')) {
                    Select.setValue('own');
                    WatermarkInput.setValue(Effects.watermark);
                } else {
                    Select.setValue('default');
                }


                self.$refreshImageEffectFrame();
                self.Loader.hide();
            });
        },

        /**
         * Refresh the effect preview image
         */
        $refreshImageEffectFrame: function () {
            if (!this.$EffectBlur || !this.$EffectBrightness || !this.$EffectContrast) {
                return;
            }

            var File              = this.getFile(),
                fileId            = File.getId(),
                project           = this.getProject().getName(),
                Content           = this.getContent(),
                Form              = Content.getElement('form'),
                WatermarkPosition = Content.getElement('[name="effect-watermark_position"]');

            var Greyscale = Content.getElement('[name="effect-greyscale"]');
            var url       = URL_LIB_DIR + 'QUI/Projects/Media/bin/effectPreview.php?';

            var effectBlur      = this.$EffectBlur.getValue();
            var effectBrightnes = this.$EffectBrightness.getValue();
            var effectContrast  = this.$EffectContrast.getValue();

            url = url + Object.toQueryString({
                id                : fileId,
                project           : project,
                blur              : parseInt(effectBlur.from),
                brightness        : effectBrightnes.from,
                contrast          : effectContrast.from,
                greyscale         : Greyscale.checked ? 1 : 0,
                watermark         : this.$EffectWatermark.value,
                watermark_position: WatermarkPosition.value,
                '__nocache'       : String.uniqueID()
            });

            this.$EffectLoader.show();

            if (typeof this.$effectDelay !== 'undefined' && this.$effectDelay) {
                clearTimeout(this.$effectDelay);
            }

            this.$effectDelay = (function () {
                Form.getElement('[name="effect-blur"]').value       = parseInt(effectBlur.from);
                Form.getElement('[name="effect-brightness"]').value = effectBrightnes.from;
                Form.getElement('[name="effect-contrast"]').value   = effectContrast.from;

                require(['image!' + url], function () {
                    this.$EffectPreview.set('src', url);
                    this.$EffectLoader.hide();
                }.bind(this));

            }).delay(300, this);
        },

        /**
         * event on file activate
         */
        $onFileActivate: function () {
            var Button = this.getButtonBar().getElement('status');

            Button.setAttribute('textimage', 'fa fa-remove');
            Button.setAttribute(
                'text',
                Locale.get(lg, 'projects.project.site.media.filePanel.btn.deactivate.text')
            );
        },

        /**
         * event on file deactivate
         */
        $onFileDeactivate: function () {
            var Button = this.getButtonBar().getElement('status');

            Button.setAttribute('textimage', 'fa fa-ok');
            Button.setAttribute(
                'text',
                Locale.get(lg, 'projects.project.site.media.filePanel.btn.activate.text')
            );
        },

        /**
         * Open the permissions
         */
        openPermissions: function () {
            var Parent = this.getParent(),
                File   = this.$File;

            require(['controls/permissions/Panel'], function (PermPanel) {
                Parent.appendChild(
                    new PermPanel({
                        Object: File
                    })
                );
            });
        }
    });
});
