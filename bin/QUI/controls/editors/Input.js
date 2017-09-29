/**
 * Input field which opens an editor in the window
 *
 * @package controls/editors/Input
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/editors/Input', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/windows/Confirm',
    'controls/editors/Preview',

    'css!controls/editors/Input.css'

], function (QUI, QUIControl, QUIConfirm, Preview) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : '',

        Binds: [
            'open',
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Input   = null;
            this.$Editor  = null;
            this.$Project = null;
            this.$Preview = null;
            this.$Click   = null;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * event : on import
         */
        $onImport: function () {
            var self = this;

            this.$Input      = this.getElm();
            this.$Input.type = 'hidden';

            this.$Input.addEvent('change', function () {
                self.$Preview.setContent(self.$Input.value);
            });

            this.$Input.addEvent('focus', this.open);

            this.$Elm = new Element('div', {
                'class' : 'field-container-field control-editor-input',
                events  : {
                    click: this.open,
                    focus: this.open
                },
                tabIndex: -1
            }).wraps(this.$Input);


            this.$Preview = new Preview({
                events: {
                    onLoad: function () {
                        self.$Preview.setContent(self.$Input.value);
                    }
                },
                styles: {
                    position: 'relative',
                    zIndex  : 1
                }
            }).inject(this.$Elm);

            this.$Click = new Element('div', {
                'class': 'control-editor-input-click',
                events : {
                    click: this.open,
                    focus: this.open
                }
            }).inject(this.$Elm);

            this.resize();
        },

        /**
         * resize
         */
        resize: function () {
            var computed = this.$Elm.getComputedSize();

            this.$Preview.setStyles({
                height: computed.height - computed.computedTop - computed.computedBottom
            });
        },

        /**
         * Set the used project
         *
         * @param {Object}  Project
         */
        setProject: function (Project) {
            if (typeOf(Project) === 'string') {
                require(['Projects'], function (Projects) {
                    this.setProject(Projects.get(Project));
                }.bind(this));
                return;
            }

            this.$Project = Project;
            this.$setProjectToEditor();
        },

        /**
         * open the editor window
         */
        open: function (event) {
            if (typeOf(event) === 'domevent') {
                event.stop();
            }

            var self = this;

            new QUIConfirm({
                title    : 'Inhalt einfügen',
                maxHeight: 600,
                maxWidth : 800,
                events   : {
                    onOpen: function (Win) {
                        var Content = Win.getContent();

                        Content.set('html', '');

                        require(['Editors'], function (Editors) {
                            Editors.getEditor().then(function (Editor) {
                                self.$Editor = Editor;

                                self.$setProjectToEditor();
                                Editor.setContent(self.$Input.value);
                                Editor.inject(Content);
                            });
                        });
                    },

                    onSubmit: function () {
                        if (!self.$Editor) {
                            return;
                        }

                        self.$Input.value = self.$Editor.getContent();
                        self.$Preview.setContent(self.$Input.value);
                    }
                }
            }).open();
        },

        /**
         * event : on set project to editor
         */
        $setProjectToEditor: function () {
            if (this.$Editor) {
                this.$Editor.setProject(this.$Project);
            }

            require(['Editors'], function (Editors) {
                Editors.getProjectFiles(this.$Project).then(function (files) {

                    files.cssFiles.each(function (file) {
                        this.$Preview.addCSSFile(file);
                    }.bind(this));

                }.bind(this));
            }.bind(this));
        }
    });
});
