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
    'Ajax'

], function(MediaItem, Ajax)
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
            Ajax.post('ajax_media_folder_create', function(result, Request)
            {
                var Folder = Request.getAttribute('Folder'),
                    Media  = Folder.getMedia();

                Request.getAttribute('oncomplete')(
                    Media.$parseResultToItem( result )
                );
            }, {
                project    : this.getMedia().getProject().getName(),
                parentid   : this.getId(),
                newfolder  : newfolder,
                oncomplete : oncomplete,
                Folder     : this
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
                Request.getAttribute('oncomplete')(result);
            }, {
                project    : this.getMedia().getProject().getName(),
                folderid   : this.getId(),
                oncomplete : oncomplete
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

            QUI.UploadManager.uploadFiles(
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