/**
 * Media for a Project
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/projects/Media
 *
 * @module classes/projects/Media
 * @package com.pcsg.qui.js.classes.project
 * @namespace QUI.classes.project
 */

define('classes/projects/project/Media', [

    'qui/classes/DOM',

    'Ajax',
    'classes/projects/project/media/Image',
    'classes/projects/project/media/File',
    'classes/projects/project/media/Folder',
    'classes/projects/project/media/Trash'

], function(DOM, Ajax, MediaImage, MediaFile, MediaFolder, MediaTrash)
{
    "use strict";

    /**
     * @class QUI.classes.projects.Media
     *
     * @param {QUI.classes.projects.Project} Project
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : DOM,
        Type    : 'classes/projects/Media',

        initialize : function(Project)
        {
            this.$Project = Project;
            this.$Panel   = null;
            this.$items   = {};
        },

        /**
         * Return the Project from the Media
         *
         * @method QUI.classes.projects.Media#getProject
         * @return {QUI.classes.Project}
         */
        getProject : function()
        {
            return this.$Project;
        },

        /**
         * Return the Trash from the Media
         *
         * @method QUI.classes.projects.Media#getTrash
         * @return {QUI.classes.projects.media.Trash}
         */
        getTrash : function()
        {
            return new MediaTrash( this );
        },

        /**
         * Get a file object from the media
         *
         * @method QUI.classes.projects.Media#get
         *
         * @param {Integer|Array} id      - ID of the file or an id list
         * @param {Function|Array} params - Item params or a callback function
         *
         * @return {QUI.classes.projects.media.Item} or callback( QUI.classes.projects.media.Item )
         */
        get : function(id, params)
        {
            if ( this.$items[ id ] )
            {
                if ( typeOf( params ) === 'function' ) {
                    return params( this.$items[ id ] );
                }

                return this.$items[ id ];
            }


            if ( typeOf( params ) !== 'function' ) {
                return this.$parseResultToItem( params );
            }

            Ajax.get('ajax_media_details', function(result, Request)
            {
                var Media    = Request.getAttribute( 'Media' ),
                    children = Media.$parseResultToItem( result );

                if ( typeOf( children ) == 'array' )
                {
                    for ( var i = 0, len = children.length; i < len; i++ ) {
                        Media.$items[ children[ i ].getId() ] = children[ i ];
                    }
                } else
                {
                    Media.$items[ children.getId() ] = children;
                }

                Request.getAttribute( 'onfinish' )( children );
            }, {
                fileid   : JSON.encode( id ),
                project  : this.getProject().getName(),
                onfinish : params,
                Media    : this
            });
        },

        /**
         * Return thr file / files array, not the objects
         *
         * @method QUI.classes.projects.Media#getData
         *
         * @param {Integer|Array} id  - ID of the file or an id list
         * @param {Function} onfinish - callback function
         *
         * @return callback( Array )
         */
        getData : function(id, onfinish)
        {
            Ajax.get('ajax_media_details', function(result, Request)
            {
                if ( Request.getAttribute('onfinish') ) {
                    Request.getAttribute('onfinish')( result );
                }
            }, {
                fileid   : JSON.encode( id ),
                project  : this.getProject().getAttribute('project'),
                onfinish : onfinish
            });
        },

        /**
         * get the first child of the media
         *
         * @method QUI.classes.projects.Media#get
         */
        firstChild : function(callback)
        {
            return this.get(1, callback);
        },

        /**
         * Open the Media in an AppPanel or create a new AppPanel
         *
         * @params {MUI.Apppanel} Panel - optional
         */
//        openInPanel : function(Panel)
//        {
//            this.$Panel = new QUI.controls.projects.media.Panel( this );
//        },

        /**
         * Replace the file
         *
         * @method QUI.classes.projects.Media#download
         *
         * @param {Integer} childid   - the Mediafile ID
         * @param {File} File         - Browser File Object
         * @param {Function} onfinish - callback function after the upload is finish
         *                              onfinish( {QUI.controls.upload.File} )
         */
        replace : function(childid, File, onfinish)
        {
            // upload file
            QUI.UploadManager.uploadFiles(
                [File],
                'ajax_media_replace',
                {
                    project    : this.getProject().getName(),
                    fileid     : childid,
                    phponstart : 'ajax_media_checkreplace',
                    events  : {
                        onComplete : onfinish
                    }
                }
            );
        },

        /**
         * Activate one ore more items
         *
         * @method QUI.classes.projects.Media#activate
         *
         * @param {Integer|Array}       - Item list or an Item id
         * @param {Function} oncomplete - [optional] callback Function
         * @params {Object} params      - [optional], parameters that are linked to the request object
         */
        activate : function(id, oncomplete, params)
        {
            params = QUI.Utils.combine(params, {
                project    : this.getProject().getName(),
                fileid     : JSON.encode( id ),
                oncomplete : oncomplete,
                Media      : this
            });

            Ajax.post('ajax_media_activate', function(result, Request)
            {
                if ( Request.getAttribute('oncomplete') ) {
                    Request.getAttribute('oncomplete')( result, Request );
                }
            }, params);
        },

        /**
         * Deactivate one ore more items
         *
         * @method QUI.classes.projects.Media#deactivate
         *
         * @param {Integer|Array}       - Item list or an Item id
         * @param {Function} oncomplete - [optional] callback Function
         * @params {Object} params      - [optional], parameters that are linked to the request object
         */
        deactivate : function(id, oncomplete, params)
        {
            params = QUI.Utils.combine(params, {
                project    : this.getProject().getName(),
                fileid     : JSON.encode( id ),
                oncomplete : oncomplete,
                Media      : this
            });

            Ajax.post('ajax_media_deactivate', function(result, Request)
            {
                if ( Request.getAttribute('oncomplete') ) {
                    Request.getAttribute('oncomplete')( result, Request );
                }
            }, params);
        },

        /**
         * Delete one ore more items
         *
         * @method QUI.classes.projects.Media#del
         *
         * @param {Integer|Array}       - Item list or an Item id
         * @param {Function} oncomplete - [optional] callback Function
         * @params {Object} params      - [optional], parameters that are linked to the request object
         */
        del : function(id, oncomplete, params)
        {
            params = QUI.Utils.combine(params, {
                project    : this.getProject().getName(),
                fileid     : JSON.encode( id ),
                oncomplete : oncomplete,
                Media      : this
            });

            Ajax.post('ajax_media_delete', function(result, Request)
            {
                if ( Request.getAttribute('oncomplete') ) {
                    Request.getAttribute('oncomplete')( result, Request );
                }
            }, params);
        },

        /**
         * Parse the get result to a file object
         *
         * @return {QUI.classes.projects.media.Item|Array}
         */
        $parseResultToItem : function(result)
        {
            if ( !result ) {
                return [];
            }

            if ( typeOf( result ) == 'array' && result.length )
            {
                var list = [];

                for ( var i = 0, len = result.length; i < len; i++ )
                {
                    list.push(
                        this.$parseResultToItem( result[i] )
                    );
                }

                return list;
            }

            switch ( result.type )
            {
                case "image":
                    return new MediaImage( result, this );

                case "folder":
                    return new MediaFolder( result, this );
            }

            return new MediaFile( result, this );
        }
    });
});