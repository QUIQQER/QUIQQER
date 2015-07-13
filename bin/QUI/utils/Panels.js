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
         *
         * @return Promise
         */
        openSitePanel : function(project, lang, id, callback)
        {
            var self = this;

            return new Promise(function(resolve, reject) {

                require([
                    'qui/QUI',
                    'qui/controls/desktop/Panel',
                    'controls/projects/project/site/Panel',
                    'Projects'
                ], function(QUI, QUIPanel, SitePanel, Projects)
                {
                    var n      = 'panel-'+ project +'-'+ lang +'-'+ id,
                        panels = QUI.Controls.get( n );

                    if (panels.length)
                    {
                        for (var i = 0, len = panels.length; i < len; i++)
                        {
                            if (!instanceOf(panels[i], QUIPanel)) {
                                continue;
                            }

                            // if a task exist, click it and open the instance
                            self.execPanelOpen(panels[i]);
                            resolve(panels[i]);
                            return;
                        }
                    }

                    panels = QUI.Controls.getByType('qui/controls/desktop/Tasks');

                    if (!panels.length) {
                        reject('panel not found');
                        return;
                    }

                    var Panel = new SitePanel(
                        Projects.get(project, lang).get(id)
                    );

                    panels[0].appendChild(Panel);

                    if (typeof callback === 'function') {
                        callback(Panel);
                    }

                    resolve(Panel);

                }, reject);
            });
        },

        /**
         * opens a media panel
         * if the panel exists, there will be used
         *
         * @param {String} project - Name of the project
         * @param {Object} [params] - Media Panel Parameter
         * @param {Function} [callback] - callback function, only triggered if the panel is not exist
         *
         * @return Promise
         */
        openMediaPanel : function(project, params, callback)
        {
            var self = this,
                folderId = false;

            if (typeof params === 'undefined') {
                params = {};
            }

            if ("fileid" in params) {
                folderId = params.fileid;
            }

            return new Promise(function(resolve, reject) {

                require([
                    'qui/QUI',
                    'qui/controls/desktop/Panel',
                    'controls/projects/project/media/Panel',
                    'Projects'
                ], function(QUI, QUIPanel, MediaPanel, Projects)
                {
                    var i, len, Panel;
                    var panels = QUI.Controls.get('projects-media-panel');

                    if (panels.length)
                    {
                        for (i = 0, len = panels.length; i < len; i++)
                        {
                            Panel = panels[i];

                            if (Panel.getProject().getName() != project) {
                                continue;
                            }

                            if (folderId) {
                                Panel.openID(parseInt(folderId));
                            }

                            self.execPanelOpen(Panel);
                            resolve(Panel);

                            return;
                        }
                    }

                    panels = QUI.Controls.getByType('qui/controls/desktop/Tasks');

                    if (!panels.length) {
                        reject('tasks not found, panel could not be inserted');
                        return;
                    }

                    Panel = new MediaPanel(
                        Projects.get(project).getMedia()
                    );

                    panels[0].appendChild(Panel);

                    if (folderId) {
                        Panel.openID(parseInt(folderId));
                    }

                    if (typeof callback === 'function') {
                        callback(Panel);
                    }

                    resolve(Panel);

                }, reject);
            });
        },

        /**
         * opens a media item panel
         *
         * @param {String} project -Name of the project
         * @param {String} id - ID of the file
         * @param {Function} [callback] - callback function, only triggered if the panel is not exist
         */
        openMediaItemPanel : function(project, id, callback)
        {
            var self = this;

            return new Promise(function(resolve, reject) {

                require([
                    'qui/QUI',
                    'qui/controls/desktop/Panel',
                    'controls/projects/project/media/Panel',
                    'controls/projects/project/media/FilePanel',
                    'controls/projects/project/media/FolderPanel',
                    'Projects'
                ], function(QUI, QUIPanel, MediaPanel, FilePanel, FolderPanel, Projects)
                {
                    var Media = Projects.get(project).getMedia();

                    Media.get(id).then(function(Item) {

                        var i, len, Panel = false;

                        var panels = QUI.Controls.get(
                            'projects-media-file-panel-'+ Item.getId()
                        );

                        if (panels.length) {

                            for (i = 0, len = panels.length; i < len; i++) {

                                Panel = panels[i];

                                if (Panel.getProject().getName() != project) {
                                    continue;
                                }

                                self.execPanelOpen(Panel);
                                resolve(Panel);

                                return;
                            }
                        }

                        panels = QUI.Controls.getByType('qui/controls/desktop/Tasks');

                        if (!panels.length) {
                            reject('tasks not found, panel could not be inserted');
                            return;
                        }

                        // if the MediaFile is no Folder
                        if (Item.getType() !== 'classes/projects/project/media/Folder') {
                            Panel = new FilePanel(Item);

                        } else if (Item.getType() === 'classes/projects/project/media/Folder') {

                            Panel = new FolderPanel({
                                folderId : Item.getId(),
                                project  : project
                            });

                        }

                        if (!Panel) {
                            return reject('panel type could not be found');
                        }

                        panels[0].appendChild(Panel);

                        if (typeof callback === 'function') {
                            callback(Panel);
                        }

                        resolve(Panel);

                    }).catch(reject);

                }, reject);

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
                    callback(Panel);
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
            var self = this;

            require(['qui/QUI'], function(QUI)
            {
                var i, len, Child;
                var panels = QUI.Controls.getByType( Panel.getType() );

                if ( panels.length )
                {
                    for ( i = 0, len = panels.length; i < len; i++ )
                    {
                        Child = panels[ i ];

                        if (!Child.getParent()) {
                            continue;
                        }

                        if (Panel.getAttribute('title') != Child.getAttribute('title')) {
                            continue;
                        }

                        if (Panel.getAttribute('#id') &&
                            Child.getAttribute('#id') &&
                            Panel.getAttribute('#id') != Child.getAttribute('#id')) {
                            continue;
                        }

                        // if a task exist, click it and open the instance
                        self.execPanelOpen( Child );
                        return;
                    }
                }

                // if panel not exists
                var tasks = QUI.Controls.getByType( 'qui/controls/desktop/Tasks' );

                if (!tasks.length) {
                    return;
                }

                for (i = 0, len = tasks.length; i < len; i++) {
                    if (tasks[i].getElm().getParent('body')) {
                        tasks[i].appendChild(Panel);

                        (function() {
                            Panel.focus();
                        }).delay( 100 );
                        break;
                    }
                }
            });
        },

        /**
         * Opens panel, if panel has a task, the task click would be executed
         * @param {Object} Panel - qui/controls/desktop/Panel
         */
        execPanelOpen : function(Panel)
        {
            // if a task exist, click it and open the instance
            var Task = Panel.getAttribute('Task');

            if  (Task && Task.getType() == 'qui/controls/taskbar/Task')
            {
                Panel.getAttribute('Task').click();
                return;
            }

            Panel.open();
        }
    };
});