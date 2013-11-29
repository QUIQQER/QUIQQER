
/**
 * The Project Manager
 *
 * @author www.namerobot.de (Henning Leutz)
 */

define('classes/projects/Manager', [

    'qui/classes/DOM',
    'classes/projects/Project',
    'Ajax'

], function(QDOM, Project, Ajax)
{
    "use strict";

    /**
     * @class classes/projects/Manager
     *
     * @memberof! <global>
     */
    return new Class({

        Extends : QDOM,
        Type    : 'classes/projects/Manager',

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
                    this.$Project = new Project({
                        name : this.getName(),
                        lang : lang
                    });
                }

                return this.$Project;
            }

            if ( this.$projects[ name +'-'+ lang ] ) {
                return this.$projects[ name +'-'+ lang ];
            }

            this.$projects[ name +'-'+ lang ] = new Project({
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
                return this.$Project.getName();
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

            Ajax.get('ajax_project_getlist', function(result, Ajax)
            {
                Ajax.getAttribute('onfinish')(result, Ajax);
            }, params);
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
            Ajax.post('ajax_project_create', function(result, Request)
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
});