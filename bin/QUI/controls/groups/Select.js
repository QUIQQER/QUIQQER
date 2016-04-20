/**
 * Makes a group input field to a field selection field
 *
 * @module controls/groups/Select
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/controls/Control
 * @require qui/controls/buttons/Button
 * @require controls/groups/SelectItem
 * @require Ajax
 * @require Locale
 * @require css!controls/groups/Select.css
 *
 * @event onAddGroup [ this, id ]
 * @event onChange [ this ]
 */
define('controls/groups/Select', [

    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/utils/Elements',
    'controls/groups/SelectItem',
    'Locale',
    'Groups',

    'css!controls/groups/Select.css'

], function (QUIControl, QUIButton, QUIElementUtils, SelectItem, QUILocale, Groups) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    /**
     * @class controls/groups/Select
     *
     * @param {Object} options
     * @param {HTMLInputElement} [Input]  - (optional), if no input given, one would be created
     *
     * @memberof! <global>
     */
    return new Class({

        Extends: QUIControl,
        Type   : 'controls/groups/Select',

        Binds: [
            'close',
            'fireSearch',
            'update',

            '$onSelectDestroy',
            '$onInputFocus',
            '$onImport'
        ],

        options: {
            max     : false, // max entries
            multiple: true,  // select more than one entry?
            name    : '',    // string
            styles  : false, // object
            label   : false  // text string or a <label> DOMNode Element
        },

        initialize: function (options, Input) {
            this.parent(options);

            this.$Input    = Input || null;
            this.$Elm      = null;
            this.$List     = null;
            this.$Search   = null;
            this.$DropDown = null;

            this.$SearchButton = null;

            this.$search = false;
            this.$values = [];

            this.addEvents({
                onImport: this.$onImport
            });
        },

        /**
         * Return the DOMNode Element
         *
         * @method controls/groups/Select#create
         * @return {HTMLElement} The main DOM-Node Element
         */
        create: function () {
            if (this.$Elm) {
                return this.$Elm;
            }

            var self = this;

            this.$Elm = new Element('div', {
                'class'     : 'quiqer-group-select',
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
                'class': 'quiqer-group-select-list'
            }).inject(this.$Elm);

            this.$Search = new Element('input', {
                'class'    : 'quiqer-group-select-search',
                placeholder: QUILocale.get(lg, 'control.groups.select.search.field.placeholder'),
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

            this.$SearchButton = new QUIButton({
                icon  : 'fa fa-search',
                styles: {
                    width: 50
                },
                events: {
                    onClick: function (Btn) {

                        Btn.setAttribute('icon', 'fa fa-spinner fa-spin');

                        require([
                            'controls/groups/sitemap/Window'
                        ], function (Window) {
                            new Window({
                                autoclose: true,
                                multiple : self.getAttribute('multiple'),
                                events   : {
                                    onSubmit: function (Win, groupIds) {
                                        self.addGroups(groupIds);
                                    }
                                }
                            }).open();

                            Btn.setAttribute('icon', 'fa fa-search');
                        });
                    }
                }
            }).inject(this.$Elm);

            this.$DropDown = new Element('div', {
                'class': 'quiqer-group-select-dropdown',
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
            if (this.$Input.value || this.$Input.value !== '') {
                this.addProduct(this.$Input.value);
            }

            return this.$Elm;
        },

        /**
         * event: on inject
         */
        $onImport: function () {
            var Elm = this.getElm();

            if (Elm.nodeName === 'INPUT') {
                this.$Input = Elm;
            }

            this.$Elm = null;
            this.create();
        },

        /**
         * fire the search
         *
         * @method controls/groups/Select#fireSearch
         */
        fireSearch: function () {
            if (this.$Search.value === '') {
                return this.close();
            }

            this.cancelSearch();

            this.$DropDown.set({
                html  : '<span class="fa fa-spinner fa-spin"></span>',
                styles: {
                    display: '',
                    top    : this.$Search.getPosition().y + this.$Search.getSize().y,
                    left   : this.$Search.getPosition().x,
                    zIndex : QUIElementUtils.getComputedZIndex(this.$Input)
                }
            });

            this.$search = this.search.delay(500, this);
        },

        /**
         * cancel the search timeout
         *
         * @method controls/groups/Select#cancelSearch
         */
        cancelSearch: function () {
            if (this.$search) {
                clearTimeout(this.$search);
            }
        },

        /**
         * close the users search
         *
         * @method controls/groups/Select#close
         */
        close: function () {
            this.cancelSearch();
            this.$DropDown.setStyle('display', 'none');
            this.$Search.value = '';
        },

        /**
         * Return the value
         *
         * @returns {String}
         */
        getValue: function () {
            return this.$Input.value;
        },

        /**
         * trigger a users search and open a field dropdown for selection
         *
         * @method controls/groups/Select#search
         */
        search: function () {
            var self  = this,
                value = this.$Search.value;

            Groups.search({
                order: 'ASC',
                limit: 5
            }, {
                id  : value,
                name: value
            }).then(function (result) {

                var i, id, len, nam, entry, Entry,
                    func_mousedown, func_mouseover,

                    DropDown = self.$DropDown;


                DropDown.set('html', '');

                if (!result || !result.length) {
                    new Element('div', {
                        html  : QUILocale.get(lg, 'control.select.no.results'),
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

                    if (!Elm.hasClass('quiqer-group-select-dropdown-entry')) {
                        Elm = Elm.getParent('.quiqer-group-select-dropdown-entry');
                    }

                    self.addGroup(Elm.get('data-id'));
                };

                func_mouseover = function () {
                    this.getParent().getElements(
                        '.quiqer-group-select-dropdown-entry-hover'
                    ).removeClass(
                        'quiqer-group-select-dropdown-entry-hover'
                    );

                    this.addClass('quiqer-group-select-dropdown-entry-hover');
                };

                // create
                for (i = 0, len = result.length; i < len; i++) {
                    entry = result[i];

                    Entry = new Element('div', {
                        html     : '<span class="fa fa-group"></span> ' +
                                   '<span>' + entry.name + ' (' + entry.id + ')</span>',
                        'class'  : 'box-sizing quiqer-group-select-dropdown-entry',
                        'data-id': entry.id,
                        events   : {
                            mousedown : func_mousedown,
                            mouseenter: func_mouseover
                        }
                    }).inject(DropDown);
                }
            });
        },

        /**
         * Add a user to the input
         *
         * @method controls/groups/Select#addUser
         * @param {Number|String} id - id of the user
         * @return {Object} this (controls/groups/Select)
         */
        addGroup: function (id) {
            if (id === '' || !id) {
                return this;
            }

            new SelectItem({
                id    : id,
                events: {
                    onDestroy: this.$onSelectDestroy
                }
            }).inject(this.$List);

            this.$values.push(id);

            this.fireEvent('addGroup', [this, id]);
            this.$refreshValues();

            return this;
        },

        /**
         * same as addField, only a array can be passed
         *
         * @param {Array} ids
         * @return {Object} this (controls/groups/Select)
         */
        addGroups: function (ids) {

            if (typeOf(ids) !== 'array') {
                return this;
            }

            ids.each(function (id) {
                if (id === '' || !id) {
                    return;
                }

                new SelectItem({
                    id    : id,
                    events: {
                        onDestroy: this.$onSelectDestroy
                    }
                }).inject(this.$List);

                this.$values.push(id);
            }.bind(this));

            this.fireEvent('addGroups', [this, ids]);
            this.$refreshValues();

            return this;
        },

        /**
         * keyup - users dropdown selection one step up
         *
         * @method controls/groups/Select#up
         * @return {Object} this (controls/groups/Select)
         */
        up: function () {
            if (!this.$DropDown) {
                return this;
            }

            var Active = this.$DropDown.getElement(
                '.quiqer-group-select-dropdown-entry-hover'
            );

            // Last Element
            if (!Active) {
                this.$DropDown.getLast().addClass(
                    'quiqer-group-select-dropdown-entry-hover'
                );

                return this;
            }

            Active.removeClass(
                'quiqer-group-select-dropdown-entry-hover'
            );

            if (!Active.getPrevious()) {
                this.up();
                return this;
            }

            Active.getPrevious().addClass(
                'quiqer-group-select-dropdown-entry-hover'
            );
        },

        /**
         * keydown - users dropdown selection one step down
         *
         * @method controls/groups/Select#down
         * @return {Object} this (controls/groups/Select)
         */
        down: function () {
            if (!this.$DropDown) {
                return this;
            }

            var Active = this.$DropDown.getElement(
                '.quiqer-group-select-dropdown-entry-hover'
            );

            // First Element
            if (!Active) {
                this.$DropDown.getFirst().addClass(
                    'quiqer-group-select-dropdown-entry-hover'
                );

                return this;
            }

            Active.removeClass(
                'quiqer-group-select-dropdown-entry-hover'
            );

            if (!Active.getNext()) {
                this.down();
                return this;
            }

            Active.getNext().addClass(
                'quiqer-group-select-dropdown-entry-hover'
            );

            return this;
        },

        /**
         * select the selected user / group
         *
         * @method controls/groups/Select#submit
         */
        submit: function () {
            if (!this.$DropDown) {
                return;
            }

            var Active = this.$DropDown.getElement(
                '.quiqer-group-select-dropdown-entry-hover'
            );

            if (Active) {
                this.addField(Active.get('data-id'));
            }

            this.$Input.value = '';
            this.search();
        },

        /**
         * Set the focus to the input field
         *
         * @method controls/groups/Select#focus
         * @return {Object} this (controls/groups/Select)
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
         * @method controls/groups/Select#$refreshValues
         */
        $refreshValues: function () {
            this.$Input.value = this.$values.join(',');
            this.$Input.fireEvent('change', [{
                target: this.$Input
            }]);

            this.fireEvent('change', [this]);
        },

        /**
         * event : if a user or a groupd would be destroyed
         *
         * @method controls/groups/Select#$onSelectDestroy
         * @param {Object} Item - controls/groups/SelectItem
         */
        $onSelectDestroy: function (Item) {
            this.$values = this.$values.erase(Item.getAttribute('id'));
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
