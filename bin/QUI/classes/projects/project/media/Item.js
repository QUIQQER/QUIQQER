/**
 * Parent class for all media items like file, image, folder
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 * @requires controls/projects/media/FilePanel
 *
 * @module classes/projects/media/Item
 * @package com.pcsg.qui.js.classes.projects.media.Item
 * @namespace QUI.classes.projects.media
 */

define('classes/projects/project/media/Item', [

    'qui/classes/DOM',
    'Ajax',
    'qui/utils/Object'

], function(DOM, Ajax, Utils)
{
    "use strict";

    /**
     * @class classes/projects/project/media/Item
     *
     * @event onSave [this]
     * @event onDelete [this]
     * @event onActivate [this]
     * @event onDeactivate [this]
     *
     * @param {classes/projects/project/Media} Media
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : DOM,
        Type    : 'classes/projects/project/media/Item',

        options : {
            id      : 0,
            name    : '',
            title   : '',
            alt     : '',
            'short' : '',
            active  : '',

            c_user : '',
            e_user : '',

            watermark    : false,
            roundcorners : false,

            mime_type    : '',
            image_height : '',
            image_width  : ''
        },

        initialize : function(params, Media)
        {
            this.$Media = Media;
            this.$Panel = null;

            this.parent( params );
        },

        /**
         * Refresh the params from the database to the file
         */
        refresh : function(oncomplete)
        {
            this.getMedia().getData(this.getId(), function(result)
            {
                this.setAttributes( result );

                oncomplete(this);

            }.bind( this ));
        },

        /**
         * Open the File in an AppPanel or create a new AppPanel
         *
         * @method QUI.classes.projects.media.Item#openInPanel
         * @params {MUI.Apppanel} Panel - optional
         */
//        openInPanel : function(Panel)
//        {
//            this.$Panel = new QUI.controls.projects.media.FilePanel( this );
//        },

        /**
         * Returns the Media Object of the item
         *
         * @method QUI.classes.projects.media.Item#getMedia
         * @return {QUI.classes.projects.Media}
         */
        getMedia : function()
        {
            return this.$Media;
        },

        /**
         * Opens the File in a new Browser Window
         */
//        openInWindow : function()
//        {
//            if ( this.getType() === 'QUI.classes.projects.media.Folder' ) {
//                return;
//            }
//
//            var url = QUI.Ajax.$url +'?'+ QUI.Ajax.parseParams('ajax_media_file_preview', {
//                project : this.getMedia().getProject().getName(),
//                fileid  : this.getId()
//            });
//
//            window.open( url );
//        },

        /**
         * Returns the ID of the item
         *
         * @method QUI.classes.projects.media.Item#getId
         * @return {Integer}
         */
        getId : function()
        {
            return this.getAttribute('id');
        },

        /**
         * Returns the breadcrumb entries (parent path)
         *
         * @method QUI.classes.projects.media.Item#getBreadcrumb
         * @param {Function} oncomplete - callback Function
         */
        getBreadcrumb : function(oncomplete)
        {
            Ajax.get('ajax_media_breadcrumb', function(result, Request)
            {
                Request.getAttribute('oncomplete')(result);
            }, {
                project    : this.getMedia().getProject().getName(),
                fileid     : this.getId(),
                oncomplete : oncomplete
            });
        },

        /**
         * Save the File attributes to the database
         *
         * @method QUI.classes.projects.media.Item#save
         * @fires onSave [this]
         * @param {Function} oncomplete - [optional] callback Function
         * @params {Object} params      - [optional], parameters that are linked to the request object
         */
        save : function(oncomplete, params)
        {
            params = Utils.combine(params, {
                project    : this.getMedia().getProject().getName(),
                fileid     : this.getId(),
                attributes : JSON.encode( this.getAttributes() ),
                oncomplete : oncomplete,
                File       : this
            });


            Ajax.post('ajax_media_file_save', function(result, Request)
            {
                var File = Request.getAttribute('File');

                File.setAttributes( result );
                File.fireEvent('save', [File]);

                if ( Request.getAttribute('oncomplete') ) {
                    Request.getAttribute('oncomplete')(result, Request);
                }
            }, params);
        },

        /**
         * Delete the item
         *
         * @method QUI.classes.projects.media.Item#del
         *
         * @fires onDelete [this]
         *
         * @param {Function} oncomplete - [optional] callback Function
         * @params {Object} params      - [optional], parameters that are linked to the request object
         */
        del : function(oncomplete, params)
        {
            this.fireEvent('delete', [this]);
            this.getMedia().del( this.getId(), oncomplete, params );
        },

        /**
         * Activate the File
         *
         * @method QUI.classes.projects.media.Item#activate
         *
         * @fires onActivate [this]
         *
         * @param {Function} oncomplete - [optional] callback Function
         * @params {Object} params      - [optional], parameters that are linked to the request object
         */
        activate : function(oncomplete, params)
        {
            this.fireEvent('activate', [this]);
            this.getMedia().activate( this.getId(), oncomplete, params );

            /*
            QUI.Ajax.post('ajax_media_file_activate', function(result, Request)
            {
                var File = Request.getAttribute('File');

                File.setAttribute('active', 1);
                File.fireEvent('activate', [File]);

                if ( Request.getAttribute('oncomplete') ) {
                    Request.getAttribute('oncomplete')(result, Request);
                }
            }, {
                File       : this,
                project    : this.getMedia().getProject().getName(),
                fileid     : this.getId(),
                oncomplete : oncomplete
            });
            */
        },

        /**
         * Deactivate the File
         *
         * @method QUI.classes.projects.media.Item#deactivate
         *
         * @fires onDeactivate [this]
         *
         * @param {Function} oncomplete - [optional] callback Function
         * @params {Object} params      - [optional], parameters that are linked to the request object
         */
        deactivate : function(oncomplete, params)
        {
            this.fireEvent('deactivate', [this]);
            this.getMedia().deactivate( this.getId(), oncomplete, params );

            /*
            QUI.Ajax.post('ajax_media_file_deactivate', function(result, Request)
            {
                var File = Request.getAttribute('File');

                File.setAttribute('active', 0);
                File.fireEvent('deActivate', [File]);

                if (Request.getAttribute('oncomplete')) {
                    Request.getAttribute('oncomplete')(result, Request);
                }
            }, {
                File       : this,
                project    : this.getMedia().getProject().getName(),
                fileid     : this.getId(),
                oncomplete : oncomplete
            });
            */
        },

        /**
         * Download the image
         *
         * @method QUI.classes.projects.media.Item#download
         */
        download : function()
        {
            if ( this.getType() === 'QUI.classes.projects.media.Folder' ) {
                return;
            }

            var url = Ajax.$url +'?'+ Ajax.parseParams('ajax_media_file_download', {
                project : this.getMedia().getProject().getName(),
                fileid  : this.getId()
            });

            // create a iframe
            if ( !$('download-frame') )
            {
                new Element('iframe#download-frame', {
                    styles : {
                        position : 'absolute',
                        width    : 100,
                        height   : 100,
                        left     : -400,
                        top      : -400
                    }
                }).inject( document.body );
            }

            $('download-frame').set('src', url);
        },

        /**
         * Replace the file
         *
         * @method QUI.classes.projects.media.Item#download
         *
         * @param {File} File
         * @param {Function} onfinish - callback function after the upload is finish
         *                              onfinish( {QUI.classes.projects.media.Item} )
         */
        replace : function(File, onfinish)
        {
            this.$Media.replace( this.getId(), onfinish );
        },

        /**
         * Returns if the File is active or not
         *
         * @method QUI.classes.projects.media.Item#isActive
         * @return {Bool}
         */
        isActive : function()
        {
            return this.getAttribute('active').toInt() ? true : false;
        },

        /**
         * Rename the folder
         *
         * @method QUI.classes.projects.media.Folder#rename
         *
         * @param {String} newname      - New folder name
         * @param {Function} oncomplete - callback() function
         * @params {Object} params      - [optional], parameters that are linked to the request object
         */
        rename : function(newname, oncomplete, params)
        {
            params = Utils.combine(params, {
                project    : this.getMedia().getProject().getName(),
                id         : this.getId(),
                newname    : newname,
                oncomplete : oncomplete
            });

            Ajax.post('ajax_media_rename', function(result, Request)
            {
                Request.getAttribute('oncomplete')( result, Request );
            }, params);
        }
    });
});