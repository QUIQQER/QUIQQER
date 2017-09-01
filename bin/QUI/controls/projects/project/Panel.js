/**
 * Displays a Project in a Panel
 *
 * @module controls/projects/project/Panel
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/desktop/Panel
 * @require Projects
 * @require controls/projects/project/Sitemap
 * @require utils/Panels
 * @require qui/controls/buttons/Button
 * @require qui/controls/buttons/Separator
 * @require qui/controls/sitemap/Map
 * @require qui/controls/sitemap/Item
 * @require qui/controls/sitemap/Filter
 * @require Locale
 * @require controls/projects/Manager
 * @require css!controls/projects/project/Panel.css'
 */
define('controls/projects/project/Panel', [

    'qui/controls/desktop/Panel',
    'Projects',
    'controls/projects/project/Sitemap',
    'utils/Panels',

    'qui/controls/buttons/Button',
    'qui/controls/buttons/Select',
    'qui/controls/buttons/Separator',
    'qui/controls/sitemap/Map',
    'qui/controls/sitemap/Item',
    'qui/controls/sitemap/Filter',

    'Locale',
    'controls/projects/Manager',

    'css!controls/projects/project/Panel.css'

], function () {
    "use strict";

    // classes
    var QUIPanel           = arguments[0],
        Projects           = arguments[1],
        ProjectSitemap     = arguments[2],
        PanelUtils         = arguments[3],

        QUIButton          = arguments[4],
        QUISelect          = arguments[5],
        QUIButtonSeparator = arguments[6],
        QUISitemap         = arguments[7],
        QUISitemapItem     = arguments[8],
        QUISitemapFilter   = arguments[9],

        Locale             = arguments[10],
        ProjectManager     = arguments[11];

    /**
     * @class controls/projects/project/Panel
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIPanel,
        Type   : 'controls/projects/project/Panel',

        Binds: [
            'refresh',
            '$onCreate',
            '$onInject',
            '$onResize',
            '$onDestroy',
            '$openSitePanel'
        ],

        initialize: function (options) {
            // defaults
            this.setAttributes({
                name   : 'projects-panel',
                project: false,
                lang   : false,
                icon   : 'fa fa-home'
            });

            this.parent(options);

            // must be after this.parent(), because locale must be set
            // and maybe the title comes from the serialize cache
            this.setAttributes({
                title: Locale.get('quiqqer/system', 'projects.project.panel.title')
            });

            this.$Map         = null;
            this.$projectmaps = {};
            this.$Filter      = null;
            this.$Button      = null;

            this.$ProjectList      = null;
            this.$ProjectContainer = null;
            this.$ProjectSearch    = null;
            this.$ProjectContent   = null;

            this.$LanguageSelect = null;
            this.$MediaButton    = null;

            this.$__fx_run = false;

            Projects.addEvents({
                onCreate     : this.refresh, // on project create
                onDelete     : this.refresh, // on project delete
                onProjectSave: this.refresh // on project saved
            });

            this.addEvents({
                onCreate : this.$onCreate,
                onInject : this.$onInject,
                onResize : this.$onResize,
                onDestroy: this.$onDestroy
            });
        },

        /**
         * import the saved attributes and the data
         *
         * @method controls/projects/project/Panel#unserialize
         * @param {Object} data
         */
        unserialize: function (data) {
            this.parent(data);

            // must be after this.parent(), because locale must be set
            // and maybe the title comes from the serialize cache
            this.setAttributes({
                title: Locale.get('quiqqer/system', 'projects.project.panel.title')
            });
        },

        /**
         * refresh the project list
         */
        refresh: function () {
            this.parent();

            if (!this.$ProjectList) {
                return;
            }

            if (this.$ProjectList.getStyle('display') === 'none') {
                return;
            }

            this.createList();
        },

        /**
         * Create the project panel body
         *
         * @method controls/projects/project/Panel#$onCreate
         */
        $onCreate: function () {
            var self    = this,
                Content = this.getContent();

            Content.set(
                'html',

                '<div class="project-container">' +
                '<div class="project-content"></div>' +
                '<div class="project-list"></div>' +
                '</div>' +
                '<div class="project-search"></div>'
            );

            Content.setStyle('opacity', 0);

            this.$ProjectContainer = Content.getElement('.project-container');
            this.$ProjectList      = Content.getElement('.project-list');
            this.$ProjectSearch    = Content.getElement('.project-search');
            this.$ProjectContent   = Content.getElement('.project-content');

            this.$ProjectContainer.setStyles({
                height: 'calc(100% - 40px)'
            });

            this.$ProjectList.setStyles({
                left: -300
            });

            // language select
            this.$LanguageSelect = new QUISelect({
                title : Locale.get('quiqqer/system', 'projects.project.panel.languageSwitch'),
                styles: {
                    width: 100
                },
                events: {
                    onChange: function (value) {
                        if (value === self.getAttribute('lang')) {
                            return;
                        }

                        self.setAttribute('lang', value);
                        self.openProject();
                    }
                }
            });

            this.addButton(this.$LanguageSelect);

            this.$MediaButton = new QUIButton({
                textimage: 'fa fa-picture-o',
                text     : Locale.get('quiqqer/system', 'projects.project.panel.media'),
                styles   : {
                    'float': 'right'
                },
                events   : {
                    onClick: function (Btn) {
                        Btn.setAttribute('textimage', '');

                        require(['controls/projects/project/Panel'], function (Panel) {
                            new Panel().openMediaPanel(
                                self.getAttribute('project')
                            );

                            Btn.setAttribute('textimage', 'fa fa-picture-o');
                        });
                    }
                }
            });

            this.addButton(this.$MediaButton);


            // draw filter
            new QUIButton({
                icon  : 'fa fa-trash',
                styles: {
                    width: 40
                },
                events: {
                    onClick: function (Btn) {
                        Btn.setAttribute('icon', 'fa fa-spinner fa-spin');

                        require(['utils/Panels'], function (Utils) {
                            Utils.openTrashPanel().then(function () {
                                Btn.setAttribute('icon', 'fa fa-trash');
                            });
                        });
                    }
                }
            }).inject(this.$ProjectSearch);

            this.$Filter = new QUISitemapFilter(null, {
                styles: {
                    background: '#F2F2F2',
                    'float'   : 'right',
                    height    : 38,
                    width     : 'calc(100% - 100px)'
                },
                events: {
                    onFilter: function (Filter, result) {
                        if (!result.length) {
                            new Fx.Scroll(this).toTop();
                            return;
                        }

                        new Fx.Scroll(this).toElement(
                            result[0].getElm()
                        );

                    }.bind(this.$ProjectContainer)
                }
            }).inject(this.$ProjectSearch);

            // site search
            new QUIButton({
                icon  : 'fa fa-search',
                title : Locale.get('quiqqer/system', 'projects.project.panel.open.search'),
                alt   : Locale.get('quiqqer/system', 'projects.project.panel.open.search'),
                events: {
                    onClick: function () {
                        require([
                            'qui/QUI',
                            'controls/projects/project/site/Search',
                            'utils/Panels'
                        ], function (QUI, Search, PanelUtils) {
                            var searchPanels = QUI.Controls.getByType(
                                'controls/projects/project/site/Search'
                            );

                            var val = self.$Filter.getInput().value;

                            if (!searchPanels.length) {
                                PanelUtils.openPanelInTasks(
                                    new Search({
                                        value: val
                                    })
                                );

                                return;
                            }

                            var Panel = searchPanels[0];

                            Panel.setAttribute('value', val);
                            Panel.search();

                            PanelUtils.execPanelOpen(Panel);

                        });
                    }
                }
            }).inject(this.$Filter.getElm());

            new QUIButtonSeparator().inject(this.getHeader(), 'top');

            // title button
            this.$Button = new QUIButton({
                name  : 'projects',
                image : 'fa fa-arrow-circle-left',
                title : Locale.get('quiqqer/system', 'projects.project.panel.projectSelect'),
                events: {
                    onClick: function (Btn, event) {
                        if (typeof event !== 'undefined') {
                            event.stop();
                        }

                        if (Btn.isActive()) {
                            // get the first projects map
                            for (var first in self.$projectmaps) {
                                if (self.$projectmaps.hasOwnProperty(first)) {
                                    continue;
                                }

                                break;
                            }

                            if (self.$projectmaps.hasOwnProperty(first)) {
                                // select the first languag of the project
                                self.$projectmaps[first].firstChild().firstChild().click();
                            }

                            return;
                        }

                        self.createList();
                    }
                }
            }).inject(this.getHeader(), 'top');

            this.$Button.getElm().removeClass('qui-button');
            this.$Button.getElm().addClass('button');
            this.$Button.getElm().addClass('btn-blue');
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self = this;

            this.Loader.show();

            // resize after insert
            (function () {
                self.resize();
                self.Loader.show();

                Projects.getList(function (result) {
                    if (Object.getLength(result)) {
                        for (var key in result) {
                            if (!result.hasOwnProperty(key)) {
                                continue;
                            }

                            self.setAttribute('project', key);
                            self.setAttribute('lang', result[key].default_lang);
                            break;
                        }

                        self.openProject();
                        self.Loader.hide();
                        return;
                    }

                    // no projects exists
                    var Body = self.getBody();

                    Body.set('html', Locale.get('quiqqer/system', 'projects.project.panel.message.no.project'));

                    new QUIButton({
                        textimage: 'fa fa-home',
                        text     : Locale.get('quiqqer/system', 'projects.project.panel.create'),
                        events   : {
                            onClick: function () {
                                var PM = new ProjectManager();

                                PanelUtils.openPanelInTasks(PM);
                                PM.openAddProject();
                            }
                        },
                        styles   : {
                            margin: '10px 0 0 0'
                        }
                    }).inject(Body);

                    self.Loader.hide();
                });
            }).delay(250);
        },

        /**
         * event : on panel resize
         */
        $onResize: function () {
            var self    = this,
                Content = this.getContent();

            if (parseInt(Content.getStyle('opacity')) === 0) {
                moofx(Content).animate({
                    opacity: 1
                }, {
                    duration: 250,
                    callback: function () {
                        self.getContent().setStyle('opacity', null);
                    }
                });
            }
        },

        /**
         * event destroy
         */
        $onDestroy: function () {
            Projects.removeEvent('onCreate', this.refresh);
            Projects.removeEvent('onDelete', this.refresh);
            Projects.removeEvent('onProjectSave', this.refresh);
        },

        /**
         * Create the Project list for the Panel
         *
         * @method controls/projects/project/Panel#createList
         */
        createList: function () {
            if (this.$__fx_run) {
                return;
            }

            this.$__fx_run = true;
            this.$ProjectContainer.setStyle('overflow', 'hidden');

            var self = this;

            if (this.$Map) {
                this.$Map.destroy();
            }

            this.$LanguageSelect.disable();
            this.$MediaButton.disable();

            this.setAttributes({
                title: Locale.get('quiqqer/system', 'projects.project.panel.title')
            });

            this.refresh();

            Projects.getList(function (result) {
                if (!Object.getLength(result)) {
                    self.$ProjectContainer.setStyle('overflow', null);
                    self.$__fx_run = false;
                    return;
                }


                var i, l, langs, len, Map, Project,
                    func_project_click, func_media_click, func_trash_click, func_settings_click;

                var List = self.$ProjectList;

                List.set('html', '');

                // click events
                func_project_click = function (Itm) {
                    self.setAttribute('project', Itm.getAttribute('project'));
                    self.setAttribute('lang', Itm.getAttribute('lang'));

                    self.openProject();
                };

                func_media_click = function (Itm) {
                    self.openMediaPanel(
                        Itm.getAttribute('project')
                    );
                };

                func_trash_click = function () {
                    PanelUtils.openTrashPanel();
                };

                func_settings_click = function (Itm) {
                    var project = Itm.getAttribute('project'),
                        lang    = Itm.getAttribute('lang');

                    Itm.setAttribute('icon', 'fa fa-spinner fa-spin');

                    require(['utils/Panels'], function (Utils) {
                        Utils.openProjectSettings(project, lang).then(function () {
                            Itm.setAttribute('icon', 'fa fa-gears');
                        });
                    });
                };

                if (self.$Filter) {
                    self.$Filter.clearBinds();
                }


                // create
                for (i in result) {
                    if (!result.hasOwnProperty(i)) {
                        continue;
                    }

                    if (!result[i].langs) {
                        continue;
                    }

                    langs = result[i].langs.split(',');

                    if (typeof self.$projectmaps[i] === 'undefined' || !self.$projectmaps[i]) {
                        self.$projectmaps[i] = new QUISitemap();
                    }

                    Map = self.$projectmaps[i];
                    Map.clearChildren();

                    if (self.$Filter) {
                        self.$Filter.bindSitemap(Map);
                    }

                    Project = new QUISitemapItem({
                        text   : Projects.get(i).getTitle(),
                        icon   : 'fa fa-home',
                        project: i,
                        lang   : result[i].default_lang,
                        events : {
                            onClick: func_project_click
                        }
                    });

                    Map.appendChild(Project);

                    for (l = 0, len = langs.length; l < len; l++) {
                        // project Lang
                        Project.appendChild(
                            new QUISitemapItem({
                                text   : langs[l],
                                icon   : URL_BIN_DIR + '16x16/flags/' + langs[l] + '.png',
                                name   : 'project.' + i + '.' + langs[l],
                                project: i,
                                lang   : langs[l],
                                events : {
                                    onClick: func_project_click
                                }
                            })
                        );
                    }

                    // Media
                    Project.appendChild(
                        new QUISitemapItem({
                            text   : Locale.get('quiqqer/system', 'projects.project.panel.media'),
                            icon   : 'fa fa-picture-o',
                            project: i,
                            events : {
                                onClick: func_media_click
                            }
                        })
                    );

                    // Trash
                    Project.appendChild(
                        new QUISitemapItem({
                            text   : Locale.get('quiqqer/system', 'projects.project.panel.tash'),
                            icon   : 'fa fa-trash-o',
                            project: i,
                            events : {
                                onClick: func_trash_click
                            }
                        })
                    );

                    // Settings
                    Project.appendChild(
                        new QUISitemapItem({
                            text   : Locale.get('quiqqer/system', 'projects.project.panel.settings'),
                            icon   : 'fa fa-gears',
                            project: i,
                            events : {
                                onClick: func_settings_click
                            }
                        })
                    );

                    List.appendChild(Map.create());

                    Map.openAll();
                }

                List.setStyles({
                    boxShadow: '0 6px 20px 0 rgba(0, 0, 0, 0.19)',
                    display  : null,
                    opacity  : 0
                });


                moofx(List).animate({
                    left   : 0,
                    opacity: 1
                }, {
                    equation: 'ease-in',
                    duration: 300,
                    callback: function () {
                        self.$__fx_run = false;

                        self.$ProjectContainer.setStyle('overflow', null);
                        self.$Button.setActive();
                    }
                });
            });
        },

        /**
         * Opens the selected Project and create a Project Sitemap in the Panel
         *
         * @method controls/projects/project/Panel#openProject
         */
        openProject: function () {
            if (this.$__fx_run) {
                return;
            }

            this.$__fx_run = true;

            var self      = this,
                List      = this.$ProjectList,
                Container = this.$ProjectContent,
                project   = this.getAttribute('project'),
                lang      = this.getAttribute('lang');

            var Project = Projects.get(project, lang);

            Container.setStyle('overflow', 'hidden');

            this.setAttribute('title', Project.getTitle());
            this.refresh();

            // create the project sitemap in the panel
            if (this.$Map) {
                this.$Map.destroy();
            }

            this.$Map = new ProjectSitemap({
                project: Project.getAttribute('name'),
                lang   : Project.getAttribute('lang'),
                media  : false
            });

            this.$Sitemap = this.$Map.getMap();

            this.$Sitemap.addEvents({
                onChildClick      : this.$openSitePanel,
                onChildContextMenu: function (Item, MapItem, event) {
                    var title = MapItem.getAttribute('text') + ' - ' +
                        MapItem.getAttribute('value');

                    MapItem.getContextMenu().setTitle(title).setPosition(
                        event.page.x,
                        event.page.y
                    ).show();

                    event.stop();
                }
            });

            this.$Filter.clearBinds();
            this.$Filter.bindSitemap(this.$Sitemap);

            this.$Map.inject(Container);

            this.$Map.getElm().setStyles({
                margin: '10px 20px'
            });

            this.$Button.setNormal();

            // project select
            this.$LanguageSelect.clear();
            this.$LanguageSelect.disable();
            this.$MediaButton.enable();

            Project.getConfig(false, 'langs').then(function (langs) {
                langs = langs.split(',');

                if (!langs.length) {
                    self.$LanguageSelect.hide();
                    return;
                }

                langs.each(function (lng) {
                    self.$LanguageSelect.appendChild(
                        Locale.get('quiqqer/system', 'language.' + lng),
                        lng,
                        URL_BIN_DIR + '16x16/flags/' + lng + '.png'
                    );

                    self.$LanguageSelect.enable();
                    self.$LanguageSelect.setValue(Project.getLang());
                });
            });

            List.setStyle('boxShadow', '0 6px 20px 0 rgba(0, 0, 0, 0.19)');

            moofx(List).animate({
                left   : List.getSize().x * -1,
                opacity: 0
            }, {
                equation: 'ease-out',
                duration: 300,
                callback: function () {
                    Container.setStyle('overflow', null);
                    List.setStyle('display', 'none');

                    self.$Map.open();
                    self.$__fx_run = false;
                }
            });
        },

        /**
         * Select an sitemap item by ID
         *
         * @method controls/projects/project/Panel#selectSitemapItemById
         *
         * @param {Number} id - the site id
         * @return {Object} (this) controls/projects/project/Panel
         */
        selectSitemapItemById: function (id) {
            if (typeof this.$Sitemap === 'undefined') {
                return this;
            }

            var children = this.getSitemapItemsById(id);

            for (var i = 0, len = children.length; i < len; i++) {
                children[i].select();
            }

            return this;
        },

        /**
         * Get all sitemap items by the id
         *
         * @method controls/projects/project/Panel#getSitemapItemsById
         * @return {Array}
         */
        getSitemapItemsById: function (id) {
            if (typeof this.$Sitemap === 'undefined') {
                return [];
            }

            return this.$Sitemap.getChildrenByValue(id);
        },

        /**
         * Opens a site in the panel<br />
         * Opens the sitemap and open the site panel
         *
         * @method controls/projects/project/Panel#openSite
         * @param {Number} id - ID from the wanted site
         */
        openSite: function (id) {
            if (typeof this.$Map !== 'undefined') {
                this.$Map.openSite(id);
            }
        },

        /**
         * event: click on sitemap item -> opens a site panel
         *
         * @method controls/projects/project/Panel#$openSitePanel
         * @param {Object} Item - qui/controls/sitemap/Item
         */
        $openSitePanel: function (Item) {
            var self    = this,
                id      = (Item.getAttribute('value')).toInt(),
                project = this.getAttribute('project'),
                lang    = this.getAttribute('lang');

            if (!id) {
                return;
            }

            PanelUtils.openSitePanel(project, lang, id, function (Panel) {
                Panel.addEvents({
                    onShow: function (Panel) {
                        if (Panel.getType() !== 'controls/projects/project/site/Panel') {
                            return;
                        }

                        var PanelSite = Panel.getSite(),
                            project   = self.getAttribute('project'),
                            lang      = self.getAttribute('lang');

                        if (project !== PanelSite.getProject().getName()) {
                            return;
                        }

                        if (lang !== PanelSite.getProject().getLang()) {
                            return;
                        }

                        // if it is a sitepanel
                        // set the item in the map active
                        self.openSite(Panel.getSite().getId());
                    }
                });
            });
        },

        /**
         * opens a media panel from a project
         *
         * @method controls/projects/project/Panel#$openSitePanel
         * @param {String} project - Name of the project
         */
        openMediaPanel: function (project) {
            PanelUtils.openMediaPanel(project);
        }
    });
});
