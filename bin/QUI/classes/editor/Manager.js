/**
 * Editor Manager
 *
 * The editor manager creates the editors and load all required classes
 *
 * @module classes/editor/Manager
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/classes/DOM
 * @require Ajax
 */

define('classes/editor/Manager', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax'

], function (QUI, QDOM, Ajax) {
    "use strict";

    /**
     * Editor Manager
     *
     * @class classes/editor/Manager
     * @memberof! <global>
     */
    return new Class({

        Extends: QDOM,
        Type   : 'classes/editor/Manager',

        options: {},

        initialize: function () {
            this.$config    = null;
            this.$editors   = {};
            this.$instances = {};
        },

        /**
         * Register editor parameter
         * You can register different editors like ckeditor3, tinymce and so on
         * with the params can you define specific functionality for different
         * editors
         *
         * @method classes/editor/Manager#register
         *

         @example

         Manager.register('package/ckeditor4', {
    events : {},
    methods : {}
});

         *
         * @param {String} name
         * @param {Object} onload_params - Editor parameters, see example
         */
        register: function (name, onload_params) {
            this.$editors[name] = onload_params;
        },

        /**
         * Register an editor instance
         *
         * @method classes/editor/Manager#$registerEditor
         * @param {Object} Instance - controls/editors/Editor
         *
         * @ignore
         */
        $registerEditor: function (Instance) {
            this.$instances[Instance.getId()] = Instance;
        },

        /**
         * It generate a {controls/editors/Editor} Instance from an editor parameter
         *
         * @method classes/editor/Manager#getEditor
         *
         * @param {String|null} [name]   - Editor parameters name, like ckeditor3, if null,
         * @param {Function} [callback]  - Callback function, if editor is loaded,
         *                                 the Parameter of the function is an {controls/editors/Editor} Instance
         * @return {Promise}
         */
        getEditor: function (name, callback) {
            var self = this;

            name = name || null;

            return new Promise(function (resolve) {

                // use the standard editor
                if (name === null) {
                    self.getConfig().then(function () {
                        return self.getEditor(self.$config.settings.standard);
                    }).then(function (Editor) {

                        if (typeof callback === 'function') {
                            callback(Editor);
                        }

                        resolve(Editor);
                    });

                    return;
                }


                if (name in self.$editors) {
                    var Editor = new self.$editors[name](this);

                    self.$registerEditor(Editor);

                    if (typeof callback === 'function') {
                        callback(Editor);
                    }

                    resolve(Editor);
                    return;
                }


                self.getConfig().then(function () {
                    require([self.$config.editors[name]], function (Editor) {
                        self.$editors[name] = Editor;

                        self.getEditor(name).then(function (Editor) {
                            if (typeof callback === 'function') {
                                callback(Editor);
                            }

                            resolve(Editor);
                        });
                    });
                });
            });
        },

        /**
         * Destroy an editor
         *
         * @method classes/editor/Manager#destroyEditor
         * @param {Object} Editor (controls/editors/Editor)
         */
        destroyEditor: function (Editor) {
            var id = Editor.getId();

            if (typeof this.$instances[id] !== 'undefined') {
                delete this.$instances[id];
            }

            QUI.Controls.destroy(Editor);
        },

        /**
         * Get the main Editor config
         *
         * @method classes/editor/Manager#getConfig
         * @param {Function} [callback] - Callback function
         * @return {Promise}
         */
        getConfig: function (callback) {
            var self = this;

            return new Promise(function (resolve) {
                if (self.$config) {
                    if (typeof callback === 'function') {
                        callback(self.$config);
                    }

                    return resolve(self.$config);
                }

                Ajax.get('ajax_editor_get_config', function (result) {
                    self.$config = result;

                    if (typeof callback === 'function') {
                        callback(self.$config);
                    }

                    resolve(self.$config);
                });
            });
        },

        /**
         * Get the toolbar for the user
         *
         * @method classes/editor/Manager#getToolbars
         * @param {Function} [callback] - Callback function
         * @return {Promise}
         */
        getToolbar: function (callback) {
            return new Promise(function (resolve, reject) {
                Ajax.get('ajax_editor_get_toolbar', function (result) {
                    if (typeof callback === 'function') {
                        callback(result);
                    }

                    resolve(result);
                }, {
                    onError: reject
                });
            });
        },

        /**
         * Get all available toolbar
         *
         * @method classes/editor/Manager#getToolbars
         * @param {Function} [callback] - Callback function
         * @return {Promise}
         */
        getToolbars: function (callback) {
            return new Promise(function (resolve, reject) {
                Ajax.get('ajax_editor_get_toolbars', function (result) {
                    if (typeof callback === 'function') {
                        callback(result);
                    }

                    resolve(result);
                }, {
                    onError: reject
                });
            });
        }
    });
});
