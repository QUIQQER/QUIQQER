/**
 * A media file
 *
 * @module classes/projects/project/media/Folder
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require classes/projects/project/media/Item
 * @require Ajax
 * @require UploadManager
 */

define('classes/projects/project/media/Folder', [

    'classes/projects/project/media/Item',
    'Ajax',
    'UploadManager'

], function (MediaItem, Ajax, UploadManager) {
    "use strict";

    /**
     * @class classes/projects/project/media/Folder
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: MediaItem,
        Type   : 'classes/projects/project/media/Folder',

        /**
         * create a sub folder
         *
         * @method classes/projects/project/media/Folder#createFolder
         *
         * @param {String} newfolder    - New folder name
         * @param {Function} oncomplete - callback( new_folder_id ) function
         */
        createFolder: function (newfolder, oncomplete) {
            return new Promise(function (resolve, reject) {

                Ajax.post('ajax_media_folder_create', function (result) {
                    var items = this.getMedia().$parseResultToItem(result);

                    if (typeof oncomplete === 'function') {
                        oncomplete(items);
                    }

                    resolve(items);
                }.bind(this), {
                    project  : this.getMedia().getProject().getName(),
                    parentid : this.getId(),
                    newfolder: newfolder,
                    onError  : reject
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
        getChildren: function (oncomplete, params) {
            return new Promise(function (resolve, reject) {

                params = params || {};

                Ajax.get('ajax_media_folder_children', function (result) {
                    if (typeof oncomplete === 'function') {
                        oncomplete(result);
                    }

                    resolve(result);

                }, {
                    project : this.getMedia().getProject().getName(),
                    folderid: this.getId(),
                    params  : JSON.encode(params),
                    onError : reject
                });

            }.bind(this));
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
        uploadFiles: function (files, onfinish) {
            return new Promise(function (resolve) {

                UploadManager.uploadFiles(files, 'ajax_media_upload', {
                    project : this.getMedia().getProject().getName(),
                    parentid: this.getId(),
                    events  : {
                        onComplete: function () {

                            if (typeof onfinish === 'function') {
                                onfinish();
                            }

                            resolve();
                        }
                    }
                });

            }.bind(this));
        },

        /**
         * Folder replace
         * you cannot replace a folder at the moment
         *
         * @method classes/projects/project/media/Folder#replace
         */
        replace: function () {
            // nothing, you cannot replace a folder
        }
    });
});
