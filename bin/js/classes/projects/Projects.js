
/**
 * The Project Factory
 * Its the main project object
 * it edit, delete, create Project
 *
 * @author www.pcsg.de (Henning Leutz)
 */

define('classes/projects/Projects', [

    'classes/DOM',
    'classes/projects/Project'

], function(QDOM)
{
    "use strict";

    QUI.namespace( 'classes.projects' );

    /**
     * @class QUI.classes.projects.Projects
     *
     * @memberof! <global>
     */
    QUI.classes.projects.Projects = new Class({

        Extends : QDOM,
        Type    : 'QUI.classes.projects.Projects',

        $Project  : null,
        $projects : {},

        /**
         * Standard project
         * @namespace
         */
        Standard :
        {
            /**
             * Return the lang of the standard project
             *
             * @returns {String}
             */
            getLang : function()
            {
                return QUI.standard.lang;
            },

            /**
             * Return the name of the standard project
             *
             * @returns {String}
             */
            getName : function()
            {
                return QUI.standard.name;
            }
        },

        /**
         * Return the wanted project
         * If no name and lang given, the current project will be return
         *
         * @param {String} name - [optional] Name of the project
         * @param {String lang - [optional] Lang of the project
         *
         * @return {QUI.classes.projects.Project}
         */
        get : function(name, lang)
        {
            if ( typeof name === 'undefined' || !name )
            {
                if ( typeof lang === 'undfined' ) {
                    lang = this.getLang();
                }


                if ( this.$Project === null )
                {
                    this.$Project = new QUI.classes.projects.Project({
                        name : this.getName(),
                        lang : lang
                    });
                }

                return this.$Project;
            }

            if ( this.$projects[ name +'-'+ lang ] ) {
                return this.$projects[ name +'-'+ lang ];
            }

            this.$projects[ name +'-'+ lang ] = new QUI.classes.projects.Project({
                name : name,
                lang : lang
            });

            return this.$projects[ name +'-'+ lang ];
        },

        /**
         * Return the current language of the current project,
         * if no project initialised than it return the name of the standard project
         *
         * @returns {String}
         */
        getLang : function()
        {
            if ( this.$Project ) {
                return this.$Project.getAttribute( 'lang' );
            }

            if ( QUI.lang !== '' ) {
                return QUI.lang;
            }

            return QUI.standard.lang;
        },

        /**
         * Return the name of the current project,
         * if no project initialised than it return the name of the standard project
         *
         * @returns {String}
         */
        getName : function()
        {
            if ( this.$Project ) {
                return this.$Project.getAttribute( 'name' );
            }

            if ( QUI.project !== '' ) {
                return QUI.project;
            }

            return QUI.standard.name;
        },

        /**
         * Return the project list
         *
         * @param {Function} onfinish - callback function
         * @param {Object} params - request params
         */
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
         * Opens a site panel
         *
         * @param {String} project - Project name
         * @param {String} lang - Project language
         * @param {Integer} id - ID of the site
         */
        createSitePanel : function(project, lang, id, Parent, ApppanelId)
        {
            require([
                'controls/projects/site/Panel',
                'classes/projects/Project',
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

        /**
         * Opens a project panel
         *
         * @param id
         * @param container
         *
         * @depricated
         */
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

        /**
         * Opens a media panel
         *
         * @param project
         * @param lang
         *
         * @depricated
         */
        createMediaPanel : function(project, lang)
        {
            require([

                'classes/projects/Project',
                'controls/projects/media/Panel'

            ], function(QUI_Project, QUI_MediaPanel)
            {
                var Project = new QUI_Project({
                    project : project,
                    lang    : lang
                });

                Project.getMedia().openInPanel();
            });
        },

        /**
         * Create a new project
         *
         * @param {String} project
         * @param {String} lang
         * @param {String} template
         * @params {Function} onfinish
         */
        createNewProject : function(project, lang, template, onfinish)
        {
            QUI.Ajax.post('ajax_project_create', function(result, Request)
            {
                if ( Request.getAttribute( 'onfinish' ) ) {
                    Request.getAttribute( 'onfinish' )( result, Request );
                }
            }, {
                params : JSON.encode({
                    project  : project,
                    lang     : lang,
                    template : template
                }),
                onfinish : onfinish
            });
        }
    });

    return QUI.classes.projects.Projects;
});