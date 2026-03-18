/**
 * Editor Main Class
 *
 * The editor main class is the parent class for all WYSIWYG editors.
 * Every WYSIWYG editor must inherit from this class
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
    'qui/classes/storage/Storage',
    'Ajax',
    'Projects',

    'css!controls/editors/Editor.css'

], function (QUIControl, QUILoader, EditorManager, Storage, QUIAjax, Projects) {
    "use strict";

    const EDITOR_MODUS_STORAGE_KEY = 'quiqqer-editor-modus';
    const EDITOR_MODUS_WYSIWYG = 'wysiwyg';
    const EDITOR_MODUS_SOURCE = 'source';

    const storage = new Storage();

    /**
     * Editor Main Class
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
     */
    return new Class({

        Extends: QUIControl,
        Type: 'controls/editors/Editor',

        Binds: [
            '$onDrop',
            '$onImport',
            '$onInject',
            '$onLoaded'
        ],

        options: {
            content: '',
            bodyId: false,  // wysiwyg DOMNode body id
            bodyClass: false,   // wysiwyg DOMNode body css class
            showLoader: true
        },

        initialize: function (Manager, options) {
            this.$Manager = Manager;
            this.$Elm = null;
            this.$Input = null;
            this.$Project = null;

            if (typeof this.$Manager === 'undefined') {
                this.$Manager = new EditorManager();
            }

            this.parent(options);

            this.Loader = null;
            this.$Instance = null;
            this.$Container = null;
            this.$loaded = false;

            this.$sourceCodeEditor = null;
            this.$sourceCode = null;

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
         * @return {HTMLElement} DOMNode Element
         */
        create: function () {
            this.$Elm = new Element('div', {
                html: '' +
                    '<div data-name="editor-instance" class="control-editor-container"></div>' +
                    '<div data-name="sourcecode" class="control-editor-sourcecode"></div>' +
                    '<div data-name="editor-sub-action"></div>',
                'class': 'control-editor',
                'data-name': 'quiqqer-editor',
                styles: {
                    minHeight: 300
                }
            });

            this.Loader = new QUILoader().inject(this.$Elm);

            this.$Elm.addClass('media-drop');
            this.$Elm.set('data-quiid', this.getId());

            this.$sourceCode = this.$Elm.getElement('[data-name="sourcecode"]');
            this.$Container = this.$Elm.getElement('[data-name="editor-instance"]');

            const subActions = this.$Elm.querySelector('[data-name="editor-sub-action"]');
            const sourceCode = document.createElement('div');
            sourceCode.dataset.name = "editor-sub-sourcecode";
            sourceCode.classList.add('btn', 'btn-link-body');
            sourceCode.innerHTML = '<span>Switch to Sourcecode</span>';
            sourceCode.addEventListener('click', () => {
                this.toggleSourceCode();
            });
            subActions.appendChild(sourceCode);

            return this.$Elm;
        },

        /**
         * Destroy the editor
         *
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
            this.getSettings().then((data) => {
                this.setAttribute('bodyId', data.bodyId);
                this.setAttribute('bodyClass', data.bodyClass);

                if (storage.get(EDITOR_MODUS_STORAGE_KEY) === EDITOR_MODUS_SOURCE) {
                    this.showSourceCode();

                    if (typeof callback === 'function') {
                        callback(data);
                    }

                    this.fireEvent('loaded', [this, null]);
                    this.Loader.hide();
                    return;
                }

                if (typeof callback === 'function') {
                    callback(data);
                }

                this.fireEvent('load', [data]);
            });
        },

        /**
         * event : on loaded
         */
        $onLoaded: function () {
            const body = this.getBodyNode();

            if (this.getAttribute('bodyId') && body) {
                body.id = this.getAttribute('bodyId');
            }

            if (this.getAttribute('bodyClass') && body) {
                let classes = this.getAttribute('bodyClass');

                if (classes) {
                    classes = classes.split(' ');
                } else {
                    classes = [];
                }

                for (let i = 0, len = classes.length; i < len; i++) {
                    body.classList.add(classes[i]);
                }
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
         * that's not optimal, because we must generate a new editor instance with the editor manager
         */
        $onImport: function () {
            const self = this,
                nodeName = this.$Elm.nodeName;

            if (nodeName === 'INPUT' || nodeName === 'TEXTAREA') {
                this.$Input = this.$Elm;
                this.$Elm = this.create();

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
         * @return {Object} Editor Manager (controls/editors/Manager)
         */
        getManager: function () {
            return this.$Manager;
        },

        /**
         * Returns the Editor Container for the editor instance
         *
         * @return {HTMLElement|null} Container
         */
        getContainer: function () {
            return this.$Container;
        },

        /**
         * Set the content to the editor
         *
         * @fires onSetContent [content, this]
         * @param {String} content - HTML String
         */
        setContent: function (content) {
            this.setAttribute('content', content);

            if (this.$sourceCodeEditor) {
                this.$sourceCodeEditor.setValue(content);
            }

            this.fireEvent('setContent', [
                content,
                this
            ]);
        },

        /**
         * Get the content from the editor
         *
         * @return {String} content
         */
        getContent: function () {
            this.fireEvent('getContent', [this]);

            if (this.$sourceCodeEditor) {
                this.setAttribute('content', this.$sourceCodeEditor.getValue());
            }

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

            const self = this;

            this.getManager().getToolbar(function (buttons) {
                self.setAttribute('buttons', buttons);

                callback(buttons);
            });
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
         * @param {Object} Instance - Editor Instance
         */
        setInstance: function (Instance) {
            this.$Instance = Instance;
        },

        /**
         * Get the editor instance
         * ckeditor, tinymce and so on
         *
         * @return {Object|null} Instance - Editor Instance
         */
        getInstance: function () {
            return this.$Instance;
        },

        /**
         * Return the Document DOM element of the editor frame
         *
         * @return {HTMLElement|null} document
         */
        getDocument: function () {
            if (!this.getInstance()) {
                return null;
            }

            return this.getInstance().getDocument();
        },

        getBodyNode: function () {
            if (!this.getDocument()) {
                return null;
            }

            return this.getDocument().body;
        },

        /**
         * Get the settings
         *
         * @param {Function} [callback] - callback function
         * @return {Promise}
         */
        getSettings: function (callback) {
            let project = null,
                buttons = this.getAttribute('buttons');

            if (this.$Project) {
                project = this.$Project.getName();
            }

            if (project === null) {
                project = Projects.Standard.getName();
            }

            return new Promise(function (resolve, reject) {
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
                    project: project,
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
                this.fireEvent('addCSS', [
                    file,
                    this
                ]);
                return;
            }

            if (!file.indexOf('?')) {
                this.fireEvent('addCSS', [
                    file,
                    this
                ]);
                return;
            }

            if ("QUIQQER" in window && 'lu' in QUIQQER) {
                file = file + '?lu=' + QUIQQER.lu;
            }

            this.fireEvent('addCSS', [
                file,
                this
            ]);
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
                options.lang = this.$Project.getLang();
            }

            require(['controls/projects/Popup'], function (Popup) {
                new Popup(options).open();
            });
        },

        //region sourcecode

        showSourceCode: function () {
            this.$Container.style.display = 'none';

            // lade source code
            require(['controls/editors/CodeEditor'], (CodeEditor) => {
                this.$sourceCodeEditor = new CodeEditor({
                    type: 'html',
                    events: {
                        onChange: () => {
                            const content = this.$sourceCodeEditor.getValue();

                            this.setAttribute('content', content);
                            this.fireEvent('setContent', [content, this]);
                        }
                    }
                });

                this.$sourceCodeEditor.inject(this.$sourceCode);
                this.$sourceCodeEditor.setValue(this.getAttribute('content'));
                this.$sourceCode.style.display = '';

                storage.set(EDITOR_MODUS_STORAGE_KEY, EDITOR_MODUS_SOURCE);
            });
        },

        hideSourceCode: function () {
            storage.set(EDITOR_MODUS_STORAGE_KEY, EDITOR_MODUS_WYSIWYG);
            this.$Container.style.display = '';
            this.$sourceCode.style.display = 'none';

            if (this.$sourceCodeEditor) {
                this.$sourceCodeEditor.destroy();
                this.$sourceCodeEditor = null;
            }

            if (this.getInstance()) {
                return;
            }

            this.Loader.show();
            this.load();
        },

        toggleSourceCode: function () {
            if (this.$Container.style.display === 'none') {
                this.hideSourceCode();
                return;
            }

            this.showSourceCode();
        }

        //endregion
    });
});
