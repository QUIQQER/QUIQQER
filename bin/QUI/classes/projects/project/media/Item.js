/**
 * Parent class for all media items like file, image, folder
 *
 * @module classes/projects/project/media/Item
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onRefresh [ {self} ]
 * @event onSave [ {self} ]
 * @event onDelete [ {self} ]
 * @event onActivate [ {self} ]
 * @event onDeactivate [ {self} ]
 *
 * @require qui/classes/DOM
 * @require Ajax
 * @require qui/utils/Object
 */

define([

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
            image_width  : '',
            cache_url    : ''
        },

        initialize : function(params, Media)
        {
            this.$Media = Media;
            this.$Panel = null;

            this.parent( params );
        },

        /**
         * Refresh the params from the database to the file
         *
         * @param {Function} oncomplete - [optional] callback function
         * @return {Promise}
         */
        refresh : function(oncomplete)
        {
            var self = this;

            return this.getMedia().getData( this.getId() ).then(function(result)
            {
                self.setAttributes( result );

                self.fireEvent( 'refresh', [ self ] );

                if ( typeOf( oncomplete ) === 'function' ) {
                    oncomplete( self );
                }
            });
        },

        /**
         * Returns the Media Object of the item
         *
         * @method classes/projects/project/media/Item#getMedia
         * @return {classes/projects/project/Media}
         */
        getMedia : function()
        {
            return this.$Media;
        },

        /**
         * Returns the ID of the item
         *
         * @method classes/projects/project/media/Item#getId
         * @return {Integer}
         */
        getId : function()
        {
            return this.getAttribute('id');
        },

        /**
         * Returns the breadcrumb entries (parent path)
         *
         * @method classes/projects/project/media/Item#getBreadcrumb
         * @param {Function} oncomplete - callback Function
         */
        getBreadcrumb : function(oncomplete)
        {
            Ajax.get('ajax_media_breadcrumb', function(result)
            {
                oncomplete( result );
            }, {
                project : this.getMedia().getProject().getName(),
                fileid  : this.getId()
            });
        },

        /**
         * Save the File attributes to the database
         *
         * @method classes/projects/project/media/Item#save
         * @fires onSave [this]
         * @param {Function} oncomplete - [optional] callback Function
         * @params {Object} params      - [optional], parameters that are linked to the request object
         */
        save : function(oncomplete, params)
        {
            var self = this;

            params = Utils.combine(params, {
                project    : this.getMedia().getProject().getName(),
                fileid     : this.getId(),
                attributes : JSON.encode( this.getAttributes() )
            });


            Ajax.post('ajax_media_file_save', function(result, Request)
            {
                self.setAttributes( result );
                self.fireEvent( 'save', [ File ] );

                if ( typeOf( oncomplete ) === 'function' ) {
                    oncomplete( result, Request );
                }
            }, params);
        },

        /**
         * Delete the item
         *
         * @method classes/projects/project/media/Item#del
         *
         * @fires onDelete [this]
         *
         * @param {Function} oncomplete - [optional] callback Function
         * @params {Object} params      - [optional], parameters that are linked to the request object
         */
        del : function(oncomplete, params)
        {
            this.fireEvent( 'delete', [ this ]) ;
            this.getMedia().del( this.getId(), oncomplete, params );
        },

        /**
         * Activate the File
         *
         * @method classes/projects/project/media/Item#activate
         *
         * @fires onActivate [this]
         *
         * @param {Function} oncomplete - [optional] callback Function
         * @params {Object} params      - [optional], parameters that are linked to the request object
         */
        activate : function(oncomplete, params)
        {
            this.fireEvent( 'activate', [ this ] );
            this.getMedia().activate( this.getId(), oncomplete, params );
        },

        /**
         * Deactivate the File
         *
         * @method classes/projects/project/media/Item#deactivate
         *
         * @fires onDeactivate [this]
         *
         * @param {Function} oncomplete - [optional] callback Function
         * @params {Object} params      - [optional], parameters that are linked to the request object
         */
        deactivate : function(oncomplete, params)
        {
            this.fireEvent( 'deactivate', [ this ] );
            this.getMedia().deactivate( this.getId(), oncomplete, params );
        },

        /**
         * Download the image
         *
         * @method classes/projects/project/media/Item#download
         */
        download : function()
        {
            if ( this.getType() === 'classes/projects/project/media/Folder' ) {
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
         * @method classes/projects/project/media/Item#download
         *
         * @param {File} File
         * @param {Function} onfinish - callback function after the upload is finish
         *                              onfinish( {classes/projects/project/media/Item} )
         */
        replace : function(File, onfinish)
        {
            this.$Media.replace( this.getId(), onfinish );
        },

        /**
         * Returns if the File is active or not
         *
         * @method classes/projects/project/media/Item#isActive
         * @return {Bool}
         */
        isActive : function()
        {
            return this.getAttribute('active').toInt() ? true : false;
        },

        /**
         * Rename the folder
         *
         * @method classes/projects/project/media/Folder#rename
         *
         * @param {String} newname      - New folder name
         * @param {Function} oncomplete - callback() function
         * @params {Object} params      - [optional], parameters that are linked to the request object
         */
        rename : function(newname, oncomplete, params)
        {
            params = Utils.combine(params, {
                project : this.getMedia().getProject().getName(),
                id      : this.getId(),
                newname : newname
            });

            Ajax.post('ajax_media_rename', function(result, Request)
            {
                oncomplete( result, Request );
            }, params);
        }
    });
});
