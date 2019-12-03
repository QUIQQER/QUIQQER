/**
 * Workspace Manager
 *
 * @module controls/workspace/Manager
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onWorkspaceLoaded [ {self} ]
 * @event onLoadWorkspace [ {self} ]
 */

define('controls/workspace/Manager', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/loader/Loader',
    'qui/controls/buttons/Button',
    'qui/controls/desktop/Workspace',
    'qui/controls/desktop/Column',
    'qui/controls/desktop/Panel',
    'qui/controls/desktop/Tasks',
    'qui/controls/windows/Popup',
    'qui/controls/windows/Confirm',
    'qui/controls/messages/Panel',
    'qui/controls/contextmenu/Item',
    'qui/controls/contextmenu/Separator',
    'qui/utils/Controls',

    'controls/desktop/panels/Bookmarks',
    'controls/projects/project/Panel',
    'controls/grid/Grid',
    'Ajax',
    'Locale',
    'UploadManager',
    'Mustache',

    'text!controls/workspace/Create.html',
    'css!controls/workspace/Manager.css'

], function () {
    "use strict";

    var QUI                     = arguments[0],
        QUIControl              = arguments[1],
        QUILoader               = arguments[2],
        QUIButton               = arguments[3],
        QUIWorkspace            = arguments[4],
        QUIColumn               = arguments[5],
        QUIPanel                = arguments[6],
        QUITasks                = arguments[7],
        QUIWindow               = arguments[8],
        QUIConfirm              = arguments[9],
        QUIMessagePanel         = arguments[10],
        QUIContextmenuItem      = arguments[11],
        QUIContextmenuSeparator = arguments[12],
        QUIControlUtils         = arguments[13],

        BookmarkPanel           = arguments[14],
        ProjectPanel            = arguments[15],
        Grid                    = arguments[16],
        Ajax                    = arguments[17],
        Locale                  = arguments[18],
        UploadManager           = arguments[19],
        Mustache                = arguments[20],
        templateCreate          = arguments[21];


    return new Class({

        Extends: QUIControl,
        Type   : 'controls/workspace/Manager',

        Binds: [
            'resize',
            'save',
            '$onInject',
            '$onColumnContextMenu',
            '$onColumnContextMenuBlur'
        ],

        options: {
            autoResize : true, // resize workspace on window resize
            workspaceId: false
        },

        initialize: function (options) {
            this.parent(options);

            this.Loader    = new QUILoader();
            this.Workspace = new QUIWorkspace({
                events: {
                    onColumnContextMenu: this.$onColumnContextMenu
                }
            });

            this.$spaces = {};

            this.$minWidth   = false;
            this.$minHeight  = false;
            this.$ParentNode = null;

            this.$availablePanels = null; // cache
            this.$resizeDelay     = null;

            this.$resizeQuestionWindow = false; // if resize quesion window open?

            this.addEvents({
                onInject: this.$onInject
            });

            if (this.getAttribute('autoResize')) {
                var self = this;

                window.addEvent('resize', function () {
                    // delay,
                    if (self.$resizeDelay) {
                        clearTimeout(self.$resizeDelay);
                    }

                    self.$resizeDelay = (function () {
                        self.resize();
                    }).delay(200);
                });
            }
        },

        /**
         * Create the DOMNode Element
         *
         * @return {Element}
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class': 'qui-workspace-manager',
                styles : {
                    overflow: 'hidden'
                }
            });


            this.Loader.inject(this.$Elm);
            this.Workspace.inject(this.$Elm);

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.$ParentNode = this.$Elm.getParent();

            this.load();
        },

        /**
         * resize the workspace
         */
        resize: function () {
            if (this.$resizeQuestionWindow) {
                return;
            }

            if (!this.$ParentNode) {
                return;
            }

            var size   = this.$ParentNode.getSize(),
                width  = size.x,
                height = size.y,

                rq     = false;

            this.$Elm.setStyle('overflow', null);

            if (this.$minWidth && width < this.$minWidth) {
                width = this.$minWidth;
                rq    = true;

                this.$Elm.setStyle('overflow', 'auto');
            }

            if (this.$minHeight && height < this.$minHeight) {
                height = this.$minHeight;
                rq     = true;

                this.$Elm.setStyle('overflow', 'auto');
            }

            this.Workspace.setWidth(width);
            this.Workspace.setHeight(height);
            this.Workspace.resize();

            if (!rq) {
                return;
            }

            this.openResizQuestionWindow();
        },

        /**
         * load the workspace for the user
         *
         * @param {Function} [callback] - (optional) callback function
         */
        load: function (callback) {
            var self = this;

            this.Loader.show();

            Ajax.get('ajax_desktop_workspace_load', function (list) {
                if (!list || !list.length) {
                    // create default workspaces
                    var colums2, colums3;

                    var Workspace = new QUIWorkspace(),
                        Parent    = self.$Elm.clone();

                    Workspace.inject(Parent);

                    // 2 columns
                    self.$loadDefault2Column(Workspace);

                    colums2 = {
                        title    : Locale.get('quiqqer/quiqqer', 'workspaces.2.columns'),
                        data     : JSON.encode(Workspace.serialize()),
                        minHeight: self.$minHeight,
                        minWidth : self.$minWidth
                    };

                    // 3 columns
                    Workspace.clear();

                    self.$loadDefault3Column(Workspace);

                    colums3 = {
                        title    : Locale.get('quiqqer/quiqqer', 'workspaces.3.columns'),
                        data     : JSON.encode(Workspace.serialize()),
                        minHeight: self.$minHeight,
                        minWidth : self.$minWidth
                    };

                    // add workspaces
                    self.add(colums2, function () {
                        self.add(colums3, function () {
                            self.load(callback);
                        });
                    });

                    return;
                }

                self.$spaces = {};


                var Standard = false;

                for (var i = 0, len = list.length; i < len; i++) {
                    self.$spaces[list[i].id] = list[i];

                    if (list[i].standard &&
                        (list[i].standard).toInt() === 1) {
                        Standard = list[i];
                    }
                }

                self.fireEvent('workspaceLoaded', [self]);

                if (typeof callback !== 'undefined') {
                    callback();
                }

                // ask which workspace
                if (!Standard) {
                    self.$openWorkspaceListWindow();
                    return;
                }

                // load standard workspace
                self.$loadWorkspace(Standard.id);
            });
        },

        /**
         * Return the workspace list, available workspaces
         *
         * @return {Object} List
         */
        getList: function () {
            return this.$spaces;
        },

        /**
         * Insert a control into a Column
         *
         * @param {String} panelRequire - panel require
         * @param {Object} Column - qui/controls/desktop/Column, Parent Column
         */
        appendControlToColumn: function (panelRequire, Column) {
            require([panelRequire], function (Cls) {
                if (QUI.Controls.isControl(Cls)) {
                    Column.appendChild(Cls);
                    return;
                }

                Column.appendChild(new Cls());
            });
        },

        /**
         * Return all available panels
         *
         * @param {Function} callback - callback function
         */
        getAvailablePanels: function (callback) {
            if (this.$availablePanels) {
                callback(this.$availablePanels);
                return;
            }

            var self = this;

            // loads available panels
            Ajax.get('ajax_desktop_workspace_getAvailablePanels', function (panels) {
                self.$availablePanels = panels;

                callback(panels);
            });
        },

        /**
         * load another Workspace
         * Saves the current workspace and load the new wanted
         *
         * @param {Number} id - workspace id
         */
        loadWorkspace: function (id) {
            if (typeof this.$spaces[id] === 'undefined') {
                QUI.getMessageHandler(function (MH) {
                    MH.addError(Locale.get('quiqqer/quiqqer', 'message.workspace.not.found'));
                });

                return;
            }

            this.Loader.show();

            this.save();
            this.Workspace.clear();

            var data = null;

            try {
                data = JSON.decode(this.$spaces[id].data);
            } catch (e) {
            }


            if (!data) {
                this.$loadDefault2Column(this.Workspace);
            } else {
                this.Workspace.unserialize(data);
                this.Workspace.fix();
                this.Workspace.resize();
            }

            this.setAttribute('workspaceId', id);

            var self = this;

            Ajax.post('ajax_desktop_workspace_setStandard', function () {
                self.fireEvent('loadWorkspace', [self]);
                self.Loader.hide();
                self.Workspace.focus();
            }, {
                'package': 'quiqqer/tags',
                id       : id
            });
        },

        /**
         * Load a workspace
         *
         * @param {Number} id
         */
        $loadWorkspace: function (id) {
            this.Loader.show();

            if (typeof id !== 'undefined') {
                id = id.toInt();
            }

            if (!id || typeOf(id) !== 'number') {
                this.$useBestWorkspace();
                return;
            }

            if (typeof this.$spaces[id] === 'undefined') {
                QUI.getMessageHandler(function (MH) {
                    MH.addError(Locale.get('quiqqer/quiqqer', 'message.workspace.not.found'));
                });

                this.Loader.hide();
                return;
            }

            var self      = this,
                workspace = this.$spaces[id];

            this.$minWidth  = workspace.minWidth;
            this.$minHeight = workspace.minHeight;

            var data = null;

            try {
                data = JSON.decode(workspace.data);
            } catch (e) {
            }

            if (!data) {
                QUI.getMessageHandler().then(function (MH) {
                    MH.addError(Locale.get('quiqqer/quiqqer', 'message.error.in.workspace'));
                });

                this.Workspace.clear();

                // load standard
                new QUIConfirm({
                    icon       : 'fa fa-laptop',
                    title      : Locale.get('quiqqer/quiqqer', 'window.workspace.corrupt.title'),
                    text       : Locale.get('quiqqer/quiqqer', 'window.workspace.corrupt.text'),
                    texticon   : false,
                    information: Locale.get('quiqqer/quiqqer', 'window.workspace.corrupt.information'),
                    maxHeight  : 600,
                    maxWidth   : 800,
                    autoclose  : false,
                    events     : {
                        onOpen: function (Win) {
                            var Content = Win.getContent();
                            var Active  = null;

                            var activate = function (Btn) {
                                if (Active !== null) {
                                    Active.setNormal();
                                }

                                Btn.setActive();
                                Active = Btn;
                            };

                            var Container = new Element('div', {
                                styles: {
                                    margin   : 10,
                                    textAlign: 'center',
                                    width    : '100%'
                                }
                            }).inject(Content.getElement('.information'), 'after');

                            var TwoColumns = new QUIButton({
                                name  : 'twoColumns',
                                text  : Locale.get('quiqqer/quiqqer', 'workspaces.2.columns'),
                                styles: {
                                    'float': 'none',
                                    margin : 5,
                                    width  : 160
                                },
                                events: {
                                    onClick: activate
                                }
                            }).inject(Container);

                            new QUIButton({
                                name  : 'threeColumns',
                                text  : Locale.get('quiqqer/quiqqer', 'workspaces.3.columns'),
                                styles: {
                                    'float': 'none',
                                    margin : 5,
                                    width  : 160
                                },
                                events: {
                                    onClick: activate
                                }
                            }).inject(Container);

                            activate(TwoColumns);
                        },

                        onSubmit: function (Win) {
                            Win.Loader.show();

                            var Content        = Win.getContent(),
                                TwoColumnsNode = Content.getElement('[name="twoColumns"]');

                            var TwoColumns = QUI.Controls.getById(TwoColumnsNode.get('data-quiid'));
                            var Prom       = TwoColumns.isActive() ? self.getTwoColumnDefault() : self.getThreeColumnDefault();

                            Prom.then(function (result) {
                                self.Workspace.unserialize(JSON.decode(result));
                                self.Workspace.fix();
                                self.Workspace.resize();
                                self.setAttribute('workspaceId', id);

                                Win.close();
                                self.Loader.hide();
                            });
                        }
                    }
                }).open();

                //this.Loader.hide();
                return;
            }

            // cleanup
            var c, i, len, clen, children;

            for (i = 0, len = data.length; i < len; i++) {
                children = data[i].children;

                for (c = 0, clen = children.length; c < clen; c++) {
                    if (children[c].type === 'qui/controls/desktop/Tasks') {
                        delete data[i].children[c].attributes.limit;
                        continue;
                    }

                    if (children[c].type === 'qui/controls/messages/Panel') {
                        data[i].children[c].attributes.title = Locale.get(
                            'quiqqer/quiqqer',
                            'panels.messages.title'
                        );
                    }
                }
            }

            this.Workspace.clear();
            this.Workspace.unserialize(data);

            this.Workspace.fix();
            this.Workspace.resize();
            this.setAttribute('workspaceId', id);

            this.Loader.hide();
        },

        /**
         * Opens a window with the workspace list
         * the user can choose the standard workspace
         */
        $openWorkspaceListWindow: function () {
            var self = this;

            // workaround
            require([
                'css!' + URL_OPT_DIR + 'bin/qui/extend/elements.css',
                'css!' + URL_OPT_DIR + 'bin/qui/extend/buttons.css',
                'css!' + URL_OPT_DIR + 'bin/qui/extend/classes.css',
                'css!' + URL_BIN_DIR + 'css/style.css'
            ], function () {
                new QUIWindow({
                    title    : Locale.get('quiqqer/quiqqer', 'window.workspaces.select.title'),
                    maxHeight: 200,
                    maxWidth : 500,
                    autoclose: false,
                    buttons  : false,
                    events   : {
                        onOpen: function (Win) {
                            var Body = Win.getContent().set(
                                'html',
                                Locale.get('quiqqer/quiqqer', 'window.workspaces.select.text') + '<select></select>'
                            );

                            var Select = Body.getElement('select');

                            Select.setStyles({
                                display: 'block',
                                margin : '10px auto',
                                width  : 200
                            });

                            Select.addEvents({
                                change: function () {
                                    var value = this.value;

                                    Win.Loader.show();

                                    Ajax.post('ajax_desktop_workspace_setStandard', function () {
                                        self.$loadWorkspace(value);
                                        Win.close();
                                    }, {
                                        id: value
                                    });
                                }
                            });

                            new Element('option', {
                                html : '',
                                value: ''
                            }).inject(Select);

                            for (var i in self.$spaces) {
                                if (self.$spaces.hasOwnProperty(i)) {
                                    new Element('option', {
                                        html : self.$spaces[i].title,
                                        value: self.$spaces[i].id
                                    }).inject(Select);
                                }
                            }
                        },

                        onCancel: function () {
                            self.$useBestWorkspace();
                        }
                    }
                }).open();
            });
        },

        /**
         * Search the best workspace that fits in space
         */
        $useBestWorkspace: function () {
            this.Loader.hide();
        },

        /**
         * Save the workspace
         *
         * @param {Boolean} [async] - (optional) asynchrone save, default = false
         * @param {Function} [callback] - (optional) callback function, triggered only at async=true
         */
        save: function (async, callback) {
            var workspace = this.Workspace.serialize();

            if (!workspace.length) {
                return;
            }

            if (typeof async !== 'undefined' && async) {
                Ajax.post('ajax_desktop_workspace_save', function () {
                    if (typeof callback !== 'undefined') {
                        callback();
                    }

                }, {
                    data: JSON.encode(workspace),
                    id  : this.getAttribute('workspaceId')
                });

                return;
            }

            // Send the beacon
            if (typeof navigator.sendBeacon !== 'undefined') {
                var data = {
                    _rf      : JSON.encode(['ajax_desktop_workspace_save']),
                    data     : JSON.encode(workspace),
                    id       : this.getAttribute('workspaceId'),
                    _FRONTEND: 0
                };

                data = Object.toQueryString(data);
                navigator.sendBeacon(Ajax.$url + '?beacon=1', data);

                return;
            }

            Ajax.syncRequest('ajax_desktop_workspace_save', 'post', {
                data: JSON.encode(workspace),
                id  : this.getAttribute('workspaceId')
            });
        },

        /**
         * Add a Workspace
         *
         * @param {Object} data - workspace data {
         * 		title
         * 		data
         * 		minWidth
         * 		minHeight
         * }
         * @param {Function} callback - callback function
         */
        add: function (data, callback) {
            Ajax.post('ajax_desktop_workspace_add', function () {
                if (typeof callback !== 'undefined') {
                    callback();
                }

            }, {
                data: JSON.encode(data)
            });
        },

        /**
         * Edit a Workspace
         *
         * @param {Number} id - Workspace-ID
         * @param {Object} data - workspace data {
         * 		title [optional]
         * 		data [optional]
         * 		minWidth [optional]
         * 		minHeight [optional]
         * }
         * @param {Function} callback - callback function
         */
        edit: function (id, data, callback) {
            Ajax.post('ajax_desktop_workspace_edit', function () {
                if (typeof callback !== 'undefined') {
                    callback();
                }

            }, {
                id  : id,
                data: JSON.encode(data)
            });
        },

        /**
         * Delete workspaces
         *
         * @param {Array} ids - list of workspace ids
         * @param {Function} [callback] - (optional), callback function
         */
        del: function (ids, callback) {
            Ajax.post('ajax_desktop_workspace_delete', function () {
                if (typeof callback !== 'undefined') {
                    callback();
                }

            }, {
                ids: JSON.encode(ids)
            });
        },

        /**
         * unfix the workspace
         */
        unfix: function () {
            this.Workspace.unfix();
        },

        /**
         * fix the worksapce
         */
        fix: function () {
            this.Workspace.fix();
            this.save(true);
        },

        /**
         * load the default 3 column workspace
         *
         * @param {Object} Workspace - qui/controls/desktop/Workspace
         */
        $loadDefault3Column: function (Workspace) {
            this.$minWidth  = 1000;
            this.$minHeight = 500;

            var size   = this.$Elm.getSize(),
                panels = this.$getDefaultPanels();

            // Columns
            var LeftColumn   = new QUIColumn({
                    height: size.y
                }),

                MiddleColumn = new QUIColumn({
                    height: size.y,
                    width : size.x * 0.7
                }),

                RightColumn  = new QUIColumn({
                    height: size.y,
                    width : size.x * 0.3
                });


            Workspace.appendChild(LeftColumn);
            Workspace.appendChild(MiddleColumn);
            Workspace.appendChild(RightColumn);

            // panels
            panels.Bookmarks.setAttribute('height', 400);
            panels.Messages.setAttribute('height', 100);
            panels.Uploads.setAttribute('height', 300);

            // insert panels
            LeftColumn.appendChild(panels.Projects);
            LeftColumn.appendChild(panels.Bookmarks);

            MiddleColumn.appendChild(panels.Tasks);

            RightColumn.appendChild(panels.Messages);
            RightColumn.appendChild(panels.Uploads);

            Workspace.fix();
        },

        /**
         * loads the default 2 column workspace
         *
         * @param {Object} Workspace - qui/controls/desktop/Workspace
         */
        $loadDefault2Column: function (Workspace) {
            this.$minWidth  = 700;
            this.$minHeight = 500;

            var size         = this.$Elm.getSize(),
                panels       = this.$getDefaultPanels(),

                LeftColumn   = new QUIColumn({
                    height: size.y
                }),

                MiddleColumn = new QUIColumn({
                    height: size.y,
                    width : size.x - 400
                });


            Workspace.appendChild(LeftColumn);
            Workspace.appendChild(MiddleColumn);

            panels.Bookmarks.setAttribute('height', 300);
            panels.Messages.setAttribute('height', 100);
            panels.Uploads.setAttribute('height', 100);

            LeftColumn.appendChild(panels.Projects);
            LeftColumn.appendChild(panels.Bookmarks);
            LeftColumn.appendChild(panels.Messages);
            LeftColumn.appendChild(panels.Uploads);

            MiddleColumn.appendChild(panels.Tasks);

            Workspace.fix();
        },

        /**
         * Return the default panels
         *
         * @return {Object}
         */
        $getDefaultPanels: function () {
            var Bookmarks = new BookmarkPanel({
                title : 'Bookmarks',
                icon  : 'fa fa-bookmark',
                name  : 'qui-bookmarks',
                events: {
                    onInject: function (Panel) {
                        Panel.Loader.show();

                        require(['Users'], function (Users) {
                            var User = Users.get(USER.id);

                            User.load(function () {
                                var data = JSON.decode(User.getAttribute('qui-bookmarks'));

                                if (!data) {
                                    Panel.Loader.hide();
                                    return;
                                }

                                Panel.unserialize(data);
                                Panel.Loader.hide();
                            });
                        });
                    },

                    onAppendChild: function (Panel) {
                        Panel.Loader.show();

                        require(['Users'], function (Users) {
                            var User = Users.get(USER.id);

                            User.setAttribute('qui-bookmarks', JSON.encode(Panel.serialize()));

                            User.save(function () {
                                Panel.Loader.hide();
                            });
                        });
                    },

                    onRemoveChild: function (Panel) {
                        Panel.Loader.show();

                        require(['Users'], function (Users) {
                            var User = Users.get(USER.id);

                            User.setExtra('qui-bookmarks', JSON.encode(Panel.serialize()));

                            User.save(function () {
                                Panel.Loader.hide();
                            });
                        });
                    }
                }
            });


            // task panel
            var Tasks = new QUITasks({
                title: 'My Panel 1',
                icon : 'fa fa-heart',
                name : 'tasks'
            });

            return {
                Projects : new ProjectPanel(),
                Bookmarks: Bookmarks,
                Tasks    : Tasks,
                Messages : new QUIMessagePanel(),
                Uploads  : UploadManager
            };
        },

        /**
         * Column helpers
         */

        /**
         * event : on workspace context menu -> on column context menu
         * Create the contextmenu for the column edit
         *
         * @param {Object} Workspace - qui/controls/desktop/Workspace
         * @param {Object} Column - qui/controls/desktop/Column
         * @param {DOMEvent} event
         */
        $onColumnContextMenu: function (Workspace, Column, event) {
            event.stop();

            Column.highlight();

            var self   = this,
                Menu   = Column.$ContextMenu,
                panels = Column.getChildren();

            Menu.addEvents({
                onBlur: this.$onColumnContextMenuBlur
            });

            Menu.clearChildren();
            Menu.setTitle('Column');

            // add panels
            Menu.appendChild(
                new QUIContextmenuItem({
                    text  : Locale.get('quiqqer/quiqqer', 'workspace.contextmenu.add.panel'),
                    icon  : 'fa fa-plus',
                    name  : 'addPanelsToColumn',
                    events: {
                        onClick: function () {
                            self.openPanelList(Column);
                        }
                    }
                })
            );


            // remove panels
            if (Object.getLength(panels)) {
                // remove panels
                var RemovePanels = new QUIContextmenuItem({
                    text: Locale.get('quiqqer/quiqqer', 'workspace.contextmenu.remove.panel'),
                    name: 'removePanelOfColumn',
                    icon: 'fa fa-trash-o'
                });

                Menu.appendChild(RemovePanels);

                Object.each(panels, function (Panel) {
                    RemovePanels.appendChild(
                        new QUIContextmenuItem({
                            text  : Panel.getAttribute('title'),
                            icon  : Panel.getAttribute('icon'),
                            name  : Panel.getAttribute('name'),
                            Panel : Panel,
                            events: {
                                onActive   : self.$onEnterRemovePanel,
                                onNormal   : self.$onLeaveRemovePanel,
                                onMouseDown: self.$onClickRemovePanel
                            }
                        })
                    );
                });
            }

            Menu.appendChild(new QUIContextmenuSeparator());

            // add columns
            var AddColumn = new QUIContextmenuItem({
                text: Locale.get('quiqqer/quiqqer', 'workspace.contextmenu.add.column'),
                name: 'add_columns',
                icon: 'fa fa-plus'
            });

            AddColumn.appendChild(
                new QUIContextmenuItem({
                    text  : Locale.get('quiqqer/quiqqer', 'workspace.contextmenu.add.column.before'),
                    name  : 'addColumnBefore',
                    icon  : 'fa fa-long-arrow-left',
                    events: {
                        onClick: function () {
                            self.Workspace.appendChild(
                                new QUIColumn({
                                    height: '100%',
                                    width : 200
                                }),
                                'before',
                                Column
                            );
                        }
                    }
                })
            ).appendChild(
                new QUIContextmenuItem({
                    text  : Locale.get('quiqqer/quiqqer', 'workspace.contextmenu.add.column.after'),
                    name  : 'addColumnAfter',
                    icon  : 'fa fa-long-arrow-right',
                    events: {
                        onClick: function () {
                            self.Workspace.appendChild(
                                new QUIColumn({
                                    height: '100%',
                                    width : 200
                                }),
                                'after',
                                Column
                            );
                        }
                    }
                })
            );

            Menu.appendChild(AddColumn);


            // remove column
            Menu.appendChild(
                new QUIContextmenuItem({
                    text  : Locale.get('quiqqer/quiqqer', 'workspace.contextmenu.delete.column'),
                    icon  : 'fa fa-trash-o',
                    name  : 'removeColumn',
                    events: {
                        onClick: function () {
                            Column.destroy();
                        }
                    }
                })
            );


            Menu.setPosition(
                event.page.x,
                event.page.y
            ).show().focus();
        },

        /**
         * event : column context onBlur
         *
         * @param {Object} Menu - qui/controls/contextmenu/Menu
         */
        $onColumnContextMenuBlur: function (Menu) {
            Menu.getAttribute('Column').normalize();
            Menu.removeEvent('onBlur', this.$onColumnContextMenuBlur);
        },

        /**
         * event : on mouse enter at a contextmenu item -> remove panel
         *
         * @param {Object} Item - qui/controls/contextmenu/Item
         */
        $onEnterRemovePanel: function (Item) {
            Item.getAttribute('Panel').highlight();
        },

        /**
         * event : on mouse leave at a contextmenu item -> remove panel
         *
         * @param {Object} Item - qui/controls/contextmenu/Item
         */
        $onLeaveRemovePanel: function (Item) {
            Item.getAttribute('Panel').normalize();
        },

        /**
         * event : on mouse click at a contextmenu item -> remove panel
         *
         * @param {Object} ContextItem - qui/controls/contextmenu/Item
         */
        $onClickRemovePanel: function (ContextItem) {
            ContextItem.getAttribute('Panel').destroy();

            this.focus();
        },

        /**
         * Return the two column workspace default settings
         *
         * @return {Promise|*}
         */
        getTwoColumnDefault: function () {
            return new Promise(function (resolve) {
                Ajax.get('ajax_desktop_workspace_twoColumns', resolve);
            });
        },

        /**
         * Return the three column workspace default settings
         *
         * @return {Promise|*}
         */
        getThreeColumnDefault: function () {
            return new Promise(function (resolve) {
                Ajax.get('ajax_desktop_workspace_threeColumns', resolve);
            });
        },

        /**
         * windows
         */

        /**
         * Opens the create workspace window
         */
        openCreateWindow: function () {
            var self = this;

            new QUIConfirm({
                title        : Locale.get('quiqqer/quiqqer', 'window.workspaces.add'),
                icon         : 'fa fa-laptop',
                maxWidth     : 600,
                maxHeight    : 600,
                autoclose    : false,
                ok_button    : {
                    text     : Locale.get('quiqqer/quiqqer', 'create'),
                    textimage: 'fa fa-check'
                },
                cancel_button: {
                    text     : Locale.get('quiqqer/quiqqer', 'cancel'),
                    textimage: 'fa fa-remove'
                },
                events       : {
                    onOpen: function (Win) {
                        var Content = Win.getContent(),
                            id      = Win.getId(),
                            size    = document.getSize();

                        Content.addClass('qui-workspace-manager-window');
                        Content.set('html', Mustache.render(templateCreate, {
                            id            : id,
                            size          : size,
                            title         : Locale.get('quiqqer/quiqqer', 'window.workspaces.create.header.title'),
                            labelTitle    : Locale.get('quiqqer/system', 'title'),
                            labelCols     : Locale.get('quiqqer/quiqqer', 'window.workspaces.create.cols'),
                            titleUsage    : Locale.get('quiqqer/quiqqer', 'window.workspaces.create.usage'),
                            labelMinWidth : Locale.get('quiqqer/quiqqer', 'window.workspaces.create.usage.minWidth'),
                            labelMinHeight: Locale.get('quiqqer/quiqqer', 'window.workspaces.create.usage.minWidth')
                        }));
                    },

                    onClose: function () {
                        self.openWorkspaceEdit();
                    },

                    onSubmit: function (Win) {
                        var Content   = Win.getContent(),
                            size      = document.getSize(),

                            Title     = Content.getElement('input[name="workspace-title"]'),
                            Columns   = Content.getElement('input[name="workspace-columns"]'),
                            minWidth  = Content.getElement('input[name="workspace-minWidth"]'),
                            minHeight = Content.getElement('input[name="workspace-minHeight"]');

                        if (Title.value === '') {
                            return;
                        }

                        if (Columns.value === '') {
                            return;
                        }


                        Win.Loader.show();

                        // create workspace for serialize
                        var Workspace = new QUIWorkspace(),
                            Parent    = self.$Elm.clone(),
                            columns   = (Columns.value).toInt();

                        Workspace.inject(Parent);

                        for (var i = 0; i < columns; i++) {
                            Workspace.appendChild(
                                new QUIColumn({
                                    height: size.y,
                                    width : (size.x / columns).ceil()
                                })
                            );
                        }

                        self.add({
                            title    : Title.value,
                            data     : JSON.encode(Workspace.serialize()),
                            minWidth : minWidth.value,
                            minHeight: minHeight.value
                        }, function () {
                            Win.close();

                            self.load();
                        });
                    }
                }
            }).open();
        },

        /**
         * Open available panel window
         *
         * @param {Object} Column - qui/controls/desktop/Column, parent column
         */
        openPanelList: function (Column) {
            if (typeof Column === 'undefined') {
                return;
            }

            var self = this;

            new QUIWindow({
                title    : Locale.get('quiqqer/quiqqer', 'window.workspaces.panel.list.title'),
                buttons  : false,
                maxWidth : 500,
                maxHeight: 700,
                events   : {
                    onResize: function () {
                        Column.highlight();
                    },

                    onOpen: function (Win) {
                        Win.Loader.show();

                        Column.highlight();

                        var click = function (event) {
                            var Target = event.target;

                            if (Target.nodeName !== 'div') {
                                Target = Target.getParent('div');
                            }

                            self.appendControlToColumn(
                                Target.get('data-require'),
                                Column
                            );
                        };

                        // loads available panels
                        self.getAvailablePanels(function (panels) {
                            var i, len, Elm, Icon;
                            var Content = Win.getContent();

                            for (i = 0, len = panels.length; i < len; i++) {
                                Icon = false;

                                Elm = new Element('div', {
                                    html          : '<h2>' + panels[i].title + '</h2>' +
                                        '<p>' + panels[i].text + '</p>',
                                    'class'       : 'qui-controls-workspace-panelList-panel smooth',
                                    'data-require': panels[i].require,
                                    events        : {
                                        click: click
                                    }
                                }).inject(Content);


                                if (QUIControlUtils.isFontAwesomeClass(panels[i].image)) {
                                    Icon = new Element('div', {
                                        'class': 'qui-controls-workspace-panelList-panel-icon'
                                    });

                                    Icon.addClass(panels[i].image);
                                    Icon.inject(Elm, 'top');
                                }
                            }

                            Win.Loader.hide();
                        });
                    },

                    onCancel: function () {
                        Column.normalize();
                    }
                }
            }).open();
        },

        /**
         * opens the workspace edit window
         * edit / delete your workspaces
         */
        openWorkspaceEdit: function () {
            var self = this;

            new QUIWindow({
                title    : Locale.get('quiqqer/quiqqer', 'window.workspaces.title'),
                buttons  : false,
                maxWidth : 800,
                maxHeight: 600,
                events   : {
                    onOpen: function (Win) {
                        Win.Loader.show();

                        var Content       = Win.getContent(),
                            size          = Content.getSize(),
                            GridContainer = new Element('div').inject(Content);

                        new Element('p', {
                            html  : Locale.get('quiqqer/quiqqer', 'window.workspaces.message'),
                            styles: {
                                marginBottom: 10
                            }
                        }).inject(Content, 'top');

                        var EditGrid = new Grid(GridContainer, {
                            columnModel      : [
                                {
                                    dataIndex: 'id',
                                    dataType : 'Integer',
                                    hidden   : true
                                }, {
                                    header   : Locale.get('quiqqer/system', 'title'),
                                    dataIndex: 'title',
                                    dataType : 'string',
                                    width    : 200,
                                    editable : true
                                }, {
                                    header   : Locale.get('quiqqer/quiqqer', 'window.workspaces.width'),
                                    dataIndex: 'minWidth',
                                    dataType : 'string',
                                    width    : 100,
                                    editable : true
                                }, {
                                    header   : Locale.get('quiqqer/quiqqer', 'window.workspaces.height'),
                                    dataIndex: 'minHeight',
                                    dataType : 'string',
                                    width    : 100,
                                    editable : true
                                }
                            ],
                            buttons          : [
                                {
                                    name     : 'add',
                                    title    : Locale.get('quiqqer/quiqqer', 'window.workspaces.add'),
                                    text     : Locale.get('quiqqer/quiqqer', 'add'),
                                    textimage: 'fa fa-plus',
                                    events   : {
                                        onClick: function () {
                                            Win.close();
                                            self.openCreateWindow();
                                        }
                                    }
                                }, {
                                    type: 'separator'
                                }, {
                                    name     : 'delete',
                                    title    : Locale.get('quiqqer/quiqqer', 'window.workspaces.delete'),
                                    text     : Locale.get('quiqqer/system', 'delete'),
                                    textimage: 'fa fa-trash-o',
                                    disabled : true,
                                    events   : {
                                        onClick: function (Btn) {
                                            // delete selected workspaces
                                            var Grid = Btn.getAttribute('Grid'),
                                                data = Grid.getSelectedData(),
                                                ids  = [];

                                            for (var i = 0, len = data.length; i < len; i++) {
                                                ids.push(data[i].id);
                                            }

                                            Win.close();

                                            new QUIConfirm({
                                                name       : 'delete',
                                                icon       : 'fa fa-trash-o',
                                                title      : Locale.get('quiqqer/quiqqer',
                                                    'window.workspaces.delete.title'),
                                                text       : Locale.get('quiqqer/quiqqer',
                                                    'window.workspaces.delete.text'),
                                                information: Locale.get('quiqqer/quiqqer',
                                                    'window.workspaces.delete.information', {
                                                        ids: ids.join(',')
                                                    }),
                                                ok_button  : {
                                                    text     : Locale.get('quiqqer/system', 'delete'),
                                                    textimage: 'fa fa-trash'
                                                },
                                                texticon   : 'fa fa-trash-o',
                                                maxWidth   : 450,
                                                maxHeight  : 300,
                                                autoclose  : false,
                                                events     : {
                                                    onCancel: function () {
                                                        self.openWorkspaceEdit();
                                                    },
                                                    onSubmit: function (Win) {
                                                        Win.Loader.show();

                                                        self.del(ids, function () {
                                                            self.load(function () {
                                                                Win.close();

                                                                self.openWorkspaceEdit();
                                                            });
                                                        });
                                                    }
                                                }
                                            }).open();
                                        }
                                    }
                                }, {
                                    name  : '',
                                    text  : Locale.get('quiqqer/quiqqer', 'workspace.fixed'),
                                    styles: {
                                        'float': 'right'
                                    },
                                    events: {
                                        onClick: function (Btn) {
                                            if (self.Workspace.$fixed) {
                                                self.unfix();
                                                Btn.setAttribute('text',
                                                    Locale.get('quiqqer/quiqqer', 'workspace.flexible'));
                                                Btn.setAttribute('status', 0);
                                                return;
                                            }

                                            self.fix();
                                            Btn.setAttribute('text', Locale.get('quiqqer/quiqqer', 'workspace.fixed'));
                                            Btn.setAttribute('status', 1);
                                        }
                                    }
                                }
                            ],
                            showHeader       : true,
                            sortHeader       : true,
                            width            : size.x - 40,
                            height           : size.y - 60,
                            multipleSelection: true,
                            editable         : true,
                            editondblclick   : true
                        });

                        var workspaces = self.getList(),
                            data       = [];

                        Object.each(workspaces, function (Workspace) {
                            data.push(Workspace);
                        });

                        EditGrid.setData({
                            data: data
                        });

                        EditGrid.addEvents({
                            onClick: function () {
                                var DelButton = EditGrid.getButtons().filter(function (Btn) {
                                    return Btn.getAttribute('name') === 'delete';
                                })[0];

                                var sels = EditGrid.getSelectedData();

                                if (sels.length) {
                                    DelButton.enable();
                                } else {
                                    DelButton.disable();
                                }
                            },

                            onEditComplete: function (data) {
                                Win.Loader.show();

                                var index   = data.columnModel.dataIndex,
                                    Data    = EditGrid.getDataByRow(data.row),
                                    newData = {};

                                newData[index] = data.input.value;

                                self.edit(Data.id, newData, function () {
                                    self.load(function () {
                                        Win.Loader.hide();
                                    });
                                });
                            }
                        });

                        EditGrid.resize();
                        Win.Loader.hide();
                    }
                }
            }).open();
        },

        /**
         * Opens the question window, if the workspace is to big for the window
         *
         * @todo muss konzeptioniert werden, Verhalten ist etwas merkwürdig
         */
        openResizQuestionWindow: function () {
            return;
            if (this.$resizeQuestionWindow) {
                return;
            }

            if (!this.$ParentNode) {
                return;
            }

            this.$resizeQuestionWindow = true;

            var self = this,
                size = this.$ParentNode.getSize();


            new QUIConfirm({
                title        : Locale.get('quiqqer/system', 'workspace.toolarge.window.title'),
                autoclose    : false,
                maxWidth     : 600,
                maxHeight    : 400,
                texticon     : false,
                cancel_button: {
                    text     : Locale.get('quiqqer/system', 'cancel'),
                    textimage: 'fa fa-remove'
                },
                ok_button    : {
                    text     : Locale.get('quiqqer/system', 'ok'),
                    textimage: 'fa fa-check'
                },
                events       : {
                    onOpen: function (Win) {
                        var i, Select, Workspace;

                        var Content = Win.getContent(),
                            width   = size.x,
                            height  = size.y;

                        Content.set(
                            'html',

                            Locale.get('quiqqer/system', 'workspace.toolarge.window.text') +
                            '<select></select>'
                        );

                        Select = Content.getElement('select');

                        Select.setStyles({
                            clear  : 'both',
                            display: 'block',
                            margin : '0px auto',
                            width  : 200
                        });

                        for (i in self.$spaces) {
                            Workspace = self.$spaces[i];

                            if (width < Workspace.minWidth) {
                                continue;
                            }

                            if (height < Workspace.minHeight) {
                                continue;
                            }

                            new Element('option', {
                                value: Workspace.id,
                                html : Workspace.title
                            }).inject(Select);
                        }

                        if (Select.length) {
                            return;
                        }

                        // no workspaces available
                        Content.set(
                            'html',
                            Locale.get('quiqqer/system', 'workspace.toolarge.window.noWorkspaces.text')
                        );
                    },

                    onSubmit: function (Win) {
                        var Content = Win.getContent(),
                            Select  = Content.getElement('select');

                        // no workspaces available
                        if (!Select) {
                            self.openCreateWindow();
                            Win.close();

                            return;
                        }

                        if (Select.value === '') {
                            return;
                        }

                        self.loadWorkspace(Select.value);

                        Win.close();
                        self.resize();
                    },

                    onClose: function () {
                        self.$resizeQuestionWindow = false;
                    }
                }
            }).open();
        }
    });
});
