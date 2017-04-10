/**
 * @module controls/users/search/Search
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Switch
 * @require Locale
 * @require Ajax
 * @require Users
 * @require controls/grid/Grid
 *
 * @event onDblClick [self]
 */
define('controls/users/search/Search', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Switch',
    'Locale',
    'Ajax',
    'Users',
    'controls/grid/Grid'

], function (QUI, QUIControl, QUISwitch, QUILocale, QUIAjax, Users, Grid) {
    "use strict";

    var lg = 'quiqqer/system';

    return new Class({
        Extends: QUIControl,
        Type   : 'controls/users/search/Search',

        Binds: [
            'search',
            '$onSwitchStatusChange',
            '$parseDataForGrid'
        ],

        options: {
            field         : 'username',
            order         : 'ASC',
            limit         : 20,
            page          : 1,
            search        : false,
            searchSettings: {}
        },

        initialize: function (options) {
            this.parent(options);

            this.$Grid      = null;
            this.$Container = null;

            this.active_text   = QUILocale.get(lg, 'users.panel.user.is.active');
            this.deactive_text = QUILocale.get(lg, 'users.panel.user.is.deactive');
        },

        /**
         * event : on open
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class': 'quiqqer-users-search',
                styles : {
                    height: '100%',
                    width : '100%'
                }
            });

            this.$Container = new Element('div');
            this.$Container.inject(this.$Elm);

            this.$Grid = new Grid(this.$Container, {
                columnModel      : [{
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
                pagination       : true,
                filterInput      : true,
                perPage          : this.getAttribute('limit'),
                page             : this.getAttribute('page'),
                sortOn           : this.getAttribute('field'),
                serverSort       : true,
                showHeader       : true,
                sortHeader       : true,
                alternaterows    : true,
                resizeColumns    : true,
                selectable       : true,
                multipleSelection: true,
                resizeHeaderOnly : true
            });

            // Events
            this.$Grid.addEvents({
                onDblClick: function () {
                    this.fireEvent('dblClick', [this]);
                }.bind(this),
                onRefresh : this.search
            });

            this.$Grid.refresh();

            return this.$Elm;
        },

        /**
         * Resize
         *
         * @return {Promise}
         */
        resize: function () {
            var size = this.$Elm.getSize();

            return Promise.all([
                this.$Grid.setHeight(size.y),
                this.$Grid.setWidth(size.x)
            ]);
        },

        /**
         * execute the search
         */
        search: function () {
            var options = this.$Grid.options;

            Users.getList({
                field         : options.sortOn,
                order         : options.sortBy,
                limit         : options.perPage,
                page          : options.page,
                search        : this.getAttribute('search'),
                searchSettings: this.getAttribute('searchSettings')
            }).then(function (result) {
                this.$Grid.setData(
                    this.$parseDataForGrid(result)
                );
            }.bind(this));
        },

        /**
         * Return the selected user data
         *
         * @return {Array}
         */
        getSelectedData: function () {
            return this.$Grid.getSelectedData();
        },

        /**
         * Parse the Ajax data for the grid
         *
         * @param {Array} data
         * @return {Array}
         */
        $parseDataForGrid: function (data) {
            var i, len, entry;

            for (i = 0, len = data.data.length; i < len; i++) {
                entry = data.data[i];

                data.data[i].active    = (entry.active).toInt();
                data.data[i].usergroup = entry.usergroup || '';

                if (entry.active == -1) {
                    continue;
                }

                data.data[i].status = new QUISwitch({
                    status: entry.active == 1,
                    uid   : entry.id,
                    title : entry.active ? this.active_text : this.deactive_text,
                    events: {
                        onChange: this.$onSwitchStatusChange
                    }
                });
            }

            return data;
        },

        /**
         * on switch change
         *
         * @param {Object} Switch - qui/controls/buttons/Switch
         */
        $onSwitchStatusChange: function (Switch) {
            var self = this,
                uid  = Switch.getAttribute('uid'),
                User = Users.get(uid);

            if (!User.isLoaded()) {
                User.load(function () {
                    self.$onSwitchStatusChange(Switch);
                });

                return;
            }

            var userStatus = !!User.isActive();

            // status is the same as the switch, we do nothing
            if (userStatus === Switch.getStatus()) {
                return;
            }

            if (!Switch.getStatus()) {
                return User.deactivate();
            }

            return User.activate();
        }
    });
});
