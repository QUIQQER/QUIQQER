/**
 * Displays a Site in a Panel
 *
 * @module controls/projects/project/site/Panel
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/desktop/Panel
 * @require Projects
 * @require Ajax
 * @require classes/projects/project/Site
 * @require qui/controls/buttons/Button
 * @require qui/utils/Form
 * @require utils/Controls
 * @require utils/Panels
 * @require utils/Site
 * @require Locale
 * @require css!controls/projects/project/site/Panel.css
 */
define('controls/projects/project/site/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'Projects',
    'Ajax',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'qui/utils/Form',
    'qui/utils/Elements',
    'utils/Controls',
    'utils/Panels',
    'utils/Site',
    'Locale',

    'css!controls/projects/project/site/Panel.css'

], function () {
    "use strict";

    var QUI          = arguments[0],
        QUIPanel     = arguments[1],
        Projects     = arguments[2],
        Ajax         = arguments[3],
        QUIButton    = arguments[4],
        QUIConfirm   = arguments[5],
        QUIFormUtils = arguments[6],
        QUIElmUtils  = arguments[7],
        ControlUtils = arguments[8],
        PanelUtils   = arguments[9],
        SiteUtils    = arguments[10],
        Locale       = arguments[11];

    var lg = 'quiqqer/system';

    /**
     * An SitePanel, opens the Site in an Apppanel
     *
     * @class controls/projects/project/site/Panel
     *
     * @param {Object} Site - classes/projects/Site
     * @param {Object} options
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIPanel,
        Type   : 'controls/projects/project/site/Panel',

        Binds: [
            'load',
            'createNewChild',
            'openPermissions',
            'openMedia',
            'openSort',
            'deleteLinked',

            '$onCreate',
            '$onDestroy',
            '$onResize',
            '$onInject',
            '$onCategoryEnter',
            '$onCategoryLeave',
            '$onEditorLoad',
            '$onEditorDestroy',
            '$onPanelButtonClick',
            '$onLogin',

            '$onSiteActivate',
            '$onSiteDeactivate',
            '$onSiteSave',
            '$onSiteDelete'
        ],

        options: {
            id           : 'projects-site-panel',
            container    : false,
            editorPeriode: 2000
        },

        initialize: function (Site, options) {
            this.$Site            = null;
            this.$CategoryControl = null;

            this.$editorPeriodicalSave = false; // delay for the wysiwyg editor, to save to the locale storage

            if (typeOf(Site) === 'classes/projects/project/Site') {
                var Project = Site.getProject(),
                    id      = 'panel-' +
                              Project.getName() + '-' +
                              Project.getLang() + '-' +
                              Site.getId();

                // default id
                this.setAttribute('id', id);
                this.setAttribute('name', id);

                this.$Site = Site;
            } else {
                // serialize data
                if (typeof Site.attributes !== 'undefined' &&
                    typeof Site.project !== 'undefined' &&
                    typeof Site.lang !== 'undefined' &&
                    typeof Site.id !== 'undefined') {
                    this.unserialize(Site);
                }
            }

            this.parent(options);

            this.addEvents({
                onCreate : this.$onCreate,
                onResize : this.$onResize,
                onDestroy: this.$onDestroy,
                onInject : this.$onInject
            });
        },

        /**
         * Save the site panel to the workspace
         *
         * @method controls/projects/project/site/Panel#serialize
         * @return {Object} data
         */
        serialize: function () {
            var Site    = this.getSite(),
                Project = Site.getProject();

            return {
                attributes: this.getAttributes(),
                id        : this.getSite().getId(),
                lang      : Project.getLang(),
                project   : Project.getName(),
                type      : this.getType()
            };
        },

        /**
         * import the saved data form the workspace
         *
         * @method controls/projects/project/site/Panel#unserialize
         * @param {Object} data
         * @return {Object} this (controls/projects/project/site/Panel)
         */
        unserialize: function (data) {
            this.setAttributes(data.attributes);

            var Project = Projects.get(
                data.project,
                data.lang
            );

            this.$Site      = Project.get(data.id);
            this.$delayTest = 0;

            return this;
        },

        /**
         * Return the Site object from the panel
         *
         * @method controls/projects/project/site/Panel#getSite
         * @return {Object} classes/projects/Site
         */
        getSite: function () {
            return this.$Site;
        },

        /**
         * Load the site attributes to the panel
         *
         * @method controls/projects/project/site/Panel#load
         */
        load: function () {
            this.refresh();

            if (this.getActiveCategory()) {
                this.$onCategoryEnter(this.getActiveCategory());
                return;
            }

            if (this.getCategoryBar().firstChild()) {
                this.getCategoryBar().firstChild().click();
                return;
            }

            // if dom is not loaded, we wait 200ms
            (function () {
                if (this.$delayTest > 10) {
                    QUI.getMessageHandler(function (MH) {
                        MH.addError(
                            'Seitenpanel mit der Seiten ID #' + this.getSite().getId() +
                            ' konnte nicht geladen werden. Das Panel wurde wieder geschlossen'
                        ); // #locale
                    });

                    this.destroy();
                    return;
                }

                this.$delayTest++;
                this.load();

            }).delay(200, this);
        },

        /**
         * Refresh the site panel
         */
        refresh: function () {
            var title, description;

            var Site    = this.getSite(),
                Project = Site.getProject();

            title = Site.getAttribute('title') + ' (' + Site.getId() + ')';

            description = Site.getAttribute('name') + ' - ' +
                          Site.getId() + ' - ' +
                          Project.getName();

            if (Site.getId() != 1) {
                description = description + ' - ' + Site.getUrl();
            }

            this.setAttributes({
                title      : title,
                description: description,
                icon       : URL_BIN_DIR + '16x16/flags/' + Project.getLang() + '.png'
            });

            this.parent();
        },

        /**
         * Create the panel design
         *
         * @method controls/projects/project/site/Panel#$onCreate
         */
        $onCreate: function () {
            this.Loader.show();

            window.addEvent('login', this.$onLogin);


            // permissions
            new QUIButton({
                image : 'fa fa-shield',
                alt   : Locale.get(lg, 'projects.project.site.panel.btn.permissions'),
                title : Locale.get(lg, 'projects.project.site.panel.btn.permissions'),
                styles: {
                    'border-left-width' : 1,
                    'border-right-width': 1,
                    'float'             : 'right',
                    width               : 40
                },
                events: {
                    onClick: this.openPermissions
                }
            }).inject(this.getHeader());

            new QUIButton({
                image : 'fa fa-picture-o',
                alt   : Locale.get(lg, 'projects.project.site.panel.btn.media'),
                title : Locale.get(lg, 'projects.project.site.panel.btn.media'),
                styles: {
                    'border-left-width': 1,
                    'float'            : 'right',
                    width              : 40
                },
                events: {
                    onClick: this.openMedia
                }
            }).inject(this.getHeader());

            new QUIButton({
                image : 'fa fa-sort',
                alt   : Locale.get(lg, 'projects.project.site.panel.btn.sort'),
                title : Locale.get(lg, 'projects.project.site.panel.btn.sort'),
                styles: {
                    'border-left-width': 1,
                    'float'            : 'right',
                    width              : 40
                },
                events: {
                    onClick: this.openSort
                }
            }).inject(this.getHeader());


            var self    = this,
                Site    = this.getSite(),
                Project = Site.getProject();

            Ajax.get([
                'ajax_site_categories_get',
                'ajax_site_buttons_get',
                'ajax_site_isLockedFromOther',
                'ajax_site_lock'
            ], function (categories, buttons, isLocked) {
                var i, ev, fn, len, data, events, category, Category;

                for (i = 0, len = buttons.length; i < len; i++) {
                    data = buttons[i];

                    if (data.onclick) {
                        data._onclick = data.onclick;
                        delete data.onclick;

                        data.events = {
                            onClick: self.$onPanelButtonClick
                        };
                    }

                    if (data.name === '_Del' || data.name === '_New') {
                        data.styles = {
                            'float': 'right',
                            width  : 40
                        };
                    }

                    self.addButton(data);
                }

                var Save = self.getButtonBar().getChildren('_Save');

                if (Save) {
                    Save.getElm().addClass('qui-site-button-save');
                }


                for (i = 0, len = categories.length; i < len; i++) {
                    events   = {};
                    category = categories[i];

                    if (typeOf(category.events) === 'object') {
                        events = category.events;
                        delete category.events;
                    }

                    Category = new QUIButton(category);

                    Category.addEvents({
                        onActive: self.$onCategoryEnter
                    });

                    for (ev in events) {
                        if (!events.hasOwnProperty(ev)) {
                            continue;
                        }

                        try {
                            eval('fn = ' + events[ev]);

                            Category.addEvent(ev, fn);

                        } catch (e) {
                        }
                    }

                    self.addCategory(Category);
                }

                if (isLocked) {
                    self.setLocked();
                }

            }, {
                project: Project.encode(),
                id     : Site.getId()
            });
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var Site = this.getSite();

            Site.addEvents({
                onLoad      : this.load,
                onActivate  : this.$onSiteActivate,
                onDeactivate: this.$onSiteDeactivate,
                onSave      : this.$onSiteSave,
                onDelete    : this.$onSiteDelete
            });

            if (!Site.hasWorkingStorage()) {
                Site.load();
                return;
            }


            var self = this;

            this.Loader.show();

            var Sheet = this.createSheet({
                title: 'Wiederherstellung der Seite #' + Site.getId()
            });

            // #locale
            Sheet.getContent().set(
                'html',

                '<div class="qui-panel-dataRestore">' +
                '<p>Es wurden nicht gespeicherte Daten der Seite #' + Site.getId() + ' gefunden.</p>' +
                '<p>Sollen die Daten wieder hergestellt werden?</p>' +
                '</div>' // #locale
            );

            Sheet.clearButtons();

            Sheet.addButton({
                text  : 'Daten verwerfen',  // #locale
                events: {
                    onClick: function () {
                        Sheet.hide(function () {
                            Sheet.destroy();
                        });

                        Site.clearWorkingStorage();
                        Site.load(function () {
                            self.load();
                        });
                    }
                }
            });

            Sheet.addButton({
                text  : 'Daten übernehmen', // #locale
                events: {
                    onClick: function () {
                        Sheet.hide(function () {
                            Sheet.destroy();
                        });

                        Site.restoreWorkingStorage();
                        Site.clearWorkingStorage();
                        self.load();
                    }
                }
            });

            Sheet.show(function () {
                self.Loader.hide();
            });
        },

        /**
         * event : on destroy
         */
        $onDestroy: function () {
            var Site    = this.getSite(),
                Project = Site.getProject();

            Site.removeEvent('onLoad', this.load);
            Site.removeEvent('onActivate', this.$onSiteActivate);
            Site.removeEvent('onDeactivate', this.$onSiteDeactivate);
            Site.removeEvent('onSave', this.$onSiteSave);
            Site.removeEvent('onDelete', this.$onSiteDelete);

            Site.clearWorkingStorage();

            window.removeEvent('login', this.$onLogin);

            Ajax.get(['ajax_site_unlock'], false, {
                project: Project.encode(),
                id     : Site.getId()
            });
        },

        /**
         * event: panel resize
         *
         * @method controls/projects/project/site/Panel#$onResize
         */
        $onResize: function () {
            if (this.$CategoryControl) {
                if ("resize" in this.$CategoryControl) {
                    this.$CategoryControl.resize();
                }
            }

            if (this.getAttribute('Editor')) {
                this.getAttribute('Editor').resize();
            }
        },

        /**
         * Opens the site permissions
         *
         * @method controls/projects/project/site/Panel#openPermissions
         */
        openPermissions: function () {
            var Parent = this.getParent(),
                Site   = this.getSite();

            require(['controls/permissions/Panel'], function (PermPanel) {
                Parent.appendChild(
                    new PermPanel({
                        Object: Site
                    })
                );
            });
        },

        /**
         * Opens the site media
         *
         * @method controls/projects/project/site/Panel#openMedia
         */
        openMedia: function () {
            var Parent  = this.getParent(),
                Site    = this.getSite(),
                Project = Site.getProject(),
                Media   = Project.getMedia();

            require(['controls/projects/project/media/Panel'], function (Panel) {
                Parent.appendChild(new Panel(Media));
            });
        },

        /**
         * Opens the sort sheet
         *
         * @method controls/projects/project/site/Panel#openSort
         */
        openSort: function () {
            var Site = this.getSite(),
                Sort = false;

            var Sheets = this.createSheet({
                title : Locale.get(lg, 'projects.project.site.panel.sort.title', {
                    id   : Site.getId(),
                    title: Site.getAttribute('title'),
                    name : Site.getAttribute('name')
                }),
                events: {
                    onOpen: function (Sheet) {
                        require([
                            'controls/projects/project/site/SiteChildrenSort'
                        ], function (SiteSort) {
                            Sort = new SiteSort(Site).inject(Sheet.getContent());
                        });
                    }
                }
            });

            Sheets.clearButtons();

            Sheets.addButton({
                name     : 'sortSave',
                textimage: 'fa fa-save',
                text     : Locale.get(lg, 'projects.project.site.childrensort.save'),
                events   : {
                    onClick: function (Btn) {
                        if (!Sort) {
                            return;
                        }

                        Btn.setAttribute('textimage', 'fa fa-spinner fa-spin');

                        Sort.save(function () {
                            Btn.setAttribute('textimage', 'fa fa-save');

                            Sheets.hide(function () {
                                Sheets.destroy();
                            });
                        });
                    }
                }
            });

            Sheets.show();
        },

        /**
         * saves site attributes
         *
         * @method controls/projects/project/site/Panel#openPermissions
         */
        save: function () {
            var self = this;

            this.$onCategoryLeave(this.getActiveCategory(), function () {
                self.getSite().save(function () {
                    // refresh data
                    var Form = self.getContent().getElement('form');

                    if (Form) {
                        QUIFormUtils.setDataToForm(
                            self.getSite().getAttributes(),
                            Form
                        );
                    }

                    self.load();
                });
            });
        },

        /**
         * opens the delet dialog
         */
        del: function () {
            var Site = this.getSite();

            require(['qui/controls/windows/Confirm'], function (Confirm) {
                new Confirm({
                    title      : Locale.get(lg, 'projects.project.site.panel.window.delete.title', {
                        id: Site.getId()
                    }),
                    icon       : 'fa fa-trash-o',
                    text       : Locale.get(lg, 'projects.project.site.panel.window.delete.text', {
                        id   : Site.getId(),
                        url  : Site.getAttribute('name') + QUIQQER.Rewrite.SUFFIX,
                        name : Site.getAttribute('name'),
                        title: Site.getAttribute('title')
                    }),
                    texticon   : 'fa fa-trash-o',
                    information: Locale.get(lg, 'projects.project.site.panel.window.delete.information', {
                        id   : Site.getId(),
                        url  : Site.getAttribute('name') + QUIQQER.Rewrite.SUFFIX,
                        name : Site.getAttribute('name'),
                        title: Site.getAttribute('title')
                    }),
                    maxHeight  : 400,
                    maxWidth   : 600,

                    cancel_button: {
                        text     : Locale.get(lg, 'cancel'),
                        textimage: 'fa fa-remove'
                    },
                    ok_button    : {
                        text     : Locale.get(lg, 'projects.project.site.panel.window.delete.button'),
                        textimage: 'fa fa-trash-o'
                    },

                    events: {
                        onSubmit: function () {
                            Site.del();
                        }
                    }
                }).open();
            });
        },

        /**
         * Opens the delete Linked dialog
         *
         * @param {Number} parentId - Parent ID
         * @return {Promise}
         */
        deleteLinked: function (parentId) {
            var self = this,
                Site = this.getSite();

            return new Promise(function (resolve, reject) {

                if (typeof parentId === 'undefined') {
                    reject();
                    return;
                }

                Site.getLinkedPath(parentId).then(function (path) {
                    require(['qui/controls/windows/Confirm'], function (Confirm) {
                        new Confirm({
                            title      : Locale.get(lg, 'projects.project.site.panel.window.deleteLinked.title', {
                                id      : Site.getId(),
                                parentId: parentId,
                                path    : path
                            }),
                            icon       : 'fa fa-trash-o',
                            text       : Locale.get(lg, 'projects.project.site.panel.window.deleteLinked.text', {
                                id      : Site.getId(),
                                url     : Site.getAttribute('name') + QUIQQER.Rewrite.SUFFIX,
                                name    : Site.getAttribute('name'),
                                title   : Site.getAttribute('title'),
                                parentId: parentId,
                                path    : path
                            }),
                            texticon   : 'fa fa-trash-o',
                            information: Locale.get(lg, 'projects.project.site.panel.window.deleteLinked.information', {
                                id      : Site.getId(),
                                url     : Site.getAttribute('name') + QUIQQER.Rewrite.SUFFIX,
                                name    : Site.getAttribute('name'),
                                title   : Site.getAttribute('title'),
                                parentId: parentId,
                                path    : path
                            }),
                            maxHeight  : 400,
                            maxWidth   : 600,
                            autoclose  : false,

                            cancel_button: {
                                text     : Locale.get(lg, 'cancel'),
                                textimage: 'fa fa-remove'
                            },
                            ok_button    : {
                                text     : Locale.get(lg, 'projects.project.site.panel.window.deleteLinked.button'),
                                textimage: 'fa fa-trash-o'
                            },

                            events: {
                                onSubmit: function (Win) {
                                    Win.Loader.show();

                                    Site.unlink(parentId, false).then(function () {
                                        self.load();
                                        Win.close();
                                        resolve();
                                    }).catch(function () {
                                        Win.Loader.hide();
                                        reject();
                                    });
                                },
                                onCancel: resolve
                            }
                        }).open();
                    });

                });
            });
        },

        /**
         * Create a child site
         *
         * @method controls/projects/project/site/Panel#createChild
         *
         * @param {String} [value] - [optional, if no newname was passed,
         *         a window would be open]
         */
        createNewChild: function (value) {
            SiteUtils.openCreateChild(this.getSite(), value);
        },

        /**
         * Enter the Tab / Category
         * Load the tab content and set the site attributes
         * or exec the plugin event
         *
         * @method controls/projects/project/site/Panel#$tabEnter
         * @fires onSiteTabLoad
         *
         * @param {Object} Category - qui/controls/toolbar/Button
         */
        $onCategoryEnter: function (Category) {
            this.Loader.show();

            var Active = this.getActiveCategory();

            if (Active &&
                Active.getAttribute('name') != Category.getAttribute('name')) {
                this.$onCategoryLeave(this.getActiveCategory());
            }

            if (Category.getAttribute('name') == 'content') {
                this.loadEditor(
                    this.getSite().getAttribute('content')
                );

                return;
            }

            if (Category.getAttribute('type') == 'wysiwyg') {
                this.loadEditor(
                    this.getSite().getAttribute(
                        Category.getAttribute('name')
                    )
                );

                return;
            }

            if (!Category.getAttribute('template')) {
                this.getContent().set('html', '');
                this.$categoryOnLoad(Category);

                QUI.parse(Category);
                return;
            }

            var self    = this,
                Site    = this.getSite(),
                Project = Site.getProject();

            Ajax.get([
                'ajax_site_categories_template',
                'ajax_site_lock'
            ], function (result) {
                var Body = self.getContent();

                if (!result) {
                    Body.set('html', '');
                    self.$categoryOnLoad(Category);

                    return;
                }

                var Form;

                Body.set('html', '<form class="qui-site-data">' + result + '</form>');

                Form = Body.getElement('form');
                Form.addEvent('submit', function (event) {
                    event.stop();
                });

                // set to the media inputs the right project
                Body.getElements('.media-image,.media-folder').each(function (Elm) {
                    Elm.set('data-project', Project.getName());
                });

                // minimize setting tables
                if (Category.getAttribute('name') == 'settings') {
                    Body.getElements('.data-table:not(.site-data)')
                        .addClass('data-table-closed');
                }

                // set data
                QUIFormUtils.setDataToForm(Site.getAttributes(), Form);

                ControlUtils.parse(Form).then(function () {

                    // information tab
                    if (Category.getAttribute('name') === 'information') {
                        self.$bindNameInputUrlFilter();

                        // site linking
                        var i, len, Row, LastCell;

                        var LinkinTable     = Body.getElement('.site-linking'),
                            LinkinLangTable = Body.getElement('.site-langs'),
                            Locked          = Body.getElement('[data-locked]');

                        if (LinkinTable) {
                            var openDeleteLink = function (Btn) {
                                Btn.setAttribute('icon', 'fa fa-spinner fa-spin');

                                self.deleteLinked(
                                    Btn.getElm().getParent().get('data-parentid')
                                ).then(function () {
                                    Btn.setAttribute('icon', 'fa fa-trash');
                                }, function () {
                                    Btn.setAttribute('icon', 'fa fa-trash');
                                });
                            };

                            LinkinTable.getElements('.site-linking-entry-button').each(function (Node) {
                                Node.set('html', '');

                                new QUIButton({
                                    icon  : 'fa fa-trash',
                                    title : 'Verknüpfung löschen',
                                    events: {
                                        onClick: openDeleteLink
                                    }
                                }).inject(Node);
                            });
                        }

                        if (LinkinLangTable) {
                            var rowList = LinkinLangTable.getElements('tbody tr');

                            new QUIButton({
                                text  : Locale.get(lg, 'projects.project.site.panel.linked.btn.add'),
                                styles: {
                                    position: 'absolute',
                                    right   : 5,
                                    top     : 5,
                                    zIndex  : 1
                                },
                                events: {
                                    onClick: function (Btn, event) {
                                        event.stop();
                                        self.addLanguagLink();
                                    }
                                }
                            }).inject(LinkinLangTable.getElement('th'));


                            for (i = 0, len = rowList.length; i < len; i++) {
                                Row      = rowList[i];
                                LastCell = rowList[i].getLast();

                                LastCell.set('html', '');

                                if (!Row.get('data-id').toInt()) {

                                    // seite in sprache kopieren und sprach verknüpfung anlegen
                                    new QUIButton({
                                        icon  : 'fa fa-copy',
                                        alt   : Locale.get(lg, 'copy.site.in.lang'),
                                        title : Locale.get(lg, 'copy.site.in.lang'),
                                        lang  : Row.get('data-lang'),
                                        events: {
                                            onClick: function (Btn) {
                                                self.copySiteToLang(
                                                    Btn.getAttribute('lang')
                                                );
                                            }
                                        },
                                        styles: {
                                            'float': 'right'
                                        }
                                    }).inject(LastCell);

                                    continue;
                                }

                                new QUIButton({
                                    icon  : 'fa fa-file-o',
                                    alt   : Locale.get(lg, 'open.site'),
                                    title : Locale.get(lg, 'open.site'),
                                    lang  : Row.get('data-lang'),
                                    siteId: Row.get('data-id'),
                                    styles: {
                                        'float': 'right'
                                    },
                                    events: {
                                        onClick: function (Btn) {
                                            PanelUtils.openSitePanel(
                                                Project.getName(),
                                                Btn.getAttribute('lang'),
                                                Btn.getAttribute('siteId')
                                            );
                                        }
                                    }
                                }).inject(LastCell);

                                new QUIButton({
                                    icon  : 'fa fa-remove',
                                    alt   : Locale.get(lg, 'projects.project.site.panel.linked.btn.delete'),
                                    title : Locale.get(lg, 'projects.project.site.panel.linked.btn.delete'),
                                    lang  : Row.get('data-lang'),
                                    siteId: Row.get('data-id'),
                                    styles: {
                                        'float': 'right'
                                    },
                                    events: {
                                        onClick: function (Btn) {
                                            self.removeLanguagLink(
                                                Btn.getAttribute('lang'),
                                                Btn.getAttribute('siteId')
                                            );
                                        }
                                    }
                                }).inject(LastCell);
                            }
                        }


                        // locked
                        if (Locked && USER.isSU) {
                            new QUIButton({
                                text  : 'Trotzdem freischalten', // #locale
                                styles: {
                                    clear  : 'both',
                                    display: 'block',
                                    'float': 'none',
                                    margin : '10px auto',
                                    width  : 200
                                },
                                events: {
                                    onClick: function () {
                                        self.unlockSite();
                                    }
                                }
                            }).inject(Locked);
                        }
                    }


                    QUI.parse(Form, function () {
                        // set the project to the controls
                        var i, len, Control;
                        var quiids = Form.getElements('[data-quiid]');

                        for (i = 0, len = quiids.length; i < len; i++) {

                            Control = QUI.Controls.getById(
                                quiids[i].get('data-quiid')
                            );

                            if (!Control) {
                                continue;
                            }

                            if (typeOf(Control.setProject) == 'function') {
                                Control.setProject(Project);
                            }

                            Control.setAttribute('Site', self.getSite());
                        }
                    });
                }).catch(function (error) {
                    console.error(error);
                });


                self.$categoryOnLoad(Category);

            }, {
                id     : Site.getId(),
                project: Project.encode(),
                tab    : Category.getAttribute('name')
            });
        },

        /**
         * Load the category
         *
         * @method controls/projects/project/site/Panel#$categoryOnLoad
         * @param {Object} Category - qui/controls/buttons/Button
         */
        $categoryOnLoad: function (Category) {
            var self          = this,

                onloadRequire = Category.getAttribute('onload_require'),
                onload        = Category.getAttribute('onload');

            if (onloadRequire) {
                require([onloadRequire], function (Plugin) {
                    if (onload) {
                        eval(onload + '( Category, self );');
                        return;
                    }

                    var type = typeOf(Plugin);

                    if (type === 'function') {
                        type(Category, self);
                        return;
                    }

                    if (type === 'class') {
                        self.$CategoryControl = new Plugin({
                            Site : self.getSite(),
                            Panel: self
                        });

                        if (QUI.Controls.isControl(self.$CategoryControl)) {
                            self.$CategoryControl.inject(self.getContent());
                            self.$CategoryControl.setParent(self);

                            self.Loader.hide();
                        }
                    }
                });

                return;
            }

            if (onload) {
                eval(onload + '( Category, self );');
                return;
            }

            this.Loader.hide();
        },

        /**
         * The site tab leave event
         *
         * @method controls/projects/project/site/Panel#$tabLeave
         * @fires onSiteTabUnLoad
         *
         * @param {Object} Category - qui/controls/buttons/Button
         * @param {Function} [callback] - (optional) callback function
         */
        $onCategoryLeave: function (Category, callback) {
            this.Loader.show();

            var Site = this.getSite(),
                Body = this.getBody();


            // main content
            if (Category.getAttribute('name') === 'content') {
                Site.setAttribute(
                    'content',
                    this.getAttribute('Editor').getContent()
                );

                this.$clearEditorPeriodicalSave();
                this.getAttribute('Editor').destroy();

                if (typeof callback === 'function') {
                    callback();
                }

                return;
            }

            // wysiwyg type
            if (Category.getAttribute('type') == 'wysiwyg') {
                Site.setAttribute(
                    Category.getAttribute('name'),
                    this.getAttribute('Editor').getContent()
                );

                this.$clearEditorPeriodicalSave();
                this.getAttribute('Editor').destroy();

                if (typeof callback === 'function') {
                    callback();
                }

                return;
            }

            // form unload
            if (!Body.getElement('form')) {
                if (this.$CategoryControl) {
                    this.$CategoryControl.destroy();
                    this.$CategoryControl = null;
                }

                if (typeof callback === 'function') {
                    callback();
                }

                return;
            }

            var Form     = Body.getElement('form'),
                elements = Form.elements;

            // information tab
            if (Category.getAttribute('name') === 'information') {
                Site.setAttribute('name', elements['site-name'].value);
                Site.setAttribute('title', elements.title.value);
                Site.setAttribute('short', elements.short.value);
                Site.setAttribute('nav_hide', elements.nav_hide.checked);
                Site.setAttribute('type', elements.type.value);

                if (typeof elements.layout !== 'undefined') {
                    Site.setAttribute('layout', elements.layout.value);
                }

                if (typeof callback === 'function') {
                    callback();
                }

                return;
            }

            // unload params
            var FormData = QUIFormUtils.getFormData(Form);

            for (var key in FormData) {
                if (key === '') {
                    continue;
                }

                if (FormData.hasOwnProperty(key)) {
                    Site.setAttribute(key, FormData[key]);
                }
            }


            var self            = this,
                onunloadRequire = Category.getAttribute('onunload_require'),
                onunload        = Category.getAttribute('onunload');

            if (onunloadRequire) {
                require([onunloadRequire], function () {
                    if (self.$CategoryControl) {
                        self.$CategoryControl.destroy();
                        self.$CategoryControl = null;
                    }


                    eval(onunload + '( Category, self );');

                    if (typeof callback === 'function') {
                        callback();
                    }
                });
            }


            if (this.$CategoryControl) {
                this.$CategoryControl.destroy();
                this.$CategoryControl = null;
            }

            if (typeof callback === 'function') {
                callback();
            }
        },

        /**
         * Execute the panel onclick from PHP
         *
         * @method controls/projects/project/site/Panel#$onPanelButtonClick
         * @param {Object} Btn - qui/controls/buttons/Button
         */
        $onPanelButtonClick: function (Btn) {
            var Panel = this,
                Site  = Panel.getSite(); // maybe in eval

            if (Btn.getAttribute('name') === 'status') {
                this.$onCategoryLeave(this.getActiveCategory(), function () {
                    // check if site must be saved
                    if (!Site.hasWorkingStorage()) {
                        eval(Btn.getAttribute('_onclick') + '();');
                        return;
                    }

                    new QUIConfirm({
                        title        : Locale.get('quiqqer/quiqqer', 'site.window.siteChangesExists.title'),
                        text         : Locale.get('quiqqer/quiqqer', 'site.window.siteChangesExists.text'),
                        information  : Locale.get('quiqqer/quiqqer', 'site.window.siteChangesExists.information'),
                        maxHeight    : 400,
                        maxWidth     : 600,
                        texticon     : 'fa fa-edit',
                        icon         : 'fa fa-edit',
                        ok_button    : {
                            text: Locale.get('quiqqer/quiqqer', 'site.window.siteChangesExists.button.ok')
                        },
                        cancel_button: {
                            text: Locale.get('quiqqer/quiqqer', 'site.window.siteChangesExists.button.cancel')
                        },
                        events       : {
                            onSubmit: function () {
                                Site.save(function () {
                                    eval(Btn.getAttribute('_onclick') + '();');
                                });
                            },

                            onCancel: function () {
                                eval(Btn.getAttribute('_onclick') + '();');
                            }
                        }
                    }).open();
                });

                return;
            }

            eval(Btn.getAttribute('_onclick') + '();');
        },

        /**
         *
         */
        $bindNameInputUrlFilter: function () {
            var Site = this.getSite(),
                Body = this.getContent();

            var NameInput  = Body.getElements('input[name="site-name"]'),
                UrlDisplay = Body.getElements('.site-url-display'),
                siteUrl    = Site.getUrl();

            if (Site.getId() != 1) {
                UrlDisplay.set('html', Site.getUrl());
            }

            // filter
            var sitePath   = siteUrl.replace(/\\/g, '/').replace(/\/[^\/]*\/?$/, '') + '/',
                notAllowed = Object.keys(SiteUtils.notAllowedUrlSigns()).join('|'),
                reg        = new RegExp('[' + notAllowed + ']', "g");

            var lastPos = null;

            NameInput.set({
                value : Site.getAttribute('name'),
                events: {
                    keydown: function (event) {
                        lastPos = QUIElmUtils.getCursorPosition(event.target);
                    },

                    keyup: function () {
                        var old = this.value;

                        this.value = this.value.replace(reg, '');
                        this.value = this.value.replace(/ /g, QUIQQER.Rewrite.URL_SPACE_CHARACTER);

                        if (old != this.value) {
                            QUIElmUtils.setCursorPosition(this, lastPos);
                        }

                        if (Site.getId() != 1) {
                            UrlDisplay.set('html', sitePath + this.value + QUIQQER.Rewrite.SUFFIX);
                        }
                    },

                    blur: function () {
                        this.fireEvent('keyup');
                    },

                    focus: function () {
                        this.fireEvent('keyup');
                    }
                }
            });
        },

        /**
         * Disable the buttons, if the site is locked
         */
        setLocked: function () {
            var buttons = this.getButtons();

            for (var i = 0, len = buttons.length; i < len; i++) {
                if ("disable" in buttons[i]) {
                    buttons[i].disable();
                }
            }
        },

        /**
         * Enable the buttons, if the site is unlocked
         */
        setUnlocked: function () {
            var buttons = this.getButtons();

            for (var i = 0, len = buttons.length; i < len; i++) {
                if ("enable" in buttons[i]) {
                    buttons[i].enable();
                }
            }
        },

        /**
         * unlock the site, only if the user has the permission
         */
        unlockSite: function () {
            var self = this,
                Site = this.getSite();

            this.Loader.show();

            Site.unlock(function () {
                self.$destroyRefresh();
            });
        },

        /**
         * destroy and refresh the panel
         *
         * @private
         */
        $destroyRefresh: function () {
            var self    = this,
                Site    = this.getSite(),
                Project = Site.getProject();

            require(['utils/Panels'], function (Utils) {
                self.destroy();

                Utils.openSitePanel(
                    Project.getName(),
                    Project.getLang(),
                    Site.getId()
                );
            });
        },

        /**
         * event : on login
         */
        $onLogin: function () {
            var Active = this.getActiveCategory(),
                Task   = this.getAttribute('Task');

            // if task exists, check if task is active
            if (Task &&
                typeOf(Task) === 'qui/controls/taskbar/Task' &&
                Task.isActive() === false
            ) {
                return;
            }

            // no active category? something is wrong, so reload the panel
            if (!Active) {
                this.$destroyRefresh();
                return;
            }

            this.$onCategoryEnter(this.getActiveCategory());
        },

        /**
         * Site event methods
         */

        /**
         * event on site save
         */
        $onSiteSave: function () {
            this.Loader.hide();
        },

        /**
         * event : on {classes/projects/Site} activation
         */
        $onSiteActivate: function () {
            var Status = this.getButtons('status');

            if (!Status) {
                return;
            }

            Status.setAttributes({
                'textimage': Status.getAttribute('dimage'),
                'text'     : Status.getAttribute('dtext'),
                '_onclick' : 'Panel.getSite().deactivate'
            });

            this.Loader.hide();
        },

        /**
         * event : on {classes/projects/Site} deactivation
         */
        $onSiteDeactivate: function () {
            var Status = this.getButtons('status');

            if (!Status) {
                return;
            }

            Status.setAttributes({
                'textimage': Status.getAttribute('aimage'),
                'text'     : Status.getAttribute('atext'),
                '_onclick' : 'Panel.getSite().activate'
            });

            this.Loader.hide();
        },

        /**
         * event : on {classes/projects/Site} delete
         */
        $onSiteDelete: function () {
            this.destroy();
        },

        /**
         * Editor (WYSIWYG) Methods
         */

        /**
         * Load the WYSIWYG Editor in the panel
         *
         * @method controls/projects/project/site/Panel#loadEditor
         * @param {String} content - content of the editor
         */
        loadEditor: function (content) {
            var self = this,
                Body = this.getBody();

            Body.set('html', '');

            require(['Editors'], function (Editors) {
                Editors.getEditor().then(function (Editor) {
                    var Site    = self.getSite(),
                        Project = Site.getProject();

                    self.setAttribute('Editor', Editor);

                    // draw the editor
                    Editor.setAttribute('Panel', self);
                    Editor.setAttribute('name', Site.getId());
                    Editor.setAttribute('showLoader', false);
                    Editor.setProject(Project);
                    Editor.addEvent('onDestroy', self.$onEditorDestroy);

                    // set the site content
                    if (typeof content === 'undefined' || !content) {
                        content = '';
                    }

                    Editor.addEvent('onLoaded', self.$onEditorLoad);

                    Editor.inject(Body);
                    Editor.setContent(content);

                    self.$startEditorPeriodicalSave();
                });
            });
        },

        /**
         * event: on editor load
         * if the editor is finished
         *
         * @method controls/projects/project/site/Panel#$onEditorLoad
         */
        $onEditorLoad: function () {
            this.Loader.hide();
        },

        /**
         * event: on editor load
         * if the editor would be destroyed
         *
         * @method controls/projects/project/site/Panel#$onEditorDestroy
         */
        $onEditorDestroy: function () {
            this.setAttribute('Editor', false);
        },

        /**
         * Start the periodical editor save
         */
        $startEditorPeriodicalSave: function () {
            if (this.$editorPeriodicalSave) {
                this.$clearEditorPeriodicalSave();
            }

            var Category  = this.getActiveCategory(),
                attribute = false;

            if (Category.getAttribute('name') === 'content') {
                attribute = 'content';
            }

            if (Category.getAttribute('type') == 'wysiwyg') {
                attribute = Category.getAttribute('name');
            }

            this.$editorPeriodicalSave = (function (attr) {
                var Editor = this.getAttribute('Editor');

                if (!Editor) {
                    return;
                }

                this.getSite().setAttribute(attr, Editor.getContent());

            }).periodical(this.getAttribute('editorPeriode'), this, [attribute]);
        },

        /**
         * clear / stop the periodical editor save
         */
        $clearEditorPeriodicalSave: function () {
            if (this.$editorPeriodicalSave) {
                clearInterval(this.$editorPeriodicalSave);
                this.$editorPeriodicalSave = false;
            }
        },

        /**
         * Opens a project popup, so, an user can set a languag link
         */
        addLanguagLink: function () {
            var self = this;

            require(['controls/projects/Popup'], function (ProjectPopup) {
                var Site    = self.getSite(),
                    Project = Site.getProject();

                Project.getConfig(function (config) {
                    var langs = config.langs,
                        lang  = Project.getLang();

                    langs = langs.split(',');

                    var needles = [];

                    for (var i = 0, len = langs.length; i < len; i++) {
                        if (langs[i] != lang) {
                            needles.push(langs[i]);
                        }
                    }

                    new ProjectPopup({
                        project: Project.getName(),
                        langs  : needles,
                        events : {
                            onSubmit: function (Popup, result) {
                                Popup.Loader.show();

                                Ajax.post('ajax_site_language_add', function () {
                                    Popup.close();

                                    self.load();
                                }, {
                                    project     : Project.encode(),
                                    id          : Site.getId(),
                                    linkedParams: JSON.encode({
                                        lang: result.lang,
                                        id  : result.ids[0]
                                    })
                                });
                            }
                        }
                    }).open();
                });
            });
        },

        /**
         * Open the remove languag link popup
         *
         * @param {String} lang - lang of the language link
         * @param {String} id - Site-ID of the language link
         */
        removeLanguagLink: function (lang, id) {
            var self = this;

            var Site    = self.getSite(),
                Project = Site.getProject();

            new QUIConfirm({
                title    : Locale.get(lg, 'projects.project.site.panel.linked.window.delete.title'),
                icon     : 'fa fa-remove',
                text     : Locale.get(lg, 'projects.project.site.panel.linked.window.delete.text'),
                maxHeight: 300,
                maxWidth : 450,
                events   : {
                    onSubmit: function (Confirm) {
                        Confirm.Loader.show();

                        Ajax.post('ajax_site_language_remove', function () {
                            Confirm.close();

                            self.load();
                        }, {
                            project     : Project.encode(),
                            id          : Site.getId(),
                            linkedParams: JSON.encode({
                                lang: lang,
                                id  : id
                            })
                        });
                    }
                }
            }).open();

        },

        /**
         * Copy site to another language and set the language link
         *
         * @param {String} lang
         */
        copySiteToLang: function (lang) {

            if (!this.$Site) {
                return;
            }

            var self    = this,
                Project = this.$Site.getProject();

            new QUIConfirm({
                title        : Locale.get(lg, 'projects.project.site.panel.copySiteToLink.window.title', {
                    lang: lang
                }),
                text         : Locale.get(lg, 'projects.project.site.panel.copySiteToLink.window.text', {
                    lang: lang
                }),
                information  : Locale.get(lg, 'projects.project.site.panel.copySiteToLink.window.information', {
                    lang: lang
                }),
                icon         : 'fa fa-copy',
                texticon     : 'fa fa-copy',
                autoclose    : false,
                maxHeight    : 400,
                maxWidth     : 600,
                events       : {
                    onSubmit: function (Win) {

                        Win.Loader.show();

                        require(['controls/projects/Popup'], function (ProjectPopup) {

                            Win.close();

                            new ProjectPopup({
                                project             : Project.getName(),
                                lang                : lang,
                                disableProjectSelect: true,
                                events              : {
                                    onSubmit: function (Popup, result) {

                                        Popup.Loader.show();

                                        self.$Site.copy({
                                            parentId: result.ids[0],
                                            project : {
                                                name: Project.getName(),
                                                lang: lang
                                            }
                                        }).then(function (newChildId) {

                                            Ajax.post('ajax_site_language_add', function () {
                                                Popup.close();

                                                self.load();
                                            }, {
                                                project     : Project.encode(),
                                                id          : self.$Site.getId(),
                                                linkedParams: JSON.encode({
                                                    lang: lang,
                                                    id  : newChildId
                                                })
                                            });

                                        });
                                    }
                                }
                            }).open();
                        });
                    }
                },
                cancel_button: {
                    text     : Locale.get(lg, 'cancel'),
                    textimage: 'fa fa-remove'
                },
                ok_button    : {
                    text     : Locale.get(lg, 'projects.project.site.panel.copySiteToLink.window.submit'),
                    textimage: 'fa fa-check'
                }
            }).open();

            //this.$Site;
        },

        /**
         * Open the preview window
         */
        openPreview: function () {
            var self = this;

            this.Loader.show();

            this.$onCategoryLeave(this.getActiveCategory(), function () {
                var Site    = self.getSite(),
                    Project = Site.getProject();

                var Form = new Element('form', {
                    method: 'POST',
                    action: URL_SYS_DIR + 'bin/preview.php',
                    target: '_blank'
                });

                var attributes = Site.getAttributes();

                new Element('input', {
                    type : 'hidden',
                    value: Project.getName(),
                    name : 'project'
                }).inject(Form);

                new Element('input', {
                    type : 'hidden',
                    value: Project.getLang(),
                    name : 'lang'
                }).inject(Form);

                new Element('input', {
                    type : 'hidden',
                    value: Site.getId(),
                    name : 'id'
                }).inject(Form);


                var val, to;

                for (var key in attributes) {

                    if (!attributes.hasOwnProperty(key)) {
                        continue;
                    }

                    if (!attributes[key]) {
                        continue;
                    }

                    to  = typeOf(attributes[key]);
                    val = attributes[key];

                    if (to !== 'string' && to !== 'number') {
                        val = JSON.encode(val);

                        new Element('input', {
                            type : 'hidden',
                            value: val,
                            name : 'siteDataJSON[' + key + ']'
                        }).inject(Form);

                        continue;
                    }

                    new Element('input', {
                        type : 'hidden',
                        value: val,
                        name : 'siteData[' + key + ']'
                    }).inject(Form);

                }

                Form.inject(document.body);
                Form.submit();

                self.Loader.hide();

                (function () {
                    Form.destroy();
                }).delay(1000);
            });
        }
    });
});
