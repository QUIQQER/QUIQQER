/**
 * Editor Main Class
 *
 * The editor main class is the parent class for all WYSIWYG editors.
 * Every WYSIWYG editor must inherit from this class
 *
 * @module controls/editors/Editor
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/Control
 *
 * @event onInit [ {self} ]
 * @event onDraw [ {self} ]
 * @event onDestroy[ {self} ]
 * @event onSetContent [ {String} content, {self} ]
 * @event onAddCSS [ {String} file, {self} ]
 */
define('controls/editors/Editor', [

    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'classes/editor/Manager',
    'Ajax',
    'Projects'

], function (QUIControl, QUILoader, EditorManager, QUIAjax, Projects) {
    "use strict";

    /**
     * Editor Main Class
     *
     * @class controls/editors/Editor
     *
     * @param {Object} Manager - classes/editor/Manager
     * @param {Object} options
     *
     * @fires onInit [this]
     * @fires onDraw [DOMNode, this]
     * @fires onDestroy [this]
     * @fires onSetContent [String, this]
     * @fires onGetContent [this]
     * @fires onLoaded [Editor, Instance]
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIControl,
        Type   : 'controls/editors/Editor',

        Binds: [
            '$onDrop',
            '$onImport',
            '$onInject',
            '$onLoaded'
        ],

        options: {
            content   : '',
            bodyId    : false,  // wysiwyg DOMNode body id
            bodyClass : false,   // wysiwyg DOMNode body css class
            showLoader: true
        },

        initialize: function (Manager, options) {

            this.$Manager = Manager;
            this.$Elm     = null;
            this.$Input   = null;
            this.$Project = null;

            if (typeof this.$Manager === 'undefined') {
                this.$Manager = new EditorManager();
            }

            this.parent(options);

            this.Loader = null;

            this.$Instance  = null;
            this.$Container = null;
            this.$loaded    = false;

            this.addEvents({
                onLoaded: this.$onLoaded,
                onImport: this.$onImport,
                onInject: this.$onInject
            });

            this.fireEvent('init', [this]);
        },

        /**
         * Create the DOMNode of the Editor
         *
         * @method controls/editors/Editor#create
         * @return {HTMLElement} DOMNode Element
         */
        create: function () {
            this.$Elm = new Element('div', {
                html   : '<div class="control-editor-container"></div>',
                'class': 'control-editor',
                styles : {
                    minHeight: 300
                }
            });

            this.Loader = new QUILoader().inject(this.$Elm);

            this.$Elm.addClass('media-drop');
            this.$Elm.set('data-quiid', this.getId());

            this.$Container = this.$Elm.getElement('.control-editor-container');

            return this.$Elm;
        },

        /**
         * Destroy the editor
         *
         * @method controls/editors/Editor#destroy
         * @fires onDestroy [this]
         */
        destroy: function () {
            this.fireEvent('destroy', [this]);
            this.removeEvents();

            this.getManager().destroyEditor(this);
        },

        /**
         * load the instance and the settings
         *
         * @param {Function} [callback] - callback function
         */
        load: function (callback) {
            var self = this;

            this.getSettings(function (data) {
                self.setAttribute('bodyId', data.bodyId);
                self.setAttribute('bodyClass', data.bodyClass);

                if (typeof callback === 'function') {
                    callback(data);
                }

                self.fireEvent('load', [data]);
            });
        },

        /**
         * event : on loaded
         */
        $onLoaded: function () {
            if (this.getAttribute('bodyId')) {
                this.getDocument().body.id = this.getAttribute('bodyId');
            }

            if (this.getAttribute('bodyClass')) {
                this.getDocument().body.className = this.getAttribute('bodyClass');
            }

            if (this.getAttribute('content')) {
                this.setContent(this.getAttribute('content'));
            }

            this.Loader.hide();
        },

        /**
         * on inject
         */
        $onInject: function () {
            if (this.getAttribute('showLoader')) {
                this.Loader.show();
            }

            this.load();
        },

        /**
         * event : on import
         * thats not optimal, because we must generate a new editor instance with the editor manager
         */
        $onImport: function () {
            var self     = this,
                nodeName = this.$Elm.nodeName;

            if (nodeName === 'INPUT' || nodeName === 'TEXTAREA') {
                this.$Input = this.$Elm;
                this.$Elm   = this.create();

                this.$Input.set('type', 'hidden');
                this.$Elm.wraps(this.$Input);
            }

            this.getManager().getEditor(null, function (Editor) {
                Editor.inject(self.$Elm);
                Editor.setHeight(self.$Elm.getSize().y);
                Editor.setWidth(self.$Elm.getSize().x);

                if (self.$Input) {
                    Editor.setContent(self.$Input.value);
                }

                self.addEvent('onGetContent', function () {
                    self.setAttribute('content', Editor.getContent());
                    self.$Input.value = self.getAttribute('content');
                });
            });
        },

        /**
         * Set the internal project
         *
         * @param {Object} Project - (classes/projects/Project)
         */
        setProject: function (Project) {
            if (typeOf(Project) === 'string') {
                this.$Project = Projects.get(Project);
                return;
            }

            this.$Project = Project;
        },

        /**
         * is editor loaded?
         *
         * @return {Boolean}
         */
        isLoaded: function () {
            return this.$loaded;
        },

        /**
         * Returns the Editor Manager
         *
         * @method controls/editors/Editor#getManager
         * @return {Object} Editor Manager (controls/editors/Manager)
         */
        getManager: function () {
            return this.$Manager;
        },

        /**
         * Returns the Editor Container for the editor instance
         *
         * @method controls/editors/Editor#getContainer
         * @return {HTMLElement|null} Container
         */
        getContainer: function () {
            return this.$Container;
        },

        /**
         * Set the content to the editor
         *
         * @method controls/editors/Editor#setContent
         * @fires onSetContent [content, this]
         * @param {String} content - HTML String
         */
        setContent: function (content) {
            this.setAttribute('content', content);
            this.fireEvent('setContent', [content, this]);
        },

        /**
         * Get the content from the editor
         *
         * @method controls/editors/Editor#getContent
         * @return {String} content
         */
        getContent: function () {
            this.fireEvent('getContent', [this]);

            return this.getAttribute('content');
        },

        /**
         * Return the buttons
         */
        getButtons: function (callback) {
            if (this.getAttribute('buttons')) {
                callback(this.getAttribute('buttons'));
                return;
            }

            var self = this;

            this.getManager().getToolbar(function (buttons) {
                self.setAttribute('buttons', buttons);

                callback(buttons);
            });
        },

        /**
         * Switch to source
         * can be overwritten
         */
        switchToSource: function () {

        },

        /**
         * Switch to WYSIWYG
         * can be overwritten
         */
        switchToWYSIWYG: function () {

        },

        /**
         * Hide toolbar
         * can be overwritten
         */
        hideToolbar: function () {

        },

        /**
         * Show toolbar
         * can be overwritten
         */
        showToolbar: function () {

        },

        /**
         * Set the editor height
         * can be overwritten
         *
         * @param {Number} height
         */
        setHeight: function (height) {
            this.setAttribute('height', height);
        },

        /**
         * Set the editor width
         * can be overwritten
         *
         * @param {Number} width
         */
        setWidth: function (width) {
            this.setAttribute('width', width);
        },

        /**
         * Set the editor instance
         *
         * @method controls/editors/Editor#setInstance
         * @param {Object} Instance - Editor Instance
         */
        setInstance: function (Instance) {
            this.$Instance = Instance;
        },

        /**
         * Get the editor instance
         * ckeditor, tinymce and so on
         *
         * @method controls/editors/Editor#getInstance
         * @return {Object} Instance - Editor Instance
         */
        getInstance: function () {
            return this.$Instance;
        },

        /**
         * Return the Document DOM element of the editor frame
         *
         * @return {HTMLElement} document
         */
        getDocument: function () {
            return this.$Elm.getElement('iframe').contentWindow.document;
        },

        /**
         * Get the settings
         *
         * @param {Function} [callback] - callback function
         */
        getSettings: function (callback) {
            var Project = this.$Project,
                buttons = this.getAttribute('buttons');

            return new Promise(function (resolve, reject) {
                if (!Project) {
                    QUIAjax.get(['ajax_editor_get_toolbar'], function (toolbarData) {
                        var data = {
                            toolbar: toolbarData
                        };

                        if (buttons && "lines" in buttons) {
                            data.toolbar.lines = buttons.lines;
                        } else if (buttons) {
                            data.toolbar.lines = buttons;
                        }

                        if (typeof callback === 'function') {
                            callback(data);
                        }

                        resolve(data);
                    }, {
                        onError: reject
                    });

                    return;
                }

                // load css files
                QUIAjax.get([
                    'ajax_editor_get_projectFiles',
                    'ajax_editor_get_toolbar'
                ], function (projectData, toolbarData) {
                    projectData.toolbar = toolbarData;

                    if (buttons && "lines" in buttons) {
                        projectData.toolbar.lines = buttons.lines;
                    } else if (buttons) {
                        projectData.toolbar.lines = buttons;
                    }

                    if (typeof callback === 'function') {
                        callback(projectData);
                    }

                    resolve(projectData);
                }, {
                    project: Project.getName(),
                    onError: reject
                });
            });
        },

        /**
         * Add an CSS file to the Instance
         */
        addCSS: function (file) {
            if (typeof file === 'undefined' || !file) {
                return;
            }

            if (file.indexOf("//") === 0 ||
                file.indexOf("https://") === 0 ||
                file.indexOf("http://") === 0) {
                this.fireEvent('addCSS', [file, this]);
                return;
            }

            if (!file.indexOf('?')) {
                this.fireEvent('addCSS', [file, this]);
                return;
            }

            if ("QUIQQER" in window && 'lu' in QUIQQER) {
                file = file + '?lu=' + QUIQQER.lu;
            }

            this.fireEvent('addCSS', [file, this]);
        },

        /**
         * Open the Meda Popup for Image insertion
         *
         * @param {Object} options - controls/projects/project/media/Popup options
         */
        openMedia: function (options) {
            if (this.$Project) {
                options.project = this.$Project.getName();
            }

            require(['controls/projects/project/media/Popup'], function (Popup) {
                new Popup(options).open();
            });
        },

        /**
         * Open the Meda Popup for Image insertion
         *
         * @param {Object} options - controls/projects/project/project/Popup options
         */
        openProject: function (options) {
            if (this.$Project) {
                options.project = this.$Project.getName();
                options.lang    = this.$Project.getLang();
            }

            require(['controls/projects/Popup'], function (Popup) {
                new Popup(options).open();
            });
        }
    });
});
