/**
 * Media trash
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module classes/project/media/Trash
 * @package com.pcsg.qui.js.classes.project.media.Trash
 * @namespace QUI.classes.project.media.Trash
 */

define('classes/project/media/Trash', [

    'classes/DOM',
    'controls/project/media/Trash'

], function(QDOM)
{
    QUI.namespace('classes.project.media');

    /**
     * @class QUI.classes.project.media.Trash
     *
     * @param {QUI.classes.project.Media} Panel - APPPanel
     * @param {Object} options
     *
     * @fires onDrawBegin - this
     * @fires onDrawEnd   - this
     */
    QUI.classes.project.media.Trash = new Class({

        Implements: [QDOM],

        options : {
            // Grid options
            order : '',
            sort  : '',
            max   : 20,
            page  : 1
        },

        initialize : function(Media, options)
        {
            this.$Media = Media;
        },

        /**
         * Return the Trash Project Control
         *
         * @return {QUI.controls.project.media.Trash}
         */
        getControl : function()
        {
            return new QUI.controls.project.media.Trash(
                this.$Media,
                this.getAttributes(),
                this
            );
        },

        /**
         * Return the sites in the trash
         *
         * @method QUI.classes.project.Trash#getList
         * @param {Function} onfinish - callback function: callback(result, Request)
         */
        getList : function(onfinish)
        {
            QUI.Ajax.get('ajax_trash_media', function(result, Request)
            {
                if ( Request.getAttribute('onfinish') ) {
                    Request.getAttribute('onfinish')( result, Request );
                }

            }, {
                onfinish : onfinish,
                project  : this.$Media.getProject().getName(),
                params   : JSON.encode({
                    order : this.getAttribute('order'),
                    sort  : this.getAttribute('sort'),
                    max   : this.getAttribute('max'),
                    page  : this.getAttribute('page')
                })
            });
        },

        /**
         * Ajax Request for restore Media-Center ids
         *
         * @method QUI.classes.project.media.Trash#restore
         *
         * @param {Array} ids         - IDs of the deleted files
         * @param {Integer} parentid  - Parent Folder ID
         * @param {Function} callback - Callback function if the ids destroyed
         */
        restore : function(ids, parentid, callback)
        {
            QUI.Ajax.post('ajax_trash_media_restore', function(result, Request)
            {
                if ( Request.getAttribute('trash_callback') ) {
                    Request.getAttribute('trash_callback')( result, Request );
                }
            }, {
                project  : this.$Media.getProject().getName(),
                ids      : JSON.encode( ids ),
                parentid : parentid,
                Trash    : this,
                trash_callback : callback
            });
        },

        /**
         * Ajax Request for destroing Media-Center ids
         *
         * @method QUI.classes.project.media.Trash#destroy
         *
         * @param {Array} ids - IDs of the sites
         * @param {Function} callback - Callback function when the ids are destroyed
         */
        destroy : function(ids, callback)
        {
            QUI.Ajax.post('ajax_trash_media_destroy', function(result, Request)
            {
                if ( Request.getAttribute('trash_callback') ) {
                    Request.getAttribute('trash_callback')( result, Request );
                }
            }, {
                project  : this.$Media.getProject().getName(),
                ids      : JSON.encode( ids ),
                Trash    : this,
                trash_callback : callback
            });
        }
    });

    return QUI.classes.project.media.Trash;
});