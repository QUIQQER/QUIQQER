/**
 * A media file
 *
 * @module classes/projects/project/media/Folder
 * @author www.pcsg.de (Henning Leutz)
 */
define('classes/projects/project/media/Folder', [

    'classes/projects/project/media/Item',
    'Ajax',
    'UploadManager'

], function(MediaItem, Ajax, UploadManager) {
    'use strict';

    /**
     * @class classes/projects/project/media/Folder
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: MediaItem,
        Type: 'classes/projects/project/media/Folder',

        /**
         * create a sub folder
         *
         * @method classes/projects/project/media/Folder#createFolder
         *
         * @param {String} newfolder    - New folder name
         * @param {Function} oncomplete - callback( new_folder_id ) function
         */
        createFolder: function(newfolder, oncomplete) {
            return new Promise(function(resolve, reject) {
                Ajax.post('ajax_media_folder_create', function(result) {
                    var items = this.getMedia().$parseResultToItem(result);

                    if (typeof oncomplete === 'function') {
                        oncomplete(items);
                    }

                    resolve(items);
                }.bind(this), {
                    project: this.getMedia().getProject().getName(),
                    parentid: this.getId(),
                    newfolder: newfolder,
                    onError: reject
                });

            }.bind(this));
        },

        /**
         * Return the children
         *
         * @method classes/projects/project/media/Folder#createFolder
         *
         * @param {Function} [oncomplete] - callback( children ) function
         * @param {Object} [params] - order params
         *
         * @return Promise
         */
        getChildren: function(oncomplete, params) {
            return new Promise(function(resolve, reject) {
                params = params || {};

                Ajax.get('ajax_media_folder_children', function(result) {
                    if (typeof oncomplete === 'function') {
                        oncomplete(result);
                    }

                    resolve(result);
                }, {
                    project: this.getMedia().getProject().getName(),
                    folderid: this.getId(),
                    params: JSON.encode(params),
                    onError: reject
                });
            }.bind(this));
        },

        /**
         * Return the size of the folder
         *
         * @return {Promise}
         */
        getSize: function() {
            return new Promise((resolve, reject) => {
                Ajax.get('ajax_media_folder_getSize', resolve, {
                    project: this.getMedia().getProject().getName(),
                    id: this.getId(),
                    onError: reject
                });
            });
        },

        /**
         * Upload files to the folder
         *
         * @method classes/projects/project/media/Folder#uploadFiles
         *
         * @param {Array|Object} files - Array | Filelist
         * @param {Function} [onfinish] - callback function
         *
         * @return Promise
         */
        uploadFiles: function(files, onfinish) {
            return new Promise((resolve) => {
                UploadManager.uploadFiles(files, 'ajax_media_upload', {
                    project: this.getMedia().getProject().getName(),
                    parentid: this.getId(),
                    events: {
                        onComplete: function(uploadedFiles) {
                            if (typeof onfinish === 'function') {
                                onfinish(uploadedFiles);
                            }

                            resolve(uploadedFiles);
                        }
                    }
                });
            });
        },

        /**
         * Download the folder
         *
         * @method classes/projects/project/media/Folder#download
         */
        download: function() {
            var url = Ajax.$url + '?' + Ajax.parseParams('ajax_media_folder_download', {
                project: this.getMedia().getProject().getName(),
                folderId: this.getId()
            });

            // create a iframe
            var Frame = document.id('download-frame');

            if (!Frame) {
                new Element('iframe#download-frame', {
                    src: url,
                    styles: {
                        position: 'absolute',
                        width: 100,
                        height: 100,
                        left: 0,
                        top: 0
                    },
                    events: {
                        load: function() {
                            this.destroy();
                        }
                    }
                }).inject(document.body);

                return;
            }

            Frame.set('src', url);
        },

        /**
         * Folder replace
         * you cannot replace a folder at the moment
         *
         * @method classes/projects/project/media/Folder#replace
         */
        replace: function() {
            // nothing, you cannot replace a folder
        }
    });
});
