/**
 * Helper for panels
 * helps to open a new panel, like a Project Panel or a Site Panel
 *
 * @module utils/Panels
 * @author www.pcsg.de (Henning Leutz)
 */

define('utils/Panels', function()
{
    "use strict";

    return {

        /**
         * opens a site panel
         * if the panel exists, there will be used
         *
         * @param {String} project - name of the Project
         * @param {String} lang - languag of the Project
         * @param {Number} id - ID of the Site
         * @param {Function} [callback] - callback function, only triggered if the panel is not exist
         */
        openSitePanel : function(project, lang, id, callback)
        {
            require([
                'qui/QUI',
                'qui/controls/desktop/Panel',
                'controls/projects/project/site/Panel',
                'Projects'
            ], function(QUI, QUIPanel, SitePanel, Projects)
            {
                var n      = 'panel-'+ project +'-'+ lang +'-'+ id,
                    panels = QUI.Controls.get( n );

                if ( panels.length )
                {
                    for ( var i = 0, len = panels.length; i < len; i++ )
                    {
                        if ( !instanceOf( panels[ i ], QUIPanel ) ) {
                            continue;
                        }

                        // if a task exist, click it and open the instance
                        var Task = panels[ i ].getAttribute( 'Task' );

                        if ( Task && Task.getType() == 'qui/controls/taskbar/Task' )
                        {
                            panels[ i ].getAttribute( 'Task' ).click();
                            return;
                        }

                        panels[ i ].open();
                        return;
                    }
                }

                panels = QUI.Controls.getByType( 'qui/controls/desktop/Tasks' );

                if ( !panels.length ) {
                    return;
                }

                var Site  = Projects.get( project, lang ).get( id ),
                    Panel = new SitePanel( Site );

                panels[ 0 ].appendChild( Panel );

                if ( typeof callback !== 'undefined' ) {
                    callback( Panel );
                }
            });
        },

        /**
         * opens a media panel
         * if the panel exists, there will be used
         *
         * @param {String} project - Name of the project
         * @param {Function} [callback] - callback function, only triggered if the panel is not exist
         */
        openMediaPanel : function(project, callback)
        {
            require([
                'qui/QUI',
                'qui/controls/desktop/Panel',
                'controls/projects/project/media/Panel',
                'Projects'
            ], function(QUI, QUIPanel, MediaPanel, Projects)
            {
                var panels = QUI.Controls.get( 'panel-'+ project +'-media' );

                if ( panels.length )
                {
                    for ( var i = 0, len = panels.length; i < len; i++ )
                    {
                        if ( !instanceOf( panels[ i ], QUIPanel ) ) {
                            continue;
                        }

                        // if a task exist, click it and open the instance
                        var Task = panels[ i ].getAttribute('Task');

                        if (Task && Task.getType() == 'qui/controls/taskbar/Task') {
                            panels[ i ].getAttribute('Task').click();
                        }

                        panels[ i ].open();

                        return;
                    }
                }

                panels = QUI.Controls.getByType( 'qui/controls/desktop/Tasks' );

                if ( !panels.length ) {
                    return;
                }


                var Project = Projects.get( project ),
                    Panel   = new MediaPanel( Project.getMedia() );

                panels[ 0 ].appendChild( Panel );

                if ( typeof callback !== 'undefined' ) {
                    callback( Panel );
                }
            });
        },

        /**
         * opens a trash panel
         * if the panel exists, there will be used
         *
         * @param {Function} [callback] - callback function
         */
        openTrashPanel : function(callback)
        {
            var self = this;

            require([
                'qui/QUI',
                'controls/trash/Panel'
            ], function(QUI, TrashPanel)
            {
                var name   = 'panel-trash',
                    panels = QUI.Controls.get( name );

                if ( panels.length )
                {
                    panels[ 0 ].open();

                    // if a task exist, click it and open the instance
                    var Task = panels[ 0 ].getAttribute( 'Task' );

                    if ( Task && Task.getType() == 'qui/controls/taskbar/Task' ) {
                        panels[ 0 ].getAttribute( 'Task' ).click();
                    }

                    if ( typeof callback !== 'undefined' ) {
                        callback( panels[ 0 ] );
                    }

                    return;
                }

                var Panel = new TrashPanel({
                    name : name
                });

                self.openPanelInTasks( Panel );

                if ( typeof callback !== 'undefined' ) {
                    callback( Panel );
                }
            });
        },

        /**
         * Open a panel in a task panel
         * it search the first taskpanel
         *
         * @param {Object} Panel - qui/controls/desktop/Panel
         */
        openPanelInTasks : function(Panel)
        {
            require(['qui/QUI'], function(QUI)
            {
                // if panel not exists
                var panels = QUI.Controls.getByType( 'qui/controls/desktop/Tasks' );

                if ( !panels.length ) {
                    return;
                }

                panels[ 0 ].appendChild( Panel );


                (function() {
                    Panel.focus();
                }).delay( 100 );
            });
        }

    };
});