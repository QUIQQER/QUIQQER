/**
 * A group panel
 *
 * @module controls/groups/Group
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require controls/desktop/Panel
 * @require qui/controls/buttons/ButtonSwitch
 * @require qui/controls/buttons/Button
 * @require controls/grid/Grid
 * @require Groups
 * @require Ajax
 * @require Editors
 * @require Locale
 * @require qui/controls/buttons/Button
 * @require qui/utils/Form
 * @require utils/Controls
 * @require css!controls/groups/Group.css
 */
define('controls/groups/Group', [

    'qui/controls/desktop/Panel',
    'qui/controls/buttons/ButtonSwitch',
    'qui/controls/buttons/Button',
    'controls/grid/Grid',
    'Groups',
    'Ajax',
    'Editors',
    'Locale',
    'qui/utils/Form',
    'utils/Controls',

    'css!controls/groups/Group.css'

], function (QUIPanel, QUIButtonSwitch, QUIButton,
             Grid, Groups, Ajax, Editors, QUILocale, FormUtils, ControlUtils) {
    "use strict";

    var lg = 'quiqqer/system';

    /**
     * @class controls/groups/Group
     *
     * @param {Number} gid - Group-ID
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIPanel,
        Type   : 'controls/groups/Group',

        Binds: [
            'save',
            'del',
            'refreshUser',
            'openPermissions',

            '$onCreate',
            '$onDestroy',
            '$onResize',
            '$onCategoryLoad',
            '$onCategoryUnload',
            '$onGroupRefresh',
            '$onGroupStatusChange',
            '$onStatusButtonChange',
            '$onGroupDelete',
            '$onGroupGetUser'
        ],

        options: {
            'user-sort' : 'DESC',
            'user-field': 'id',
            'user-limit': 20,
            'user-page' : 1
        },

        initialize: function (gid) {
            this.$Group    = null;
            this.$UserGrid = null;

            if (typeOf(gid) === 'string' || typeOf(gid) === 'number') {
                this.$Group = Groups.get(gid);
            }

            this.addEvents({
                onCreate : this.$onCreate,
                onDestroy: this.$onDestroy,
                onResize : this.$onResize,
                onShow   : function () {
                    var Status = this.getButtons('status');

                    if (Status) {
                        Status.resize();
                    }
                }
            });

            this.parent();
        },

        /**
         * Save the group panel to the workspace
         *
         * @method controls/groups/Group#serialize
         * @return {Object} data
         */
        serialize: function () {
            return {
                attributes: this.getAttributes(),
                groupid   : this.getGroup().getId(),
                type      : this.getType()
            };
        },

        /**
         * import the saved data form the workspace
         *
         * @method controls/groups/Group#unserialize
         * @param {Object} data
         * @return {Object} this (controls/groups/Group)
         */
        unserialize: function (data) {
            this.setAttributes(data.attributes);

            this.$Group = Groups.get(data.groupid);

            return this;
        },

        /**
         * Return the assigned group
         *
         * @return {Object} classes/groups/Group
         */
        getGroup: function () {
            return this.$Group;
        },

        /**
         * Save the group
         */
        save: function () {
            this.$onCategoryUnload();
            this.getGroup().save();
        },

        /**
         * Delete the Group
         * Opens the delete dialog
         */
        del: function () {
            var self = this;

            require(['qui/controls/windows/Confirm'], function (Confirm) {
                new Confirm({
                    name       : 'DeleteUser' + self.getGroup().getId(),
                    icon       : 'fa fa-trash-o',
                    texticon   : 'fa fa-trash-o',
                    title      : QUILocale.get(lg, 'groups.group.delete.title'),
                    information: QUILocale.get(lg, 'groups.group.delete.information'),
                    text       : QUILocale.get(lg, 'groups.group.delete.text', {
                        group: self.getGroup().getAttribute('name')
                    }),
                    ok_button  : {
                        text     : QUILocale.get(lg, 'delete'),
                        textimage: 'fa fa-trash'
                    },
                    maxWidth   : 600,
                    maxHeight  : 400,
                    autoclose  : false,
                    events     : {
                        onSubmit: function (Win) {
                            Win.Loader.show();

                            Groups.deleteGroups([
                                self.getGroup().getId()
                            ]).then(function () {
                                Win.close();
                            });
                        }
                    }
                }).open();
            });
        },

        /**
         * Opens the group permissions
         */
        openPermissions: function () {
            var Parent = this.getParent(),
                Group  = this.getGroup();

            require(['controls/permissions/Panel'], function (PermPanel) {
                Parent.appendChild(
                    new PermPanel({
                        Object: Group
                    })
                );
            });
        },

        /**
         * event : on create
         * Group panel content creation
         */
        $onCreate: function () {
            var self = this;

            this.$drawButtons();

            this.$drawCategories().then(function () {
                var Group = self.getGroup();

                Group.addEvents({
                    'onRefresh': self.$onGroupRefresh
                });

                Groups.addEvents({
                    'onSwitchStatus': self.$onGroupStatusChange,
                    'onActivate'    : self.$onGroupStatusChange,
                    'onDeactivate'  : self.$onGroupStatusChange,
                    'onDelete'      : self.$onGroupDelete
                });

                self.setAttribute('icon', 'fa fa-group');

                var Prom = Promise.resolve();

                if (Group.getAttribute('title') === false) {
                    Prom = Group.load();
                }

                Prom.then(function () {
                    self.getButtons('status').enable();
                    self.$onGroupRefresh();
                });
            });
        },

        /**
         * event: on panel destroying
         */
        $onDestroy: function () {
            this.getGroup().removeEvents({
                'refresh': this.$onGroupRefresh
            });

            Groups.removeEvents({
                'switchStatus': this.$onGroupStatusChange,
                'activate'    : this.$onGroupStatusChange,
                'deactivate'  : this.$onGroupStatusChange,
                'delete'      : this.$onGroupDelete
            });
        },

        /**
         * event : onresize
         * Resize the panel
         */
        $onResize: function () {

        },

        /**
         * event : on group refresh
         * if the group will be refreshed
         */
        $onGroupRefresh: function () {
            this.setAttribute(
                'title',

                QUILocale.get(lg, 'groups.group.title', {
                    group: this.getGroup().getAttribute('name')
                })
            );

            this.refresh();

            var Bar    = this.getCategoryBar(),
                Status = this.getButtons('status');

            if (this.getGroup().isActive()) {
                Status.on();
                Status.setAttribute('text', QUILocale.get('quiqqer/quiqqer', 'isActivate'));
            } else {
                Status.off();
                Status.setAttribute('text', QUILocale.get('quiqqer/quiqqer', 'isDeactivate'));
            }

            if (Bar.getActive()) {
                this.$onCategoryLoad(Bar.getActive());
                return;
            }

            Bar.firstChild().click();

            // button bar refresh
            this.getButtonBar().resize();
        },

        /**
         * event: groups on delete
         * if one group deleted, check if the group is this group
         *
         * @param {Object} Groups - classes/groups/Manager
         * @param {Array} ids - Array of group ids which have been deleted
         */
        $onGroupDelete: function (Groups, ids) {
            var id = this.getGroup().getId();

            for (var i = 0, len = ids.length; i < len; i++) {
                if (ids[i] == id) {
                    this.destroy();
                }
            }
        },

        /**
         * event: groups on status change
         * if one groups status change, check if the group is this group
         *
         * @param {Object} Groups - classes/groups/Manager
         * @param {Object} groups - groups that change the status
         */
        $onGroupStatusChange: function (Groups, groups) {
            var Group = this.getGroup(),
                id    = Group.getId();

            for (var gid in groups) {
                if (gid != id) {
                    continue;
                }

                var Status = this.getButtons('status');

                if (Group.isActive()) {
                    Status.setSilentOn();
                    Status.setAttribute('text', QUILocale.get('quiqqer/quiqqer', 'isActivate'));
                } else {
                    Status.setSilentOff();
                    Status.setAttribute('text', QUILocale.get('quiqqer/quiqqer', 'isDeactivate'));
                }

                return;
            }
        },

        /**
         * event :Status Button change
         * @param {Object} Button
         */
        $onStatusButtonChange: function (Button) {
            var buttonStatus = Button.getStatus(),
                Group        = this.getGroup(),
                groupStatus  = Group.isActive();

            if (buttonStatus == groupStatus) {
                return;
            }

            this.Loader.show();

            var Prom;

            if (buttonStatus) {
                Prom = Group.activate();
            } else {
                Prom = Group.deactivate();
            }

            Prom.then(function () {
                if (Group.isActive()) {
                    Button.on();
                    Button.setAttribute('text', QUILocale.get('quiqqer/quiqqer', 'isActivate'));
                } else {
                    Button.off();
                    Button.setAttribute('text', QUILocale.get('quiqqer/quiqqer', 'isDeactivate'));
                }

                this.Loader.hide();
            }.bind(this));
        },

        /**
         * Draw the panel action buttons
         *
         * @method controls/groups/Group#$drawButtons
         */
        $drawButtons: function () {
            this.addButton({
                name     : 'groupSave',
                text     : QUILocale.get(lg, 'groups.group.btn.save'),
                textimage: 'fa fa-save',
                events   : {
                    onClick: this.save
                }
            });

            this.addButton({
                type: 'seperator'
            });

            this.addButton(
                new QUIButtonSwitch({
                    name    : 'status',
                    text    : QUILocale.get('quiqqer/quiqqer', 'isActivate'),
                    disabled: true,
                    events  : {
                        onChange: this.$onStatusButtonChange
                    }
                })
            );

            this.addButton({
                name  : 'groupDelete',
                title : QUILocale.get(lg, 'groups.group.btn.delete'),
                icon  : 'fa fa-trash-o',
                events: {
                    onClick: this.del
                },
                styles: {
                    'float': 'right'
                }
            });

            // permissions
            new QUIButton({
                image : 'fa fa-gears',
                alt   : QUILocale.get(lg, 'groups.group.btn.permissions.alt'),
                title : QUILocale.get(lg, 'groups.group.btn.permissions.title'),
                styles: {
                    'float': 'right'
                },
                events: {
                    onClick: this.openPermissions
                }
            }).inject(this.getHeader());
        },

        /**
         * Get the category buttons for the pannel
         *
         * @method controls/groups/Group#drawCategories
         *
         * @param {Function} [onfinish] - Callback function
         * @return {Promise}
         * @ignore
         */
        $drawCategories: function (onfinish) {
            this.Loader.show();

            return new Promise(function (resolve) {

                Ajax.get('ajax_groups_panel_categories', function (result) {

                    for (var i = 0, len = result.length; i < len; i++) {
                        result[i].events = {
                            onActive: this.$onCategoryLoad,
                            onNormal: this.$onCategoryUnload
                        };

                        this.addCategory(result[i]);
                    }

                    if (typeof onfinish === 'function') {
                        onfinish(result);
                    }

                    resolve();

                }.bind(this), {
                    gid: this.getGroup().getId()
                });
            }.bind(this));
        },

        /**
         * event: on category click
         *
         * @param {Object} Category - qui/controls/buttons/Button
         */
        $onCategoryLoad: function (Category) {
            var self = this;

            this.Loader.show();

            Ajax.get('ajax_groups_panel_category', function (result, Request) {
                var Form;

                var Category = Request.getAttribute('Category'),
                    Group    = self.getGroup(),
                    Body     = self.getBody();

                Body.set(
                    'html',
                    '<form name="group-panel-' + Group.getId() + '">' + result + '</form>'
                );

                Form = Body.getElement('form');

                ControlUtils.parse(Body);
                FormUtils.setDataToForm(Group.getAttributes(), Form);

                switch (Category.getAttribute('name')) {
                    case 'settings':
                        self.$onCategorySettingsLoad();
                        break;

                    case 'users':
                        self.$onCategoryUsersLoad();
                        break;

                    default:
                        Category.fireEvent('onLoad', [Category, self]);
                }

                self.Loader.hide();

            }, {
                plugin  : Category.getAttribute('plugin'),
                tab     : Category.getAttribute('name'),
                gid     : this.getGroup().getId(),
                Category: Category
            });
        },

        /**
         * event: on set normal a category = unload a category
         */
        $onCategoryUnload: function () {
            var Content = this.getBody(),
                Frm     = Content.getElement('form'),
                data    = FormUtils.getFormData(Frm);

            this.getGroup().setAttributes(data);
        },

        /**
         * event: on category click (settings)
         */
        $onCategorySettingsLoad: function () {
            // load the wysiwyg toolbars
            Editors.getToolbars(function (toolbars) {
                var i, len, Sel;

                var Content = this.getBody(),
                    Toolbar = Content.getElement('.toolbar-listing');

                if (!Toolbar) {
                    return;
                }

                Toolbar.set('html', '');

                Sel = new Element('select', {
                    'class': 'field-container-field',
                    name   : 'toolbar'
                });

                for (i = 0, len = toolbars.length; i < len; i++) {
                    new Element('option', {
                        value: toolbars[i],
                        html : toolbars[i].replace('.xml', '')
                    }).inject(Sel);
                }

                Sel.replaces(Toolbar);
                Sel.value = this.getAttribute('toolbar');

            }.bind(this));
        },

        /**
         * event: on category click (user listing)
         */
        $onCategoryUsersLoad: function () {
            var Content = this.getBody(),
                GridCon = new Element('div');

            Content.set('html', '');
            GridCon.inject(Content);

            this.$UserGrid = new Grid(GridCon, {
                columnModel: [{
                    header   : QUILocale.get(lg, 'status'),
                    dataIndex: 'status',
                    dataType : 'node',
                    width    : 50
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
                perPage    : this.getAttribute('user-limit'),
                page       : this.getAttribute('user-page'),
                sortOn     : this.getAttribute('user-sort'),
                serverSort : true,
                showHeader : true,
                sortHeader : true,
                width      : Content.getSize().x,
                height     : Content.getSize().y - 45,
                onrefresh  : this.refreshUser,

                alternaterows    : true,
                resizeColumns    : true,
                selectable       : true,
                multipleSelection: true,
                resizeHeaderOnly : true
            });

            this.$UserGrid.addEvents({
                onDblClick: function (data) {
                    require(['controls/users/User'], function (QUI_User) {
                        this.getParent().appendChild(
                            new QUI_User(
                                data.target.getDataByRow(data.row).id
                            )
                        );
                    }.bind(this));
                }.bind(this)
            });

            GridCon.setStyles({
                margin: 0
            });

            this.$UserGrid.refresh();
        },

        /**
         * Refresh the user grid
         *
         * @return {Object} this (controls/groups/Group)
         */
        refreshUser: function () {
            if (typeof this.$UserGrid === 'undefined') {
                return this;
            }

            this.Loader.show();

            var Grid = this.$UserGrid;

            this.setAttribute('user-field', Grid.getAttribute('sortOn'));
            this.setAttribute('user-order', Grid.getAttribute('sortBy'));
            this.setAttribute('user-limit', Grid.getAttribute('perPage'));
            this.setAttribute('user-page', Grid.getAttribute('page'));

            this.getGroup().getUsers(this.$onGroupGetUser, {
                limit: this.getAttribute('user-limit'),
                page : this.getAttribute('user-page'),
                field: this.getAttribute('user-field'),
                order: this.getAttribute('user-order')
            });

            return this;
        },

        /**
         * if users return for the user grid
         *
         * @param {Array} result - user list
         */
        $onGroupGetUser: function (result) {
            if (typeof this.$UserGrid === 'undefined') {
                return;
            }

            if (typeof result.data === 'undefined') {
                return;
            }

            for (var i = 0, len = result.data.length; i < len; i++) {
                if (result.data[i].active) {
                    result.data[i].status = new Element('div', {
                        'class': 'fa fa-check',
                        styles : {
                            margin: '5px 0 5px 12px'
                        }
                    });

                } else {
                    result.data[i].status = new Element('div', {
                        'class': 'fa fa-remove',
                        styles : {
                            margin: '5px 0 5px 12px'
                        }
                    });
                }
            }

            this.$UserGrid.setData(result);
            this.Loader.hide();
        }
    });
});
