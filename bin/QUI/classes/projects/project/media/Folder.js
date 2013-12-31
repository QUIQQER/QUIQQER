/**
 * A media file
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/projects/media/Item
 *
 * @module classes/projects/media/Folder
 * @package com.pcsg.qui.js.classes.projects.media.Folder
 * @namespace classes.projects.media
 */

define('classes/projects/project/media/Folder', [

    'classes/projects/project/media/Item',
    'Ajax',
    'UploadManager'

], function(MediaItem, Ajax, UploadManager)
{
    "use strict";

    /**
     * @class QUI.classes.projects.media.File
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : MediaItem,
        Type    : 'classes/projects/project/media/Folder',

        /**
         * create a sub folder
         *
         * @method QUI.classes.projects.media.Folder#createFolder
         *
         * @param {String} newfolder    - New folder name
         * @param {Function} oncomplete - callback( new_folder_id ) function
         */
        createFolder : function(newfolder, oncomplete)
        {
            var self = this;

            Ajax.post('ajax_media_folder_create', function(result, Request)
            {
                oncomplete(
                    self.getMedia().$parseResultToItem( result )
                );
            }, {
                project   : this.getMedia().getProject().getName(),
                parentid  : this.getId(),
                newfolder : newfolder
            });
        },

        /**
         * create a sub folder
         *
         * @method QUI.classes.projects.media.Folder#createFolder
         *
         * @param {String} newfolder    - New folder name
         * @param {Function} oncomplete - callback( children ) function
         */
        getChildren : function(oncomplete)
        {
            Ajax.get('ajax_media_folder_children', function(result, Request)
            {
                oncomplete( result );
            }, {
                project    : this.getMedia().getProject().getName(),
                folderid   : this.getId()
            });
        },

        /**
         * Upload files to the folder
         *
         * @method QUI.classes.projects.media.Folder#uploadFiles
         *
         * @param {Array|Filelist} files
         * @param {Function} onfinish - callback function
         */
        uploadFiles : function(files, onfinish)
        {
            onfinish = onfinish || function() {};

            UploadManager.uploadFiles(
                files,
                'ajax_media_upload',
                {
                    project  : this.getMedia().getProject().getName(),
                    parentid : this.getId(),
                    events   : {
                        onComplete : onfinish
                    }
                }
            );
        },

        /**
         * Folder replace
         * you cannot replace a folder at the moment
         *
         * @method QUI.classes.projects.media.Folder#replace
         */
        replace : function()
        {
            // nothing, you cannot replace a folder
        }
    });
});