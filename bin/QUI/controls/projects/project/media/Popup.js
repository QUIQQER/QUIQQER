/**
 * A media Popup
 *
 * @module controls/projects/project/media/Popup
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/projects/project/media/Popup', [

    'qui/controls/windows/Popup',
    'qui/controls/windows/Confirm',
    'qui/controls/buttons/Button',
    'controls/projects/project/media/Panel',
    'controls/projects/Select',
    'Projects',
    'Locale',
    'Ajax'

], function (QUIPopup, QUIConfirm, QUIButton, MediaPanel, ProjectSelect, Projects, QUILocale, Ajax) {
    "use strict";

    let LAST_FILE_ID = false;

    return new Class({

        Extends: QUIPopup,
        Type   : 'controls/projects/project/media/Popup',

        Binds: [
            '$onCreate',
            '$onOpen',
            '$onOpenBegin',
            '$getDetails'
        ],

        options: {
            project             : false,
            fileid              : false,
            closeButtonText     : QUILocale.get('quiqqer/core', 'cancel'),
            breadcrumb          : true,
            selectable          : true,
            selectable_types    : false,   // you can specified which types are selectable
            selectable_mimetypes: false    // you can specified which mime types are selectable
        },

        initialize: function (options) {
            this.parent(options);

            this.$Panel = null;
            this.$folderData = {
                id: this.getAttribute('fileid') || 1
            };

            this.$created = false;

            this.addEvents({
                onCreate   : this.$onCreate,
                onOpen     : this.$onOpen,
                onOpenBegin: this.$onOpenBegin,
                onClose    : function () {
                    if (this.$Panel) {
                        this.$Panel.destroy();
                    }
                }.bind(this)
            });
        },

        /**
         * event : on create
         */
        $onCreate: function () {
            if (this.$created) {
                return;
            }

            this.Loader.show();

            let Media, Project, Content;

            const self    = this,
                  project = this.getAttribute('project');

            Content = this.getContent();

            // project selection, if no project exists
            if (!project) {
                Projects.getList(function (result) {
                    const length = Object.getLength(result);

                    if (length === 1) {
                        self.setAttribute('project', Object.keys(result)[0]);
                        self.$onCreate();

                        return;
                    }

                    const SelectContainer = new Element('div', {
                        styles: {
                            height   : '100%',
                            left     : 0,
                            padding  : 20,
                            position : 'absolute',
                            textAlign: 'center',
                            top      : 0,
                            width    : '100%'
                        },
                        html  : '<div style="margin-bottom: 20px;">' +
                                QUILocale.get(
                                    'quiqqer/core',
                                    'projects.project.site.media.popup.noProject.text'
                                ) +
                                '</div>'
                    }).inject(Content);

                    new ProjectSelect({
                        langSelect: false,
                        styles    : {
                            'float': 'none',
                            display: 'inline-block'
                        },
                        events    : {
                            onChange: function (value) {
                                if (value === '') {
                                    return;
                                }

                                self.setAttribute('project', value);
                                self.Loader.show();

                                moofx(SelectContainer).animate({
                                    opacity: 0
                                }, {
                                    duration: 250,
                                    callback: function () {
                                        self.$onCreate();
                                    }
                                });
                            }
                        }
                    }).inject(SelectContainer);

                    this.Loader.hide();
                }.bind(this));

                return;
            }

            Project = Projects.get(project);
            Media = Project.getMedia();

            this.addButton(
                new QUIButton({
                    text     : QUILocale.get('quiqqer/core', 'accept'),
                    textimage: 'fa fa-check',
                    events   : {
                        onClick: function () {
                            self.$getDetails(self.$folderData, function (data) {
                                self.$submit(data, true);
                            });
                        }
                    }
                })
            );

            Content.setStyles({
                padding: 0
            });

            let fileId = this.getAttribute('fileid');

            if (!fileId) {
                fileId = LAST_FILE_ID;
            }

            Ajax.get('ajax_media_file_getParentId', (parentId) => {
                this.$Panel = new MediaPanel(Media, {
                    startid             : parentId,
                    dragable            : false,
                    collapsible         : false,
                    selectable          : true,
                    breadcrumb          : this.getAttribute('breadcrumb'),
                    selectable_types    : this.getAttribute('selectable_types'),
                    selectable_mimetypes: this.getAttribute('selectable_mimetypes'),
                    isInPopup           : true,
                    events              : {
                        onCreate: (Panel) => {
                            Panel.getElm().setStyle('borderRadius', 0);
                            this.Loader.hide();
                        },

                        onChildClick: (Panel, imageData) => {
                            this.$itemClick(imageData);
                        },

                        onUploadOpenBegin: (Panel) => {
                            moofx(Panel.getContent()).animate({
                                opacity: 0
                            });
                            this.hideButtons();
                        },

                        onUploadClose: (Panel) => {
                            this.showButtons();

                            moofx(Panel.getContent()).animate({
                                opacity: 1
                            });
                        }
                    }
                });

                this.$Panel.inject(Content);

                if (this.isOpened()) {
                    this.$Panel.resize();
                }
            }, {
                fileid : fileId,
                project: Project.getName()
            });

            this.$created = true;
        },

        /**
         * event : on open
         */
        $onOpen: function () {
            if (this.$Panel) {
                this.$Panel.resize();
            }
        },

        /**
         * event: on open begin
         */
        $onOpenBegin: function () {
            const ckDialogs = document.getElements('.cke_dialog');

            if (!ckDialogs.length) {
                return;
            }

            // ckeditor stuff has extrem high zindex
            let currentIndex = this.getElm().getStyle('z-index');

            for (let i = 0, len = ckDialogs.length; i < len; i++) {
                if (currentIndex < parseInt(ckDialogs[i].getStyle('z-index'))) {
                    currentIndex = parseInt(ckDialogs[i].getStyle('z-index'));
                }
            }

            this.Background.getElm().setStyle('z-index', currentIndex + 9);
            this.getElm().setStyle('z-index', currentIndex + 10);
        },

        /**
         * If item is inactive
         * @param {Object} imageData - data of the image
         */
        $activateItem: function (imageData) {
            const self = this;

            this.close();

            const Confirm = new QUIConfirm({
                title      : QUILocale.get('quiqqer/core', 'projects.project.site.media.popup.window.activate.title'),
                text       : QUILocale.get('quiqqer/core', 'projects.project.site.media.popup.window.activate.text'),
                information: QUILocale.get('quiqqer/core', 'projects.project.site.media.popup.window.activate.information'),
                autoclose  : false,
                events     : {
                    onCancel: function () {
                        require([
                            'controls/projects/project/media/Popup'
                        ], function (MediaPopup) {
                            const MP = new MediaPopup(self.getAttributes());

                            if ("submit" in self.$events) {
                                self.$events.submit.each(function (f) {
                                    MP.addEvent('submit', f);
                                });
                            }

                            MP.open();
                        });
                    },

                    onSubmit: function (Win) {
                        // activate file
                        Win.Loader.show();

                        Ajax.post('ajax_media_activate', function () {
                            Win.close();
                            self.$submit(imageData, true);
                        }, {
                            project: imageData.project,
                            fileid : imageData.id
                        });
                    }
                }
            });

            (function () {
                Confirm.open();
            }).delay(500);
        },

        /**
         * submit
         * @param {Object} imageData      - data of the image
         * @param {Boolean} [folderCheck] - (optional) make folder submit check?
         */
        $submit: function (imageData, folderCheck) {
            folderCheck = folderCheck || false;

            if (typeof imageData === 'undefined') {
                return;
            }

            // if folder is in the selectable_types, you can select folders
            if (folderCheck) {
                const folders = this.getAttribute('selectable_types');

                if (folders && folders.contains('folder')) {
                    LAST_FILE_ID = imageData.id;

                    this.close();
                    this.fireEvent('submit', [
                        this,
                        imageData
                    ]);
                    return;
                }
            }


            if (imageData.type === 'folder') {
                this.$Panel.openID(imageData.id);
                this.$folderData = imageData;
                return;
            }

            LAST_FILE_ID = imageData.id;

            this.close();
            this.fireEvent('submit', [
                this,
                imageData
            ]);
        },

        /**
         * event : click on item
         * @param {Object} imageData -  data of the image
         */
        $itemClick: function (imageData) {
            const self = this;

            this.$Panel.Loader.hide();

            this.$getDetails(imageData, function (data) {
                if (data.type === 'folder') {
                    self.$submit(imageData);
                    return;
                }

                if (!parseInt(data.active)) {
                    self.$Panel.Loader.hide();
                    self.$activateItem(imageData);
                    return;
                }

                self.$submit(imageData);
            });
        },

        /**
         * Get details of a media item
         *
         * @param {Object} imageData - media data
         * @param {Function} callback
         */
        $getDetails: function (imageData, callback) {
            let project = this.getAttribute('project');

            if (this.$Panel) {
                project = this.$Panel.getMedia().getProject().getName();
            }

            Ajax.get('ajax_media_details', callback, {
                project: project,
                fileid : imageData.id
            });
        }
    });
});
