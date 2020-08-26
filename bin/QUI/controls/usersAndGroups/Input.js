/**
 * Makes an input field to a user / group selection field
 *
 * @module controls/usersAndGroups/Input
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/Control
 * @require qui/controls/buttons/Button
 * @require controls/users/Entry
 * @require controls/groups/Entry
 * @require Ajax
 * @require Locale
 *
 * @event onAddUser [ this, id ]
 * @event onAddgroup [ this, id ]
 *
 * @deprecated
 */
define('controls/usersAndGroups/Input', [

    'qui/QUI',
    'qui/controls/elements/Select',
    'qui/controls/buttons/Button',
    'controls/users/Entry',
    'controls/groups/Entry',
    'Ajax',
    'Locale',

    'css!controls/usersAndGroups/Input.css'

], function (QUI, QUIElementSelect, QUIButton, UsersEntry, GroupsEntry, Ajax, Locale) {
    "use strict";

    /**
     * @class controls/usersAndGroups/Input
     *
     * @param {Object} options
     * @param {HTMLInputElement} [Input]  - (optional), if no input given, one would be created
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIElementSelect,
        Type   : 'controls/usersAndGroups/Input',

        Binds: [
            'close',
            'fireSearch',
            'update',

            '$onGroupUserDestroy',
            '$onInputFocus',
            '$onInject'
        ],

        options: {
            max     : false, // max entries
            multiple: true,  // select more than one entry?
            name    : '',    // string
            styles  : false, // object
            label   : false,  // text string or a <label> DOMNode Element
            value   : false
        },

        initialize: function (options, Input) {
            this.parent(options);

            this.$Input    = Input || null;
            this.$Elm      = null;
            this.$List     = null;
            this.$Search   = null;
            this.$DropDown = null;

            this.$search = false;
            this.$values = [];

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Return the DOMNode of the users and groups search
         *
         * @method controls/usersAndGroups/Input#create
         * @return {HTMLElement} The main DOM-Node Element
         */
        create: function () {
            if (this.$Elm) {
                return this.$Elm;
            }

            this.$Elm = new Element('div', {
                'class'     : 'qui-users-and-groups',
                'data-quiid': this.getId()
            });

            if (!this.$Input) {
                this.$Input = new Element('input', {
                    name: this.getAttribute('name')
                }).inject(this.$Elm);
            } else {
                this.$Elm.wraps(this.$Input);
            }

            if (this.getAttribute('styles')) {
                this.$Elm.setStyles(this.getAttribute('styles'));
            }

            this.$Input.set({
                styles: {
                    opacity : 0,
                    position: 'absolute',
                    zIndex  : 1,
                    left    : 5,
                    top     : 5,
                    cursor  : 'pointer'
                },
                events: {
                    focus: this.$onInputFocus
                }
            });


            this.$List = new Element('div', {
                'class': 'qui-users-and-groups-list'
            }).inject(this.$Elm);

            this.$Search = new Element('input', {
                'class'    : 'qui-users-and-groups-search',
                placeholder: Locale.get('quiqqer/quiqqer', 'usersAndGroups.input.search.placeholder'),
                events     : {
                    keyup: function (event) {
                        if (event.key === 'down') {
                            this.down();
                            return;
                        }

                        if (event.key === 'up') {
                            this.up();
                            return;
                        }

                        if (event.key === 'enter') {
                            this.submit();
                            return;
                        }

                        this.fireSearch();
                    }.bind(this),

                    blur : this.close,
                    focus: this.fireSearch
                }
            }).inject(this.$Elm);

            this.$DropDown = new Element('div', {
                'class': 'qui-users-and-groups-dropdown',
                styles : {
                    display: 'none',
                    top    : this.$Search.getPosition().y + this.$Search.getSize().y,
                    left   : this.$Search.getPosition().x
                }
            }).inject(document.body);

            if (this.getAttribute('label')) {
                var Label = this.getAttribute('label');

                if (typeof this.getAttribute('label').nodeName === 'undefined') {
                    Label = new Element('label', {
                        html: this.getAttribute('label')
                    });
                }

                Label.inject(this.$Elm, 'top');

                if (Label.get('data-desc') && Label.get('data-desc') != '&nbsp;') {
                    new Element('div', {
                        'class': 'description',
                        html   : Label.get('data-desc'),
                        styles : {
                            marginBottom: 10
                        }
                    }).inject(Label, 'after');
                }
            }


            // load values
            var value = '';

            if (!this.$Input.value || this.$Input.value !== '') {
                value = this.$Input.value;
            }

            if (value === '' && this.getAttribute('value')) {
                value = this.getAttribute('value');
            }

            if (value) {
                var val = value.split(',');

                for (var i = 0, len = val.length; i < len; i++) {
                    if (val[i] === '' || val[i] === false) {
                        continue;
                    }

                    switch (val[i].substr(0, 1)) {
                        case 'u':
                            this.addUser(val[i].substr(1));
                            break;

                        case 'g':
                            this.addGroup(val[i].substr(1));
                            break;
                    }
                }
            }

            return this.$Elm;
        },

        /**
         * event: on inject
         */
        $onInject: function () {
            var Elm = this.getElm();

            if (Elm.nodeName === 'INPUT') {
                this.$Input = Elm;
            }

            this.create();
        },

        /**
         * Return the value, the UG-String
         * @returns {String}
         */
        getValue: function () {
            return this.$Input.value;
        },

        /**
         * fire the search
         *
         * @method controls/usersAndGroups/Input#fireSearch
         */
        fireSearch: function () {
            if (this.$Search.value === '') {
                return this.close();
            }

            this.cancelSearch();

            this.$DropDown.set({
                html  : '<img src="' + URL_BIN_DIR + 'images/loader.gif" />',
                styles: {
                    display: '',
                    top    : this.$Search.getPosition().y + this.$Search.getSize().y,
                    left   : this.$Search.getPosition().x
                }
            });

            this.$search = this.search.delay(500, this);
        },

        /**
         * cancel the search timeout
         *
         * @method controls/usersAndGroups/Input#cancelSearch
         */
        cancelSearch: function () {
            if (this.$search) {
                clearTimeout(this.$search);
            }
        },

        /**
         * close the users search
         *
         * @method controls/usersAndGroups/Input#close
         */
        close: function () {
            this.cancelSearch();
            this.$DropDown.setStyle('display', 'none');
            this.$Search.value = '';
        },

        /**
         * trigger a users search and open a user dropdown for selection
         *
         * @method controls/usersAndGroups/Input#search
         */
        search: function () {
            Ajax.get('ajax_usersgroups_search', function (result, Request) {

                var data = result.users.combine(result.groups).slice(0, 10);

                var i, len, nam, type, Entry,
                    func_mousedown, func_mouseover,
                    value    = Request.getAttribute('value'),
                    Elm      = Request.getAttribute('Elm'),
                    DropDown = Elm.$DropDown;

                DropDown.set('html', '');

                if (!data || !data.length) {
                    new Element('div', {
                        html  : Locale.get('quiqqer/quiqqer', 'usersAndGroups.no.results'),
                        styles: {
                            'float': 'left',
                            'clear': 'both',
                            padding: 5,
                            margin : 5
                        }
                    }).inject(DropDown);

                    return;
                }

                // events
                func_mousedown = function (event) {
                    var Elm = event.target;

                    if (!Elm.hasClass('qui-users-and-groups-dropdown-entry')) {
                        Elm = Elm.getParent('.qui-users-and-groups-dropdown-entry');
                    }

                    this.add(
                        Elm.get('data-id'),
                        Elm.get('data-type')
                    );

                }.bind(Elm);

                func_mouseover = function () {
                    this.getParent().getElements(
                        '.qui-users-and-groups-dropdown-entry-hover'
                    ).removeClass(
                        'qui-users-and-groups-dropdown-entry-hover'
                    );

                    this.addClass('qui-users-and-groups-dropdown-entry-hover');
                };

                // create
                for (i = 0, len = data.length; i < len; i++) {
                    nam = data[i].username || data[i].name;

                    if (value) {
                        nam = nam.toString().replace(
                            new RegExp('(' + value + ')', 'gi'),
                            '<span class="mark">$1</span>'
                        );
                    }


                    type = 'group';

                    if (data[i].username) {
                        type = 'user';
                    }

                    Entry = new Element('div', {
                        html       : '<span>' + nam + ' (' + data[i].id + ')</span>',
                        'class'    : 'box-sizing qui-users-and-groups-dropdown-entry',
                        'data-id'  : data[i].id,
                        'data-name': data[i].username || data[i].name,
                        'data-type': type,
                        events     : {
                            mousedown : func_mousedown,
                            mouseenter: func_mouseover
                        }
                    }).inject(DropDown);

                    if (type == 'group') {
                        new Element('span', {
                            'class': 'fa fa-group',
                            styles : {
                                marginRight: 5
                            }
                        }).inject(Entry, 'top');
                    } else {
                        new Element('span', {
                            'class': 'fa fa-user',
                            styles : {
                                marginRight: 5
                            }
                        }).inject(Entry, 'top');
                    }

                }
            }, {
                Elm   : this,
                search: this.$Search.value,
                params: JSON.encode({
                    order         : 'ASC',
                    limit         : 5,
                    page          : 1,
                    search        : true,
                    searchSettings: {
                        userSearchString: this.$Search.value
                    }
                })
            });
        },

        /**
         * Add a entry / item to the list
         *
         * @method controls/usersAndGroups/Input#add
         * @param {Number} id  - id of the group or the user
         * @param {String} type - group or user
         *
         * @return {Object} this (controls/usersAndGroups/Input)
         */
        add: function (id, type) {
            if (type == 'user') {
                return this.addUser(id);
            }

            return this.addGroup(id);
        },

        /**
         * Add a group to the input
         *
         * @method controls/usersAndGroups/Input#addGroup
         * @param {Number|String} id - id of the group
         * @return {Object} this (controls/usersAndGroups/Input)
         */
        addGroup: function (id) {
            if (id === false || id === '') {
                return this;
            }

            new GroupsEntry(id, {
                events: {
                    onDestroy: this.$onGroupUserDestroy
                }
            }).inject(
                this.$List
            );

            this.$values.push('g' + id);

            this.fireEvent('addGroup', [this, id]);
            this.$refreshValues();

            return this;
        },

        /**
         * Add a user to the input
         *
         * @method controls/usersAndGroups/Input#addUser
         * @param {Number|String} id - id of the user
         * @return {Object} this (controls/usersAndGroups/Input)
         */
        addUser: function (id) {
            if (id === false || id === '') {
                return this;
            }

            new UsersEntry(id, {
                events: {
                    onDestroy: this.$onGroupUserDestroy,
                    onError  : function (UserEntry, uid) {
                        this.$values = this.$values.erase('u' + uid);
                        this.$refreshValues();
                    }.bind(this)
                }
            }).inject(this.$List);

            this.$values.push('u' + id);

            this.fireEvent('addUser', [this, id]);
            this.$refreshValues();

            return this;
        },

        /**
         * Add a object to the list
         * eq: over dragdrop
         *
         * @return {Object} this (controls/usersAndGroups/Input)
         */
        appendChild: function () {
            return this;
        },

        /**
         * keyup - users dropdown selection one step up
         *
         * @method controls/usersAndGroups/Input#up
         * @return {Object} this (controls/usersAndGroups/Input)
         */
        up: function () {
            if (!this.$DropDown) {
                return this;
            }

            var Active = this.$DropDown.getElement(
                '.qui-users-and-groups-dropdown-entry-hover'
            );

            // Last Element
            if (!Active) {
                this.$DropDown.getLast().addClass(
                    'qui-users-and-groups-dropdown-entry-hover'
                );

                return this;
            }

            Active.removeClass(
                'qui-users-and-groups-dropdown-entry-hover'
            );

            if (!Active.getPrevious()) {
                this.up();
                return this;
            }

            Active.getPrevious().addClass(
                'qui-users-and-groups-dropdown-entry-hover'
            );
        },

        /**
         * keydown - users dropdown selection one step down
         *
         * @method controls/usersAndGroups/Input#down
         * @return {Object} this (controls/usersAndGroups/Input)
         */
        down: function () {
            if (!this.$DropDown) {
                return this;
            }

            var Active = this.$DropDown.getElement(
                '.qui-users-and-groups-dropdown-entry-hover'
            );

            // First Element
            if (!Active) {
                this.$DropDown.getFirst().addClass(
                    'qui-users-and-groups-dropdown-entry-hover'
                );

                return this;
            }

            Active.removeClass(
                'qui-users-and-groups-dropdown-entry-hover'
            );

            if (!Active.getNext()) {
                this.down();
                return this;
            }

            Active.getNext().addClass(
                'qui-users-and-groups-dropdown-entry-hover'
            );

            return this;
        },

        /**
         * select the selected user / group
         *
         * @method controls/usersAndGroups/Input#submit
         */
        submit: function () {
            if (!this.$DropDown) {
                return;
            }

            var Active = this.$DropDown.getElement(
                '.qui-users-and-groups-dropdown-entry-hover'
            );

            if (Active) {
                this.addUser(Active.get('data-id'));
            }

            this.$Input.value = '';
            this.search();
        },

        /**
         * Set the focus to the input field
         *
         * @method controls/usersAndGroups/Input#focus
         * @return {Object} this (controls/usersAndGroups/Input)
         */
        focus: function () {
            if (this.$Search) {
                this.$Search.focus();
            }

            return this;
        },

        /**
         * Write the ids to the real input field
         *
         * @method controls/usersAndGroups/Input#$refreshValues
         */
        $refreshValues: function () {
            this.$Input.value = this.$values.join(',');
            this.$Input.fireEvent('change', [{
                target: this.$Input
            }]);
        },

        /**
         * event : if a user or a groupd would be destroyed
         *
         * @method controls/usersAndGroups/Input#$onGroupUserDestroy
         * @param {Object} Item - controls/groups/Entry|controls/users/Entry
         */
        $onGroupUserDestroy: function (Item) {
            var id = false;

            switch (Item.getType()) {
                case 'controls/groups/Entry':
                    id = 'g' + Item.getGroup().getId();
                    break;

                case 'controls/users/Entry':
                    id = 'u' + Item.getUser().getId();
                    break;

                default:
                    return;
            }

            this.$values = this.$values.erase(id);
            this.$refreshValues();
        },

        /**
         * event : on input focus, if the real input field get the focus
         *
         * @param {DOMEvent} event
         */
        $onInputFocus: function (event) {
            if (typeof event !== 'undefined') {
                event.stop();
            }

            this.focus();
        }
    });
});
