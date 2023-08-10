/**
 * Upload manager
 * Uploads files and show the upload status
 *
 * @module controls/upload/Manager
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onFileCancel [ {self}, {File} ]
 * @event onFileComplete [ {self}, {File} ]
 * @event onFileUploadRefresh [ {self}, {Number} percent ]
 */
define('controls/upload/Manager', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/utils/Progressbar',
    'qui/controls/windows/Alert',
    'qui/utils/Math',
    'controls/upload/File',
    'Ajax',
    'Locale',

    'css!controls/upload/Manager.css'

], function (QUI, QUIPanel, QUIProgressbar, QUIAlert, MathUtils, UploadFile, Ajax, Locale) {
    "use strict";

    const lg = 'quiqqer/quiqqer';

    /**
     * @class controls/upload/Manager
     *
     * @param {Object} options
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIPanel,
        Type: 'controls/upload/Manager',

        Binds: [
            '$onCreate',
            '$onInject',
            'uploadFiles',
            'clear',
            '$onFileUploadRefresh'
        ],

        options: {
            icon: 'fa fa-upload',
            pauseAllowed: true,
            contextMenu: true
        },

        initialize: function (options) {
            this.parent(options);

            this.$files = [];
            this.$container = null;
            this.$uploads = {};

            this.$maxPercent = 0;
            this.$uploadPerCents = {};

            this.$Container = null;

            this.addEvents({
                onCreate: this.$onCreate,
                onInject: this.$onInject
            });
        },

        /**
         * event : onCreate
         */
        $onCreate: function () {
            this.$Container = new Element('div', {
                'class': 'upload-manager'
            }).inject(this.getContent());

            this.addButton({
                icon: 'fa fa-trash',
                title: Locale.get(lg, 'upload.manager.clear'),
                styles: {
                    'float': 'right'
                },
                events: {
                    onClick: this.clear
                }
            });
        },

        /**
         * event: on inject
         */
        $onInject: function () {
            this.setAttribute('title', Locale.get(lg, 'upload.manager.title'));
        },

        /**
         * Clear all upload
         */
        clear: function () {
            for (let i = 0, len = this.$files.length; i < len; i++) {
                this.$files[i].getElm().destroy();
            }

            this.$files = [];
            this.$Container.set('html', '');
        },

        /**
         * Send a Message to the Message Handler
         *
         * @param {Array} message -
         */
        sendMessage: function (message) {
            QUI.getMessageHandler(function (MH) {
                MH.add(MH.parse(message));
            });
        },

        /**
         * Trigger function for the php upload
         *
         * @param {Number|String} uploadid
         */
        isFinish: function (uploadid) {
            // uploadid
            if (this.$uploads[uploadid]) {
                this.$uploads[uploadid].finish();
            }
        },

        /**
         * Upload files to the destination
         *
         * @method controls/upload/Manager#uploadFiles
         *
         * @param {Array} files - Array of file list
         * @param {String} rf - php request function
         * @param {object} params - the params what would be send, too
         */
        uploadFiles: function (files, rf, params) {
            if (typeof files === 'undefined') {
                return;
            }

            if (!files.length) {
                return;
            }

            let Container;

            // application/zip
            let i, len;

            const self = this;

            let foundPackageFiles = false,
                archiveFiles = [],
                extract = false;

            params = params || {};

            if (typeof params.extract !== 'undefined') {
                extract = params.extract;
            }

            // check for archive files (like zip or tar)
            // if undefined, ask for it
            if (typeof params.extract === 'undefined') {
                for (i = 0, len = files.length; i < len; i++) {
                    if (files[i].type === 'application/zip') {
                        archiveFiles.push(files[i]);
                        foundPackageFiles = true;
                    }
                }
            }

            if (foundPackageFiles) {
                let list = '';

                for (i = 0, len = archiveFiles.length; i < len; i++) {
                    list = list + '<div>' +
                        '<input id="upload-file-' + i + '" type="checkbox" value="' + archiveFiles[i].name + '" />' +
                        '<label for="upload-file-' + i + '" style="line-height: 20px; margin-left: 10px;">' +
                        Locale.get(lg, 'upload.manager.message.archivfile.label', {
                            file: archiveFiles[i].name
                        }) +
                        '</label>' +
                        '</div>';
                }


                // ask for extraction
                new QUIAlert({
                    title: Locale.get(lg, 'upload.manager.message.archivfile.title'),
                    content: Locale.get(lg, 'upload.manager.message.archivfile.text') + '<br />' + list,
                    closeButtonText: Locale.get(lg, 'upload.manager.message.archivfile.btn.start'),
                    events: {
                        onClose: function (Win) {
                            const Body = Win.getContent(),
                                checkboxs = Body.getElements('input[type="checkbox"]'),
                                ext = {};

                            // collect all which must be extract
                            for (i = 0, len = checkboxs.length; i < len; i++) {
                                if (checkboxs[i].checked) {
                                    ext[checkboxs[i].get('value')] = true;
                                }
                            }

                            params.extract = ext;

                            self.uploadFiles(files, rf, params);
                        }
                    }
                }).open();

                return;
            }


            let file_params;
            let events = false;

            this.$maxPercent = files.length * 100;

            const cleanupFiles = function (File) {
                const newFileList = [];

                for (let i = 0, len = self.$files.length; i < len; i++) {
                    if (self.$files[i].$File !== File.$File) {
                        newFileList.push(self.$files[i]);
                    }
                }

                self.$files = newFileList;
            };

            const onComplete = function (File) {
                cleanupFiles(File);
                self.fireEvent('fileComplete', [
                    self,
                    File
                ]);

                if (File.getElm().getParent() === document.body) {
                    (function () {
                        moofx(File.getElm()).animate({
                            opacity: 0
                        }, {
                            duration: 200,
                            callback: function () {
                                File.getElm().destroy();
                            }
                        });
                    }).delay(1000);
                }
            };

            const onRefresh = function (File, percent) {
                self.$uploadPerCents[File.getId()] = percent;
                self.$onFileUploadRefresh();
            };

            const onError = function (Exception, File) {
                cleanupFiles(File);

                if ('error' in self.$events) {
                    self.fireEvent('error', [Exception]);
                    return;
                }

                QUI.getMessageHandler(function (MessageHandler) {
                    MessageHandler.add(Exception);
                });
            };

            const onCancel = function (File) {
                cleanupFiles(File);
                self.fireEvent('fileCancel', [
                    self,
                    File
                ]);
            };

            if (files.length > 1) {
                require(['classes/request/BulkUpload'], (BulkUpload) => {
                    const p = Object.keys(params).reduce((acc, key) => {
                        if (typeof params[key] !== 'object') {
                            acc[key] = params[key];
                        }
                        return acc;
                    }, {});

                    new BulkUpload({
                        parentId: params.parentid,
                        project: params.project,
                        phpOnFinish: rf,
                        params: p,
                        events: {
                            onFinish: () => {
                                this.fireEvent('finished', [this]);
                                this.fireEvent('complete', [this]);

                                if (typeof params.onComplete === 'function') {
                                    params.onComplete();
                                }

                                if (typeof params.events !== 'undefined' &&
                                    typeof params.events.onComplete === 'function') {
                                    params.events.onComplete();
                                }

                                this.fireEvent('fileUploadRefresh', [
                                    this,
                                    100
                                ]);
                            }
                        }
                    }).upload(files);
                });

                return;
            }

            for (i = 0, len = files.length; i < len; i++) {
                file_params = Object.clone(params);
                file_params.extract = !!(extract && extract[files[i].name]);

                if (typeof file_params.events !== 'undefined') {
                    events = file_params.events;

                    delete file_params.events;
                }

                const QUIFile = new UploadFile(files[i], {
                    phpfunc: rf,
                    params: file_params,
                    events: events,
                    pauseAllowed: this.getAttribute('pauseAllowed'),
                    contextMenu: this.getAttribute('contextMenu')
                });

                QUIFile.addEvents({
                    onComplete: onComplete,
                    onRefresh: onRefresh,
                    onError: onError,
                    onCancel: onCancel
                });

                if (file_params.phponstart) {
                    QUIFile.setAttribute('phponstart', file_params.phponstart);
                }

                this.$files.push(QUIFile);

                if (this.$Container) {
                    QUIFile.inject(this.$Container, 'top');
                } else {
                    // exist upload container? ... not nice but functional
                    Container = document.getElement('.qui-panel-content .upload-manager');

                    if (Container) {
                        QUIFile.inject(Container, 'top');
                    } else {
                        // @todo multiple anzeige umsetzen
                        const Node = QUIFile.create();

                        Node.setStyles({
                            background: '#fff',
                            border: '1px solid #f1f1f1',
                            bottom: 10,
                            boxShadow: '0 0 10px rgba(0, 0, 0, 0.3)',
                            position: 'absolute',
                            right: 10,
                            width: 300,
                            zIndex: 1000
                        });

                        Node.inject(document.body);
                    }
                }

                QUIFile.upload();

                events = false;
            }
        },

        /**
         * Starts a none html5 upload
         *
         * @param {controls/upload/Form} Form - Upload form object
         */
        injectForm: function (Form) {
            if (this.$Container) {
                Form.createInfo().inject(this.$Container, 'top');
            }

            this.$uploads[Form.getId()] = Form;
        },

        /**
         * Check if unfinished uploads exist from the user
         *
         * @method controls/upload/Manager#getUnfinishedUploads
         */
        getUnfinishedUploads: function () {
            Ajax.get('ajax_uploads_unfinished', function (files) {
                if (!files.length) {
                    return;
                }

                let i, len, QUIFile, params,
                    func_oncancel, func_oncomplete;

                QUI.getMessageHandler(function (MH) {
                    MH.addInformation(
                        Locale.get(lg, 'upload.manager.message.not.finish')
                    );
                });

                // events
                func_oncancel = function (File) {
                    Ajax.post('ajax_uploads_cancel', function () {
                        File.destroy();
                    }, {
                        file: File.getFilename()
                    });
                };

                func_oncomplete = function () {

                };

                // create
                for (i = 0, len = files.length; i < len; i++) {
                    if (!files[i].params) {
                        continue;
                    }

                    params = files[i].params;

                    if (!params.phpfunc) {
                        // @todo trigger error
                        continue;
                    }

                    if (!params.file) {
                        // @todo trigger error
                        continue;
                    }

                    QUIFile = new UploadFile(params.file, {
                        phpfunc: params.phpfunc,
                        params: params,
                        events: {
                            onComplete: func_oncomplete,
                            onCancel: func_oncancel
                        }
                    });

                    if (this.$Container) {
                        QUIFile.inject(this.$Container, 'top');
                    }

                    QUIFile.refresh();
                }
            });
        },

        /**
         * event : on file upload refresh
         * display the percent of the upload
         */
        $onFileUploadRefresh: function () {
            const percent = MathUtils.percent(
                Object.values(this.$uploadPerCents).sum(),
                this.$maxPercent
            );

            this.fireEvent('fileUploadRefresh', [
                this,
                percent
            ]);
        }
    });
});
