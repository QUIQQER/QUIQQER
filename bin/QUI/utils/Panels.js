/**
 * Helper for panels
 * helps to open a new panel, like a Project Panel or a Site Panel
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('utils/Panels', function()
{
    "use strict";

    return {

        /**
         * open a site panel
         * if the panel exists, there this is used
         *
         * @param {String} project - name of the Project
         * @param {String} lang - languag of the Project
         * @param {Integer} id - ID of the Site
         * @param {Function} callback - callback function, only triggered if the panel is not exist
         */
        openSitePanel : function(project, lang, id, callback)
        {
            require([
                'qui/QUI',
                'controls/projects/project/site/Panel',
                'classes/projects/Project',
                'classes/projects/project/Site',
                'Projects'
            ], function(QUI, SitePanel, Project, Site, Projects)
            {
                var n      = 'panel-'+ project +'-'+ lang +'-'+ id,
                    panels = QUI.Controls.get( n );


                if ( panels.length )
                {
                    panels[ 0 ].open();

                    // if a task exist, click it and open the instance
                    var Task = panels[ 0 ].getAttribute( 'Task' );

                    if ( Task && Task.getType() == 'qui/controls/taskbar/Task' ) {
                        panels[ 0 ].getAttribute( 'Task' ).click();
                    }

                    return;
                }

                panels = QUI.Controls.getByType( 'qui/controls/desktop/Tasks' );

                if ( !panels.length ) {
                    return;
                }

                var Project = Projects.get( project, lang ),
                    Site    = Project.get( id );

                var SitePanel = new SitePanel(Site, {
                    events :
                    {
                        onShow : function(Panel)
                        {
//                            if ( Panel.getType() != 'controls/projects/project/site/Panel' ) {
//                                return;
//                            }
                            // if it is a sitepanel
                            // set the item in the map active
                            // self.openSite( Panel.getSite().getId() );
                        }
                    }
                });

                panels[ 0 ].appendChild( SitePanel );

                if ( typeof callback !== 'undefined' ) {
                    callback( SitePanel );
                }
            });
        },

        /**
         * open a site panel
         * if the panel exists, there this is used
         *
         * @param {String} project - Name of the project
         * @param {Function} callback - callback function, only triggered if the panel is not exist
         */
        openMediaPanel : function(project, callback)
        {
            require([
                 'qui/QUI',
                 'controls/projects/project/media/Panel',
                 'classes/projects/Project',
                 'Projects'
             ], function(QUI, MediaPanel, Project, Projects)
             {
                var panels = QUI.Controls.get( 'panel-'+ project +'-media' );

                if ( panels.length )
                {
                    panels[ 0 ].open();

                    // if a task exist, click it and open the instance
                    var Task = panels[ 0 ].getAttribute( 'Task' );

                    if ( Task && Task.getType() == 'qui/controls/taskbar/Task' ) {
                        panels[ 0 ].getAttribute( 'Task' ).click();
                    }

                    return;
                }

                panels = QUI.Controls.getByType( 'qui/controls/desktop/Tasks' );

                if ( !panels.length ) {
                    return;
                }


                var Project = Projects.get( project ),
                    Media   = Project.getMedia(),
                    Panel   = new MediaPanel( Project.getMedia() );

                panels[ 0 ].appendChild( Panel );

                if ( typeof callback !== 'undefined' ) {
                    callback( Panel );
                }
            });
        }

    };
});