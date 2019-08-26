/**
 * @module controls/usersAndGroups/search/Search
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Button
 * @require qui/controls/buttons/Switch
 * @require Locale
 * @require Ajax
 * @require Users
 * @require controls/grid/Grid
 *
 * @event onDblClick [self]
 * @event onSearchBegin [self]
 * @event onSearchEnd [self]
 */
define('controls/usersAndGroups/search/Search', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/controls/buttons/Switch',
    'Locale',
    'Ajax',
    'Users',
    'controls/grid/Grid',

    'css!controls/usersAndGroups/search/Search.css'

], function (QUI, QUIControl, QUIButton, QUISwitch, QUILocale, QUIAjax, Users, Grid) {
    "use strict";

    var lg = 'quiqqer/system';

    return new Class({
        Extends: QUIControl,
        Type   : 'controls/usersAndGroups/search/Search',

        Binds: [
            'search',
            '$onSwitchStatusChange',
            '$parseDataForGrid'
        ],

        options: {
            limit : 20,
            page  : 1,
            search: false
        },

        initialize: function (options) {
            this.parent(options);

            this.$Container = null;
            this.$Grid      = null;
            this.$Input     = null;
            this.$Form      = null;

            this.active_text   = QUILocale.get(lg, 'users.panel.user.is.active');
            this.deactive_text = QUILocale.get(lg, 'users.panel.user.is.deactive');
        },

        /**
         * event : on open
         */
        create: function () {
            var placeholder = QUILocale.get(
                'quiqqer/quiqqer',
                'control.usersgroups.select.search.field.placeholder'
            );

            this.$Elm = new Element('div', {
                'class': 'quiqqer-users-search',
                html   : '<form class="quiqqer-users-search-form">' +
                         '  <input type="search" placeholder="' + placeholder + '" />' +
                         '</form>',
                styles : {
                    height: 'calc(100% - 40px)',
                    width : '100%'
                }
            });

            this.$Input = this.$Elm.getElement('[type="search"]');
            this.$Form  = this.$Elm.getElement('form');

            if (this.getAttribute('search')) {
                this.$Input.value = this.getAttribute('search');
            }

            // render
            new QUIButton({
                icon  : 'fa fa-search',
                styles: {
                    width: 80
                },
                events: {
                    onClick: this.search
                }
            }).inject(this.$Form);

            this.$Container = new Element('div');
            this.$Container.inject(this.$Elm);

            this.$Grid = new Grid(this.$Container, {
                columnModel      : [{
                    header   : QUILocale.get(lg, 'type'),
                    dataIndex: 'typeIcon',
                    dataType : 'node',
                    width    : 40
                }, {
                    header   : QUILocale.get(lg, 'status'),
                    dataIndex: 'status',
                    dataType : 'QUI',
                    width    : 60
                }, {
                    header   : QUILocale.get(lg, 'id'),
                    dataIndex: 'id',
                    dataType : 'integer',
                    width    : 150
                }, {
                    header   : QUILocale.get(lg, 'name'),
                    dataIndex: 'name',
                    dataType : 'integer',
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

            this.$Form.addEvent('submit', function (event) {
                event.stop();
                this.search();
            }.bind(this));

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
            this.fireEvent('searchBegin', [this]);

            var options = this.$Grid.options;

            return new Promise(function (resolve) {
                QUIAjax.get('ajax_usersgroups_searchGrid', function (result) {
                    this.$Grid.setData(
                        this.$parseDataForGrid(result)
                    );

                    this.fireEvent('searchEnd', [this]);

                    resolve();
                }.bind(this), {
                    search: this.$Input.value,
                    fields: false,
                    params: JSON.encode({
                        limit: options.perPage,
                        page : options.page
                    })
                });

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

            var GroupIcon = new Element('span', {
                'class': 'fa fa-group'
            });

            var UserIcon = new Element('span', {
                'class': 'fa fa-user'
            });

            for (i = 0, len = data.data.length; i < len; i++) {
                entry = data.data[i];

                data.data[i].active = (entry.active).toInt();
                data.data[i].status = new QUISwitch({
                    status: entry.active == 1,
                    uid   : entry.id,
                    title : entry.active ? this.active_text : this.deactive_text,
                    events: {
                        onChange: this.$onSwitchStatusChange
                    }
                });

                if (entry.type === 'group') {
                    data.data[i].typeIcon = GroupIcon.clone();
                    continue;
                }

                data.data[i].typeIcon = UserIcon.clone();
                data.data[i].name     = entry.username;
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

            var userStatus = User.isActive() ? true : false;

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
