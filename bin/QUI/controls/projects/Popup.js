/**
 * Projects Sitemap Popup
 *
 * In this Popup you can select a site from a project and submit it
 * eq for insert a link into a input element or editor
 *
 *
 * @event onSubmit [ {this}, {Object} result ];
 */
define('controls/projects/Popup', [

    'qui/controls/windows/Confirm',
    'qui/controls/buttons/Select',
    'Projects',
    'Locale',
    'controls/projects/project/Sitemap',
    'Ajax',
    'Mustache',
    'text!controls/projects/Popup.html',

    'css!controls/projects/Popup.css',
    'css!qui/controls/messages/Message.css'

], function (QUIPopup, QUISelect, Projects, Locale, ProjectMap, Ajax, Mustache, template) {
    "use strict";

    return new Class({

        Extends: QUIPopup,
        Type   : 'controls/projects/Popup',

        Binds: [
            '$onCreate',
            '$onOpen',
            '$onOpenBegin'
        ],

        options: {
            project             : false,
            lang                : false,
            langs               : false,
            icon                : 'fa fa-home',
            title               : Locale.get('quiqqer/core', 'projects'),
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
            this.$selectedSites = new Map();
            this.$treeSelection = new Set();
            this.$searchRevision = 0;
            this.$searchTimer = null;
            this.$searchClosed = false;

            this.addEvents({
                onOpen     : this.$onOpen,
                onOpenBegin: this.$onOpenBegin,
                onCloseBegin: () => this.$cancelSearch(),
                onDestroy: () => this.$cancelSearch()
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

            this.$searchClosed = false;
            Content.classList.add('qui-project-popup-content');
            Content.innerHTML = Mustache.render(template, {
                searchLabel: Locale.get('quiqqer/core', 'projects.popup.search'),
                searchButtonLabel: Locale.get('quiqqer/core', 'projects.project.site.btn.start')
            });

            Content.setStyles({
                padding: 0
            });

            this.$Header = Content.querySelector('[data-name="header"]');
            this.$Body = Content.querySelector('[data-name="tree"]');
            this.$Search = Content.querySelector('[data-name="search"]');
            this.$Results = Content.querySelector('[data-name="results"]');
            this.$ResultList = Content.querySelector('[data-name="resultList"]');
            this.$Status = Content.querySelector('[data-name="status"]');
            this.$Selection = Content.querySelector('[data-name="selection"]');
            this.$SearchButton = Content.querySelector('[data-name="searchButton"]');
            this.$Search.addEventListener('input', () => this.$scheduleSearch());
            this.$SearchButton.addEventListener('click', () => this.$scheduleSearch(true));
            this.$Search.addEventListener('keydown', event => {
                if (event.key === 'Enter' || (event.key === 'Escape' && this.$Search.value)) {
                    event.preventDefault();
                    event.stopPropagation();
                    if (event.key === 'Escape') {
                        this.$Search.value = '';
                    }
                    this.$scheduleSearch(true);
                }
            });

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
                if (this.$searchClosed) {
                    return;
                }
                var i, len, langs, project;

                var selfLangs      = self.getAttribute('langs'),
                    allowedProject = self.getAttribute('project'),
                    allowedLangs   = !selfLangs ? false : {};

                if (selfLangs && selfLangs.length && allowedLangs) {
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
                        if (selfLangs && allowedProject && allowedProject != project) {
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
         * event: on open begin
         */
        $onOpenBegin: function () {
            var ckDialogs = document.getElements('.cke_dialog');

            if (!ckDialogs.length) {
                return;
            }

            // ckeditor stuff has extrem high zindex
            var currentIndex = this.getElm().getStyle('z-index');

            for (var i = 0, len = ckDialogs.length; i < len; i++) {
                if (currentIndex < parseInt(ckDialogs[i].getStyle('z-index'))) {
                    currentIndex = parseInt(ckDialogs[i].getStyle('z-index'));
                }
            }

            this.Background.getElm().setStyle('z-index', currentIndex + 9);
            this.getElm().setStyle('z-index', currentIndex + 10);
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
            clearTimeout(this.$searchTimer);
            this.$searchRevision++;
            this.$selectedSites.clear();
            this.$treeSelection.clear();
            this.$renderSelection();

            if (this.$Map) {
                this.$Map.destroy();
            }

            var value = this.$Select.getValue().split(',');

            this.$Map = new ProjectMap({
                project : value[0],
                lang    : value[1],
                multiple: this.getAttribute('multiple')
            });

            this.$Map.inject(this.$Body);
            this.$Map.getMap().addEvent('childClick', () => {
                queueMicrotask(() => {
                    if (!this.$searchClosed && !this.$Body.hidden) {
                        this.$captureTreeSelection();
                    }
                });
            });
            this.$Map.open();
            this.$scheduleSearch();

            this.Loader.hide();
        },

        $cancelSearch: function () {
            this.$searchClosed = true;
            this.$searchRevision++;
            clearTimeout(this.$searchTimer);
        },

        $captureTreeSelection: function () {
            const children = this.$Map.getSelectedChildren();
            const current = new Set(children.map(Item => Number(Item.getAttribute('value'))));

            for (const id of this.$treeSelection) {
                if (!current.has(id)) {
                    this.$selectedSites.delete(id);
                }
            }

            if (!this.getAttribute('multiple') && current.size) {
                this.$selectedSites.clear();
            }

            for (const Item of children) {
                const id = Number(Item.getAttribute('value'));
                this.$selectedSites.set(id, {id, title: Item.getAttribute('text')});
            }

            this.$treeSelection = current;
            this.$renderSelection();
        },

        $syncTreeSelection: function () {
            const Sitemap = this.$Map.getMap();
            Sitemap.deselectAllChildren();
            this.$treeSelection.clear();

            for (const id of this.$selectedSites.keys()) {
                const Item = Sitemap.getChildrenByValue(id)[0];
                if (Item) {
                    Item.select({control: true});
                    this.$treeSelection.add(id);
                }
            }
        },

        $scheduleSearch: function (immediate = false) {
            clearTimeout(this.$searchTimer);
            const revision = ++this.$searchRevision;
            const search = this.$Search.value.trim();

            if (!this.$Map || this.$searchClosed) {
                return;
            }

            this.$SearchButton.disabled = false;

            if (!this.$Body.hidden) {
                this.$captureTreeSelection();
            }

            this.$Body.hidden = search !== '';
            this.$Results.hidden = search === '';
            this.$ResultList.replaceChildren();

            if (!search) {
                this.$Results.removeAttribute('aria-busy');
                this.$syncTreeSelection();
                return;
            }

            this.$Results.setAttribute('aria-busy', 'true');
            this.$Status.textContent = Locale.get('quiqqer/core', 'projects.popup.search.loading');
            const [project, lang] = this.$Select.getValue().split(',');
            this.$searchTimer = setTimeout(() => {
                Ajax.get('ajax_project_sites_searchForSelection', result => {
                    if (revision !== this.$searchRevision || this.$searchClosed) {
                        return;
                    }
                    this.$Results.removeAttribute('aria-busy');
                    this.$renderResults(result);
                }, {
                    project: JSON.stringify({name: project, lang}),
                    search,
                    showError: false,
                    onError: () => {
                        if (revision === this.$searchRevision && !this.$searchClosed) {
                            this.$Results.removeAttribute('aria-busy');
                            this.$Status.textContent = Locale.get('quiqqer/core', 'projects.popup.search.error');
                        }
                    }
                });
            }, immediate ? 0 : 250);
        },

        $renderResults: function (result) {
            this.$ResultList.replaceChildren();
            this.$Status.textContent = Locale.get('quiqqer/core',
                result.limited ? 'projects.popup.search.limited' : 'projects.popup.search.count',
                {count: result.items.length});

            for (const site of result.items) {
                const id = Number(site.id);
                const Item = document.createElement('li');
                const Label = document.createElement('label');
                const Input = document.createElement('input');
                Input.type = this.getAttribute('multiple') ? 'checkbox' : 'radio';
                Input.name = 'site-search-' + this.getId();
                Input.dataset.name = 'resultSelection';
                Input.value = id;
                Input.checked = this.$selectedSites.has(id);
                const Icon = document.createElement('span');
                Icon.className = 'fa fa-file-o';
                Icon.setAttribute('aria-hidden', 'true');
                const Text = document.createElement('span');
                Text.className = 'qui-project-popup-result-text';
                const Title = document.createElement('span');
                Title.className = 'qui-project-popup-result-title';
                Title.textContent = site.title || site.name || '';
                Text.append(Title);
                if (site.name && site.name !== site.title) {
                    const Name = document.createElement('small');
                    Name.textContent = site.name;
                    Text.append(Name);
                }
                const Id = document.createElement('span');
                Id.className = 'qui-project-popup-result-id';
                Id.textContent = '#' + id;
                Label.append(Input, Icon, Text, Id);
                Item.append(Label);
                this.$ResultList.append(Item);
                Input.addEventListener('change', () => {
                    if (!this.getAttribute('multiple')) {
                        this.$selectedSites.clear();
                    }
                    if (Input.checked) {
                        this.$selectedSites.set(id, site);
                    } else {
                        this.$selectedSites.delete(id);
                    }
                    this.$syncTreeSelection();
                    this.$renderSelection();
                });
            }
        },

        $renderSelection: function () {
            this.$Selection.replaceChildren();
            this.$Selection.hidden = !this.getAttribute('multiple') || this.$selectedSites.size === 0;

            if (this.$Selection.hidden) {
                return;
            }

            for (const [id, site] of this.$selectedSites) {
                const Button = document.createElement('button');
                const title = (site.title || site.name || '') + ' (#' + id + ')';
                Button.type = 'button';
                Button.className = 'qui-button';
                Button.textContent = title + ' ×';
                Button.setAttribute('aria-label', Locale.get('quiqqer/core', 'projects.popup.selection.remove', {title}));
                Button.addEventListener('click', () => {
                    this.$selectedSites.delete(id);
                    this.$syncTreeSelection();
                    for (const Input of this.$ResultList.querySelectorAll('[data-name="resultSelection"]')) {
                        Input.checked = this.$selectedSites.has(Number(Input.value));
                    }
                    this.$renderSelection();
                    this.$Search.focus();
                });
                this.$Selection.append(Button);
            }
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

            if (!this.$Body.hidden) {
                this.$captureTreeSelection();
            }
            var projectString = 'project=' + project + '&' + 'lang=' + lang;

            ids = Array.from(this.$selectedSites.keys());

            urls = ids.map(function (id) {
                return 'index.php?id=' + id + '&' + projectString;
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
