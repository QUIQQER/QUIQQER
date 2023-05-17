/**
 * QUI upload class for multiple files
 *
 * drag/drop upload script
 * add dragdrop Events to the elements
 *
 * @module classes/request/Upload
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onDragenter [{DOMEvent}, {DOMNode Target}, {self}]
 * @event onDragleave [{DOMEvent}, {DOMNode Target}, {self}]
 * @event onDragover [{DOMEvent}, {DOMNode Target}, {self}]
 * @event onDrop [{DOMEvent}, {Array file list}, {DOMNode Target}, {self}]
 * @event onDragend [{DOMEvent}, {DOMNode Target}, {self}]
 */
define('classes/request/Upload', [

    'qui/QUI',
    'qui/classes/DOM'

], function (QUI, QDOM) {
    "use strict";

    const DEFAULT_FILES_TO_IGNORE = [
        '.DS_Store', // OSX indexing file
        'Thumbs.db'  // Windows indexing file
    ];

    /**
     * QUI upload class for multiple files
     * add dragdrop Events to the elements
     *
     * @class classes/request/Upload
     *
     * @fires onDragenter [event, Target, this]
     * @fires onDragend [event, Target, this]#
     * @fires onDrop [event, file_list, Target, this]
     *
     * @param {Array} elements - list pf DOMNode Elements
     * @param {Object} events - list of event functions
     */
    return new Class({

        Extends: QDOM,
        Type   : 'classes/request/Upload',

        $Request: null,
        $result : null,

        options: {},

        initialize: function (elements, events) {
            const self = this;

            // extend mootools with desktop drag drop
            Object.append(Element.NativeEvents, {
                dragenter: 2,
                dragleave: 2,
                dragover : 2,
                dragend  : 2,
                drop     : 2
            });

            this.addEvents(events);
            this.$elms = elements;

            const add_events = {
                dragenter: function (event) {
                    self.fireEvent('dragenter', [
                        event,
                        event.target,
                        self
                    ]);
                },

                dragleave: function (event) {
                    self.fireEvent('dragleave', [
                        event,
                        event.target,
                        self
                    ]);
                },

                dragover: function (event) {
                    event.preventDefault();
                },

                drop: function (event) {
                    if (QUI.$dropped === Slick.uidOf(event.target)) {
                        return;
                    }

                    // no double dropping
                    QUI.$dropped = Slick.uidOf(event.target);

                    (function () {
                        QUI.$dropped = false;
                    }).delay(100);

                    self.$getFilesByEvent(event).then(function (files) {
                        self.fireEvent('drop', [
                            event,
                            files,
                            event.target,
                            self
                        ]);

                        self.fireEvent('dragend', [
                            event,
                            event.target,
                            self
                        ]);
                    });

                    event.preventDefault();
                    event.stop();
                },

                dragend: function (event) {
                    event.preventDefault();
                    event.stop();

                    self.fireEvent('dragend', [
                        event,
                        event.target,
                        self
                    ]);
                }
            };

            for (let i = 0, len = this.$elms.length; i < len; i++) {
                if (this.$elms[i].get('data-drag-events')) {
                    continue;
                }

                this.$elms[i].addEvents(add_events);
                this.$elms[i].set('data-drag-events', 1);
            }
        },

        /**
         * Trigger the send event
         *
         * @param {Event} event - event triggered from onDrop
         * @return {Promise}
         */
        $getFilesByEvent: function (event) {
            if (typeof FileReader === 'undefined' ||
                typeof FileList === 'undefined') {
                QUI.getMessageHandler(function (MH) {
                    MH.addError("Your Browser doesn't support Drag & Drop uploads");
                });

                return Promise.resolve([]);
            }

            return this.getDataTransferFiles(event.event.dataTransfer);
        },

        /**
         * @param dataTransfer
         * @return {*}
         */
        getDataTransferFiles: function (dataTransfer) {
            const dataTransferFiles = [];
            const folderPromises = [];
            const filePromises = [];

            [].slice.call(dataTransfer.items).forEach((listItem) => {
                if (typeof listItem.webkitGetAsEntry === 'function') {
                    const entry = listItem.webkitGetAsEntry();

                    if (entry) {
                        if (entry.isDirectory) {
                            folderPromises.push(this.traverseDirectory(entry));
                        } else {
                            filePromises.push(this.getFile(entry));
                        }
                    }
                } else {
                    dataTransferFiles.push(listItem);
                }
            });

            if (folderPromises.length) {
                const flatten = (array) => array.reduce((a, b) => a.concat(Array.isArray(b) ? flatten(b) : b), []);

                return Promise.all(folderPromises).then((fileEntries) => {
                    const flattenedEntries = flatten(fileEntries);
                    // collect async promises to convert each fileEntry into a File object
                    flattenedEntries.forEach((fileEntry) => {
                        filePromises.push(this.getFile(fileEntry));
                    });
                    return this.handleFilePromises(filePromises, dataTransferFiles);
                });
            } else if (filePromises.length) {
                return this.handleFilePromises(filePromises, dataTransferFiles);
            }

            return Promise.resolve(dataTransferFiles);
        },

        /**
         * @param entry
         * @return {*}
         */
        traverseDirectory: function (entry) {
            const self = this;
            const reader = entry.createReader();
            // Resolved when the entire directory is traversed
            return new Promise((resolveDirectory) => {
                const iterationAttempts = [];
                const errorHandler = () => {
                };

                function readEntries() {
                    // According to the FileSystem API spec, readEntries() must be called until
                    // it calls the callback with an empty array.
                    reader.readEntries((batchEntries) => {
                        if (!batchEntries.length) {
                            // Done iterating this particular directory
                            resolveDirectory(Promise.all(iterationAttempts));
                        } else {
                            // Add a list of promises for each directory entry.  If the entry is itself
                            // a directory, then that promise won't resolve until it is fully traversed.
                            iterationAttempts.push(
                                Promise.all(batchEntries.map((batchEntry) => {
                                    if (batchEntry.isDirectory) {
                                        return self.traverseDirectory(batchEntry);
                                    }
                                    return Promise.resolve(batchEntry);
                                }))
                            );
                            // Try calling readEntries() again for the same dir, according to spec
                            readEntries();
                        }
                    }, errorHandler);
                }

                // initial call to recursive entry reader function
                readEntries();
            });
        },

        /**
         * @param file
         * @param entry
         * @return {{fullPath: (string|*), size, fileObject, lastModifiedDate, name, webkitRelativePath: *, lastModified: *, type}}
         */
        packageFile: function (file, entry) {
            return {
                fileObject        : file, // provide access to the raw File object (required for uploading)
                fullPath          : entry ? this.copyString(entry.fullPath) : file.name,
                lastModified      : file.lastModified,
                lastModifiedDate  : file.lastModifiedDate,
                name              : file.name,
                size              : file.size,
                type              : file.type,
                webkitRelativePath: file.webkitRelativePath
            };
        },

        /**
         * @param entry
         * @return {*}
         */
        getFile: function (entry) {
            return new Promise((resolve) => {
                entry.file((file) => {
                    resolve(this.packageFile(file, entry));
                });
            });
        },

        /**
         * @param promises
         * @param fileList
         * @return {*}
         */
        handleFilePromises: function (promises, fileList) {
            return Promise.all(promises).then((files) => {
                files.forEach((file) => {
                    if (!this.shouldIgnoreFile(file)) {
                        fileList.push(file);
                    }
                });

                return fileList;
            });
        },

        /**
         * @param aString
         * @return {string}
         */
        copyString: function (aString) {
            return ` ${aString}`.slice(1);
        },

        /**
         * @param file
         * @return {boolean}
         */
        shouldIgnoreFile: function (file) {
            return DEFAULT_FILES_TO_IGNORE.indexOf(file.name) >= 0;
        }
    });
});
