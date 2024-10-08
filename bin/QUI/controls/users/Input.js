/**
 * Makes an input field to a user selection field
 *
 * @module controls/users/Input
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onAdd [ {this}, {String} userid ]
 * @event onChange [ {this} ]
 *
 * @deprecated controls/users/Select
 */
define('controls/users/Input', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'controls/users/Entry',
    'Ajax',
    'Locale',

    'css!controls/users/Input.css'

], function (QUI, QUIControl, QUIButton, UserEntry, Ajax, Locale) {
    "use strict";

    /**
     * @class controls/users/Input
     *
     * @param {Object} options
     * @param {HTMLInputElement} [Input] - (optional), if no input given, one would be created
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIControl,
        Type   : 'controls/users/Input',

        Binds: [
            'close',
            'fireSearch',
            'update',
            '$onImport'
        ],

        options: {
            max   : false,
            name  : '',
            styles: false
        },

        initialize: function (options, Input) {
            this.parent(options);

            this.$search = false;

            this.$Input = null;
            this.$Elm = false;
            this.$Container = null;
            this.$search = false;
            this.$DropDown = null;
            this.$disabled = false;

            this.$Bind = Input || null;

            this.addEvents({
                onImport : this.$onImport,
                onDestroy: function () {
                    if (this.$DropDown) {
                        this.$DropDown.destroy();
                    }
                }.bind(this)
            });
        },

        /**
         * Return the DOMNode of the users search
         *
         * @return {HTMLElement|Element}
         */
        create: function () {
            this.$Elm = new Element('div.users-input');

            if (!this.$Bind) {
                this.$Bind = new Element('input', {
                    name: this.getAttribute('name')
                }).inject(this.$Elm);
            } else {
                this.$disabled = this.$Bind.disabled;

                this.$Elm.wraps(this.$Bind);
            }

            if (this.getAttribute('styles')) {
                this.$Elm.setStyles(this.getAttribute('styles'));
            }


            this.$Bind.set('type', 'hidden');
            this.$Bind.set('data-quiid', this.getId());

            this.$Input = new Element('input', {
                type  : 'text',
                name  : this.$Bind.get('name') + '-search',
                styles: {
                    'float'      : 'left',
                    'margin'     : '3px 0',
                    'paddingLeft': 20,
                    'background' : 'url(' + URL_BIN_DIR + '10x10/search.png) no-repeat 4px center',
                    width        : '100%',
                    cursor       : 'pointer',
                    display      : 'none'
                },
                events: {
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
            }).inject(this.$Bind, 'before');


            this.$DropDown = new Element('div.users-input-dropdown', {
                styles: {
                    display: 'none',
                    top    : this.$Input.getPosition().y + this.$Input.getSize().y,
                    left   : this.$Input.getPosition().x
                }
            }).inject(document.body);

            this.$Container = new Element('div', {
                'class': 'user-input-container'
            }).inject(this.$Input, 'after');

            // loading
            if (this.$Bind.value === '') {
                if (!this.isDisabled()) {
                    this.enable();
                }

                return this.$Elm;
            }

            var wasDisabled = this.isDisabled();

            this.$Bind.disabled = false;
            this.$disabled = false;


            var i, len;
            var values = this.$Bind.value.toString().split(',');

            for (i = 0, len = values.length; i < len; i++) {
                if (values[i] !== '') {
                    this.addUser(values[i]);
                }
            }

            if (wasDisabled) {
                this.$Bind.disabled = true;
                this.$disabled = true;

                // disable children
                var list = this.$getUserEntries();

                for (i = 0, len = list.length; i < len; i++) {
                    list[i].disable();
                }
            }

            return this.$Elm;
        },

        /**
         * event: on import
         */
        $onImport: function () {
            var Elm = this.getElm();

            if (Elm.nodeName === 'INPUT') {
                this.$Bind = Elm;
            }

            this.create();
        },

        /**
         * Return the current value
         *
         * @return {String}
         */
        getValue: function () {
            return this.$Bind.value;
        },

        /**
         * updates the users search field
         */
        update: function () {

            if (this.isDisabled()) {
                return this;
            }

            if (!this.$Container) {
                return;
            }

            // set value
            var i, len;

            var list = this.$Container.getElements('.users-entry'),
                ids  = [];


            // hide or display input field
            if (this.getAttribute('max') &&
                this.getAttribute('max') <= list.length) {

                // hide
                this.$Input.setStyle('position', 'relative');

                //var computedSize = this.$Input.getComputedSize();

                moofx(this.$Input).animate({
                    height : 0,
                    margin : 0,
                    opacity: 0,
                    padding: 0
                }, {
                    duration: 250,
                    callback: function () {
                        this.$Input.setStyles({
                            display: 'none'
                        });
                    }.bind(this)
                });

            } else if (this.$Input.getStyle('display') == 'none') {

                this.$Input.setStyles({
                    display : null,
                    height  : null,
                    margin  : null,
                    padding : null,
                    position: 'absolute'
                });

                this.$Input.setStyle('paddingLeft', 20);

                var computedSize = this.$Input.getComputedSize();

                this.getElm().setStyle('height', computedSize.height);

                // show
                this.$Input.setStyles({
                    display : null,
                    height  : 0,
                    position: 'absolute'
                });

                moofx(this.$Input).animate({
                    height       : computedSize.height,
                    opacity      : 1,
                    paddingBottom: computedSize['padding-bottom'],
                    paddingTop   : computedSize['padding-top']
                }, {
                    duration: 250,
                    callback: function () {
                        this.$Input.setStyle('height', null);
                        this.getElm().setStyle('height', null);
                    }.bind(this)
                });
            }


            if (!list.length) {
                this.$Bind.set('value', '');
                this.fireEvent('change', [this]);
                this.enable();
                return;
            }

            for (i = 0, len = list.length; i < len; i++) {
                ids.push(list[i].get('data-id'));
            }


            if (ids.length == 1) {
                this.$Bind.set('value', ids[0]);
                this.fireEvent('change', [this]);
                return;
            }

            this.$Bind.set(
                'value',
                ',' + ids.join(',') + ','
            );

            this.fireEvent('change', [this]);
        },

        /**
         * fire the search
         */
        fireSearch: function () {
            if (this.isDisabled()) {
                return;
            }

            this.cancelSearch();

            this.$DropDown.set({
                html  : '<img src="' + URL_BIN_DIR + 'images/loader.gif" />',
                styles: {
                    display: '',
                    top    : this.$Input.getPosition().y + this.$Input.getSize().y,
                    left   : this.$Input.getPosition().x
                }
            });

            this.$search = this.search.delay(500, this);
        },

        /**
         * cancel the search timeout
         */
        cancelSearch: function () {
            if (this.$search) {
                clearTimeout(this.$search);
            }
        },

        /**
         * close the users search
         */
        close: function () {
            if (this.isDisabled()) {
                return this;
            }

            this.cancelSearch();
            this.$DropDown.setStyle('display', 'none');
            this.$Input.value = '';
        },

        /**
         * Add a users to the field
         *
         * @param {Number} uid - User-ID
         */
        addUser: function (uid) {

            if (this.isDisabled()) {
                return this;
            }

            if (typeof uid === 'undefined') {
                return;
            }

            if (this.$Container.getElement('.users-entry[data-id="' + uid + '"]')) {
                return;
            }

            var entries = this.$Container.getElements('.users-entry');

            if (this.getAttribute('max') &&
                this.getAttribute('max') <= entries.length) {
                return;
            }

            var self = this;

            var User = new UserEntry(uid, {
                events: {
                    onDestroy: function () {
                        (function () { // delay, because enable event is too early
                            self.update();
                        }).delay(300);
                    }
                }
            }).inject(this.$Container);

            if (this.isDisabled()) {
                User.disable();
            }

            this.fireEvent('add', [
                this,
                uid
            ]);
            this.update();
        },

        /**
         * trigger a users search and open a user dropdown for selection
         */
        search: function () {
            if (this.isDisabled()) {
                return this;
            }

            Ajax.get('ajax_users_search', function (result, Request) {
                var i, len, nam, func_mousedown, func_mouseover;

                var data     = result.data,
                    value    = Request.getAttribute('value'),
                    Elm      = Request.getAttribute('Elm'),
                    DropDown = Elm.$DropDown;

                DropDown.set('html', '');

                if (!data.length) {
                    new Element('div', {
                        html  : Locale.get('quiqqer/core', 'users.input.no.results'),
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
                    this.addUser(
                        event.target.get('data-id')
                    );

                }.bind(Elm);

                func_mouseover = function () {
                    this.getParent().getElements('.hover').removeClass('hover');
                    this.addClass('hover');
                };

                // create
                for (i = 0, len = data.length; i < len; i++) {
                    nam = data[i].username.toString().replace(
                        new RegExp('(' + value + ')', 'gi'),
                        '<span class="mark">$1</span>'
                    );

                    new Element('div', {
                        html       : nam + ' (' + data[i].id + ')',
                        'class'    : 'box-sizing radius5',
                        'data-id'  : data[i].id,
                        'data-name': data[i].username,
                        styles     : {
                            'float': 'left',
                            'clear': 'both',
                            padding: 5,
                            cursor : 'pointer',
                            width  : '100%'
                        },
                        events     : {
                            mousedown: func_mousedown,
                            mouseover: func_mouseover
                        }
                    }).inject(DropDown);
                }
            }, {
                Elm   : this,
                value : this.$Input.value,
                params: JSON.encode({
                    order         : 'ASC',
                    limit         : 5,
                    page          : 1,
                    search        : true,
                    searchSettings: {
                        userSearchString: this.$Input.value
                    }
                })
            });
        },

        /**
         * keyup - users dropdown selection one step up
         *
         * @return {Object} this (controls/users/Input)
         */
        up: function () {
            if (this.isDisabled()) {
                return this;
            }

            if (!this.$DropDown) {
                return this;
            }

            var Active = this.$DropDown.getElement('.hover');

            // Last Element
            if (!Active) {
                this.$DropDown.getLast().addClass('hover');
                return this;
            }

            Active.removeClass('hover');

            if (!Active.getPrevious()) {
                this.up();
                return this;
            }

            Active.getPrevious().addClass('hover');
        },

        /**
         * keydown - users dropdown selection one step down
         *
         * @return {Object} this (controls/users/Input)
         */
        down: function () {
            if (this.isDisabled()) {
                return this;
            }

            if (!this.$DropDown) {
                return this;
            }

            var Active = this.$DropDown.getElement('.hover');

            // First Element
            if (!Active) {
                this.$DropDown.getFirst().addClass('hover');
                return this;
            }

            Active.removeClass('hover');

            if (!Active.getNext()) {
                this.down();
                return this;
            }

            Active.getNext().addClass('hover');

            return this;
        },

        /**
         * select the selected users
         */
        submit: function () {
            if (!this.$DropDown) {
                return;
            }

            var Active = this.$DropDown.getElement('.hover');

            if (Active) {
                this.addUser(Active.get('data-id'));
            }

            this.$Input.value = '';
            this.search();
        },

        /**
         * Set the focus to the input field
         *
         * @return {Object} this (controls/users/Input)
         */
        focus: function () {
            if (this.$Input) {
                this.$Input.focus();
            }

            return this;
        },

        /**
         * Disable the input field
         * no changes are possible
         */
        disable: function () {
            if (this.isDisabled()) {
                return;
            }


            this.$disabled = true;

            if (this.$Bind) {
                this.$Bind.disabled = true;
            }

            var self = this;

            moofx(this.$Input).animate({
                opacity: 0
            }, {
                callback: function () {
                    self.$Input.setStyle('display', 'none');
                }
            });

            // disable children
            var list = this.$getUserEntries();

            for (var i = 0, len = list.length; i < len; i++) {
                list[i].disable();
            }
        },

        /**
         * Enable the input field if it is disabled
         * changes are possible
         */
        enable: function () {
            this.$disabled = false;

            if (this.$Bind) {
                this.$Bind.disabled = false;
            }

            this.$Input.setStyle('display', null);


            // enable children
            var list = this.$getUserEntries();

            for (var i = 0, len = list.length; i < len; i++) {
                list[i].enable();
            }
        },

        /**
         * Is it disabled?
         * if disabled, no changes are possible
         */
        isDisabled: function () {
            if (this.$Bind) {
                return this.$Bind.disabled;
            }

            return this.$disabled;
        },

        /**
         * Return the UserEntry objects
         *
         * @return {Array}
         */
        $getUserEntries: function () {
            var list   = this.$Container.getElements('.users-entry'),
                result = [];

            for (var i = 0, len = list.length; i < len; i++) {
                result.push(
                    QUI.Controls.getById(
                        list[i].get('data-quiid')
                    )
                );
            }

            return result;
        }
    });
});
