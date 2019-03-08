/**
 * Children sort control
 *
 * @module controls/projects/project/site/SiteChildrenSort
 * @author www.pcsg.de (Henning Leutz)
 */
define('controls/projects/project/site/SiteChildrenSort', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/controls/loader/Loader',
    'controls/grid/Grid',
    'Ajax',
    'Locale',

    'css!controls/projects/project/site/SiteChildrenSort.css'

], function (QUI, QUIControl, QUIButton, QUILoader, Grid, Ajax, Locale) {
    "use strict";

    var lg = 'quiqqer/system';

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/projects/project/site/SiteChildrenSort',

        Binds: [
            '$onInject',
            '$onResize'
        ],

        options: {
            buttons: true
        },

        initialize: function (Site, options) {
            this.parent(options);

            this.$Site      = Site;
            this.$Container = null;
            this.$Select    = null;
            this.$GridTable = null;

            this.Loader = new QUILoader();

            this.addEvents({
                onInject: this.$onInject,
                onResize: this.$onResize
            });
        },

        /**
         * Create the DOMNode
         *
         * @return {Element}
         */
        create: function () {
            var self = this;

            this.$Elm = new Element('div', {
                'class': 'qui-project-children-sort box',
                html   : '' +
                    '<div class="qui-project-children-sort-buttons qui-panel-buttons"></div>' +
                    '<div class="qui-project-children-sort-body">' +
                    '   <div class="qui-project-children-sort-container"></div>' +
                    '</div>'
            });

            this.Loader.inject(this.$Elm);

            // buttons
            if (this.getAttribute('buttons')) {
                var Buttons = this.$Elm.getElement('.qui-project-children-sort-buttons');

                new QUIButton({
                    name     : 'sortSave',
                    textimage: 'fa fa-save',
                    text     : Locale.get(lg, 'projects.project.site.childrensort.save'),
                    events   : {
                        onClick: function (Btn) {
                            Btn.setAttribute('textimage', 'fa fa-spinner fa-spin');

                            self.save(function () {
                                Btn.setAttribute('textimage', 'fa fa-save');
                            });
                        }
                    }
                }).inject(Buttons);
            }

            // body
            this.$Container = this.$Elm.getElement(
                '.qui-project-children-sort-container'
            );

            this.$GridTable = new Grid(this.$Container, {
                columnModel: [{
                    header   : Locale.get(lg, 'id'),
                    dataIndex: 'id',
                    dataType : 'string',
                    width    : 50
                }, {
                    header   : Locale.get(lg, 'name'),
                    dataIndex: 'name',
                    dataType : 'string',
                    width    : 200
                }, {
                    header   : Locale.get(lg, 'title'),
                    dataIndex: 'title',
                    dataType : 'string',
                    width    : 200
                }, {
                    header   : Locale.get(lg, 'c_date'),
                    dataIndex: 'c_date',
                    dataType : 'string',
                    width    : 150
                }, {
                    header   : Locale.get(lg, 'e_date'),
                    dataIndex: 'e_date',
                    dataType : 'string',
                    width    : 150
                }, {
                    header   : Locale.get(lg, 'projects.project.site.childrensort.order_field'),
                    dataIndex: 'order_field',
                    dataType : 'string',
                    width    : 150
                }],
                buttons    : [{
                    name     : 'up',
                    textimage: 'fa fa-angle-up',
                    text     : Locale.get(lg, 'up'),
                    disabled : true,
                    events   : {
                        onClick: function () {
                            self.$GridTable.moveup();
                        }
                    }
                }, {
                    name     : 'down',
                    textimage: 'fa fa-angle-down',
                    text     : Locale.get(lg, 'down'),
                    disabled : true,
                    events   : {
                        onClick: function () {
                            self.$GridTable.movedown();
                        }
                    }
                }, {
                    type: 'separator'
                }],
                height     : 300,
                pagination : true,
                onrefresh  : function () {
                    self.displayChildren();
                }
            });

            // select
            new Element('div', {
                'class': 'qui-project-children-sort-field',
                html   : '' +
                    '<label>' +
                    '   <span>' + Locale.get(lg, 'projects.project.site.childrensort.order_field') + '</span>' +
                    '   <select name="order-type">' +
                    '       <option value="manuell">' + Locale.get(lg, 'manually') + '</option>' +
                    '       <option value="name ASC">' + Locale.get(lg, 'name_asc') + '</option>' +
                    '       <option value="name DESC">' + Locale.get(lg, 'name_desc') + '</option>' +
                    '       <option value="title ASC">' + Locale.get(lg, 'title_asc') + '</option>' +
                    '       <option value="title DESC">' + Locale.get(lg, 'title_desc') + '</option>' +
                    '       <option value="c_date ASC">' + Locale.get(lg, 'c_date_asc') + '</option>' +
                    '       <option value="c_date DESC">' + Locale.get(lg, 'c_date_desc') + '</option>' +
                    '       <option value="e_date ASC">' + Locale.get(lg, 'e_date_asc') + '</option>' +
                    '       <option value="e_date DESC">' + Locale.get(lg, 'e_date_desc') + '</option>' +
                    '       <option value="release_from ASC">' + Locale.get(lg, 'release_from_asc') + '</option>' +
                    '       <option value="release_from DESC">' + Locale.get(lg, 'release_from_desc') + '</option>' +
                    '   </select>' +
                    '</label>'
            }).inject(this.$GridTable.getElm().getElement('.tDiv'));

            this.$GridTable.addEvents({
                click: function () {
                    var sel = self.$GridTable.getSelectedIndices();

                    if (!sel.length) {
                        return;
                    }

                    if (self.$Select.value === 'manuell') {
                        self.enableUpDownButtons();
                    } else {
                        self.disableUpDownButtons();
                    }
                }
            });

            this.$Select       = this.$Elm.getElement('[name="order-type"]');
            this.$Select.value = this.$Site.getAttribute('order_type');

            this.$Select.addEvent('change', function () {
                self.$Site.setAttribute('order_type', this.value);
                self.disableUpDownButtons();
            });

            this.displayChildren();


            return this.$Elm;
        },

        /**
         * Display the children in the grid
         */
        displayChildren: function () {
            this.Loader.show();

            var self    = this,
                perPage = this.$GridTable.options.perPage,
                page    = this.$GridTable.options.page;

            var limit = ((page - 1) * perPage) + ',' + perPage;

            this.$Site.getChildren({
                limit: limit
            }).then(function (result) {
                var i, len, entry;
                var data     = [],
                    children = result.children;


                for (i = 0, len = children.length; i < len; i++) {
                    entry = children[i];

                    data.push({
                        id         : entry.id,
                        name       : entry.name,
                        title      : entry.title,
                        e_date     : entry.e_date,
                        c_date     : entry.c_date,
                        order_field: entry.order_field
                    });
                }

                self.$GridTable.setData({
                    data   : data,
                    total  : self.$Site.countChild(),
                    page   : page,
                    perPage: perPage
                });

                self.Loader.hide();
            });
        },

        /**
         * Enable the up and down buttons
         */
        enableUpDownButtons: function () {
            var buttons = this.$Container.getElements('button');

            for (var i = 0, len = buttons.length; i < len; i++) {
                var quiid  = buttons[i].get('data-quiid'),
                    Button = QUI.Controls.getById(quiid);

                if (!Button) {
                    continue;
                }

                if (Button.getAttribute('name') !== 'up' &&
                    Button.getAttribute('name') !== 'down') {
                    continue;
                }

                Button.enable();
            }
        },

        /**
         * Disable the up and down buttons
         */
        disableUpDownButtons: function () {
            var buttons = this.$Container.getElements('button');

            for (var i = 0, len = buttons.length; i < len; i++) {
                var quiid  = buttons[i].get('data-quiid'),
                    Button = QUI.Controls.getById(quiid);

                if (!Button) {
                    continue;
                }

                if (Button.getAttribute('name') !== 'up' &&
                    Button.getAttribute('name') !== 'down') {
                    continue;
                }

                Button.disable();
            }
        },

        /**
         * Save the actually sort of the children
         *
         * @param {Function} [callback] - (optional), callback function
         */
        save: function (callback) {
            var self = this;

            if (this.$Select.value !== 'manuell') {
                this.$Site.setAttribute('order', this.$Select.value);

                this.$Site.save(function () {
                    if (typeof callback !== 'undefined') {
                        callback();
                    }

                    self.$Site.fireEvent('sortSave', [self.$Site]);
                });

                return;
            }

            var Project = this.$Site.getProject(),
                ids     = [],
                perPage = this.$GridTable.options.perPage,
                page    = this.$GridTable.options.page,
                data    = this.$GridTable.getData();


            for (var i = 0, len = data.length; i < len; i++) {
                ids.push(data[i].id);
            }

            this.$Site.setAttribute('order', 'manuell');

            this.$Site.save(function () {
                Ajax.post('ajax_site_children_sort', function () {
                    if (typeof callback !== 'undefined') {
                        callback();
                    }

                    self.$Site.fireEvent('sortSave', [self.$Site]);

                }, {
                    project: Project.encode(),
                    ids    : JSON.encode(ids),
                    start  : (page - 1) * perPage
                });
            });
        },

        /**
         * event on inject
         */
        $onInject: function () {
            this.$onResize();
        },

        /**
         * event on resize
         */
        $onResize: function () {
            var Parent = this.getElm().getParent(),
                size   = Parent.getSize();

            this.$GridTable.setHeight(size.y - 100);
            this.$GridTable.resize();
        }
    });
});
