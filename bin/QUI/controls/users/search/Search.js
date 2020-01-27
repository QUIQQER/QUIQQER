/**
 * @module controls/users/search/Search
 * @author www.pcsg.de (Henning Leutz)
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
    'Mustache',
    'controls/grid/Grid',

    'text!controls/users/search/Search.html',
    'css!controls/users/search/Search.css'

], function (QUI, QUIControl, QUISwitch, QUILocale, QUIAjax, Users, Mustache, Grid, template) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

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
            searchSettings: {},
            editable      : true
        },

        initialize: function (options) {
            this.parent(options);

            this.$Grid       = null;
            this.$Result     = null;
            this.$Container  = null;
            this.$SearchForm = null;

            this.active_text   = QUILocale.get(lg, 'users.panel.user.is.active');
            this.deactive_text = QUILocale.get(lg, 'users.panel.user.is.deactive');
        },

        /**
         * event : on open
         */
        create: function () {
            var self = this;

            this.$Elm = new Element('div', {
                'class': 'user-search-control',
                html   : Mustache.render(template, {
                    textUserId   : QUILocale.get(lg, 'user_id'),
                    textUsername : QUILocale.get(lg, 'username'),
                    textFirstname: QUILocale.get(lg, 'firstname'),
                    textLastname : QUILocale.get(lg, 'lastname'),
                    textEmail    : QUILocale.get(lg, 'email'),
                    textGroup    : QUILocale.get(lg, 'group'),
                    textCDate    : QUILocale.get(lg, 'c_date'),
                    textFrom     : QUILocale.get(lg, 'from'),
                    textTo       : QUILocale.get(lg, 'to')
                }),
                styles : {
                    height: '100%',
                    width : '100%'
                }
            });

            this.$Result    = this.$Elm.getElement('.user-search-control-result');
            this.$Container = this.$Elm.getElement('.user-search-control-result-container');

            this.$SearchForm   = this.$Elm.getElement('[name="user-search-control-form"]');
            this.$SearchInput  = this.$Elm.getElement('[name="search"]');
            this.$SubmitButton = this.$Elm.getElement('[name="submit"]');
            this.$FilterButton = this.$Elm.getElement('button[name="filter"]');

            this.$SearchForm.addEvent('focus', function (event) {
                event.stop();
            });

            this.$SubmitButton.addEvent('click', function (event) {
                event.stop();
                self.search();
            });

            this.$SearchInput.addEvent('keydown', function (event) {
                if (event.key === 'enter') {
                    event.stop();
                }
            });

            this.$SearchInput.addEvent('keyup', function (event) {
                if (event.key === 'enter') {
                    self.search();
                }
            });

            this.$FilterButton.addEvent('click', function (event) {
                event.stop();
                self.toggleFilter();
            });

            // grid
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
            var size = this.$Result.getSize();

            return Promise.all([
                this.$Grid.setHeight(size.y - 40),
                this.$Grid.setWidth(size.x - 40)
            ]);
        },

        /**
         * execute the search
         */
        search: function () {
            var options = this.$Grid.options;
            var search  = this.getAttribute('searchSettings');
            var Form    = this.$SearchForm;

            if (!search) {
                search = {};
            }

            search.userSearchString = this.$SearchInput.value;

            search.fields = {
                id       : Form.elements.userId.checked ? 1 : 0,
                username : Form.elements.username.checked ? 1 : 0,
                firstname: Form.elements.firstname.checked ? 1 : 0,
                lastname : Form.elements.lastname.checked ? 1 : 0,
                email    : Form.elements.email.checked ? 1 : 0,
                group    : Form.elements.group.checked ? 1 : 0
            };

            search.filter = {
                regdate_from: Form.elements['registration-from'].value,
                regdate_to  : Form.elements['registration-to'].value
            };

            Users.getList({
                field         : options.sortOn,
                order         : options.sortBy,
                limit         : options.perPage,
                page          : options.page,
                search        : this.getAttribute('search'),
                searchSettings: search
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

            var editable = this.getAttribute('editable');

            for (i = 0, len = data.data.length; i < len; i++) {
                entry = data.data[i];

                data.data[i].active    = (entry.active).toInt();
                data.data[i].usergroup = entry.usergroup || '';

                if (entry.active === -1) {
                    continue;
                }

                if (editable) {
                    data.data[i].status = new QUISwitch({
                        status: entry.active === 1,
                        uid   : entry.id,
                        title : entry.active ? this.active_text : this.deactive_text,
                        events: {
                            onChange: this.$onSwitchStatusChange
                        }
                    });

                    continue;
                }

                data.data[i].status = new Element('div', {
                    class : entry.active ? 'fa fa-check' : 'fa fa-minus',
                    styles: {
                        textAlign: 'center',
                        width    : 'calc(100% - 5px)'
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
        },

        //region filter

        /**
         * Toggle the filter
         */
        toggleFilter: function () {
            var FilterContainer = this.getElm().getElement('.user-search-control-form-filter');

            if (FilterContainer.getStyle('display') === 'none') {
                this.openFilter();
            } else {
                this.closeFilter();
            }
        },

        /**
         * Open the filter
         */
        openFilter: function () {
            var self            = this,
                FilterContainer = this.getElm().getElement('.user-search-control-form-filter');

            FilterContainer.setStyle('position', 'absolute');
            FilterContainer.setStyle('opacity', 0);
            FilterContainer.setStyle('overflow', 'hidden');

            // reset
            FilterContainer.setStyle('display', null);
            FilterContainer.setStyle('height', null);
            FilterContainer.setStyle('paddingBottom', null);
            FilterContainer.setStyle('paddingTop', null);

            var height = FilterContainer.getSize().y;

            FilterContainer.setStyle('height', 0);
            FilterContainer.setStyle('paddingBottom', 0);
            FilterContainer.setStyle('paddingTop', 0);
            FilterContainer.setStyle('position', null);

            moofx(FilterContainer).animate({
                height       : height,
                marginTop    : 20,
                opacity      : 1,
                paddingBottom: 10,
                paddingTop   : 10
            }, {
                duration: 300,
                callback: function () {
                    self.resize();
                }
            });
        },

        /**
         * Close the filter
         */
        closeFilter: function () {
            var self            = this,
                FilterContainer = this.getElm().getElement('.user-search-control-form-filter');

            moofx(FilterContainer).animate({
                height       : 0,
                marginTop    : 0,
                opacity      : 1,
                paddingBottom: 0,
                paddingTop   : 0
            }, {
                duration: 300,
                callback: function () {
                    FilterContainer.setStyle('display', 'none');

                    FilterContainer.setStyle('height', null);
                    FilterContainer.setStyle('paddingBottom', null);
                    FilterContainer.setStyle('paddingTop', null);

                    self.resize();
                }
            });
        }

        //endregion
    });
});
