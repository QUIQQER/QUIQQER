/**
 * Trash for a media center
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires classes/DOM
 *
 * @module controls/projects/media/Trash
 * @package com.pcsg.qui.js.controls.projects.media
 * @namespace QUI.controls.projects.media
 */

define('controls/projects/media/Trash', [

    'classes/DOM',
    'classes/projects/media/Trash',
    'controls/projects/media/FolderWindow'

], function(QDOM)
{
    QUI.namespace('controls.projects.media');

    /**
     * @class QUI.controls.projects.media.Trash
     *
     * @param {QUI.classes.Project} Panel - APPPanel
     * @param {Object} options
     *
     * @fires onDrawBegin - this
     * @fires onDrawEnd   - this
     */
    QUI.controls.projects.media.Trash = new Class({

        Implements : [QDOM],
        Type       : 'QUI.controls.projects.media.Trash',

        options : {
            // Grid options
            order : '',
            sort  : '',
            max   : 20,
            page  : 1
        },

        initialize : function(Media, options, Trash)
        {
            this.init( options );

            this.$Media = Media;

            if ( typeof Trash === 'undefined' )
            {
                this.$Trash = new QUI.classes.projects.media.Trash(
                    this.$Media.getProject(),
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
            return 'Mülleimer '+ this.$Media.getProject().getName() +' media';
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
         * Destroy all marked files
         * Opens a {QUI.controls.windows.Submit}
         *
         * @method QUI.controls.projects.Trash#destroyMarkedSites
         *
         * @param {Array} ids         - array with the ids
         * @param {Function} onfinish - callback function, if the deletion is finish
         */
        destroy : function(ids, onfinish)
        {
            QUI.Windows.create('submit', {
                title  : 'Möchten Sie wirklich alle markierten Dateien zerstören?',
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
         * Restore all marked files
         *
         * @method QUI.controls.projects.Trash#restore
         *
         * @params {Array} ids         - ids which where deleted
         * @params {Function} onfinish - callback function, if the deletion is finish
         */
        restore : function(ids, onfinish)
        {
            new QUI.controls.projects.media.FolderWindow(this.$Media, {
                ids         : ids,
                Control     : this,
                information : 'Wählen Sie den Ordner aus unter der die Datei(en) abgelegt werden sollen',
                autoclose   : false,

                events :
                {
                    onSubmit : function(ids, Win)
                    {
                        if ( typeof ids[0] === 'undefined' ) {
                            return;
                        }

                        var Control = Win.getAttribute('Control'),
                            Trash   = Control.$Trash;

                        Win.Loader.show();

                        Trash.restore(
                            Win.getAttribute('ids'),
                            ids[0],
                            Control.$restore.bind( Control, [Win] )
                        );
                    }
                }
            }).create();
        },

        /**
         * on restore finish
         *
         * @param {QUI.controls.projects.media.FolderWindow} Win
         * @ignore
         */
        $restore : function(Win)
        {
            console.info( Win );

            if ( Win.getAttribute('onfinish') ) {
                Win.getAttribute('onfinish')();
            }

            Win.close();
        }
    });

    return QUI.controls.projects.media.Trash;
});