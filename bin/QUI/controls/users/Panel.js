/**
 * User Manager (View)
 *
 * @module controls/users/Panel
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/desktop/Panel
 * @require controls/grid/Grid
 * @require Users
 * @require qui/controls/messages/Attention
 * @require qui/controls/windows/Confirm
 * @require qui/controls/windows/Prompt
 * @require qui/controls/buttons/Button
 * @require utils/Template
 * @require utils/Controls
 * @require Locale
 * @require css!controls/users/Panel.css
 */
define('controls/users/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'controls/grid/Grid',
    'Users',
    'qui/controls/messages/Attention',
    'qui/controls/windows/Confirm',
    'qui/controls/windows/Prompt',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Switch',
    'utils/Template',
    'utils/Controls',
    'Locale',
    'Permissions',

    'css!controls/users/Panel.css'

], function () {
    "use strict";

    var lg = 'quiqqer/system';

    var QUI          = arguments[0],
        Panel        = arguments[1],
        Grid         = arguments[2],
        Users        = arguments[3],
        Attention    = arguments[4],
        QUIConfirm   = arguments[5],
        QUIPrompt    = arguments[6],
        QUIButton    = arguments[7],
        QUISwitch    = arguments[8],
        Template     = arguments[9],
        ControlUtils = arguments[10],
        QUILocale    = arguments[11],
        Permissions  = arguments[12];

    /**
     * @class controls/users/Panel
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: Panel,
        Type   : 'controls/users/Panel',

        Binds: [
            '$onCreate',
            '$onResize',
            '$onSwitchStatus',
            '$btnSwitchStatus',
            '$onDeleteUser',
            '$onUserRefresh',
            '$onButtonEditClick',
            '$onButtonDelClick',
            '$gridClick',
            '$gridDblClick',
            '$gridBlur',

            'search',
            'createUser'
        ],

        initialize: function (options) {
            this.$uid = String.uniqueID();

            // defaults
            this.setAttributes({
                field         : 'username',
                order         : 'ASC',
                limit         : 20,
                page          : 1,
                search        : false,
                searchSettings: {},
                tabbar        : false
            });

            this.parent(options);

            this.$Grid      = null;
            this.$Container = null;

            this.addEvent('onCreate', this.$onCreate);
            this.addEvent('onResize', this.$onResize);

            Users.addEvents({
                onSwitchStatus: this.$onSwitchStatus,
                onDelete      : this.$onDeleteUser,
                onRefresh     : this.$onUserRefresh,
                onSave        : this.$onUserRefresh
            });

            this.addEvent('onDestroy', function () {
                Users.removeEvents({
                    onSwitchStatus: this.$onSwitchStatus,
                    onDelete      : this.$onDeleteUser,
                    onRefresh     : this.$onUserRefresh,
                    onSave        : this.$onUserRefresh
                });
            }.bind(this));


            this.active_image = 'fa fa-check';
            this.active_text  = QUILocale.get(lg, 'users.panel.user.is.active');

            this.deactive_image = 'fa fa-remove';
            this.deactive_text  = QUILocale.get(lg, 'users.panel.user.is.deactive');
        },

        /**
         * Return the user grid
         *
         * @return {controls/grid/Grid|null}
         */
        getGrid: function () {
            return this.$Grid;
        },

        /**
         * create the user panel
         */
        $onCreate: function () {
            this.addButton({
                name  : 'userSearch',
                Users : this,
                alt   : QUILocale.get(lg, 'users.panel.btn.search.alt'),
                title : QUILocale.get(lg, 'users.panel.btn.search.title'),
                image : 'fa fa-search',
                events: {
                    onClick: this.search
                }
            });

            this.addButton({
                name: 'usep',
                type: 'separator'
            });

            this.addButton({
                name  : 'userNew',
                Users : this,
                events: {
                    onClick: this.createUser
                },
                text  : QUILocale.get(lg, 'users.panel.btn.create')
            });

            this.addButton({
                name     : 'userEdit',
                Users    : this,
                text     : QUILocale.get(lg, 'users.panel.btn.edit'),
                disabled : true,
                textimage: 'fa fa-edit',
                events   : {
                    onMousedown: this.$onButtonEditClick
                }
            });

            this.addButton({
                name     : 'userDel',
                Users    : this,
                text     : QUILocale.get(lg, 'users.panel.btn.delete'),
                disabled : true,
                textimage: 'fa fa-trash-o',
                events   : {
                    onMousedown: this.$onButtonDelClick
                }
            });

            // create grid
            var Body = this.getBody();

            this.$Container = new Element('div');
            this.$Container.inject(Body);

            this.$Grid = new Grid(this.$Container, {
                columnModel: [{
                    header   : QUILocale.get(lg, 'status'),
                    dataIndex: 'status',
                    dataType : 'QUI',
                    width    : 60
                }, {
                    header   : QUILocale.get(lg, 'user_id'),
                    dataIndex: 'id',
                    dataType : 'integer',
                    width    : 150
                }, {
                    header   : QUILocale.get(lg, 'username'),
                    dataIndex: 'username',
                    dataType : 'integer',
                    width    : 150
                }, {
                    header   : QUILocale.get(lg, 'group'),
                    dataIndex: 'usergroup',
                    dataType : 'integer',
                    width    : 150
                }, {
                    header   : QUILocale.get(lg, 'email'),
                    dataIndex: 'email',
                    dataType : 'string',
                    width    : 150
                }, {
                    header   : QUILocale.get(lg, 'firstname'),
                    dataIndex: 'firstname',
                    dataType : 'string',
                    width    : 150
                }, {
                    header   : QUILocale.get(lg, 'lastname'),
                    dataIndex: 'lastname',
                    dataType : 'string',
                    width    : 150
                }, {
                    header   : QUILocale.get(lg, 'c_date'),
                    dataIndex: 'regdate',
                    dataType : 'date',
                    width    : 150
                }],
                pagination : true,
                filterInput: true,
                perPage    : this.getAttribute('limit'),
                page       : this.getAttribute('page'),
                sortOn     : this.getAttribute('field'),
                serverSort : true,
                showHeader : true,
                sortHeader : true,
                width      : Body.getSize().x - 40,
                height     : Body.getSize().y - 40,
                onrefresh  : function (me) {
                    var options = me.options;

                    this.setAttribute('field', options.sortOn);
                    this.setAttribute('order', options.sortBy);
                    this.setAttribute('limit', options.perPage);
                    this.setAttribute('page', options.page);

                    this.load();

                }.bind(this),

                alternaterows    : true,
                resizeColumns    : true,
                selectable       : true,
                multipleSelection: true,
                resizeHeaderOnly : true
            });

            // Events
            this.$Grid.addEvents({
                onClick   : this.$gridClick,
                onDblClick: this.$gridDblClick,
                onBlur    : this.$gridBlur
            });

            // toolbar resize after insert
            (function () {
                this.getButtonBar().setAttribute('width', '98%');
                this.getButtonBar().resize();
            }).delay(200, this);

            // start and list the users
            this.load();
        },

        /**
         * Load the users with the settings
         *
         * @param {Function} [callback]
         */
        load: function (callback) {
            this.Loader.show();
            this.$loadUsers(callback);
        },

        /**
         * create a user panel
         *
         * @param {Number} uid - User-ID
         * @return {Object} this
         */
        openUser: function (uid) {
            require([
                'controls/users/User',
                'utils/Panels'
            ], function (User, PanelUtils) {
                PanelUtils.openPanelInTasks(new User(uid));
            });

            return this;
        },

        /**
         * Opens the users search settings
         */
        search: function () {
            this.Loader.show();

            var self  = this,
                Sheet = this.createSheet({
                    title      : QUILocale.get(lg, 'users.panel.search.title'),
                    icon       : 'fa fa-search',
                    closeButton: {
                        text: 'schließen'
                    }
                });

            Sheet.addEvent('onOpen', function (Sheet) {
                Template.get('users_searchtpl', function (result) {
                    var i, len, inputs, new_id, Frm, Search, Label;

                    var Body     = Sheet.getBody(),
                        settings = self.getAttribute('searchSettings'),

                        values   = Object.merge(
                            {},
                            settings.filter,
                            settings.fields
                        );

                    Body.set('html', result);


                    Frm    = Body.getElement('form');
                    Search = Frm.elements.search;

                    Search.addEvent('keyup', function (event) {
                        if (event.key === 'enter') {
                            self.execSearch(Sheet);
                        }
                    });

                    if (values.id) {
                        values.uid = values.id;
                    }

                    // elements
                    inputs = Frm.elements;

                    for (i = 0, len = inputs.length; i < len; i++) {
                        new_id = inputs[i].name + self.getId();

                        inputs[i].set('id', new_id);

                        if (values[inputs[i].name]) {
                            if (inputs[i].type === 'checkbox') {
                                inputs[i].checked = true;
                            } else {
                                inputs[i].value = values[inputs[i].name];
                            }
                        } else {
                            if (inputs[i].type === 'checkbox') {
                                inputs[i].checked = false;
                            } else {
                                inputs[i].value = '';
                            }
                        }

                        Label = Frm.getElement('label[for="' + inputs[i].name + '"]');

                        if (Label) {
                            Label.set('for', new_id);
                        }
                    }

                    Search.value = settings.userSearchString || '';
                    Search.focus();

                    // search button
                    new QUIButton({
                        textimage: 'fa fa-search',
                        text     : QUILocale.get(lg, 'users.panel.search.btn.start'),
                        events   : {
                            onClick: function () {
                                self.execSearch(Sheet);
                            }
                        }
                    }).inject(Search, 'after');

                    Sheet.addButton({
                        textimage: 'fa fa-search',
                        text     : QUILocale.get(lg, 'users.panel.search.btn.start'),
                        events   : {
                            onClick: function () {
                                self.execSearch(Sheet);
                            }
                        }
                    });


                    ControlUtils.parse(Body).then(function () {
                        return QUI.parse(Body);
                    }).then(function () {
                        self.Loader.hide();
                    });
                });
            });

            Sheet.show();
        },

        /**
         * Execute the search
         *
         * @param {Object} Sheet - qui/desktop/panels/Sheet
         */
        execSearch: function (Sheet) {
            var Frm = Sheet.getBody().getElement('form');

            this.setAttribute('search', true);

            // check if one checkbox is active
            if (!Frm.elements.uid.checked && !Frm.elements.username.checked && !Frm.elements.email.checked && !Frm.elements.firstname.checked && !Frm.elements.lastname.checked) {
                Frm.elements.uid.checked      = true;
                Frm.elements.username.checked = true;
            }

            this.setAttribute('searchSettings', {
                userSearchString: Frm.elements.search.value,
                fields          : {
                    id       : Frm.elements.uid.checked,
                    username : Frm.elements.username.checked,
                    email    : Frm.elements.email.checked,
                    firstname: Frm.elements.firstname.checked,
                    lastname : Frm.elements.lastname.checked
                },
                filter          : {
                    filter_status       : Frm.elements.filter_status.value,
                    filter_group        : Frm.elements.filter_group.value,
                    filter_regdate_first: Frm.elements.filter_regdate_first.value,
                    filter_regdate_last : Frm.elements.filter_regdate_last.value
                }
            });

            Sheet.hide();

            this.$loadUsers();
        },

        /**
         * Open the user create dialog
         */
        createUser: function () {
            var self = this;

            new QUIPrompt({
                name       : 'CreateUser',
                title      : QUILocale.get(lg, 'users.panel.create.window.title'),
                icon       : 'fa fa-user',
                titleicon  : false,
                text       : QUILocale.get(lg, 'users.panel.create.window.text'),
                information: QUILocale.get(lg, 'users.panel.create.window.information'),

                maxWidth : 600,
                maxHeight: 400,

                check: function (Win) {
                    Win.Loader.show();

                    Users.existsUsername(Win.getValue(), function (result) {
                        // Benutzer existiert schon
                        if (result === true) {
                            QUI.getMessageHandler(function (MH) {
                                MH.addAttention(
                                    QUILocale.get(lg, 'exception.create.user.exists')
                                );
                            });

                            Win.Loader.hide();
                            return;
                        }

                        Win.fireEvent('onsubmit', [Win.getValue(), Win]);
                        Win.close();
                    });

                    return false;
                },

                events: {
                    onOpen: function (Win) {
                        Win.getContent()
                           .getElement('.qui-windows-prompt-information')
                           .setStyle('paddingBottom', 20);

                        Win.Loader.show();


                        Permissions.hasPermission(
                            'quiqqer.admin.users.create'
                        ).then(function (hasPermission) {
                            if (!hasPermission) {
                                QUI.getMessageHandler().then(function (MH) {
                                    MH.addError(
                                        QUILocale.get('quiqqer/system', 'exception.no.permission')
                                    );
                                });

                                Win.close();
                            }

                            Win.Loader.hide();
                        });
                    },

                    // own event, line 488
                    onsubmit: function (value) {
                        Users.createUser(value, function (result) {
                            self.openUser(result);
                        });
                    }
                }
            }).open();
        },


        /**
         * onclick on the grid
         */
        $gridClick: function (data) {
            var len    = data.target.selected.length,
                Edit   = this.getButtons('userEdit'),
                Delete = this.getButtons('userDel');

            if (len === 0) {
                Edit.disable();
                Delete.disable();

                return;
            }

            Edit.enable();
            Delete.enable();

            data.evt.stop();
        },

        /**
         * dblclick on the grid
         */
        $gridDblClick: function (data) {
            this.openUser(
                data.target.getDataByRow(data.row).id
            );
        },

        /**
         * onblur on the grid
         */
        $gridBlur: function () {
            this.getGrid().unselectAll();
            this.getGrid().removeSections();

            this.getButtons('userEdit').disable();
            this.getButtons('userDel').disable();
        },

        /**
         * Resize the users panel
         */
        $onResize: function () {
            var Body = this.getBody();

            if (!Body) {
                return;
            }

            if (!this.getGrid()) {
                return;
            }

            if (this.getAttribute('search')) {
                this.getGrid().setHeight(Body.getSize().y - 120);

            } else {
                this.getGrid().setHeight(Body.getSize().y - 40);
            }

            var Message = Body.getElement('.messages-message');

            if (Message) {
                Message.setStyle('width', this.getBody().getSize().x - 40);
            }

            this.getGrid().setWidth(Body.getSize().x - 40);


            // resize switches
            var i, len, Control;
            var switches = Body.getElements('.qui-switch');

            for (i = 0, len = switches.length; i < len; i++) {
                Control = QUI.Controls.getById(switches[i].get('data-quiid'));

                if (Control) {
                    Control.resize();
                }
            }
        },

        /**
         * Load the users to the grid
         *
         * @param {Function} [callback]
         */
        $loadUsers: function (callback) {
            var self = this;

            this.Loader.show();

            this.setAttribute('title', QUILocale.get(lg, 'users.panel.title'));
            this.setAttribute('icon', 'fa fa-spinner fa-spin');
            this.refresh();

            if (this.getAttribute('search') && !this.getBody().getElement('.messages-message')) {
                new Attention({
                    Users  : this,
                    message: QUILocale.get(lg, 'users.panel.search.info'),
                    events : {
                        onClick: function (Message) {
                            self.setAttribute('search', false);
                            self.setAttribute('searchSettings', {});

                            Message.destroy();
                            self.load();
                        }
                    },
                    styles : {
                        margin        : '0 0 20px',
                        'border-width': 1,
                        cursor        : 'pointer'
                    }
                }).inject(this.getBody(), 'top');
            }

            this.resize();


            Users.getList({
                field         : this.getAttribute('field'),
                order         : this.getAttribute('order'),
                limit         : this.getAttribute('limit'),
                page          : this.getAttribute('page'),
                search        : this.getAttribute('search'),
                searchSettings: this.getAttribute('searchSettings')
            }).then(function (result) {
                var Grid = self.getGrid();

                self.setAttribute('title', QUILocale.get(lg, 'users.panel.title'));
                self.setAttribute('icon', 'fa fa-user');
                self.refresh();

                if (!Grid) {
                    self.Loader.hide();
                    return;
                }

                self.$parseDataForGrid(result.data);

                Grid.setData(result);

                if (typeof callback === 'function') {
                    callback();
                }

                self.Loader.hide();
            });
        },

        /**
         * execute a user user status switch
         *
         * @param {Object} Switch - qui/controls/buttons/Switch
         */
        $btnSwitchStatus: function (Switch) {
            var self = this,
                User = Users.get(Switch.getAttribute('uid'));

            if (!User.isLoaded()) {
                User.load(function () {
                    Switch = self.$getUserSwitch(User);
                    self.$btnSwitchStatus(Switch);
                });

                return;
            }

            var userStatus = !!User.isActive();

            // status is the same as the switch, we must do nothing
            if (userStatus === Switch.getStatus()) {
                return;
            }

            if (Switch.getStatus() === false) {
                new QUIConfirm({
                    title      : QUILocale.get(lg, 'users.panel.deactivate.window.title'),
                    text       : QUILocale.get(lg, 'users.panel.deactivate.window.text', {
                        userid  : User.getId(),
                        username: User.getName()
                    }),
                    information: QUILocale.get(lg, 'users.panel.deactivate.window.information'),
                    maxHeight  : 400,
                    maxWidth   : 600,
                    autoclose  : false,
                    events     : {
                        onSubmit: function (Win) {
                            Win.Loader.show();

                            Users.deactivate(Switch.getAttribute('uid')).then(function () {
                                var Switch = self.$getUserSwitch(User);

                                if (Switch) {
                                    Switch.setSilentOff();
                                }

                                Win.close();
                            });
                        },

                        onCancel: function () {
                            Switch.setSilentOn();
                        }
                    }
                }).open();

                return;
            }

            Users.activate(Switch.getAttribute('uid')).then(function () {
                var Switch = self.$getUserSwitch(User);

                if (Switch) {
                    Switch.setSilentOn();
                }
            });
        },

        /**
         * if a user status is changed
         *
         * @param {Object} Users - classes/users/Users
         * @param {Object} ids - User-IDs
         */
        $onSwitchStatus: function (Users, ids) {
            var i, len, Switch, entry, status;

            var Grid = this.getGrid(),
                data = Grid.getData();

            for (i = 0, len = data.length; i < len; i++) {
                if (typeof ids[data[i].id] === 'undefined') {
                    continue;
                }

                entry = data[i];

                status = parseInt(entry.active);
                Switch = entry.status;

                // user is active
                if (status) {
                    Switch.setAttribute('alt', this.active_text);
                    Switch.setSilentOn();
                    continue;
                }

                // user is deactive
                Switch.setAttribute('alt', this.deactive_text);
                Switch.setSilentOff();
            }
        },

        /**
         * if a user status is changed
         *
         * @param {Object} Users - classes/users/Users
         * @param {Object} User - classes/users/User
         */
        $onUserRefresh: function (Users, User) {
            var Grid = this.getGrid(),
                data = Grid.getData(),
                id   = User.getId();

            for (var i = 0, len = data.length; i < len; i++) {
                if (parseInt(data[i].id) === id) {
                    Grid.setDataByRow(i, this.userToGridData(User));
                }
            }
        },

        /**
         * if a user is deleted
         */
        $onDeleteUser: function (Users, ids) {
            var i, id, len;

            var Grid = this.getGrid(),
                data = Grid.getData(),
                tmp  = {};

            for (i = 0, len = ids.length; i < len; i++) {
                tmp[ids[i]] = true;
            }

            for (i = 0, len = data.length; i < len; i++) {
                id = data[i].id;

                if (tmp[id]) {
                    this.load();
                    break;
                }
            }
        },

        /**
         * Open all marked users
         */
        $onButtonEditClick: function () {
            var Parent  = this.getParent(),
                Grid    = this.getGrid(),
                seldata = Grid.getSelectedData();

            if (!seldata.length) {
                return;
            }

            if (seldata.length === 1) {
                this.openUser(seldata[0].id);
                return;
            }

            var i, len;

            if (Parent.getType() === 'qui/controls/desktop/Tasks') {
                require([
                    'controls/users/User',
                    'qui/controls/taskbar/Group'
                ], function (UserPanel, QUITaskGroup) {
                    var User, Task, TaskGroup;

                    TaskGroup = new QUITaskGroup();
                    Parent.appendTask(TaskGroup);

                    for (i = 0, len = seldata.length; i < len; i++) {
                        User = new UserPanel(seldata[i].id);
                        Task = Parent.instanceToTask(User);

                        TaskGroup.appendChild(Task);
                    }

                    // TaskGroup.refresh( Task );
                    TaskGroup.click();
                });

                return;
            }

            for (i = 0, len = seldata.length; i < len; i++) {
                this.openUser(seldata[i].id);
            }
        },

        /**
         * Open deletion popup
         */
        $onButtonDelClick: function () {
            var i, len;

            var uids = [],
                data = this.getGrid().getSelectedData();

            for (i = 0, len = data.length; i < len; i++) {
                uids.push(data[i].id);
            }

            if (!uids.length) {
                return;
            }

            new QUIConfirm({
                name       : 'DeleteUsers',
                icon       : 'fa fa-trash-o',
                texticon   : 'fa fa-trash-o',
                title      : QUILocale.get(lg, 'users.panel.delete.window.title'),
                text       : QUILocale.get(lg, 'users.panel.delete.window.text', {
                    userids: uids.join(', ')
                }),
                information: QUILocale.get(lg, 'users.panel.delete.window.information'),
                maxWidth   : 600,
                maxHeight  : 400,
                uids       : uids,
                events     : {
                    onSubmit: function (Win) {
                        require(['Users'], function (Users) {
                            Users.deleteUsers(Win.getAttribute('uids')).then(function () {
                                Win.close();
                            });
                        });
                    }
                }
            }).open();
        },

        /**
         * Parse the Ajax data for the grid
         *
         * @param {Array} data
         * @return {Array}
         */
        $parseDataForGrid: function (data) {
            var i, len, entry;

            for (i = 0, len = data.length; i < len; i++) {
                entry = data[i];

                data[i].active    = parseInt(entry.active);
                data[i].usergroup = entry.usergroup || '';

                if (entry.active === -1) {
                    data[i].status = new Element('span', {html: '&nbsp;'});
                    continue;
                }

                data[i].status = new QUISwitch({
                    status: entry.active === 1,
                    uid   : entry.id,
                    title : entry.active ? this.active_text : this.deactive_text,
                    events: {
                        onChange: this.$btnSwitchStatus
                    }
                });
            }

            return data;
        },

        /**
         * Return the Switch button for the user entry
         *
         * @param {Object} User - classes/users/User
         * @returns {Object|Boolean} (qui/controls/buttons/Switch)
         */
        $getUserSwitch: function (User) {
            var Grid = this.getGrid(),
                data = Grid.getData(),
                id   = User.getId();

            for (var i = 0, len = data.length; i < len; i++) {
                if (parseInt(data[i].id) === id) {
                    return Grid.getDataByRow(i).status;
                }
            }

            return false;
        },

        /**
         * Parse the attributes to grid data entry
         *
         * @param {Object} User - classes/users/User
         * @return {Object}
         */
        userToGridData: function (User) {
            var active = parseInt(User.isActive()),
                id     = User.getId(),
                result = User.getAttributes();

            result.usergroup = result.usergroup || '';

            if (active !== -1) {
                result.status = new QUISwitch({
                    status: active === 1,
                    uid   : id,
                    title : active ? this.active_text : this.deactive_text,
                    events: {
                        onChange: this.$btnSwitchStatus
                    }
                });
            } else {
                result.status = new Element('span', {html: '&nbsp;'});
            }

            return result;
        }
    });
});
