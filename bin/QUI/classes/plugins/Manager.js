/**
 * @todo -> irgendwann als package manager
 */

define('classes/plugins/Manager', [

    'qui/QUI',
    'qui/classes/DOM',
    'classes/plugins/Plugin',
    'qui/utils/Object',
    'Ajax',
    'Plugins',
    'Projects'

], function (QUI, DOM, Plugin, ObjectUtils, Ajax, Plugins, Projects) {
    "use strict";

    return new Class({

        Extends: DOM,
        Type   : 'classes/plugins/Manager',

        initialize: function (options) {
            this.parent(options);

            this.$plugins    = {};
            this.$typesNames = {};
        },

        /**
         * create an Plugin
         *
         * @param plugin - String: Plugin Name
         * @param options - Plugin Params
         *     events
         *     methods
         */
        create: function (plugin, options) {
            options      = options || {};
            options.name = plugin;

            this.$plugins[plugin] = new Plugin(options);
        },


        get: function (plugin, onfinish) {
            if (this.$plugins[plugin]) {
                this.$get(plugin, onfinish);
                return;
            }

            if (typeof QUI.MVC.plugins[plugin] === 'undefined') {
                onfinish(false);
                return;
            }

            QUI.MVC.require([plugin], function (plugin, onfinish) {
                this.$get(plugin, onfinish);

            }.bind(this, [plugin, onfinish]));
        },

        /**
         * Load ausführen
         */
        $get: function (plugin, onfinish) {
            if (this.$plugins[plugin].isLoaded()) {
                onfinish(this.$plugins[plugin]);
                return;
            }

            this.$plugins[plugin].load(function () {
                onfinish(this.$plugins[plugin]);
            }.bind(this, [plugin, onfinish]));
        },

        /**
         * Return the name of a type
         *
         * @param {String} type
         * @param {Function} [onfinish]
         * @param {Object} [params]
         */
        getTypeName: function (type, onfinish, params) {
            if (typeof this.$typesNames[type] !== 'undefined') {
                if (typeof onfinish === 'function') {
                    onfinish(this.$typesNames[type]);
                }

                return;
            }

            params = ObjectUtils.combine(params, {
                sitetype: type
            });

            Ajax.get('ajax_project_types_get_title', function (result) {
                if (typeof onfinish === 'function') {
                    onfinish(result);
                }
            }, params);
        },

        /**
         * Return all available types of a project
         *
         * @param {String} project - project name
         * @param {Function} [onfinish] - callback
         * @param {Object} [params]
         */
        getTypes: function (project, onfinish, params) {
            var Project = Projects.get();

            if (typeof project !== 'undefined') {
                Project = Projects.get(project);
            }

            params         = params || {};
            params.project = Project.encode();

            Ajax.get('ajax_project_types_get_list', function (result, Ajax) {
                if (typeof onfinish === 'function') {
                    onfinish(result, Ajax);
                }
            }, params);
        }
    });
});
