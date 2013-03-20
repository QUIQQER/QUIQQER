/**
 * Trash for the Projects
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 *
 * @module controls/projects/Trash
 * @package com.pcsg.qui.js.project.project
 * @namespace QUI.classes.project
 */

define('controls/projects/Trash', [

    'controls/Control',
    'classes/projects/Trash',
    'controls/projects/Window'

], function(Control)
{
    "use strict";

    QUI.namespace('controls.projects');

    /**
     * @class QUI.controls.projects.Trash
     *
     * @param {QUI.classes.Project} Panel - APPPanel
     * @param {Object} options
     *
     * @fires onDrawBegin - this
     * @fires onDrawEnd   - this
     *
     * @memberof! <global>
     */
    QUI.controls.projects.Trash = new Class({

        Implements : [ Control ],
        Type       : 'QUI.controls.projects.Trash',

        options : {
            // Grid options
            order : '',
            sort  : '',
            max   : 20,
            page  : 1
        },

        initialize : function(Project, options, Trash)
        {
            this.init( options );
            this.$Project = Project;

            if ( typeof Trash === 'undefined' )
            {
                this.$Trash = new QUI.classes.projects.Trash(
                    this.$Project,
                    this.getAttributes()
                );
            } else
            {
                this.$Trash = Trash;
            }
        },

        /**
         * Return the title for the panel
         *
         * @return String
         */
        getTitle : function()
        {
            return 'Mülleimer '+ this.$Project.getName();
        },

        /**
         * Return the sites in the trash
         *
         * @method QUI.controls.projects.Trash#getList
         */
        getList : function(onfinish)
        {
            this.$Trash.setAttributes( this.getAttributes() );
            this.$Trash.getList( onfinish );
        },

        /**
         * Destroy all marked sites
         * Opens a {QUI.controls.windows.Submit}
         *
         * @method QUI.controls.projects.Trash#destroyMarkedSites
         *
         * @param {Array} ids         - array with the ids
         * @param {Function} onfinish - call back function, if the deletion is finish
         */
        destroy : function(ids, onfinish)
        {
            QUI.Windows.create('submit', {
                title  : 'Möchten Sie wirklich alle markierten Seiten zerstören?',
                width  : 500,
                height : 160,

                text        : 'Folgende IDs werden unwiderruflich gelöscht:',
                texticon    : URL_BIN_DIR +'32x32/trashcan_full.png',
                information : ids.join(', '),

                onfinish : onfinish,
                Control  : this,
                ids      : ids,

                events :
                {
                    onSubmit : function(Win)
                    {
                        var Control = Win.getAttribute('Control'),
                            Trash   = Control.$Trash;

                        Trash.destroy(
                            Win.getAttribute('ids'),
                            Win.getAttribute('onfinish')
                        );
                    }
                }
            });
        },

        /**
         * Restore all marked sites
         *
         * @method QUI.controls.projects.Trash#restore
         * @return {this}
         */
        restore : function(ids, onfinish)
        {
            new QUI.controls.projects.Window({
                project     : this.$Project.getName(),
                lang        : this.$Project.getAttribute('lang'),
                ids         : ids,
                Control     : this,
                information : 'Wählen Sie die Elternseite aus unter der die Seiten eingehängt werden sollen',
                onfinish    : onfinish,

                events :
                {
                    onSubmit : function(ids, Win)
                    {
                        if ( typeof ids[0] === 'undefined' ) {
                            return;
                        }

                        var Control = Win.getAttribute('Control'),
                            Trash   = Control.$Trash;

                        Trash.restore(
                            Win.getAttribute('ids'),
                            ids[0],
                            Win.getAttribute('onfinish')
                        );
                    }
                }
            });
        }
    });

    return QUI.controls.projects.Trash;
});