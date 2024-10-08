/**
 * Makes an input field to a group selection field
 *
 * @module controls/groups/Input
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onAdd [this, groupid]
 *
 * @deprecated use controls/groups/Select
 */
define('controls/groups/Input', [

    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'controls/groups/Entry',
    'controls/groups/sitemap/Window',
    'Ajax',
    'Locale',

    'css!controls/groups/Input.css'

], function (QUIControl, QUIButton, GroupEntry, GroupSitemapWindow, Ajax, Locale) {
    "use strict";

    /**
     * @class controls/groups/Input
     *
     * @param {Object} options
     * @param {HTMLInputElement} [Input]  - (optional), -> if no input given, one would be created
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIControl,
        Type   : 'controls/groups/Input',

        Binds: [
            'close',
            'fireSearch',
            'update',
            '$onImport'
        ],

        options: {
            max     : false,
            multiple: true,
            name    : '',
            styles  : false
        },

        initialize: function (options, Input) {
            this.parent(options);

            this.$search = false;

            this.$Input = null;
            this.$Elm = false;
            this.$Container = null;
            this.$search = false;
            this.$DropDown = null;

            this.$Parent = Input || null;

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
         * Return the DOMNode of the group search
         *
         * @method controls/groups/Input#create
         * @return {HTMLElement} DOM-Element
         */
        create: function () {
            var self = this;

            this.$Elm = new Element('div.group-input');

            if (!this.$Parent) {
                this.$Parent = new Element('input', {
                    name: this.getAttribute('name')
                }).inject(this.$Elm);
            } else {
                this.$Elm.wraps(this.$Parent);
            }

            if (this.getAttribute('styles')) {
                this.$Elm.setStyles(this.getAttribute('styles'));
            }


            this.$Parent.set('type', 'hidden');

            // sitemap button
            new QUIButton({
                name  : 'groupSitemapBtn',
                image : 'fa fa-plus',
                styles: {
                    marginTop: 4
                },
                events: {
                    onClick: function () {
                        new GroupSitemapWindow({
                            multiple: self.getAttribute('multiple'),
                            events  : {
                                onSubmit: function (Window, values) {
                                    for (var i = 0, len = values.length; i < len; i++) {
                                        self.addGroup(values[i]);
                                    }
                                }
                            }
                        }).open();
                    }
                }
            }).inject(this.$Parent, 'before');


            this.$Input = new Element('input', {
                type  : 'text',
                name  : this.$Parent.get('name') + '-search',
                styles: {
                    'float'      : 'left',
                    'margin'     : '3px 0',
                    'paddingLeft': 20,
                    'background' : 'url(' + URL_BIN_DIR + '10x10/search.png) no-repeat 4px center',
                    width        : 165,
                    cursor       : 'pointer'
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
            }).inject(this.$Parent, 'before');


            this.$DropDown = new Element('div.group-input-dropdown', {
                styles: {
                    display: 'none',
                    top    : this.$Input.getPosition().y + this.$Input.getSize().y,
                    left   : this.$Input.getPosition().x
                }
            }).inject(document.body);

            this.$Container = new Element('div', {
                styles: {
                    'float': 'left',
                    margin : '0 0 0 10px',
                    width  : '100%'
                }
            }).inject(this.$Input, 'after');

            // loading
            if (this.$Parent.value === '') {
                return this.$Elm;
            }

            var i, len, val;
            var values = this.$Parent.value.toString().split(',');

            for (i = 0, len = values.length; i < len; i++) {
                val = values[i];

                if (val) {
                    this.addGroup(val);
                }
            }

            return this.$Elm;
        },

        /**
         * event: on import
         */
        $onImport: function () {
            this.$Parent = this.getElm();

            this.create();
        },

        /**
         * updates the group search field
         *
         * @method controls/groups/Input#update
         * @return {self} this
         */
        update: function () {
            if (!this.$Container) {
                return this;
            }

            // set value
            var i, len;

            var list = this.$Container.getElements('.group-entry'),
                ids  = [];

            for (i = 0, len = list.length; i < len; i++) {
                ids.push(list[i].get('data-id'));
            }

            if (ids.length) {
                this.$Parent.set('value', ',' + ids.join(',') + ',');
            } else {
                this.$Parent.set('value', '');
            }

            return this;
        },

        /**
         * fire the search
         *
         * @method controls/groups/Input#fireSearch
         * @return {Object} this (controls/groups/Input)
         */
        fireSearch: function () {
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

            return this;
        },

        /**
         * cancel the search timeout
         *
         * @method controls/groups/Input#cancelSearch
         * @return {Object} this (controls/groups/Input)
         */
        cancelSearch: function () {
            if (this.$search) {
                clearTimeout(this.$search);
            }

            return this;
        },

        /**
         * close the group search
         *
         * @method controls/groups/Input#close
         * @return {Object} this (controls/groups/Input)
         */
        close: function () {
            this.cancelSearch();
            this.$DropDown.setStyle('display', 'none');
            this.$Input.value = '';

            return this;
        },

        /**
         * Add a group to the field
         *
         * @method controls/groups/Input#addGroup
         * @param {Number|String} gid - Group-ID
         * @return {Object} this (controls/groups/Input)
         */
        addGroup: function (gid) {
            if (!gid || gid === '') {
                return this;
            }

            if (this.$Container.getElement('.group-entry[data-id="' + gid + '"]')) {
                return this;
            }

            var entries = this.$Container.getElements('.group-entry');

            if (this.getAttribute('max') &&
                this.getAttribute('max') <= entries.length) {
                return this;
            }

            new GroupEntry(gid, {
                events: {
                    onDestroy: function () {
                        // because the node still exists, trigger after 100 miliseconds
                        this.update.delay(100);
                    }.bind(this)
                }
            }).inject(this.$Container);

            this.fireEvent('add', [
                this,
                gid
            ]);
            this.update();

            return this;
        },

        /**
         * trigger a group search and open a group dropdown for selection
         *
         * @method controls/groups/Input#search
         */
        search: function () {
            Ajax.get('ajax_groups_search', function (result, Ajax) {
                var i, len, nam, func_mousedown, func_mouseover;

                var data     = result,
                    value    = Ajax.getAttribute('value'),
                    Elm      = Ajax.getAttribute('Elm'),
                    DropDown = Elm.$DropDown;

                DropDown.set('html', '');

                if (!data || !data.length) {
                    new Element('div', {
                        html  : Locale.get('quiqqer/core', 'groups.input.no.results'),
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
                    this.addGroup(
                        event.target.get('data-id')
                    );

                }.bind(Elm);

                func_mouseover = function () {
                    this.getParent().getElements('.hover').removeClass('hover');
                    this.addClass('hover');
                };

                // create
                for (i = 0, len = data.length; i < len; i++) {
                    nam = data[i].name.toString().replace(
                        new RegExp('(' + value + ')', 'gi'),
                        '<span class="mark">$1</span>'
                    );

                    new Element('div', {
                        html       : nam + ' (' + data[i].id + ')',
                        'class'    : 'box-sizing radius5',
                        'data-id'  : data[i].id,
                        'data-name': data[i].name,
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
                    field : 'name',
                    order : 'ASC',
                    limit : 5,
                    page  : 1,
                    search: this.$Input.value
                })
            });
        },

        /**
         * keyup - group dropdown selection one step up
         *
         * @method controls/groups/Input#up
         * @return {Object} this (controls/groups/Input)
         */
        up: function () {
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
         * keydown - group dropdown selection one step down
         *
         * @method controls/groups/Input#down
         * @return {Object} this (controls/groups/Input)
         */
        down: function () {
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
         * select the selected group
         *
         * @method controls/groups/Input#submit
         * @return {Object} this (controls/groups/Input)
         */
        submit: function () {
            if (!this.$DropDown) {
                return this;
            }

            var Active = this.$DropDown.getElement('.hover');

            if (Active) {
                this.addGroup(Active.get('data-id'));
            }

            this.$Input.value = '';
            this.search();

            return this;
        },

        /**
         * Set the focus to the input field
         *
         * @method controls/groups/Input#focus
         * @return {Object} this (controls/groups/Input)
         */
        focus: function () {
            if (this.$Input) {
                this.$Input.focus();
            }

            return this;
        }
    });
});
