/**
 * Project settings panel
 *
 * @module controls/projects/project/Settings
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/desktop/Panel
 * @require qui/controls/buttons/Button
 * @require qui/controls/windows/Confirm
 * @require qui/utils/Form
 * @require utils/Template
 * @require controls/lang/Popup
 * @require Projects
 * @require Ajax
 * @require Locale
 * @require utils/Controls
 * @require css!controls/projects/project/Settings.css
 */
define('controls/projects/project/Settings', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'qui/utils/Form',
    'utils/Template',
    'controls/lang/Popup',
    'Projects',
    'Ajax',
    'Locale',
    'utils/Controls',

    'css!controls/projects/project/Settings.css'

], function (QUI,
             QUIPanel,
             QUIButton,
             QUIConfirm,
             QUIFormUtils,
             UtilsTemplate,
             LangPopup,
             Projects,
             Ajax,
             Locale,
             ControlUtils) {
    "use strict";

    var lg = 'quiqqer/system';

    /**
     * The Project settings panel
     *
     * @class controls/projects/project/Settings
     *
     * @param {String} project
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIPanel,
        Type   : 'controls/projects/project/Settings',

        Binds: [
            '$onCreate',
            '$onResize',
            '$onCategoryEnter',
            '$onCategoryLeave',

            'save',
            'del',
            'openSettings',
            'openCustomCSS',
            'openAdminSettings',
            'openBackup',
            'openMediaSettings'
        ],

        options: {
            project: ''
        },

        initialize: function (options) {
            this.parent(options);

            if (!this.getAttribute('project') && "attributes" in options) {
                this.parent(options.attributes);
            }

            // defaults
            this.$Project = Projects.get(
                this.getAttribute('project')
            );

            this.$config   = {};
            this.$defaults = {};

            this.addEvents({
                onCreate       : this.$onCreate,
                onResize       : this.$onResize,
                onCategoryEnter: this.$onCategoryEnter,
                onCategoryLeave: this.$onCategoryLeave
            });
        },

        /**
         * Return the Project of the Panel
         *
         * @method controls/projects/project/Settings#getProject
         * @return {Object} classes/projects/Project -  Project of the Panel
         */
        getProject: function () {
            return this.$Project;
        },

        /**
         * Create the project settings body
         *
         * @method controls/projects/project/Settings#$onCreate
         */
        $onCreate: function () {
            var self = this;

            this.Loader.show();
            this.getContent().addClass('qui-project-settings');

            this.addButton({
                text     : Locale.get(lg, 'projects.project.panel.settings.btn.save'),
                textimage: 'icon-save',
                events   : {
                    onClick: this.save
                }
            });

            this.addButton({
                text     : Locale.get(lg, 'projects.project.panel.settings.btn.remove'),
                textimage: 'icon-remove',
                events   : {
                    onClick: this.del
                }
            });

            this.addCategory({
                name  : 'settings',
                text  : Locale.get(lg, 'projects.project.panel.settings.btn.settings'),
                icon  : 'icon-gear',
                events: {
                    onClick: this.openSettings
                }
            });

            this.addCategory({
                name  : 'adminSettings',
                text  : Locale.get(lg, 'projects.project.panel.settings.btn.adminSettings'),
                icon  : 'icon-gear',
                events: {
                    onClick: this.openAdminSettings
                }
            });

            this.addCategory({
                name  : 'mediaSettings',
                text  : Locale.get(lg, 'projects.project.panel.settings.btn.media'),
                icon  : 'icon-picture',
                events: {
                    onClick: this.openMediaSettings
                }
            });

            this.addCategory({
                name  : 'customCSS',
                text  : Locale.get(lg, 'projects.project.panel.settings.btn.customCSS'),
                icon  : 'icon-css3',
                events: {
                    onClick: this.openCustomCSS
                }
            });


            Ajax.get('ajax_project_panel_categories_get', function (list) {
                for (var i = 0, len = list.length; i < len; i++) {
                    self.addCategory(list[i]);
                }

                self.refresh();

            }, {
                project: this.getProject().encode()
            });
        },

        /**
         * Refresh the project data
         */
        refresh: function () {
            this.parent();
            this.Loader.show();

            var self = this;

            Promise.all([
                this.getProject().getConfig(),
                this.getProject().getDefaults()
            ]).then(function (result) {

                self.setAttributes({
                    name : 'projects-panel',
                    icon : 'icon-home',
                    title: self.getProject().getName()
                });

                self.$Title.set('html', self.getAttribute('title'));

                self.$config   = result[0];
                self.$defaults = result[1];

                self.getCategoryBar().firstChild().click();
                self.Loader.hide();
            });
        },

        /**
         * Save the project settings
         */
        save: function () {
            var self = this;

            this.Loader.show();
            this.$onCategoryLeave();

            var Project = this.getProject();

            // clear config for projects
            var name = Project.getName();

            for (var project in Projects.$projects) {
                if (!Projects.$projects.hasOwnProperty(project)) {
                    continue;
                }

                if (project.match(name + '-')) {
                    if ("$config" in Projects.$projects[project]) {
                        Projects.$projects[project].$config = false;
                    }
                }
            }

            Project.setConfig(this.$config).then(function () {
                self.Loader.hide();
            }).catch(function () {
                self.Loader.hide();
            });
        },

        /**
         * Opens the delete dialog
         */
        del: function () {
            var self = this;

            new QUIConfirm({
                icon       : 'fa fa-exclamation-circle icon-exclamation-sign',
                title      : Locale.get(lg, 'projects.project.project.delete.window.title'),
                text       : Locale.get(lg, 'projects.project.project.delete.window.text'),
                texticon   : 'fa fa-exclamation-circle icon-exclamation-sign',
                information: Locale.get(lg, 'projects.project.project.delete.window.information'),
                maxWidth   : 450,
                maxHeight  : 300,
                events     : {
                    onSubmit: function () {
                        new QUIConfirm({
                            icon     : 'fa fa-exclamation-circle icon-exclamation-sign',
                            title    : Locale.get(lg, 'projects.project.project.delete.window.title'),
                            text     : Locale.get(lg, 'projects.project.project.delete.window.text.2'),
                            texticon : 'fa fa-exclamation-circle icon-exclamation-sign',
                            maxWidth : 450,
                            maxHeight: 300,
                            events   : {
                                onSubmit: function () {
                                    Projects.deleteProject(self.$Project.getName(), function () {
                                        self.destroy();
                                    });
                                }
                            }
                        }).open();
                    }
                }
            }).open();
        },

        /**
         * Opens the Settings
         *
         * @method controls/projects/project/Settings#openSettings
         */
        openSettings: function () {
            this.Loader.show();

            var self = this,
                Body = this.getBody();

            Ajax.get('ajax_project_panel_settings', function (result) {
                Body.set('html', result);

                // set data
                var Form     = Body.getElement('Form'),
                    Standard = Form.elements.default_lang,
                    Template = Form.elements.template,
                    Langs    = Form.elements.langs,

                    langs    = self.$config.langs.split(',');

                for (var i = 0, len = langs.length; i < len; i++) {
                    new Element('option', {
                        html : langs[i],
                        value: langs[i]
                    }).inject(Standard);

                    new Element('option', {
                        html : langs[i],
                        value: langs[i]
                    }).inject(Langs);
                }

                new QUIButton({
                    text     : Locale.get(lg, 'projects.project.panel.btn.addlanguage'),
                    textimage: 'icon-plus',
                    styles   : {
                        width: 200,
                        clear: 'both'
                    },
                    events   : {
                        onClick: function () {
                            new LangPopup({
                                events: {
                                    onSubmit: function (value) {
                                        self.addLangToProject(value[0]);
                                    }
                                }
                            }).open();
                        }
                    }
                }).inject(Langs, 'after');


                Standard.value = self.$config.default_lang;
                Template.value = self.$config.template;

                QUIFormUtils.setDataToForm(self.$config, Form);

                ControlUtils.parse(Body).then(function () {

                    QUI.Controls.getControlsInElement(Body).each(function (Control) {
                        if ("setProject" in Control) {
                            Control.setProject(self.$Project);
                        }
                    });

                    self.Loader.hide();
                });

            }, {
                project: this.getProject().encode()
            });
        },

        /**
         * Opens the Settings for the administration
         *
         * @method controls/projects/project/Settings#openAdminSettings
         */
        openAdminSettings: function () {
            this.Loader.show();

            var self = this,
                Body = this.getBody();

            UtilsTemplate.get('project/settingsAdmin', function (result) {
                Body.set('html', result);

                QUIFormUtils.setDataToForm(self.$config, Body.getElement('Form'));

                self.Loader.hide();
            });
        },

        /**
         * Open Custom CSS
         */
        openCustomCSS: function () {
            this.Loader.show();

            var self = this;

            this.getBody().set('html', '<form></form>');

            require([
                'controls/projects/project/settings/CustomCSS'
            ], function (CustomCSS) {
                var css  = false,
                    Form = self.getBody().getElement('form');

                if ("project-custom-css" in self.$config) {
                    css = self.$config["project-custom-css"];
                }

                new CustomCSS({
                    Project: self.getProject(),
                    css    : css,
                    events : {
                        onLoad: function () {
                            self.Loader.hide();
                        }
                    }
                }).inject(Form);

                Form.setStyles({
                    'float': 'left',
                    height : '100%',
                    width  : '100%'
                });
            });
        },

        /**
         * Opens the Media Settings
         *
         * @method controls/projects/project/Settings#openMediaSettings
         */
        openMediaSettings: function () {
            this.Loader.show();

            var self = this,
                Body = this.getBody();

            Body.set('html', '');

            require([
                'controls/projects/project/settings/Media'
            ], function (MediaSettings) {
                new MediaSettings({
                    config : self.$config,
                    Project: self.$Project,
                    events : {
                        onLoad: function () {
                            self.Loader.hide();
                        }
                    }
                }).inject(Body);
            });
        },

        /**
         * event : on panel resize
         *
         * @method controls/projects/project/Settings#$onResize
         */
        $onResize: function () {

        },

        /**
         * unload the category and set the values into the config
         */
        $onCategoryLeave: function () {
            var Content = this.getContent(),
                Form    = Content.getElement('form');

            if (!Form) {
                return;
            }

            var data = QUIFormUtils.getFormData(Form);

            for (var i in data) {
                if (data.hasOwnProperty(i)) {
                    this.$config[i] = data[i];
                }
            }

            // exist langs?
            if (typeof Form.elements.langs !== 'undefined') {
                var Langs = Form.elements.langs,
                    langs = Langs.getElements('option').map(function (Elm) {
                        return Elm.value;
                    });

                this.$config.langs = langs.join(',');
            }
        },

        /**
         * Add a language to the project
         *
         * @param {String} lang
         */
        addLangToProject: function (lang) {
            var self = this;

            self.Loader.show();

            this.$Project.getConfig(function (config) {
                var langs = config.langs.split(',');
                langs.push(lang);

                self.$Project.setConfig({
                    langs: langs.join(',')
                }).then(function () {
                    // self.Loader.hide();
                    self.refresh();
                }).catch(function () {
                    // self.Loader.hide();
                    self.refresh();
                });
            });
        },

        /**
         * event : on category enter
         *
         * @param {Object} Panel - qui/controls/desktop/Panel
         * @param {Object} Category - qui/controls/buttons/Button
         */
        $onCategoryEnter: function (Panel, Category) {
            var self = this,
                name = Category.getAttribute('name');

            switch (name) {
                case "settings":
                case "adminSettings":
                case "customCSS":
                case "mediaSettings":
                    return;
            }

            this.Loader.show();
            this.getBody().set('html', '');

            Ajax.get('ajax_settings_category', function (result) {
                var Body = self.getBody();

                if (!result) {
                    result = '';
                }

                Body.set('html', '<form>' + result + '</form>');
                Body.getElements('tr td:first-child').addClass('first');

                var Form = Body.getElement('form');

                Form.name = Category.getAttribute('name');
                Form.addEvent('submit', function (event) {
                    event.stop();
                });

                // set data to the form
                QUIFormUtils.setDataToForm(self.$config, Form);

                Form.getElements('input').each(function (Input) {
                    var name = Input.get('name');
                    if (name in self.$defaults) {
                        Input.set('data-qui-options-defaultcolor', self.$defaults[name]);
                    }
                });

                ControlUtils.parse(Body).then(function () {
                    var i, len, Control;
                    var quiids = Body.getElements('[data-quiid]');

                    for (i = 0, len = quiids.length; i < len; i++) {
                        Control = QUI.Controls.getById(quiids[i].get('data-quiid'));

                        if (!Control) {
                            continue;
                        }

                        if (typeOf(Control.setProject) == 'function') {
                            Control.setProject(self.getProject());
                        }
                    }

                    self.Loader.hide();
                });


            }, {
                file    : Category.getAttribute('file'),
                category: Category.getAttribute('name')
            });
        }
    });
});
