/**
 * Projects Sitemap Popup
 *
 * In this Popup you can select a site from a project and submit it
 * eq for insert a link into a input element or editor
 *
 * @module controls/projects/Popup
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/windows/Confirm
 * @require qui/controls/buttons/Select
 * @require Projects
 * @require Locale
 * @require controls/projects/project/Sitemap
 * @require css!controls/projects/Popup.css
 *
 * @event onSubmit [ {this}, {Object} result ];
 */

define('controls/projects/Popup', [

    'qui/controls/windows/Confirm',
    'qui/controls/buttons/Select',
    'Projects',
    'Locale',
    'controls/projects/project/Sitemap',

    'css!controls/projects/Popup.css'

], function (QUIPopup, QUISelect, Projects, Locale, ProjectMap) {
    "use strict";

    return new Class({

        Extends: QUIPopup,
        Type   : 'controls/projects/Popup',

        Binds: [
            '$onCreate',
            '$onOpen'
        ],

        options: {
            project             : false,
            lang                : false,
            langs               : false,
            icon                : 'fa fa-home',
            title               : Locale.get('quiqqer/system', 'projects'),
            maxWidth            : 400,
            maxHeight           : 600,
            autoclose           : true,
            multiple            : false,  // select multiple items
            disableProjectSelect: false,  // Can the user change the projects?
            information         : false   // information text
        },

        initialize: function (options) {
            this.parent(options);

            this.$Header      = null;
            this.$Body        = null;
            this.$Map         = null;
            this.$Information = null;
            this.$Select      = null;

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        /**
         * Open the Project Sitemap Window
         *
         * @return {Object} this (controls/projects/Popup)
         */
        $onOpen: function () {
            var self    = this,
                Content = this.getContent();

            this.Loader.show();

            Content.set(
                'html',

                '<div class="qui-project-popup-header box"></div>' +
                '<div class="qui-project-popup-body box"></div>'
            );

            Content.setStyles({
                padding: 0
            });

            this.$Header = Content.getElement('.qui-project-popup-header');
            this.$Body   = Content.getElement('.qui-project-popup-body');

            if (this.getAttribute('information')) {
                this.$Information = new Element('div', {
                    'class': 'qui-project-popup-information box',
                    html   : this.getAttribute('information')
                });

                this.$Information.inject(Content, 'top');
            }

            this.$Select = new QUISelect({
                styles: {
                    margin  : 8,
                    position: 'relative'
                },
                events: {
                    onChange: function () {
                        var value = this.getValue().split(',');

                        self.setAttribute('project', value[0]);
                        self.setAttribute('lang', value[1]);

                        self.loadMap();
                    }
                }
            }).inject(this.$Header);

            if (this.getAttribute('disableProjectSelect')) {
                this.$Select.disable();
            }

            // load the projects
            Projects.getList(function (result) {
                var i, len, langs, project;

                var selfLangs      = self.getAttribute('langs'),
                    allowedProject = self.getAttribute('project'),
                    allowedLangs   = !selfLangs ? false : {};

                if (selfLangs && selfLangs.length) {
                    for (i = 0, len = selfLangs.length; i < len; i++) {
                        allowedLangs[selfLangs[i]] = true;
                    }
                }

                for (project in result) {
                    if (!result.hasOwnProperty(project)) {
                        continue;
                    }

                    langs = result[project].langs.split(',');

                    for (i = 0, len = langs.length; i < len; i++) {
                        if (allowedProject && allowedProject != project) {
                            continue;
                        }

                        if (allowedLangs && !allowedLangs[langs[i]]) {
                            continue;
                        }

                        this.$Select.appendChild(
                            project + ' (' + langs[i] + ')',
                            project + ',' + langs[i],
                            'fa fa-home'
                        );
                    }
                }

                if (self.getAttribute('lang') && self.getAttribute('project')) {
                    this.$Select.setValue(
                        self.getAttribute('project') + ',' +
                        self.getAttribute('lang')
                    );

                } else if (this.$Select.firstChild()) {
                    this.$Select.setValue(
                        this.$Select.firstChild().getAttribute('value')
                    );
                }


                self.Loader.hide();
            }.bind(this));
        },

        /**
         * Load the Sitemap of the Popup
         *
         * @return {Object} this (controls/projects/Popup)
         */
        loadMap: function () {
            if (!this.$Body) {
                return this;
            }

            this.Loader.show();

            if (this.$Map) {
                this.$Map.destroy();
            }

            this.$Map = new ProjectMap({
                project : this.getAttribute('project'),
                lang    : this.getAttribute('lang'),
                multiple: this.getAttribute('multiple')
            });

            this.$Map.inject(this.$Body);
            this.$Map.open();

            this.Loader.hide();
        },

        /**
         * Submit the window
         *
         * @method controls/projects/Popup#submit
         */
        submit: function () {
            if (!this.$Map) {
                if (this.getAttribute('autoclose')) {
                    this.close();
                }

                return;
            }

            var ids, urls;

            var value   = this.$Select.getValue().split(','),
                project = value[0],
                lang    = value[1];

            var children      = this.$Map.getSelectedChildren();
            var projectString = 'project=' + project + '&' + 'lang=' + lang;

            ids = children.map(function (o) {
                return o.getAttribute('value');
            });

            urls = children.map(function (o) {
                return 'index.php?id=' + o.getAttribute('value') + '&' + projectString;
            });

            var result = {
                project: project,
                lang   : lang,
                ids    : ids,
                urls   : urls
            };

            this.fireEvent('submit', [this, result]);

            if (this.getAttribute('autoclose')) {
                this.close();
            }
        }
    });
});
