/* jslint bitwise: true */
/* jshint evil: true */

/**
 * Bulk Upload -> Upload of multiple Files
 *
 * @module classes/request/Upload
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onFinish [this, uploadedFiles]
 * @event onUploadPartStart [this]
 * @event onUploadPartEnd [this]
 * @event onRefresh [this] - is triggered after one complete file upload
 */
define('classes/request/BulkUpload', [

    'qui/QUI',
    'qui/classes/DOM',
    'qui/utils/Object',
    'qui/utils/Math',
    'Locale'

], function(QUI, QDOM, ObjectUtils, QUIMath, QUILocale) {
    'use strict';

    const cyrb53 = (str, seed = 0) => {
        let h1 = 0xdeadbeef ^ seed, h2 = 0x41c6ce57 ^ seed;

        for (let i = 0, len = str.length, ch; i < len; i++) {
            ch = str.charCodeAt(i);
            h1 = Math.imul(h1 ^ ch, 2654435761);
            h2 = Math.imul(h2 ^ ch, 1597334677);
        }

        h1 = Math.imul(h1 ^ (h1 >>> 16), 2246822507);
        h1 ^= Math.imul(h2 ^ (h2 >>> 13), 3266489909);
        h2 = Math.imul(h2 ^ (h2 >>> 16), 2246822507);
        h2 ^= Math.imul(h1 ^ (h1 >>> 13), 3266489909);

        return 4294967296 * (2097151 & h2) + (h1 >>> 0);
    };


    const STATUS_WAITING = 0;
    const STATUS_RUNNING = 2;
    const STATUS_DONE = 1;

    return new Class({

        Extends: QDOM,
        Type: 'classes/request/BulkUpload',

        options: {
            parentId: false,
            project: false,
            phpOnFinish: false,
            phpOnStart: false,
            params: {}
        },

        initialize: function(options) {
            this.parent(options);

            this.$size = 0;
            this.$uploaded = 0;

            this.$files = {};
            this.$result = [];

            this.$currentChunkSize = (1024 * 256); // 256kb
            this.$currentRangeStart = 0;
            this.$currentFileSize = 0;

            this.$CurrentFile = null;
            this.$currentHash = null;

            this.$MessageHandler = null;
            this.$LoadingMessage = null;
        },

        $calc: function(files) {
            this.$size = 0;

            if (!files.length) {
                return;
            }

            // slize mathod
            this.$slice_method = 'slice';

            if ('mozSlice' in files[0]) {
                this.$slice_method = 'mozSlice';
            } else {
                if ('webkitSlice' in files[0]) {
                    this.$slice_method = 'webkitSlice';
                }
            }

            files.forEach((file) => {
                let hash;
                let File = file;
                let path = '';

                if (typeof File.fileObject !== 'undefined') {
                    File = File.fileObject;
                    path = file.fullPath;
                    hash = cyrb53(path);
                } else {
                    hash = cyrb53(File.name);
                }

                this.$files[hash] = {
                    file: File,
                    status: STATUS_WAITING,
                    path: path
                };

                this.$size = this.$size + File.size;
            });
        },

        /**
         * Return the file entry
         *
         * {
         *      file  : File,
         *      status: STATUS_WAITING,
         *      path  : path
         *  }
         *
         * @return {*}
         */
        getFileEntry: function() {
            return this.$files[this.$currentHash];
        },

        /**
         * starts the upload
         * @param files
         */
        upload: function(files) {
            this.$calc(files);
            QUI.fireEvent('upload', [this]);
            this.$run();
        },

        /**
         * Returns the current progress
         * @return {{running: number, total: *, waiting: number, size: (*|number), uploaded: (boolean|number|*), files: ([]|*), done: number, percent: *}}
         */
        getProgress: function() {
            let waiting = 0;
            let running = 0;
            let done = 0;

            for (let file in this.$files) {
                if (!this.$files.hasOwnProperty(file)) {
                    continue;
                }

                switch (this.$files[file].status) {
                    case STATUS_WAITING:
                        waiting++;
                        break;

                    case STATUS_RUNNING:
                        running++;
                        break;

                    case STATUS_DONE:
                        done++;
                        break;
                }
            }

            const total = Object.getLength(this.$files);

            return {
                total: total,
                files: this.$files,
                waiting: waiting,
                running: running,
                done: done,
                size: this.$size,
                uploaded: this.$uploaded,
                percent: QUIMath.percent(this.$uploaded, this.$size)
            };
        },

        $refreshLoadingMessage: function() {
            if (!this.$LoadingMessage) {
                return;
            }

            const progress = this.getProgress();
            const MessageNode = this.$LoadingMessage.getElm();

            MessageNode.getElement('.quiqqer-message-loading-progress-bar').setStyle('width', progress.percent + '%');
        },

        /**
         * internal upload starting
         */
        $run: function() {
            if (!this.$CurrentFile) {
                this.$CurrentFile = this.$getNextFile();
            }

            if (!this.$CurrentFile) {
                // all is uploaded
                this.fireEvent('finish', [this, this.$result]);
                return;
            }

            this.$uploadFile(this.$CurrentFile).then(() => {
                this.$CurrentFile = null; // next file
                this.fireEvent('refresh', [this]);
                this.$refreshLoadingMessage();
                return this.$run();
            });
        },

        /**
         * get next file to upload
         *
         * @return {boolean}
         */
        $getNextFile: function() {
            for (let filehash in this.$files) {
                if (!this.$files.hasOwnProperty(filehash)) {
                    continue;
                }

                if (this.$files[filehash].status !== STATUS_DONE) {
                    this.$CurrentFile = this.$files[filehash].file;
                    this.$currentHash = filehash;

                    return this.$CurrentFile;
                }
            }

            // all files are done
            return false;
        },

        $uploadFile: function(File) {
            if (typeof File !== 'undefined') {
                this.$currentRangeStart = 0;
                this.$currentFileSize = File.size;
                this.$currentRangeEnd = this.$currentRangeStart + this.$currentChunkSize;

                if (this.$currentRangeEnd > this.$currentFileSize) {
                    this.$currentRangeEnd = this.$currentFileSize;
                }
            } else {
                File = this.$CurrentFile;
            }

            this.fireEvent('uploadPartStart', [this]);

            return this.$uploadFilePart(File).then(() => {
                this.fireEvent('uploadPartEnd', [this]);

                if (this.getFileEntry().status === STATUS_DONE) {
                    // done
                    return;
                }

                return this.$uploadFile();
            });
        },

        $uploadFilePart: function(File) {
            this.getFileEntry().status = STATUS_RUNNING;

            // the file part
            const data = File[this.$slice_method](
                this.$currentRangeStart,
                this.$currentRangeEnd
            );

            let FileParams = this.getAttribute('params');
            FileParams = Object.assign({}, FileParams); // workaround, otherwise always duplicates itself

            // extra params for ajax function
            const UploadParams = ObjectUtils.combine((FileParams || {}), {
                parentid: this.getAttribute('parentId'),
                project: this.getAttribute('project'),
                onfinish: this.getAttribute('phpOnFinish'),
                onstart: this.getAttribute('phpOnStart'),
                file: JSON.encode({
                    chunksize: this.$currentChunkSize,
                    chunkstart: this.$currentRangeStart
                }),
                filesize: File.size,
                filename: File.name,
                filetype: File.type,
                filepath: this.getFileEntry().path
            });

            if (typeof FileParams.extract !== 'undefined') {
                if (FileParams.extract) {
                    UploadParams.extract = 1;
                }
            }

            if (typeof FileParams.callable !== 'undefined') {
                UploadParams.callable = FileParams.callable;
            }

            if (typeof FileParams.package !== 'undefined') {
                UploadParams.package = FileParams.package;
            }

            UploadParams.fileparams = JSON.encode(FileParams);

            if (typeof UploadParams.lang === 'undefined') {
                UploadParams.lang = QUILocale.getCurrent();
            }

            const p = Object.keys(UploadParams).reduce((acc, key) => {
                if (typeof UploadParams[key] !== 'object') {
                    acc[key] = UploadParams[key];
                }
                return acc;
            }, {});

            let qs = Object.toQueryString(p);
            qs = btoa(qs);

            const url = URL_LIB_DIR + 'QUI/Upload/bin/upload.php?qs=' + qs;

            return fetch(url, {
                method: 'PUT',
                cache: 'no-cache',
                headers: {
                    'Content-Type': 'application/octet-stream',
                    'Content-Range': 'bytes ' + this.$currentRangeStart + '-' + this.$currentRangeEnd + '/' +
                        this.$currentFileSize
                },
                body: data
            }).then((response) => {
                this.$uploaded = this.$uploaded + (this.$currentRangeEnd - this.$currentRangeStart);

                if (this.$currentRangeEnd === this.$currentFileSize) {
                    this.getFileEntry().status = STATUS_DONE;
                }

                this.$currentRangeStart = this.$currentRangeEnd;
                this.$currentRangeEnd = this.$currentRangeStart + this.$currentChunkSize;

                if (this.$currentRangeEnd > this.$currentFileSize) {
                    this.$currentRangeEnd = this.$currentFileSize;
                }

                if (this.$currentRangeStart > this.$currentFileSize) {
                    this.$currentRangeStart = this.$currentFileSize;
                }

                return response.text().then((text) => {
                    this.$parseResult(text);
                });
            }).catch(function(err) {
                console.error(err);
            });
        },

        /**
         * Parse the request result from the server
         * send errors to the message handler and cancel the request if some errors exist
         *
         * @param {String} responseText - server answer
         */
        $parseResult: function(responseText) {
            const str = responseText || '',
                len = str.length,
                start = 9,
                end = len - 10;

            if (!len) {
                return;
            }

            if (!str.match('<quiqqer>') || !str.match('</quiqqer>')) {
                this.$error = true;
            }

            if (str.substring(0, start) !== '<quiqqer>' ||
                str.substring(end, len) !== '</quiqqer>') {
                this.$error = true;
            }

            // callback
            const result = eval('(' + str.substring(start, end) + ')');


            // exist a main exception?
            if (result.Exception) {
                this.$error = true;
                this.$execute = false;
            }

            if (result.Exception) {
                this.$error = true;
            }

            if (result.result) {
                this.$result.push(result.result);
            }
        }
    });
});
