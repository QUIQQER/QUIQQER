/**
 * Projekt Verwaltung
 * Standard Projekt bekommen, Projektlistings etc
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('lib/Projects', function(QUI_SitePanel)
{
    QUI.namespace( 'lib' );

    QUI.Projects = QUI.lib.Projects =
    {
        $Project  : null,
        $projects : {},

        /**
         * Projekt bekommen
         * Falls keine Parameter übergeben werden, wird das aktuelle Projekt zurück gegeben
         *
         * @param name - Name des Projektes (optional)
         */
        get : function(name, lang)
        {
            if ( typeof name === 'undefined' || !name )
            {
                if ( typeof lang === 'undfined' ) {
                    lang = QUI.lib.Projects.getLang();
                }


                if ( QUI.lib.Projects.$Project === null )
                {
                    QUI.lib.Projects.$Project = new QUI.classes.Project({
                        name : QUI.lib.Projects.getName(),
                        lang : lang
                    });
                }

                return QUI.lib.Projects.$Project;
            }

            if ( this.$projects[ name +'-'+ lang ] ) {
                return this.$projects[ name +'-'+ lang ];
            }

            this.$projects[ name +'-'+ lang ] = new QUI.classes.Project({
                name : name,
                lang : lang
            });

            return this.$projects[ name +'-'+ lang ];
        },

        getLang : function()
        {
            if ( QUI.lib.Projects.$Project ) {
                return QUI.lib.Projects.$Project.getAttribute('lang');
            }

            if ( QUI.lang !== '' ) {
                return QUI.lang;
            }

            return QUI.standard.lang;
        },

        getName : function()
        {
            if ( QUI.lib.Projects.$Project ) {
                return QUI.lib.Projects.$Project.getAttribute('name');
            }

            if ( QUI.project !== '' ) {
                return QUI.project;
            }

            return QUI.standard.name;
        },

        Standard :
        {
            getLang : function()
            {
                return QUI.standard.lang;
            },

            getName : function()
            {
                return QUI.standard.name;
            }
        },

        getList : function(onfinish, params)
        {
            params = params || {};
            params.onfinish = onfinish;

            QUI.Ajax.get('ajax_project_getlist', function(result, Ajax)
            {
                Ajax.getAttribute('onfinish')(result, Ajax);
            }, params);
        },

        /**
         * Projekt Seite in einem Panel öffnen
         */
        createSitePanel : function(project, lang, id, Parent, ApppanelId)
        {
            require([
                'controls/projects/site/Panel',
                'classes/Project',
                'classes/projects/Site'
            ], function(QUI_SitePanel, QUI_Site)
            {
                var panels  = QUI.Controls.get( 'content-panel' ),
                    Project = this.get( project, lang ),
                    Site    = Project.get( id );

                if ( !panels.length ) {
                    return;
                }

                panels[ 0 ].appendChild(
                    new QUI_SitePanel( Site )
                );

            }.bind(this));
        },

        createProjectPanel : function(id, container)
        {
            require(['controls/projects/Panel'], function(QUI_ProjectPanel)
            {
                new QUI_ProjectPanel({
                    id        : id,
                    container : container
                });
            });
        },

        createMediaPanel : function(project, lang)
        {
            require([

                'classes/Project',
                'controls/projects/media/Panel'

            ], function(QUI_Project, QUI_MediaPanel)
            {
                var Project = new QUI_Project({
                    project : project,
                    lang    : lang
                });

                Project.getMedia().openInPanel();
            });
        }
    };

    return QUI.lib.Projects;
});