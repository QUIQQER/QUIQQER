/**
 * Displays a Site in a Panel
 *
 * @module controls/projects/project/site/Panel
 * @author www.pcsg.de (Henning Leutz)
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
    'Users',
    'Mustache',

    'text!controls/projects/project/site/Panel.restore.html',
    'css!controls/projects/project/site/Panel.css'

], function () {
    "use strict";

    var QUI             = arguments[0],
        QUIPanel        = arguments[1],
        Projects        = arguments[2],
        Ajax            = arguments[3],
        QUIButton       = arguments[4],
        QUIConfirm      = arguments[5],
        QUIFormUtils    = arguments[6],
        QUIElmUtils     = arguments[7],
        ControlUtils    = arguments[8],
        PanelUtils      = arguments[9],
        SiteUtils       = arguments[10],
        Locale          = arguments[11],
        Users           = arguments[12],
        Mustache        = arguments[13],
        templateRestore = arguments[14];

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
            this.$Container       = null;

            this.$PreviousCategory     = null;
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
                            Locale.get('quiqqer/quiqqer', 'exception.site.panel.error', {
                                id: this.getSite().getId()
                            })
                        );
                    }.bind(this));

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

            if (Site.getId() !== 1) {
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

            this.$Container = new Element('div', {
                styles: {
                    height  : '100%',
                    position: 'relative',
                    width   : '100%'
                }
            }).inject(this.getContent());

            this.getContent().setStyle('position', 'relative');


            // permissions
            new QUIButton({
                image : 'fa fa-shield',
                name  : 'permissions',
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
                name  : 'media',
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
                name  : 'sort',
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

                    if (data.name === 'delete' || data.name === 'new') {
                        data.styles = {
                            'float': 'right',
                            width  : 40
                        };
                    }

                    self.addButton(data);
                }

                var Save = self.getButtonBar().getChildren('save');

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

            this.Loader.show();

            if (!Site.hasWorkingStorage()) {
                Site.load();
                return;
            }

            var self = this;

            Site.hasWorkingStorageChanges().then(function (hasStorage) {
                if (hasStorage === false) {
                    Site.load();
                    return;
                }

                var EditUser = Users.get(Site.getAttribute('e_user'));

                if (EditUser.isLoaded()) {
                    return Promise.resolve(EditUser);
                }

                return EditUser.load().then(function () {
                    return Promise.resolve(EditUser);
                });
            }).then(function (EditUser) {
                if (!EditUser) {
                    Site.load();
                    return;
                }

                var Sheet = self.createSheet({
                    icon : 'fa fa-window-restore',
                    title: Locale.get('quiqqer/quiqqer', 'panel.site.restore.title', {
                        id: Site.getId()
                    })
                });


                var StorageTime = null;
                var storageDate = '---';
                var storage     = Site.getWorkingStorage();
                var EditDate    = new Date(Site.getAttribute('e_date'));

                if ("__storageTime" in storage) {
                    StorageTime = new Date(storage.__storageTime);
                    storageDate = StorageTime.toLocaleDateString([], {
                        hour  : '2-digit',
                        minute: '2-digit'
                    });
                }

                var localeParams = {
                    id          : Site.getId(),
                    title       : Site.getAttribute('title'),
                    editUser    : EditUser.getName(),
                    editUsername: EditUser.getUsername(),
                    editDate    : EditDate.toLocaleDateString([], {
                        hour  : '2-digit',
                        minute: '2-digit'
                    }),
                    localeDate  : storageDate
                };

                var text = Locale.get(
                    'quiqqer/quiqqer',
                    'panel.site.restore.message.local.newer',
                    localeParams
                );

                if (StorageTime && EditDate > StorageTime) {
                    text = Locale.get(
                        'quiqqer/quiqqer',
                        'panel.site.restore.message.online.newer',
                        localeParams
                    );
                }

                Sheet.getContent().set('html', Mustache.render(templateRestore, {
                    id  : Site.getId(),
                    text: text
                }));

                Sheet.clearButtons();

                Sheet.addButton({
                    text  : Locale.get('quiqqer/quiqqer', 'panel.site.restore.button.cancel'),
                    events: {
                        onClick: function () {
                            Sheet.hide(function () {
                                Sheet.destroy();
                            });

                            Site.clearWorkingStorage();
                            self.load();
                        }
                    }
                });

                Sheet.addButton({
                    text  : Locale.get('quiqqer/quiqqer', 'panel.site.restore.button.restore'),
                    events: {
                        onClick: function () {
                            Sheet.hide(function () {
                                Sheet.destroy();
                            });

                            Site.setAttributes(storage);
                            Site.clearWorkingStorage();
                            self.load();
                        }
                    }
                });

                Sheet.show(function () {
                    self.Loader.hide();
                });
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

            // only unlock if the site was not locked from another user
            if (!this.$Container.getElement('[data-locked]')) {
                Ajax.get(['ajax_site_unlock'], false, {
                    project: Project.encode(),
                    id     : Site.getId()
                });
            }
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
                icon   : 'fa fa-sort',
                buttons: false,
                title  : Locale.get(lg, 'projects.project.site.panel.sort.title', {
                    id   : Site.getId(),
                    title: Site.getAttribute('title'),
                    name : Site.getAttribute('name')
                }),
                events : {
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
            //
            // Sheets.addButton({
            //     name     : 'sortSave',
            //     textimage: 'fa fa-save',
            //     text     : Locale.get(lg, 'projects.project.site.childrensort.save'),
            //     events   : {
            //         onClick: function (Btn) {
            //             if (!Sort) {
            //                 return;
            //             }
            //
            //             Btn.setAttribute('textimage', 'fa fa-spinner fa-spin');
            //
            //             Sort.save(function () {
            //                 Btn.setAttribute('textimage', 'fa fa-save');
            //
            //                 Sheets.hide(function () {
            //                     Sheets.destroy();
            //                 });
            //             });
            //         }
            //     }
            // });

            Sheets.show();
        },

        /**
         * saves site attributes
         *
         * @method controls/projects/project/site/Panel#openPermissions
         */
        save: function () {
            var self = this;

            this.$onCategoryLeave(this.getActiveCategory()).then(function () {
                return self.getSite().save();
            }).then(function () {
                // refresh data
                var Form = self.$Container.getElement('form');

                if (Form) {
                    QUIFormUtils.setDataToForm(
                        self.getSite().getAttributes(),
                        Form
                    );
                }

                self.load();
            }).catch(function (err) {
                console.error(err);
                self.Loader.hide();
            });
        },

        /**
         * opens the site delete dialog
         */
        del: function () {
            var Site = this.getSite();

            require(['qui/controls/windows/Confirm'], function (Confirm) {
                new Confirm({
                    title        : Locale.get(lg, 'projects.project.site.panel.window.delete.title', {
                        id: Site.getId()
                    }),
                    icon         : 'fa fa-trash-o',
                    text         : Locale.get(lg, 'projects.project.site.panel.window.delete.text', {
                        id   : Site.getId(),
                        url  : Site.getAttribute('name') + QUIQQER.Rewrite.SUFFIX,
                        name : Site.getAttribute('name'),
                        title: Site.getAttribute('title')
                    }),
                    texticon     : 'fa fa-trash-o',
                    information  : Locale.get(lg, 'projects.project.site.panel.window.delete.information', {
                        id   : Site.getId(),
                        url  : Site.getAttribute('name') + QUIQQER.Rewrite.SUFFIX,
                        name : Site.getAttribute('name'),
                        title: Site.getAttribute('title')
                    }),
                    maxHeight    : 400,
                    maxWidth     : 600,
                    autoclose    : false,
                    cancel_button: {
                        text     : Locale.get(lg, 'cancel'),
                        textimage: 'fa fa-remove'
                    },
                    ok_button    : {
                        text     : Locale.get(lg, 'projects.project.site.panel.window.delete.button'),
                        textimage: 'fa fa-trash-o'
                    },

                    events: {
                        onSubmit: function (Win) {
                            Win.Loader.show();

                            Site.del().then(function () {
                                Win.close();
                            }).catch(function () {
                                Win.Loader.hide();
                            });
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
         * @return {Promise}
         */
        $onCategoryEnter: function (Category) {
            var self = this;

            if (Category === this.getActiveCategory()) {
                this.Loader.hide();
                return Promise.resolve();
            }

            var setProject = function () {
                // set the project to the controls
                var i, len, Control;

                var Site    = self.getSite(),
                    Project = Site.getProject(),
                    Form    = self.getBody().getElement('form'),
                    quiids  = Form.getElements('[data-quiid]');

                for (i = 0, len = quiids.length; i < len; i++) {

                    Control = QUI.Controls.getById(
                        quiids[i].get('data-quiid')
                    );

                    if (!Control) {
                        continue;
                    }

                    if (typeOf(Control.setProject) === 'function') {
                        Control.setProject(Project);
                    }

                    Control.setAttribute('Site', self.getSite());
                }

                return self.$categoryOnLoad(Category);
            };

            this.Loader.show();

            return this.$onCategoryLeaveHide().then(function () {
                return self.$onCategoryLeave(self.$PreviousCategory);
            }).then(function () {
                // cleanup controls
                if (self.getAttribute('Editor')) {
                    self.getAttribute('Editor').destroy();
                }

                if (self.$CategoryControl) {
                    self.$CategoryControl.destroy();
                    self.$CategoryControl = null;
                }

                // create content
                self.$PreviousCategory = Category;

                if (Category.getAttribute('name') === 'content') {
                    return this.loadEditor(
                        this.getSite().getAttribute('content')
                    );
                }

                if (Category.getAttribute('type') === 'wysiwyg') {
                    return this.loadEditor(
                        this.getSite().getAttribute(
                            Category.getAttribute('name')
                        )
                    );
                }

                if (Category.getAttribute('type') === 'xml') {
                    this.$Container.set('html', '');

                    return this.$getCategoryFromXml(Category.getAttribute('name')).then(function (result) {
                        var Form = new Element('form', {
                            html: result
                        }).inject(self.$Container);

                        QUIFormUtils.setDataToForm(self.getSite().getAttributes(), Form);

                        return QUI.parse(Form).then(setProject);
                    });
                }

                if (!Category.getAttribute('template')) {
                    this.$Container.set('html', '');
                    this.$categoryOnLoad(Category);

                    return QUI.parse(this.$Container);
                }

                var Site    = self.getSite(),
                    Project = Site.getProject();

                return new Promise(function (resolve) {
                    Ajax.get([
                        'ajax_site_categories_template',
                        'ajax_site_lock'
                    ], function (result) {
                        var Body = self.$Container;

                        if (!result) {
                            Body.set('html', '');
                            self.$categoryOnLoad(Category).then(resolve);
                            return;
                        }

                        Body.set('html', '<form class="qui-site-data">' + result + '</form>');

                        var Form = Body.getElement('form');

                        Form.addEvent('submit', function (event) {
                            event.stop();
                        });

                        // set to the media inputs the right project
                        Body.getElements('.media-image,.media-folder').each(function (Elm) {
                            Elm.set('data-project', Project.getName());
                        });

                        // minimize setting tables
                        if (Category.getAttribute('name') === 'settings') {
                            Body.getElements('.data-table:not(.site-data)')
                                .addClass('data-table-closed');
                        }

                        // search editor controls
                        if (Category.getAttribute('name') === 'settings') {
                            var Editors = Body.getElements('[data-qui="controls/editors/Editor"]');

                            if (Editors.length) {
                                Editors.set('data-qui', null);
                                console.error('Please don\'t include controls/editors/Editor in Settings.');
                            }
                        }


                        // set data
                        QUIFormUtils.setDataToForm(Site.getAttributes(), Form);

                        ControlUtils.parse(Form).then(function () {
                            // information tab
                            if (Category.getAttribute('name') === 'information') {
                                self.$bindNameInputUrlFilter();

                                // site linking
                                var i, len, Row;

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
                                            name  : 'delete-linking',
                                            icon  : 'fa fa-trash',
                                            title : Locale.get(lg, 'projects.project.site.panel.window.deleteLinked.button'),
                                            styles: {
                                                width: 50
                                            },
                                            events: {
                                                onClick: openDeleteLink
                                            }
                                        }).inject(Node);
                                    });
                                }

                                if (LinkinLangTable) {
                                    var Buttons,
                                        rowList = LinkinLangTable.getElements('tbody tr');

                                    new QUIButton({
                                        name  : 'add-linking',
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
                                                self.addLanguageLink();
                                            }
                                        }
                                    }).inject(LinkinLangTable.getElement('th'));

                                    // helper functions
                                    var copyLinking = function (Btn) {
                                        self.copySiteToLang(
                                            Btn.getAttribute('lang')
                                        );
                                    };

                                    var openSite = function (Btn) {
                                        PanelUtils.openSitePanel(
                                            Project.getName(),
                                            Btn.getAttribute('lang'),
                                            Btn.getAttribute('siteId')
                                        );
                                    };

                                    var removeLinking = function (Btn) {
                                        self.removeLanguageLink(
                                            Btn.getAttribute('lang'),
                                            Btn.getAttribute('siteId')
                                        );
                                    };

                                    for (i = 0, len = rowList.length; i < len; i++) {
                                        Row     = rowList[i];
                                        Buttons = rowList[i].getElement('.site-lang-entry-button');

                                        if (!Row.get('data-id').toInt()) {
                                            // seite in sprache kopieren und sprach verknüpfung anlegen
                                            new QUIButton({
                                                name  : 'copy-linking',
                                                icon  : 'fa fa-copy',
                                                alt   : Locale.get(lg, 'copy.site.in.lang'),
                                                title : Locale.get(lg, 'copy.site.in.lang'),
                                                lang  : Row.get('data-lang'),
                                                events: {
                                                    onClick: copyLinking
                                                },
                                                styles: {
                                                    width: 50
                                                }
                                            }).inject(Buttons);

                                            continue;
                                        }

                                        new QUIButton({
                                            name  : 'open-site',
                                            icon  : 'fa fa-file-o',
                                            alt   : Locale.get(lg, 'open.site'),
                                            title : Locale.get(lg, 'open.site'),
                                            lang  : Row.get('data-lang'),
                                            siteId: Row.get('data-id'),
                                            styles: {
                                                width: 50
                                            },
                                            events: {
                                                onClick: openSite
                                            }
                                        }).inject(Buttons);

                                        new QUIButton({
                                            name  : 'remove-linking',
                                            icon  : 'fa fa-remove',
                                            alt   : Locale.get(lg, 'projects.project.site.panel.linked.btn.delete'),
                                            title : Locale.get(lg, 'projects.project.site.panel.linked.btn.delete'),
                                            lang  : Row.get('data-lang'),
                                            siteId: Row.get('data-id'),
                                            styles: {
                                                width: 50
                                            },
                                            events: {
                                                onClick: removeLinking
                                            }
                                        }).inject(Buttons);
                                    }
                                }


                                // locked
                                if (Locked && USER.isSU) {
                                    new QUIButton({
                                        name  : 'unlock',
                                        text  : Locale.get(lg, 'projects.project.site.panel.unlock'),
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

                            QUI.parse(Form).then(setProject).then(resolve);
                        }).catch(function (error) {
                            console.error(error);
                        });
                    }, {
                        id     : Site.getId(),
                        project: Project.encode(),
                        tab    : Category.getAttribute('name')
                    });
                });
            }.bind(this)).then(function () {
                return self.$onCategoryEntryShow();
            });
        },

        /**
         * Load the category
         *
         * @method controls/projects/project/site/Panel#$categoryOnLoad
         * @param {Object} Category - qui/controls/buttons/Button
         * @return {Promise}
         */
        $categoryOnLoad: function (Category) {
            var self          = this,
                onloadRequire = Category.getAttribute('onload_require'),
                onload        = Category.getAttribute('onload');

            return new Promise(function (resolve) {
                if (onloadRequire) {

                    require([onloadRequire], function (Plugin) {
                        if (onload) {
                            eval(onload + '( Category, self );');
                            resolve();
                            return;
                        }

                        var type = typeOf(Plugin);

                        if (type === 'function') {
                            type(Category, self);
                            resolve();
                            return;
                        }

                        if (type === 'class') {
                            self.$CategoryControl = new Plugin({
                                Site : self.getSite(),
                                Panel: self
                            });

                            if (QUI.Controls.isControl(self.$CategoryControl)) {
                                self.$CategoryControl.inject(self.$Container);
                                self.$CategoryControl.setParent(self);
                            }
                        }

                        resolve();
                    }, resolve);

                    return;
                }

                if (onload) {
                    eval(onload + '(Category, self);');
                    return resolve();
                }

                resolve();
            });
        },

        /**
         * Return the html from a xml category
         *
         * @return {Promise}
         */
        $getCategoryFromXml: function (name) {
            var Site    = this.getSite(),
                Project = Site.getProject();

            return new Promise(function (resolve) {
                Ajax.get('ajax_site_categories_xml', resolve, {
                    project : Project.encode(),
                    id      : Site.getId(),
                    category: name
                });
            });
        },

        /**
         * The site tab leave event
         * Unload the category
         *
         * @method controls/projects/project/site/Panel#$tabLeave
         * @fires onSiteTabUnLoad
         *
         * @param {Object} [Category] - qui/controls/buttons/Button
         * @param {Function} [callback] - (optional) callback function
         * @return {Promise}
         */
        $onCategoryLeave: function (Category, callback) {
            this.Loader.show();

            var Site = this.getSite(),
                Body = this.$Container;

            if (typeof Category === 'undefined' || !Category) {
                return Promise.resolve();
            }

            // main content
            if (Category.getAttribute('name') === 'content') {
                if (!this.getAttribute('Editor')) {
                    return Promise.resolve();
                }

                Site.setAttribute(
                    'content',
                    this.getAttribute('Editor').getContent()
                );

                this.$clearEditorPeriodicalSave();

                if (typeof callback === 'function') {
                    callback();
                }

                return Promise.resolve();
                //self.getAttribute('Editor').destroy();
            }

            // wysiwyg type
            if (Category.getAttribute('type') === 'wysiwyg') {
                if (!this.getAttribute('Editor')) {
                    return Promise.resolve();
                }

                Site.setAttribute(
                    Category.getAttribute('name'),
                    this.getAttribute('Editor').getContent()
                );

                this.$clearEditorPeriodicalSave();

                if (typeof callback === 'function') {
                    callback();
                }

                return Promise.resolve();
            }

            // form unload
            if (this.$CategoryControl && "unload" in this.$CategoryControl) {
                this.$CategoryControl.unload();
            }

            if (!Body.getElement('form')) {
                return Promise.resolve().then(function () {
                    if (typeof callback === 'function') {
                        callback();
                    }
                });
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

                return Promise.resolve();
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

            var onunloadRequire = Category.getAttribute('onunload_require'),
                onunload        = Category.getAttribute('onunload');

            return Promise.resolve().then(function () {
                if (onunloadRequire) {
                    return new Promise(function (resolve) {
                        require([onunloadRequire], function () {
                            // if (self.$CategoryControl) {
                            //     self.$CategoryControl.destroy();
                            //     self.$CategoryControl = null;
                            // }

                            eval(onunload + '( Category, self );');

                            if (typeof callback === 'function') {
                                callback();
                            }

                            resolve();
                        }, resolve);
                    });
                }

                // if (self.$CategoryControl) {
                //     self.$CategoryControl.destroy();
                //     self.$CategoryControl = null;
                // }

                if (typeof callback === 'function') {
                    callback();
                }
            });
        },

        /**
         * Helper to hide body
         *
         * @return {Promise}
         */
        $onCategoryLeaveHide: function () {
            var self = this;

            return new Promise(function (resolve) {
                moofx(self.$Container).animate({
                    opacity: 0,
                    top    : -20
                }, {
                    duration: 250,
                    callback: resolve
                });
            });
        },

        /**
         * Helper to hide body
         *
         * @return {Promise}
         */
        $onCategoryEntryShow: function () {
            var self = this;

            return new Promise(function (resolve) {
                moofx(self.$Container).animate({
                    opacity: 1,
                    top    : 0
                }, {
                    duration: 250,
                    callback: function () {
                        self.Loader.hide();
                        resolve();
                    }
                });
            });
        },

        /**
         * Execute the panel onclick from PHP
         *
         * @method controls/projects/project/site/Panel#$onPanelButtonClick
         * @param {Object} Btn - qui/controls/buttons/Button
         */
        $onPanelButtonClick: function (Btn) {
            var Panel = this,
                Site  = Panel.getSite();

            var evalButtonClick = function () {
                eval(Btn.getAttribute('_onclick') + '();');
            };

            if (Btn.getAttribute('name') === 'status') {
                var saving = function () {
                    return new Promise(function (resolve) {
                        // check if site must be saved
                        if (!Site.hasWorkingStorage()) {
                            evalButtonClick();
                            return resolve();
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
                                        evalButtonClick();
                                        resolve();
                                    });
                                },

                                onCancel: function () {
                                    evalButtonClick();
                                    resolve();
                                }
                            }
                        }).open();
                    });
                };

                Panel.$onCategoryLeave(Panel.getActiveCategory())
                     .then(saving)
                     .then(function () {
                         Panel.$onCategoryEnter(Panel.getActiveCategory());
                     });

                return;
            }

            evalButtonClick();
        },

        /**
         *
         */
        $bindNameInputUrlFilter: function () {
            var Site = this.getSite(),
                Body = this.$Container;

            var NameInput     = Body.getElement('input[name="site-name"]'),
                UrlDisplay    = Body.getElement('.site-url-display'),
                UrlEditButton = Body.getElement('.site-url-display-edit'),
                siteUrl       = Site.getUrl();

            if (!NameInput) {
                return;
            }

            if (Site.getId() !== 1) {
                UrlDisplay.set('html', Site.getUrl());
            }

            new QUIButton({
                icon  : 'fa fa-edit',
                styles: {
                    width: 50
                },
                events: {
                    onClick: function (Btn) {
                        if (Btn.isActive()) {
                            NameInput.setStyle('display', 'none');
                            UrlDisplay.removeClass('site-url-display-active');
                            Btn.setNormal();
                            return;
                        }

                        NameInput.setStyle('display', null);
                        NameInput.focus();
                        UrlDisplay.addClass('site-url-display-active');
                        Btn.setActive();
                    }
                }
            }).inject(UrlEditButton);

            // filter
            var sitePath   = siteUrl.replace(/\\/g, '/').replace(/\/[^\/]*\/?$/, '') + '/',
                notAllowed = Object.keys(SiteUtils.notAllowedUrlSigns()).join('|'),
                reg        = new RegExp('[' + notAllowed + ']', "g");

            var lastPos = null,
                hold    = false;

            NameInput.set({
                value : Site.getAttribute('name'),
                events: {
                    keydown: function (event) {
                        if (hold) {
                            return;
                        }

                        hold = true;
                    },

                    keyup: function () {
                        var old = this.value;

                        lastPos = QUIElmUtils.getCursorPosition(event.target);
                        hold    = false;

                        this.value = this.value.replace(reg, '');
                        this.value = this.value.replace(/ /g, QUIQQER.Rewrite.URL_SPACE_CHARACTER);

                        if (old !== this.value) {
                            QUIElmUtils.setCursorPosition(this, lastPos - 1);
                        }

                        if (Site.getId() !== 1) {
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
         * @return {Promise}
         */
        loadEditor: function (content) {
            var self = this,
                Body = this.$Container;

            Body.set('html', '');
            Body.setStyle('opacity', 0);

            return new Promise(function (resolve) {
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

                        Editor.addEvent('onLoaded', resolve);
                        Editor.inject(Body);
                        Editor.setContent(content);

                        self.$startEditorPeriodicalSave();
                    });
                });
            });
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

            var self      = this,
                Category  = this.getActiveCategory(),
                attribute = false;

            if (Category.getAttribute('name') === 'content') {
                attribute = 'content';
            }

            if (Category.getAttribute('type') === 'wysiwyg') {
                attribute = Category.getAttribute('name');
            }

            this.$editorPeriodicalSave = (function (attr) {
                var Editor = self.getAttribute('Editor');

                if (!Editor) {
                    return;
                }

                self.getSite().setAttribute(attr, Editor.getContent());
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
        addLanguageLink: function () {
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
                        if (langs[i] !== lang) {
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
                                    self.$ActiveCat = null;
                                    self.getSite().load(function () {
                                        Popup.close();
                                        self.load();
                                    });
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
        removeLanguageLink: function (lang, id) {
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
                            self.$ActiveCat = null;
                            self.getSite().load(function () {
                                Confirm.close();
                                self.load();
                            });
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
                title      : Locale.get(lg, 'projects.project.site.panel.copySiteToLink.window.title', {
                    lang: lang
                }),
                text       : Locale.get(lg, 'projects.project.site.panel.copySiteToLink.window.text', {
                    lang: lang
                }),
                information: Locale.get(lg, 'projects.project.site.panel.copySiteToLink.window.information', {
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
                                                self.$ActiveCat = null;
                                                self.getSite().load(function () {
                                                    Popup.close();
                                                    self.load();
                                                });
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

            return this.$onCategoryLeave(this.getActiveCategory()).then(function () {
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
            }).then(function () {
                this.$onCategoryEnter(this.getActiveCategory());
            }.bind(this));
        }
    });
});
