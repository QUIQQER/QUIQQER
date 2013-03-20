/**
 * Trash for the Projects
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 *
 * @module classes/projects/Trash
 * @package com.pcsg.qui.js.project.project
 * @namespace QUI.classes.project
 */

define('classes/projects/Trash', [

    'classes/DOM',
    'controls/projects/Trash'

], function(QDOM)
{
    "use strict";

    QUI.namespace('classes.projects');

    /**
     * @class QUI.classes.projects.Trash
     *
     * @param {QUI.classes.Project} Panel - APPPanel
     * @param {Object} options
     *
     * @fires onDrawBegin - this
     * @fires onDrawEnd   - this
     *
     * @memberof! <global>
     */
    QUI.classes.projects.Trash = new Class({

        Implements : [ QDOM ],
        Type       : 'QUI.classes.projects.Trash',

        options : {
            // Grid options
            order : '',
            sort  : '',
            max   : 20,
            page  : 1
        },

        initialize : function(Project, options)
        {
            this.$Project = Project;
        },

        /**
         * Return the Trash Project Control
         *
         * @return {QUI.controls.projects.Trash}
         */
        getControl : function()
        {
            return new QUI.controls.projects.Trash(
                this.$Project,
                this.getAttributes(),
                this
            );
        },

        /**
         * Return the sites in the trash
         *
         * @method QUI.classes.projects.Trash#getList
         */
        getList : function(onfinish)
        {
            QUI.Ajax.get('ajax_trash_sites', function(result, Request)
            {
                if ( Request.getAttribute('onfinish') ) {
                    Request.getAttribute('onfinish')( result, Request );
                }

            }, {
                onfinish : onfinish,
                project  : this.$Project.getName(),
                lang     : this.$Project.getAttribute('lang'),
                params   : JSON.encode({
                    order : this.getAttribute('order'),
                    sort  : this.getAttribute('sort'),
                    max   : this.getAttribute('max'),
                    page  : this.getAttribute('page')
                })
            });
        },

        /**
         * Ajax Request for Destroing ids
         *
         * @method QUI.classes.projects.Trash#destroy
         *
         * @param {Array} ids - IDs of the sites
         * @param {Function} callback - Callback function if the ids destroyed
         */
        destroy : function(ids, callback)
        {
            QUI.Ajax.post('ajax_trash_destroy', function(result, Request)
            {
                if ( Request.getAttribute('trash_callback') ) {
                    Request.getAttribute('trash_callback')( result, Request );
                }
            }, {
                project  : this.$Project.getName(),
                lang     : this.$Project.getAttribute('lang'),
                ids      : JSON.encode( ids ),
                Trash    : this,
                trash_callback : callback
            });
        },

        /**
         * Ajax Request for Restore ids
         *
         * @method QUI.classes.projects.Trash#restore
         *
         * @param {Array} ids             - IDs of the deleted sites
         * @param {Integer} parentid    - Parent ID
         * @param {Function} callback     - Callback function if the ids destroyed
         */
        restore : function(ids, parentid, callback)
        {
            QUI.Ajax.post('ajax_trash_restore', function(result, Request)
            {
                if ( Request.getAttribute('trash_callback') ) {
                    Request.getAttribute('trash_callback')( result, Request );
                }
            }, {
                project  : this.$Project.getName(),
                lang     : this.$Project.getAttribute('lang'),
                ids      : JSON.encode( ids ),
                parentid : parentid,
                Trash    : this,
                trash_callback : callback
            });
        }
    });

    return QUI.classes.projects.Trash;
});