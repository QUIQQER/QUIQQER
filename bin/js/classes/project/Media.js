/**
 * Media for a Project
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/project/Media
 *
 * @module classes/project/Media
 * @package com.pcsg.qui.js.classes.project
 * @namespace QUI.classes.project
 */

define('classes/project/Media', [

    'classes/DOM',
    'controls/project/media/Panel',
    'classes/project/media/Image',
    'classes/project/media/File',
    'classes/project/media/Folder',
    'classes/project/media/Trash'

], function(DOM, QUI_MediaPanel)
{
    QUI.namespace( 'classes.project' );

    /**
     * @class QUI.classes.project.Media
     *
     * @param {QUI.classes.project.Project} Project
     */
    QUI.classes.project.Media = new Class({

        Implements: [ DOM ],

        initialize : function(Project)
        {
            this.$Project = Project;
            this.$Panel   = null;
            this.$items   = {};
        },

        /**
         * Return the Project from the Media
         *
         * @method QUI.classes.project.Media#getProject
         * @return {QUI.classes.Project}
         */
        getProject : function()
        {
            return this.$Project;
        },

        /**
         * Return the Trash from the Media
         *
         * @method QUI.classes.project.Media#getTrash
         * @return {QUI.classes.project.media.Trash}
         */
        getTrash : function()
        {
            return new QUI.classes.project.media.Trash( this );
        },

        /**
         * Get a file object from the media
         *
         * @method QUI.classes.project.Media#get
         *
         * @param {Integer|Array} id      - ID of the file or an id list
         * @param {Function|Array} params - Item params or a callback function
         *
         * @return {QUI.classes.project.media.Item} or callback( QUI.classes.project.media.Item )
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

            QUI.Ajax.get('ajax_media_details', function(result, Request)
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
                project  : this.getProject().getAttribute( 'project' ),
                onfinish : params,
                Media    : this
            });
        },

        /**
         * Return thr file / files array, not the objects
         *
         * @method QUI.classes.project.Media#getData
         *
         * @param {Integer|Array} id  - ID of the file or an id list
         * @param {Function} onfinish - callback function
         *
         * @return callback( Array )
         */
        getData : function(id, onfinish)
        {
            QUI.Ajax.get('ajax_media_details', function(result, Request)
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
         * @method QUI.classes.project.Media#get
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
        openInPanel : function(Panel)
        {
            this.$Panel = new QUI.controls.project.media.Panel( this );
        },

        /**
         * Replace the file
         *
         * @method QUI.classes.project.Media#download
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
         * @method QUI.classes.project.Media#activate
         *
         * @param {Integer|Array}       - Item list or an Item id
         * @param {Function} oncomplete - [optional] callback Function
         * @params {Object} params      - [optional], parameters that are linked to the request object
         */
        activate : function(id, oncomplete, params)
        {
            params = QUI.lib.Utils.combine(params, {
                project    : this.getProject().getName(),
                fileid     : JSON.encode( id ),
                oncomplete : oncomplete,
                Media      : this
            });

            QUI.Ajax.post('ajax_media_activate', function(result, Request)
            {
                if ( Request.getAttribute('oncomplete') ) {
                    Request.getAttribute('oncomplete')( result, Request );
                }
            }, params);
        },

        /**
         * Deactivate one ore more items
         *
         * @method QUI.classes.project.Media#deactivate
         *
         * @param {Integer|Array}       - Item list or an Item id
         * @param {Function} oncomplete - [optional] callback Function
         * @params {Object} params      - [optional], parameters that are linked to the request object
         */
        deactivate : function(id, oncomplete, params)
        {
            params = QUI.lib.Utils.combine(params, {
                project    : this.getProject().getName(),
                fileid     : JSON.encode( id ),
                oncomplete : oncomplete,
                Media      : this
            });

            QUI.Ajax.post('ajax_media_deactivate', function(result, Request)
            {
                if ( Request.getAttribute('oncomplete') ) {
                    Request.getAttribute('oncomplete')( result, Request );
                }
            }, params);
        },

        /**
         * Delete one ore more items
         *
         * @method QUI.classes.project.Media#del
         *
         * @param {Integer|Array}       - Item list or an Item id
         * @param {Function} oncomplete - [optional] callback Function
         * @params {Object} params      - [optional], parameters that are linked to the request object
         */
        del : function(id, oncomplete, params)
        {
            params = QUI.lib.Utils.combine(params, {
                project    : this.getProject().getName(),
                fileid     : JSON.encode( id ),
                oncomplete : oncomplete,
                Media      : this
            });

            QUI.Ajax.post('ajax_media_delete', function(result, Request)
            {
                if ( Request.getAttribute('oncomplete') ) {
                    Request.getAttribute('oncomplete')( result, Request );
                }
            }, params);
        },

        /**
         * Parse the get result to a file object
         *
         * @return {QUI.classes.project.media.Item|Array}
         */
        $parseResultToItem : function(result)
        {
            if ( result.length )
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
                    return new QUI.classes.project.media.Image( result, this );

                case "folder":
                    return new QUI.classes.project.media.Folder( result, this );
            }

            return new QUI.classes.project.media.File( result, this );
        }
    });
});