define('controls/projects/project/site/Select', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Popup',
    'qui/controls/elements/Help',
    'controls/projects/TypeWindow',
    'controls/projects/Popup',
    'Projects',
    'Ajax',
    'Locale',

    'css!controls/projects/project/site/Select.css'

], function (QUI,
             QUIControl,
             QUIButton,
             QUIPopup,
             QUIHelp,
             TypeWindow,
             ProjectWindow,
             Projects,
             Ajax,
             QUILocale) {
    "use strict";

    var lg = 'quiqqer/core';

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/projects/project/site/Select',

        Binds: [
            'openSitemap',
            'openSiteTypes',
            'openParentSitemap',
            '$onImport'
        ],

        options: {
            styles      : false,
            name        : '',
            value       : '',
            projectName : false,
            projectLang : false,
            placeholder : '',
            selectids   : true,
            selecttypes : true,
            selectparent: true
        },

        initialize: function (options) {
            this.parent(options);

            this.$Input = false;
            this.$Buttons = false;
            this.$Container = false;
            this.$Project = false;

            this.$ButtonTypes = false;
            this.$ButtonSite = false;
            this.$ButtonParents = false;

            this.$labelCache = {};
            this.$labelProject = '';
            this.$isSettingValue = false;

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * Return the domnode element
         *
         * @return {HTMLElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class': 'control-site-select',
                html   : '<div class="control-site-select-container"></div>' +
                         '<div class="control-site-select-buttons"></div>' +
                         '<div class="control-site-select-description"></div>'
            });

            if (!this.$Input) {
                this.$Input = new Element('input', {
                    type: 'hidden'
                }).inject(this.$Elm);
            }

            if (this.getAttribute('styles')) {
                this.$Elm.setStyles(this.getAttribute('styles'));
            }

            this.$Buttons = this.$Elm.getElement('.control-site-select-buttons');
            this.$Container = this.$Elm.getElement('.control-site-select-container');
            this.$Description = this.$Elm.getElement('.control-site-select-description');

            this.$Container.set(
                'html',

                '<p class="control-site-select-container-placeholder">' +
                this.getAttribute('placeholder') +
                '</p>'
            );
            var selecttypesLocale  = '',
                selectidsLocale    = '',
                selectparentLocale = '';

            if (this.getAttribute('selecttypes')) {
                selecttypesLocale = QUILocale.get(lg, 'projects.project.site.select.description.site_types');
            }

            if (this.getAttribute('selectids')) {
                selectidsLocale = QUILocale.get(lg, 'projects.project.site.select.description.sites');
            }

            if (this.getAttribute('selectparent')) {
                selectparentLocale = QUILocale.get(lg, 'projects.project.site.select.description.site_children');
            }

            new QUIHelp({
                text: QUILocale.get(lg, 'projects.project.site.select.description', {
                    site_types   : selecttypesLocale,
                    sites        : selectidsLocale,
                    site_children: selectparentLocale
                })
            }).inject(this.$Description);

            if (this.getAttribute('selectids')) {
                this.$ButtonSite = new QUIButton({
                    name     : 'add-site',
                    text     : QUILocale.get(lg, 'projects.project.site.select.btn.addSite'),
                    title    : QUILocale.get(lg, 'projects.project.site.select.btn.addSite'),
                    textimage: 'fa fa-file-o',
                    events   : {
                        onClick: this.openSitemap
                    },
                    disabled: true
                }).inject(this.$Buttons);
            }

            if (this.getAttribute('selecttypes')) {
                this.$ButtonTypes = new QUIButton({
                    name     : 'add-types',
                    text     : QUILocale.get(lg, 'projects.project.site.select.btn.addTypes'),
                    title    : QUILocale.get(lg, 'projects.project.site.select.btn.addTypes'),
                    textimage: 'fa fa-puzzle-piece',
                    events   : {
                        onClick: this.openSiteTypes
                    },
                    disabled: true
                }).inject(this.$Buttons);
            }

            if (this.getAttribute('selectparent')) {
                this.$ButtonParents = new QUIButton({
                    name     : 'add-parent',
                    text     : QUILocale.get(lg, 'projects.project.site.select.btn.addParent'),
                    title    : QUILocale.get(lg, 'projects.project.site.select.btn.addParent'),
                    textimage: 'fa fa-sitemap',
                    events   : {
                        onClick: this.openParentSitemap
                    },
                    disabled: true
                }).inject(this.$Buttons);
            }


            return this.$Elm;
        },

        /**
         * Resize the control
         */
        resize: function () {
            if (!this.$Elm) {
                return;
            }

            this.parent();

            var maxSize  = this.$Elm.getSize(),
                btnSize  = this.$Buttons.getSize(),
                descSize = this.$Description.getSize();

            this.$Container.setStyle('height', maxSize.y - btnSize.y - descSize.y - 2);
        },

        /**
         * Refresh the control
         */
        refresh: function () {
            if (!this.$Elm) {
                return;
            }

            this.resize();
            this.refreshValues();
        },

        /**
         * event : on import
         */
        $onImport: function () {
            if (this.$Elm.nodeName !== 'INPUT') {
                return;
            }

            this.$Input = this.$Elm;
            this.$Input.type = 'hidden';
            this.$Input.set('data-quiid', this.getId());

            this.$Elm = this.create();
            this.$Elm.wraps(this.$Input);

            this.setAttribute('name', this.$Input.name);
            this.setAttribute('value', this.$Input.value);

            this.setProject(
                this.$Input.get('data-project'),
                this.$Input.get('data-lang')
            );

            if (this.$Input.value !== '') {
                this.setValue(this.$Input.value);
            }

            this.resize();
        },

        /**
         * Set the project
         *
         * @param {String|Object} project - Name of the Project
         * @param {String} [lang] - Language of the Project
         */
        setProject: function (project, lang) {
            var projectKey;

            if (typeOf(project) === 'classes/projects/Project') {
                this.$Project = project;

                projectKey = project.getName() + ':' + project.getLang();

                if (projectKey !== this.$labelProject) {
                    this.$labelProject = projectKey;
                    this.$labelCache = {};
                }

                if (this.$ButtonTypes) {
                    this.$ButtonTypes.enable();
                }

                if (this.$ButtonSite) {
                    this.$ButtonSite.enable();
                }

                if (this.$ButtonParents) {
                    this.$ButtonParents.enable();
                }

                this.$loadEntryLabels();
                return;
            }

            this.setAttribute('projectName', project);
            this.setAttribute('projectLang', lang);

            if (!project || !lang) {
                return;
            }

            this.$Project = Projects.get(
                this.getAttribute('projectName'),
                this.getAttribute('projectLang')
            );

            projectKey = this.$Project.getName() + ':' + this.$Project.getLang();

            if (projectKey !== this.$labelProject) {
                this.$labelProject = projectKey;
                this.$labelCache = {};
            }


            if (this.$ButtonTypes) {
                this.$ButtonTypes.enable();
            }

            if (this.$ButtonSite) {
                this.$ButtonSite.enable();
            }

            if (this.$ButtonParents) {
                this.$ButtonParents.enable();
            }

            this.$loadEntryLabels();
        },

        /**
         * Set the input value
         *
         * @param {String} value
         */
        setValue: function (value) {
            var i, len, val;
            var values = value.split(';');

            this.$isSettingValue = true;

            for (i = 0, len = values.length; i < len; i++) {
                val = values[i];

                if (val.match(':') || val.match('%')) {
                    this.addSiteType(val);
                    continue;
                }

                if (/^p[0-9]+$/.test(val)) {
                    this.addParentSiteId(val);
                    continue;
                }

                val = parseInt(val);

                if (val) {
                    this.addSiteId(val);
                }
            }

            this.$isSettingValue = false;
            this.$loadEntryLabels();
        },

        /**
         * Opens the sitemap window, to add some side ids
         */
        openSitemap: function () {
            if (!this.$Project) {
                return;
            }

            const self = this;

            new ProjectWindow({
                project: this.$Project.getName(),
                lang   : this.$Project.getLang(),
                events : {
                    onSubmit: function (Win, params) {
                        var ids = params.ids;

                        for (var i = 0, len = ids.length; i < len; i++) {
                            self.addSiteId(ids[i]);
                        }
                    }
                }
            }).open();
        },

        /**
         * Opens the sitemap window, to add some parent ids
         */
        openParentSitemap: function () {
            if (!this.$Project) {
                return;
            }

            const self = this;

            new ProjectWindow({
                project: this.$Project.getName(),
                lang   : this.$Project.getLang(),
                events : {
                    onSubmit: function (Win, params) {
                        var ids = params.ids;

                        for (var i = 0, len = ids.length; i < len; i++) {
                            self.addParentSiteId(ids[i]);
                        }
                    }
                }
            }).open();
        },

        /**
         * Opens a site type window, to add some side types
         */
        openSiteTypes: function () {
            if (!this.$Project) {
                console.error('No Project was given.');
                return;
            }

            const self = this;

            new TypeWindow({
                multiple         : true,
                project          : this.$Project.getName(),
                pluginsSelectable: true,
                events           : {
                    onSubmit: function (Win, values) {
                        for (var i = 0, len = values.length; i < len; i++) {
                            self.addSiteType(values[i]);
                        }
                    }
                }
            }).open();
        },

        /**
         * Add a site ID to the select
         *
         * @param {number} siteId
         */
        addSiteId: function (siteId) {
            if (typeof siteId === 'undefined') {
                return;
            }

            siteId = parseInt(siteId);

            if (!siteId) {
                return;
            }


            var Elm = this.createEntry(siteId).inject(this.$Container);

            this.refreshValues();

            if (!this.$isSettingValue) {
                this.$loadEntryLabels([Elm]);
            }
        },

        /**
         * Add a parent site ID to the select
         *
         * @param {number} siteId
         */
        addParentSiteId: function (siteId) {
            if (typeof siteId === 'undefined') {
                return;
            }

            siteId = parseInt(siteId.toString().replace('p', ''));

            if (!siteId) {
                return;
            }

            var value = 'p' + siteId.toString(),
                Elm   = this.createEntry(value).inject(this.$Container);

            this.refreshValues();

            if (!this.$isSettingValue) {
                this.$loadEntryLabels([Elm]);
            }
        },

        /**
         * Add a site type to the select or a site type selection
         *
         * @param {String} type - eq: "quiqqer/%" "quiqqer/blog:blog/entry" "quiqqer/blog:%"
         */
        addSiteType: function (type) {
            if (typeof type === 'undefined') {
                return;
            }

            if (type === '') {
                return;
            }


            if (!type.match(':') && !type.match('%')) {
                type = type + ':%';
            }

            var Elm = this.createEntry(type).inject(this.$Container);

            this.refreshValues();

            if (!this.$isSettingValue) {
                this.$loadEntryLabels([Elm]);
            }
        },

        /**
         * Return display information which is available without a request
         *
         * @param {String|Number} value
         * @returns {Object}
         */
        $getEntryDisplay: function (value) {
            value = value.toString();

            var data = {
                badge   : '',
                icon     : 'fa fa-puzzle-piece',
                kind     : 'type',
                title    : value,
                metaLabel: QUILocale.get(lg, 'projects.project.site.select.entry.type'),
                technical: value
            };

            if (/^[0-9]+$/.test(value)) {
                data.icon = 'fa fa-file-o';
                data.kind = 'site';
                data.title = '#' + value;
                data.metaLabel = QUILocale.get(lg, 'projects.project.site.select.entry.site');
                data.technical = '#' + value;

                return data;
            }

            if (/^p[0-9]+$/.test(value)) {
                data.icon = 'fa fa-sitemap';
                data.kind = 'children';
                data.technical = '#' + value.substring(1);
                data.metaLabel = QUILocale.get(lg, 'projects.project.site.select.entry.children');
                data.title = data.technical;
                data.badge = QUILocale.get(lg, 'projects.project.site.select.entry.children.badge');

                return data;
            }

            if (value.indexOf('%') !== -1) {
                data.icon = 'fa fa-layer-group';
                data.kind = 'typeWildcard';
                data.metaLabel = QUILocale.get(lg, 'projects.project.site.select.entry.typeWildcard');
                data.title = value;
                data.badge = QUILocale.get(lg, 'projects.project.site.select.entry.typeWildcard.badge');
            }

            return data;
        },

        /**
         * Update the visible information of an entry
         *
         * @param {HTMLElement} Entry
         * @param {Object} result
         */
        $applyEntryLabel: function (Entry, result) {
            if (!Entry) {
                return;
            }

            var value = Entry.get('data-value'),
                data  = this.$getEntryDisplay(value);

            if (result && result.value === value) {
                if (result.kind) {
                    data.kind = result.kind;
                }

                if (result.icon) {
                    data.icon = result.icon;
                }

                if (result.title) {
                    data.title = result.title;
                }
            }

            Entry.set('data-kind', data.kind);
            Entry.removeClass('control-site-select-entry--loading');

            Entry.getElement('.control-site-select-entry-icon span').set('class', data.icon);
            Entry.getElement('.control-site-select-entry-title').set({
                text : data.title,
                title: data.title
            });

            var Badge = Entry.getElement('.control-site-select-entry-badge');

            Badge.set('text', data.badge);
            Badge.setStyle('display', data.badge ? null : 'none');

            Entry.getElement('.control-site-select-entry-meta-label').set('text', data.metaLabel + ' ·');
            Entry.getElement('.control-site-select-entry-technical').set({
                text : data.technical,
                title: data.technical
            });
            Entry.set('title', data.title + ' — ' + data.metaLabel + ' · ' + data.technical);
        },

        /**
         * Load the visible labels for entries
         *
         * @param {Array<HTMLElement>} [entries]
         */
        $loadEntryLabels: function (entries) {
            if (!this.$Project || !this.$Container) {
                return;
            }

            entries = entries || this.$Container.getElements('.control-site-select-entry');

            var self = this,
                values = [],
                requestEntries = [];

            Array.from(entries).each(function (Entry) {
                var value = Entry.get('data-value');

                if (self.$labelCache[value]) {
                    self.$applyEntryLabel(Entry, self.$labelCache[value]);
                    return;
                }

                if (!values.contains(value)) {
                    values.push(value);
                }

                requestEntries.push(Entry);
                Entry.addClass('control-site-select-entry--loading');
            });

            if (!values.length) {
                return;
            }

            var projectKey = this.$labelProject;

            Ajax.get('ajax_site_getSelectLabels', function (result) {
                if (projectKey !== self.$labelProject) {
                    return;
                }

                Object.each(result || {}, function (entryData, value) {
                    self.$labelCache[value] = entryData;
                });

                requestEntries.each(function (Entry) {
                    if (!Entry.parentNode) {
                        return;
                    }

                    var value = Entry.get('data-value');

                    self.$applyEntryLabel(Entry, self.$labelCache[value] || null);
                });
            }, {
                project  : this.$Project.encode(),
                selectors: JSON.encode(values),
                onError  : function () {
                    requestEntries.each(function (Entry) {
                        if (!Entry.parentNode) {
                            return;
                        }

                        self.$applyEntryLabel(Entry, null);
                    });
                }
            });
        },

        /**
         * Create an entry element
         *
         * @param {String|Number} value
         * @returns {HTMLElement}
         */
        createEntry: function (value) {
            const self = this;

            var Item = new Element('div', {
                'class'     : 'control-site-select-entry',
                "data-value": value
            });

            new Element('div', {
                'class': 'control-site-select-entry-icon',
                html   : '<span></span>'
            }).inject(Item);

            var Content = new Element('div', {
                'class': 'control-site-select-entry-content'
            }).inject(Item);

            new Element('div', {
                'class': 'control-site-select-entry-text'
            }).adopt(
                new Element('span', {
                    'class': 'control-site-select-entry-title'
                }),
                new Element('span', {
                    'class': 'control-site-select-entry-badge'
                })
            ).inject(Content);

            var Meta = new Element('div', {
                'class': 'control-site-select-entry-meta'
            }).inject(Content);

            new Element('span', {
                'class': 'control-site-select-entry-meta-label'
            }).inject(Meta);

            new Element('code', {
                'class': 'control-site-select-entry-technical'
            }).inject(Meta);

            var Delete = new Element('button', {
                'class': 'control-site-select-entry-delete',
                type   : 'button',
                title  : QUILocale.get(lg, 'delete'),
                html   : '<span class="fa fa-remove"></span>'
            }).inject(Item);

            this.$applyEntryLabel(Item, null);

            Delete.addEvent('click', function () {
                this.getParent('.control-site-select-entry').destroy();

                self.refreshValues();
            });

            return Item;
        },

        /**
         * Refresh the value, read the elements and set the value to the input field
         */
        refreshValues: function () {
            if (!this.$Elm) {
                return;
            }

            var i, len;

            var list   = this.$Elm.getElements('.control-site-select-entry'),
                values = [];

            for (i = 0, len = list.length; i < len; i++) {
                values.push(
                    list[i].get('data-value')
                );
            }

            this.$Input.value = values.join(';');
            this.setAttribute('value', this.$Input.value);

            this.$Elm.getElements('.control-site-select-container-placeholder').destroy();

            if (!values.length) {
                this.$Container.set(
                    'html',

                    '<p class="control-site-select-container-placeholder">' +
                    this.getAttribute('placeholder') +
                    '</p>'
                );
            }
        }
    });
});
