/**
 * The Project Manager
 *
 * @module classes/projects/Manager
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onCreate
 * @event onDelete
 * @event onProjectSave -> triggerd via project
 */
define('classes/projects/Manager', [

    'qui/classes/DOM',
    'classes/projects/Project',
    'Ajax'

], function (QDOM, Project, Ajax) {
    "use strict";

    /**
     * @class classes/projects/Manager
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QDOM,
        Type   : 'classes/projects/Manager',

        $Project : false,
        $projects: {},
        $getList : null, // internal project cache

        /**
         * Standard project
         * @namespace
         */
        Standard: {
            /**
             * Return the lang of the standard project
             *
             * @returns {String}
             */
            getLang: function () {
                return QUIQQER_PROJECT.lang;
            },

            /**
             * Return the name of the standard project
             *
             * @returns {String}
             */
            getName: function () {
                return QUIQQER_PROJECT.name;
            }
        },

        /**
         * Return the wanted project
         * If no name and lang given, the current project will be return
         *
         * @param {String} [name] - (optional), Name of the project
         * @param {String} [lang] - (optional), Lang of the project
         *
         * @return {Object} classes/projects/Project
         */
        get: function (name, lang) {
            if (typeof name === 'undefined' || !name) {
                if (typeof lang === 'undefined') {
                    lang = this.getLang();
                }


                if (this.$Project === null) {
                    this.$Project = new Project({
                        name: this.getName(),
                        lang: lang
                    });
                }

                return this.$Project;
            }

            if (this.$projects[name + '-' + lang]) {
                return this.$projects[name + '-' + lang];
            }

            this.$projects[name + '-' + lang] = new Project({
                name: name,
                lang: lang
            });

            return this.$projects[name + '-' + lang];
        },

        /**
         * Return the current language of the current project,
         * if no project initialised than it return the name of the standard project
         *
         * @returns {String}
         */
        getLang: function () {
            if (this.$Project) {
                return this.$Project.getAttribute('lang');
            }

            return QUIQQER_PROJECT.lang;
        },

        /**
         * Return the name of the current project,
         * if no project initialised than it return the name of the standard project
         *
         * @returns {String}
         */
        getName: function () {
            if (this.$Project) {
                return this.$Project.getName();
            }

            return QUIQQER_PROJECT.name;
        },

        /**
         * Return the project list
         *
         * @param {Function} [onFinish] - callback function
         * @return {Promise}
         */
        getList: function (onFinish) {
            if (this.$getList) {
                if (typeOf(onFinish) === 'function') {
                    onFinish(this.$getList);
                }

                return Promise.resolve(this.$getList);
            }

            return new Promise(function (resolve) {
                Ajax.get('ajax_project_getlist', function (result) {
                    this.$getList = result;

                    if (typeOf(onFinish) === 'function') {
                        onFinish(result);
                    }

                    resolve(result);
                }.bind(this));
            }.bind(this));
        },

        /**
         * Create a new project
         *
         * @param {String} project
         * @param {String} lang
         * @param {String} template
         * @param {Function} [onfinish]
         */
        createNewProject: function (project, lang, template, onfinish) {
            var self = this;

            this.$getList = null;

            Ajax.post('ajax_project_create', function (result) {

                if (typeof onfinish !== 'undefined') {
                    onfinish(result);
                }

                self.fireEvent('create', [project, lang]);
            }, {
                params: JSON.encode({
                    project : project,
                    lang    : lang,
                    template: template
                })
            });
        },

        /**
         * Delete a project
         *
         * @param {String} project - name of the project
         * @param {Function} [callback] - callback function
         */
        deleteProject: function (project, callback) {
            var self    = this,
                Project = this.get(project);

            this.$getList = null;

            Ajax.post('ajax_project_delete', function () {
                var list = {};

                for (var pro in self.$projects) {
                    if (!self.$projects.hasOwnProperty(pro)) {
                        continue;
                    }

                    if (!pro.contains(project + '-')) {
                        list[pro] = self.$projects[pro];
                    }
                }

                self.$projects = list;
                self.fireEvent('delete', [project]);

                if (typeof callback === 'function') {
                    callback();
                }
            }, {
                project: Project.encode()
            });
        }
    });
});
