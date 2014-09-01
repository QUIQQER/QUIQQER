/**
 * Media for a Project
 *
 * @module classes/projects/project/Media
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires qui/classes/DOM
 * @requires qui/utils/Object
 * @requires Ajax
 * @requires classes/projects/project/media/Image
 * @requires classes/projects/project/media/File
 * @requires classes/projects/project/media/Folder
 * @requires classes/projects/project/media/Trash
 */

define([

    'qui/classes/DOM',
    'qui/utils/Object',

    'Ajax',
    'classes/projects/project/media/Image',
    'classes/projects/project/media/File',
    'classes/projects/project/media/Folder',
    'classes/projects/project/media/Trash'

], function(DOM, ObjectUtils, Ajax, MediaImage, MediaFile, MediaFolder, MediaTrash)
{
    "use strict";

    /**
     * @class classes/projects/project/Media
     *
     * @param {classes/projects/Project} Project
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : DOM,
        Type    : 'classes/projects/project/Media',

        initialize : function(Project)
        {
            this.$Project = Project;
            this.$Panel   = null;
            this.$items   = {};
        },

        /**
         * Return the Project from the Media
         *
         * @method classes/projects/project/Media#getProject
         * @return {classes/projects/Project}
         */
        getProject : function()
        {
            return this.$Project;
        },

        /**
         * Return the Trash from the Media
         *
         * @method classes/projects/project/Media#getTrash
         * @return {classes/projects/project/media/Trash}
         */
        getTrash : function()
        {
            return new MediaTrash( this );
        },

        /**
         * Get a file object from the media
         *
         * @method classes/projects/project/Media#get
         *
         * @param {Integer|Array} id      - ID of the file or an id list
         * @param {Function|Array} params - Item params or a callback function
         *
         * @return {Promise}
         */
        get : function(id, params, onerror)
        {
            var self = this;

            return new Promise(function(resolve, reject)
            {
                // id list
                if ( typeOf( id ) == 'array' )
                {
                    var i, len, itemId;
                    var result = [];

                    for ( i = 0, len = id.length; i < len; i++ )
                    {
                        itemId = id[ i ];

                        if ( self.$items[ itemId ] ) {
                            result.push( self.$items[ itemId ] );
                        }
                    }

                    if ( result.length === len )
                    {
                        if ( typeOf( params ) === 'function' ) {
                            return params( result );
                        }

                        resolve( result );
                    }
                }

                // one id
                if ( self.$items[ id ] )
                {
                    if ( typeOf( params ) === 'function' ) {
                        return params( self.$items[ id ] );
                    }

                    resolve( self.$items[ id ] );
                }

                if ( typeOf( params ) === 'object' ) {
                    return resolve( self.$parseResultToItem( params ) );
                }

                Ajax.get('ajax_media_details', function(result)
                {
                    var children = self.$parseResultToItem( result );

                    if ( typeOf( children ) == 'array' )
                    {
                        for ( var i = 0, len = children.length; i < len; i++ ) {
                            self.$items[ children[ i ].getId() ] = children[ i ];
                        }

                    } else
                    {
                        self.$items[ children.getId() ] = children;
                    }

                    if ( typeOf( params ) === 'function' ) {
                        return params( children );
                    }

                    resolve( children );

                }, {
                    fileid  : JSON.encode( id ),
                    project : self.getProject().getName(),
                    onError : function(Exception)
                    {
                        reject( Exception );
                    }
                });
            });
        },

        /**
         * Return thr file / files array, not the objects
         *
         * @method classes/projects/project/Media#getData
         *
         * @param {Integer|Array} id  - ID of the file or an id list
         * @param {Function} onfinish - [optional] callback function
         *
         * @return {Promise}
         */
        getData : function(id, onfinish)
        {
            var self = this;

            return new Promise(function(resolve, reject)
            {
                Ajax.get('ajax_media_details', function(result, Request)
                {
                    if ( typeOf( onfinish ) == 'function' ) {
                        onfinish( result );
                    }

                    resolve( result );
                }, {
                    fileid  : JSON.encode( id ),
                    project : self.getProject().getName(),
                    onError : function(Exception)
                    {
                        reject( Exception );
                    }
                });
            });
        },

        /**
         * get the first child of the media
         *
         * @method classes/projects/project/Media#get
         * @return {Promise}
         */
        firstChild : function(callback)
        {
            return this.get( 1, callback );
        },

        /**
         * Replace the file
         *
         * @method classes/projects/project/Media#download
         *
         * @param {Integer} childid   - the Mediafile ID
         * @param {File} File         - Browser File Object
         * @param {Function} onfinish - callback function after the upload is finish
         *                              onfinish( {controls/upload/File} )
         */
        replace : function(childid, File, onfinish)
        {
            var self = this;

            // upload file
            require(['UploadManager'], function(UploadManager)
            {
                UploadManager.uploadFiles( [ File ], 'ajax_media_replace', {
                    project    : self.getProject().getName(),
                    fileid     : childid,
                    phponstart : 'ajax_media_checkreplace',
                    events  : {
                        onComplete : onfinish
                    }
                });
            });
        },

        /**
         * Activate one ore more items
         *
         * @method classes/projects/project/Media#activate
         *
         * @param {Integer|Array}       - Item list or an Item id
         * @param {Function} oncomplete - [optional] callback Function
         * @params {Object} params      - [optional], parameters that are linked to the request object
         */
        activate : function(id, oncomplete, params)
        {
            params = ObjectUtils.combine(params, {
                project : this.getProject().getName(),
                fileid  : JSON.encode( id )
            });

            Ajax.post('ajax_media_activate', function(result, Request)
            {
                if ( oncomplete ) {
                    oncomplete( result, Request );
                }
            }, params);
        },

        /**
         * Deactivate one ore more items
         *
         * @method classes/projects/project/Media#deactivate
         *
         * @param {Integer|Array}       - Item list or an Item id
         * @param {Function} oncomplete - [optional] callback Function
         * @params {Object} params      - [optional], parameters that are linked to the request object
         */
        deactivate : function(id, oncomplete, params)
        {
            params = ObjectUtils.combine(params, {
                project : this.getProject().getName(),
                fileid  : JSON.encode( id )
            });

            Ajax.post('ajax_media_deactivate', function(result, Request)
            {
                if ( oncomplete ) {
                    oncomplete( result, Request );
                }
            }, params);
        },

        /**
         * Delete one ore more items
         *
         * @method classes/projects/project/Media#del
         *
         * @param {Integer|Array}       - Item list or an Item id
         * @param {Function} oncomplete - [optional] callback Function
         * @params {Object} params      - [optional], parameters that are linked to the request object
         */
        del : function(id, oncomplete, params)
        {
            if ( !id.length )
            {
                if ( typeof oncomplete !== 'undefined' ) {
                    oncomplete( result, Request );
                }

                return;
            }


            params = ObjectUtils.combine(params, {
                project : this.getProject().getName(),
                fileid  : JSON.encode( id )
            });

            Ajax.post('ajax_media_delete', function(result, Request)
            {
                if ( typeof oncomplete !== 'undefined' ) {
                    oncomplete( result, Request );
                }
            }, params);
        },

        /**
         * Parse the get result to a file object
         *
         * @return {classes/projects/project/media/Item|Array}
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