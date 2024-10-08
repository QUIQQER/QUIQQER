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
    'qui/controls/buttons/Separator',
    'qui/controls/buttons/Select',
    'qui/controls/windows/Confirm',
    'qui/controls/input/Range',
    'qui/utils/Form',
    'utils/Template',
    'controls/projects/project/media/Input',
    'Projects',
    'Locale',
    'Ajax',

    'css!controls/projects/project/media/FolderPanel.css'

], function (QUI,
             QUIPanel,
             QUIButton,
             QUIButtonSeparator,
             QUISelect,
             QUIConfirm,
             QUIRange,
             QUIFormUtils,
             Template,
             MediaInput,
             Projects,
             Locale,
             Ajax) {
    "use strict";

    const lg = 'quiqqer/core';

    return new Class({

        Extends: QUIPanel,
        Type   : 'controls/projects/project/media/FolderPanel',

        Binds: [
            '$onInject',
            '$onDestroy',
            'openDetails',
            'openEffects',
            'openPriorityOrder',
            'openPermissions',
            'executeEffectsRecursive',
            '$refreshImageEffectFrame',
            '$onFolderRefresh'
        ],

        options: {
            folderId: false,
            project : false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Folder = null;
            this.$Media = null;
            this.$EffectPreview = null;
            this.$EffectBlur = null;
            this.$EffectBrightness = null;
            this.$EffectContrast = null;
            this.$EffectWatermark = null;

            this.$loaded = false;

            this.addEvents({
                onInject : this.$onInject,
                onDestroy: this.$onDestroy
            });
        },

        /**
         * @event : on panel inject
         */
        $onInject: function () {
            this.$load();
        },

        /**
         * event : on panel destroy
         */
        $onDestroy: function () {
            if (this.$Folder) {
                this.$Folder.removeEvents({
                    onRefresh: this.$onFolderRefresh,
                    onSave   : this.$onFolderRefresh
                });
            }
        },

        /**
         * load the folder data
         */
        $load: function () {
            this.Loader.show();

            if (this.$Folder || this.$loaded) {
                this.Loader.hide();
                return;
            }

            this.$loaded = true;

            const self    = this,
                  Project = Projects.get(this.getAttribute('project')),
                  Media   = Project.getMedia();

            Media.get(this.getAttribute('folderId')).then(function (Folder) {
                self.$Folder = Folder;
                self.$Media = Media;

                self.$Folder.addEvents({
                    onRefresh: self.$onFolderRefresh,
                    onSave   : self.$onFolderRefresh
                });

                const title = Project.getName() + '://' + Folder.getAttribute('file');

                self.setAttributes({
                    icon : 'fa fa-folder-open-o',
                    title: title,
                    name : 'projects-media-file-panel-' + Folder.getId(),
                    id   : 'projects-media-file-panel-' + Folder.getId()
                });

                self.refresh();

                self.$createCategories();
                self.$createButtons();

                self.getCategoryBar().firstChild().click();
            });
        },

        /**
         * on refresh
         */
        $onFolderRefresh: function () {
            const Project = Projects.get(this.getAttribute('project')),
                  title   = Project.getName() + '://' + this.$Folder.getAttribute('file');

            this.setAttributes({
                title: title
            });

            this.refresh();

            const Category = this.getActiveCategory();

            if (Category.getAttribute('name') === 'details') {
                this.openDetails(false);
                return;
            }

            this.openEffects();
        },

        /**
         * Saves the folder
         *
         * @param {Function} [callback] - optional, callback function
         * @return {Promise}
         */
        save: function (callback) {
            if (!this.$Folder) {
                return Promise.resolve();
            }

            const self = this;

            this.Loader.show();
            this.$unloadCategory();

            return this.$Folder.save().then(function () {
                if (typeof callback === 'function') {
                    callback();
                }

                self.Loader.hide();
            }).catch(function (Exception) {
                console.error(Exception);

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
        del: function () {
            const self = this;

            new QUIConfirm({
                icon     : 'fa fa-trash-o',
                texticon : 'fa fa-trash-o',
                maxWidth : 533,
                maxHeight: 300,
                title    : Locale.get('quiqqer/core', 'projects.project.site.media.folderPanel.window.delete.title', {
                    folder: this.$Folder.getAttribute('file')
                }),

                text: Locale.get('quiqqer/core', 'projects.project.site.media.folderPanel.window.delete.text', {
                    folder: this.$Folder.getAttribute('file')
                }),

                information: Locale.get('quiqqer/core', 'projects.project.site.media.folderPanel.window.delete.information', {
                    folder: this.$Folder.getAttribute('file')
                }),
                autoclose  : false,
                events     : {
                    onSubmit: function (Win) {
                        Win.Loader.show();

                        self.$Folder.del(function () {
                            self.close();
                            Win.close();
                        });
                    }
                }
            }).open();
        },

        /**
         * Open the folder details
         *
         * @param {Boolean} [unload] - execute an unload
         */
        openDetails: function (unload) {
            if (typeof unload === 'undefined') {
                unload = true;
            }

            if (unload) {
                this.$unloadCategory();
            }


            const self = this,
                  Body = this.getContent();

            Body.set('html', '');
            Body.setStyle('opacity', 0);

            this.Loader.show();

            Template.get('project/media/folder', function (result) {
                Body.set(
                    'html',
                    '<form>' + result + '</form>'
                );

                const Form  = Body.getElement('form'),
                      Order = Form.getElement('[name="order"]');

                QUIFormUtils.setDataToForm(self.$Folder.getAttributes(), Form);

                Order.setStyles({
                    'borderRadius': 0,
                    'float'       : 'left'
                });

                new QUIButton({
                    alt    : Locale.get(lg, 'projects.project.site.panel.btn.priority'),
                    title  : Locale.get(lg, 'projects.project.site.panel.btn.priority'),
                    icon   : 'fa fa-sort-amount-asc',
                    'class': 'field-container-item',
                    events : {
                        onClick: self.openPriorityOrder
                    },
                    styles : {
                        border   : '1px solid rgba(147, 128, 108, 0.25)',
                        boxShadow: 'none',
                        width    : 50
                    }
                }).inject(Order, 'after');

                QUI.parse().then(Form).then(function () {
                    return self.$Folder.getSize();
                }).then(function (bytes) {
                    let value;
                    const sizes = [
                        'Bytes',
                        'KB',
                        'MB',
                        'GB',
                        'TB'
                    ];

                    bytes = parseInt(bytes);

                    if (bytes === 0) {
                        value = '0 Byte';
                    } else {
                        const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
                        value = Math.round(bytes / Math.pow(1024, i), 2) + ' ' + sizes[i];
                    }

                    Form.elements.size.value = value;

                    // cleanup button
                    Form.elements.cleanup.addEvent('click', function (e) {
                        e.stop();

                        const Fa = Form.elements.cleanup.getElement('.fa');

                        Fa.removeClass('fa-paint-brush');
                        Fa.addClass('fa-spinner fa-spin');

                        self.$Folder.clearCache().then(function () {
                            Fa.addClass('fa-paint-brush');
                            Fa.removeClass('fa-spinner');
                            Fa.removeClass('fa-spin');

                            QUI.getMessageHandler().then(function (MH) {
                                MH.addSuccess(Locale.get(lg, 'message.quiqqer.project.media.fileCacheClear.success'));
                            });
                        });
                    });

                    Form.elements.cleanup.disabled = false;

                    self.Loader.hide();
                }).catch(function () {
                    self.Loader.hide();
                }).then(function () {
                    moofx(Body).animate({
                        opacity: 1
                    });
                });
            });
        },

        /**
         * Open the folder effects
         *
         * @param {Boolean} [unload] - execute an unload
         */
        openEffects: function (unload) {
            if (typeof unload === 'undefined') {
                unload = true;
            }

            if (unload) {
                this.$unloadCategory();
            }

            this.Loader.show();

            const self   = this,
                  Body   = this.getContent(),
                  Folder = this.$Folder;

            Body.set('html', '');

            Template.get('project/media/effects', function (result) {
                Body.set(
                    'html',
                    '<form>' + result + '</form>'
                );

                let WatermarkInput;

                const Effects           = Folder.getEffects(),
                      Greyscale         = Body.getElement('[name="effect-greyscale"]'),
                      WatermarkPosition = Body.getElement('[name="effect-watermark_position"]'),
                      WatermarkRatio    = Body.getElement('[name="effect-watermark_ratio"]'),
                      Watermark         = Body.getElement('.effect-watermark'),
                      WatermarkCell     = Body.getElement('.effect-watermark-cell'),
                      WatermarkRow      = Body.getElement('.effect-watermark-row');

                self.$EffectWatermark = Body.getElement('[name="effect-watermark"]');

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

                self.$EffectBlur = new QUIRange({
                    name  : 'effect-blur',
                    value : Effects.blur,
                    min   : 0,
                    max   : 100,
                    events: {
                        onChange: self.$refreshImageEffectFrame
                    }
                }).inject(Body.getElement('.effect-blur'));

                self.$EffectBrightness = new QUIRange({
                    name  : 'effect-brightness',
                    value : Effects.brightness,
                    min   : -100,
                    max   : 100,
                    events: {
                        onChange: self.$refreshImageEffectFrame
                    }
                }).inject(Body.getElement('.effect-brightness'));

                self.$EffectContrast = new QUIRange({
                    name  : 'effect-contrast',
                    value : Effects.contrast,
                    min   : -100,
                    max   : 100,
                    events: {
                        onChange: self.$refreshImageEffectFrame
                    }
                }).inject(Body.getElement('.effect-contrast'));

                // extra values
                Greyscale.checked = Effects.greyscale || false;
                Greyscale.addEvent('change', self.$refreshImageEffectFrame);

                WatermarkPosition.value = Effects.watermark_position || '';
                WatermarkPosition.addEvent('change', self.$refreshImageEffectFrame);

                WatermarkRatio.value = Effects.watermark_ratio || '';
                WatermarkRatio.addEvent('change', self.$refreshImageEffectFrame);


                new QUIButton({
                    text  : Locale.get(lg, 'projects.project.site.media.folderPanel.btn.effectsRecursive'),
                    styles: {
                        'float'     : 'right',
                        marginBottom: 20
                    },
                    events: {
                        onClick: self.executeEffectsRecursive
                    }
                }).inject(
                    Body.getElement('.data-table'), 'before'
                );

                // watermark
                const Select = new QUISelect({
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
                        clear  : 'both',
                        'float': 'left'
                    },
                    events: {
                        onChange: function (Input, value) {
                            self.$EffectWatermark.value = value;
                            self.$refreshImageEffectFrame();
                        }
                    }
                }).inject(WatermarkCell);

                WatermarkInput.setProject(Folder.getMedia().getProject());

                if (Effects.watermark === '') {
                    Select.setValue('');

                } else if (Effects.watermark.toString().match('image.php')) {
                    Select.setValue('own');
                    WatermarkInput.setValue(Effects.watermark);
                } else {
                    Select.setValue('default');
                }

                // get one image from the folder
                Folder.getChildren(function (children) {
                    children = children.data;

                    for (let i = 0, len = children.length; i < len; i++) {
                        if (children[i].type === 'image') {
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
         * Opens the children order
         */
        openPriorityOrder: function () {
            const self = this;

            this.createSheet({
                icon       : 'fa fa-sort-amount-asc',
                title      : Locale.get('quiqqer/core', 'projects.project.site.media.priority.sheet.title'),
                closeButton: {
                    textimage: 'fa fa-remove',
                    text     : Locale.get('quiqqer/core', 'cancel')
                },
                events     : {
                    onOpen: function (Sheet) {
                        const Content = Sheet.getContent();

                        Content.setStyles({
                            padding: 20
                        });

                        require([
                            'controls/projects/project/media/Priority'
                        ], function (Priority) {
                            new Priority({
                                project : self.getAttribute('project'),
                                folderId: self.getAttribute('folderId')
                            }).inject(Content);
                        });
                    }
                }
            }).show();
        },

        /**
         * Opens the confirm window for the resursive effect execution
         */
        executeEffectsRecursive: function () {
            if (!this.$Folder) {
                return;
            }

            const self = this;

            new QUIConfirm({
                title      : Locale.get(lg, 'media.folderPanel.window.effect.recursive.title'),
                maxWidth   : 533,
                maxHeight  : 300,
                text       : Locale.get(lg, 'media.folderPanel.window.effect.recursive.text'),
                information: Locale.get(lg, 'media.folderPanel.window.effect.recursive.information'),
                autoclose  : false,
                events     : {
                    onSubmit: function (Win) {
                        Win.Loader.show();

                        self.save(function () {
                            Ajax.post('ajax_media_folder_recursiveEffects', function () {
                                Win.close();
                            }, {
                                folderId: self.$Folder.getId(),
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
        $createButtons: function () {
            const self = this;

            this.getButtonBar().clear();

            // permissions
            new QUIButton({
                image : 'fa fa-shield',
                name  : 'permissions',
                alt   : Locale.get('quiqqer/core', 'projects.project.site.media.filePanel.permissions'),
                title : Locale.get('quiqqer/core', 'projects.project.site.media.filePanel.permissions'),
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

            this.addButton(
                new QUIButton({
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
         * create the left categories of the panel
         */
        $createCategories: function () {
            this.getCategoryBar().clear();

            this.addCategory({
                text  : Locale.get(lg, 'projects.project.site.media.filePanel.details.text'),
                name  : 'details',
                icon  : 'fa fa-folder-open-o',
                events: {
                    onActive: this.openDetails
                }
            });

            this.addCategory({
                text  : Locale.get(lg, 'projects.project.site.media.filePanel.image.effects.text'),
                name  : 'effects',
                icon  : 'fa fa-magic',
                events: {
                    onActive: this.openEffects
                }
            });
        },

        /**
         * Refresh the preview
         */
        $refreshImageEffectFrame: function () {
            if (!this.$Media) {
                return;
            }

            const PreviewParent = this.getContent().getElement('.preview-frame');

            if (typeof this.$previewImageData === 'undefined') {
                PreviewParent.set(
                    'html',
                    Locale.get(lg, 'projects.project.site.folder.has.no.images')
                );

                return;
            }

            if (!this.$EffectBlur || !this.$EffectBrightness || !this.$EffectContrast) {
                return;
            }

            let Image = PreviewParent.getElement('img');

            if (!Image) {
                PreviewParent.set('html', '');

                Image = new Element('img', {
                    src: URL_LIB_DIR + 'QUI/Projects/Media/bin/effectPreview.php'
                }).inject(this.getContent().getElement('.preview-frame'));
            }


            const fileId            = this.$previewImageData.id,
                  project           = this.$Media.getProject().getName(),
                  Content           = this.getContent(),
                  WatermarkPosition = Content.getElement('[name="effect-watermark_position"]'),
                  WatermarkRatio    = Content.getElement('[name="effect-watermark_ratio"]');

            const Greyscale = Content.getElement('[name="effect-greyscale"]');
            let url = URL_LIB_DIR + 'QUI/Projects/Media/bin/effectPreview.php?';

            url = url + Object.toQueryString({
                id                : fileId,
                project           : project,
                blur              : this.$EffectBlur.getValue(),
                brightness        : this.$EffectBrightness.getValue(),
                contrast          : this.$EffectContrast.getValue(),
                greyscale         : Greyscale.checked ? 1 : 0,
                watermark         : this.$EffectWatermark.value,
                watermark_position: WatermarkPosition.value,
                watermark_ratio   : WatermarkRatio.value,
                '__nocache'       : String.uniqueID()
            });

            Image.set('src', url);
        },

        /**
         * unload the category and set the data to the folder
         */
        $unloadCategory: function () {
            const Body = this.getContent();
            const Form = Body.getElement('form');

            if (!Form || !Form.getParent()) {
                return;
            }

            if (!this.$Folder) {
                return;
            }

            const data = QUIFormUtils.getFormData(Form);

            for (const i in data) {
                if (!data.hasOwnProperty(i)) {
                    return;
                }

                // effects
                if (i.match('effect-')) {
                    this.$Folder.setEffect(i.replace('effect-', ''), data[i]);
                    continue;
                }

                this.$Folder.setAttribute(i, data[i]);
            }
        },

        /**
         * Open the permissions
         */
        openPermissions: function () {
            const Parent = this.getParent(),
                  Folder = this.$Folder;

            require(['controls/permissions/Panel'], function (PermPanel) {
                Parent.appendChild(
                    new PermPanel({
                        Object: Folder
                    })
                );
            });
        }
    });
});
