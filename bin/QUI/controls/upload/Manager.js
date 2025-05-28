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
    'use strict';

    const lg = 'quiqqer/core';

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
         * @param {object} params - the params what would be sent, too
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
                archiveFiles = [];

            params = params || {};

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
                let list = '<ul style="clear: both; margin-top: 2rem; list-style-type: none; padding-left: 0;">';

                for (i = 0, len = archiveFiles.length; i < len; i++) {
                    list = list + '<li>' +
                        '<input id="upload-file-' + i + '" type="checkbox" value="' + archiveFiles[i].name + '" />' +
                        '<label for="upload-file-' + i + '" style="line-height: 20px; margin-left: 10px;">' +
                        Locale.get(lg, 'upload.manager.message.archivfile.label', {
                            file: archiveFiles[i].name
                        }) +
                        '</label>' +
                        '</li>';
                }

                list = list + '</ul>';

                // ask for extraction
                new QUIAlert({
                    icon: 'fa fa-light fa-file-zipper',
                    title: Locale.get(lg, 'upload.manager.message.archivfile.title'),
                    content: '<div style="font-size: 1.5rem; text-align: center">' +
                        '<span class="fa fa-light fa-file-zipper" style="font-size: 5rem; margin-bottom: 1rem"></span>' +
                        '<br />' +
                        Locale.get(lg, 'upload.manager.message.archivfile.text') +
                        '</div>' +
                        list,
                    closeButtonText: Locale.get(lg, 'upload.manager.message.archivfile.btn.start'),
                    maxHeight: 500,
                    maxWidth: 510,
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


            this.$maxPercent = files.length * 100;

            require(['classes/request/BulkUpload'], (BulkUpload) => {
                const p = Object.keys(params).reduce((acc, key) => {
                    if (typeof params[key] !== 'object' || key === 'extract') {
                        acc[key] = params[key];
                    }

                    return acc;
                }, {});

                this.fireEvent('fileUploadRefresh', [this, 0]);

                new BulkUpload({
                    parentId: params.parentid,
                    project: params.project,
                    phpOnFinish: rf,
                    params: p,
                    events: {
                        onFinish: (BulkUploadInstance, uploadedFiles) => {
                            this.fireEvent('finished', [this, uploadedFiles]);
                            this.fireEvent('complete', [this, uploadedFiles]);

                            if (typeof params.onComplete === 'function') {
                                params.onComplete(uploadedFiles);
                            }

                            if (typeof params.events !== 'undefined' &&
                                typeof params.events.onComplete === 'function') {
                                params.events.onComplete(uploadedFiles);
                            }

                            this.fireEvent('fileUploadRefresh', [
                                this,
                                100
                            ]);
                        },
                        uploadPartEnd: (BulkUploadInstance) => {
                            const progress = BulkUploadInstance.getProgress();
                            this.fireEvent('fileUploadRefresh', [this, progress.percent]);
                        }
                    }
                }).upload(files);
            });
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
                            onCancel: func_oncancel,
                            onRefresh: this.$onFileUploadRefresh
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
