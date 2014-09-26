
/**
 * Media trash
 *
 * @module classes/projects/media/Trash
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/classes/DOM
 * @require Ajax
 * @require controls/projects/project/media/Trash
 */

define([

    'qui/classes/DOM',
    'Ajax',
    'controls/trash/Panel'

], function(QDOM, Ajax, TrashPanel)
{
    "use strict";

    /**
     * @class classes/projects/media/Trash
     *
     * @param {classes/projects/Media} Panel - APPPanel
     * @param {Object} options
     *
     * @fires onDrawBegin - this
     * @fires onDrawEnd   - this
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QDOM,
        Type   : 'classes/projects/media/Trash',

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
         * @return {controls/projects/media/Trash}
         */
        getControl : function()
        {
            return new TrashPanel(
                this.$Media,
                this.getAttributes(),
                this
            );
        },

        /**
         * Return the sites in the trash
         *
         * @method classes/projects/media/Trash#getList
         * @param {Function} onfinish - callback function: callback(result, Request)
         */
        getList : function(onfinish)
        {
            Ajax.get('ajax_trash_media', function(result, Request)
            {
                if ( typeof onfinish !== 'undefined' ) {
                    onfinish( result, Request );
                }

            }, {
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
         * @method classes/projects/media/Trash#restore
         *
         * @param {Array} ids         - IDs of the deleted files
         * @param {Integer} parentid  - Parent Folder ID
         * @param {Function} callback - Callback function if the ids destroyed
         */
        restore : function(ids, parentid, callback)
        {
            Ajax.post('ajax_trash_media_restore', function(result, Request)
            {
                if ( typeof callback !== 'undefined' ) {
                    callback( result, Request );
                }
            }, {
                project  : this.$Media.getProject().getName(),
                ids      : JSON.encode( ids ),
                parentid : parentid,
                Trash    : this
            });
        },

        /**
         * Ajax Request for destroing Media-Center ids
         *
         * @method classes/projects/media/Trash#destroy
         *
         * @param {Array} ids - IDs of the sites
         * @param {Function} callback - Callback function when the ids are destroyed
         */
        destroy : function(ids, callback)
        {
            Ajax.post('ajax_trash_media_destroy', function(result, Request)
            {
                if ( typeof callback !== 'undefined' ) {
                    callback( result, Request );
                }
            }, {
                project : this.$Media.getProject().getName(),
                ids     : JSON.encode( ids ),
                Trash   : this
            });
        }
    });
});
