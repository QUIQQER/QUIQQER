/**
 * @module Content Security Policy
 *
 * Config / Settings Control for CSP Settings
 * Content Security Policy
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require controls/grid/Grid
 * @require Locale
 */
define('controls/system/settings/CSPSettings', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/windows/Confirm',
    'controls/grid/Grid',
    'Locale',
    'Ajax',
    'Mustache',

    'text!controls/system/settings/CSPSettings.Directive.html'

], function (QUI, QUIControl, QUIConfirm, Grid, QUILocale, QUIAjax, Mustache, templateDirective) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/system/settings/CSPSettings',

        Binds: [
            'refresh',
            'openAddDialoge',
            'openEditDialoge',
            'openDeleteDialoge',
            '$onInject',
            '$onImport'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$Elm   = null;
            this.$Input = null;
            this.$Grid  = null;

            this.$cspDirective = {
                'base'   : 'base-uri',
                'child'  : 'child-src',
                'connect': 'connect-src',
                'default': 'default-src',
                'font'   : 'font-src',
                'form'   : 'form-action',
                'image'  : 'img-src',
                'img'    : 'img-src',
                'script' : 'script-src',
                'style'  : 'style-src',
                'object' : 'object-src',
                'report' : 'report-uri'
            };

            this.addEvents({
                onImport: this.$onImport,
                onInject: this.$onInject
            });
        },

        /**
         * Create the domnode element
         *
         * @return {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('div.quiqqer-cspsettings');

            return this.$Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var Container = new Element('div', {
                styles: {
                    overflow: 'hidden',
                    position: 'relative',
                    width   : '100%'
                }
            }).inject(this.getElm());

            var GridCon = new Element('div').inject(Container);

            var self  = this,
                width = Container.getSize().x;

            Container.setStyle('width', width);

            this.$Grid = new Grid(GridCon, {
                buttons: [{
                    name     : 'add',
                    text     : QUILocale.get(lg, 'add'),
                    textimage: 'fa fa-plus',
                    events   : {
                        onClick: this.openAddDialoge
                    }
                }, {
                    type: 'seperator'
                }, {
                    name     : 'edit',
                    text     : QUILocale.get(lg, 'edit'),
                    textimage: 'fa fa-edit',
                    disabled : true,
                    events   : {
                        onClick: this.openEditDialoge
                    }
                }, {
                    name     : 'remove',
                    text     : QUILocale.get(lg, 'remove'),
                    textimage: 'fa fa-trash',
                    disabled : true,
                    events   : {
                        onClick: this.openDeleteDialoge
                    }
                }],

                columnModel: [{
                    header   : QUILocale.get(lg, 'quiqqer.settings.security_headers.csp.value'),
                    dataIndex: 'value',
                    width    : 200
                }, {
                    header   : QUILocale.get(lg, 'quiqqer.settings.security_headers.csp.directive'),
                    dataIndex: 'directive',
                    width    : 200
                }],

                multipleSelection: true,
                height           : 300
            });

            this.$Grid.setWidth(width);

            this.$Grid.addEvents({
                onClick: function () {
                    var buttons  = self.$Grid.getButtons(),
                        selected = self.$Grid.getSelectedIndices();

                    var Edit = buttons.filter(function (Btn) {
                        return Btn.getAttribute('name') === 'edit';
                    })[0];

                    var Remove = buttons.filter(function (Btn) {
                        return Btn.getAttribute('name') === 'remove';
                    })[0];

                    if (selected.length === 1) {
                        Edit.enable();
                        Remove.enable();
                        return;
                    }

                    Edit.disable();
                    Remove.enable();
                },

                onDblClick: this.openEditDialoge
            });

            this.refresh();
        },

        /**
         * event : on import
         */
        $onImport: function () {
            this.$Input = this.getElm();
            this.$Elm   = this.create();

            this.$Elm.addClass('field-container-field');
            this.$Input.removeClass('field-container-field');
            this.$Elm.wraps(this.$Input);

            this.$onInject();
        },

        /**
         * Refresh the data
         *
         * @return {Promise}
         */
        refresh: function () {
            var self = this;

            return new Promise(function (resolve) {
                QUIAjax.get('ajax_system_settings_getCSP', function (result) {
                    var i, values, directive;
                    var data = [];

                    var appendData = function (value) {
                        data.push({
                            value    : value.replace(/'/g, ''),
                            directive: directive
                        });
                    };

                    for (i in result) {
                        if (!result.hasOwnProperty(i)) {
                            continue;
                        }

                        values    = result[i];
                        directive = i;

                        if (directive in self.$cspDirective) {
                            directive = self.$cspDirective[directive];
                        }

                        values.split(' ').each(appendData);
                    }

                    self.$Grid.setData({
                        data: data
                    });

                    resolve();
                });
            });
        },

        /**
         * Save the current
         *
         * @returns {Promise}
         */
        save: function () {
            var self = this;

            return new Promise(function (resolve, reject) {
                var data     = {};
                var selected = self.$Grid.getData();

                selected.each(function (entry) {
                    if (typeof data[entry.directive] === 'undefined') {
                        data[entry.directive] = [];
                    }

                    data[entry.directive].push(entry.value);
                });

                for (var directive in data) {
                    data[directive] = data[directive].join(' ');
                }

                QUIAjax.post('ajax_system_settings_saveCSP', function () {
                    self.refresh().then(resolve);
                }, {
                    onError: reject,
                    data   : JSON.encode(data)
                });
            });
        },

        /**
         * Open the add directive dialog
         */
        openAddDialoge: function () {
            var self = this;

            new QUIConfirm({
                title    : QUILocale.get(lg, 'quiqqer.settings.security_headers.csp.add.title'),
                icon     : 'fa fa-plus',
                maxHeight: 400,
                maxWidth : 600,
                autoclose: false,
                events   : {
                    onOpen: function (Win) {
                        var Content = Win.getContent();

                        Content.set('html', Mustache.render(templateDirective, {
                            titleValue         : QUILocale.get(lg, 'quiqqer.settings.security_headers.csp.value'),
                            titleDirective     : QUILocale.get(lg, 'quiqqer.settings.security_headers.csp.directive'),
                            textVariableListing: QUILocale.get(lg, 'quiqqer.settings.security_headers.csp.valuePlaceholder'),
                        }));

                        Content.getElements('.predefined-values a').addEvent('click', function (event) {
                            event.stop();
                            Content.getElement('[name="value"]').value = this.get('text').trim();
                        });
                    },

                    onSubmit: function (Win) {
                        var Content   = Win.getContent(),
                            Value     = Content.getElement('[name="value"]'),
                            Directive = Content.getElement('[name="directive"]');

                        if (Value === '') {
                            return;
                        }

                        self.$Grid.addRow({
                            value    : Value.value,
                            directive: Directive.value
                        });

                        Win.close();
                        self.save();
                    }
                }
            }).open();
        },

        /**
         * Open the edit directive dialog
         */
        openEditDialoge: function () {
            var self     = this,
                row      = this.$Grid.getSelectedIndices()[0],
                selected = this.$Grid.getSelectedData()[0];

            new QUIConfirm({
                title    : QUILocale.get(lg, 'quiqqer.settings.security_headers.csp.edit.title'),
                icon     : 'fa fa-plus',
                maxHeight: 400,
                maxWidth : 600,
                autoclose: false,
                events   : {
                    onOpen: function (Win) {
                        var Content = Win.getContent();

                        Content.set('html', Mustache.render(templateDirective, {
                            titleValue         : QUILocale.get(lg, 'quiqqer.settings.security_headers.csp.value'),
                            titleDirective     : QUILocale.get(lg, 'quiqqer.settings.security_headers.csp.directive'),
                            textVariableListing: QUILocale.get(lg, 'quiqqer.settings.security_headers.csp.valuePlaceholder'),
                        }));

                        var Value     = Content.getElement('[name="value"]'),
                            Directive = Content.getElement('[name="directive"]');

                        Value.value     = selected.value;
                        Directive.value = selected.directive;

                        Content.getElements('.predefined-values a').addEvent('click', function (event) {
                            event.stop();
                            Content.getElement('[name="value"]').value = this.get('text').trim();
                        });
                    },

                    onSubmit: function (Win) {
                        var Content   = Win.getContent(),
                            Value     = Content.getElement('[name="value"]'),
                            Directive = Content.getElement('[name="directive"]');

                        if (Value === '') {
                            return;
                        }

                        self.$Grid.setDataByRow(row, {
                            value    : Value.value,
                            directive: Directive.value
                        });

                        Win.close();
                        self.save();
                    }
                }
            }).open();

        },

        /**
         * opens the delete dialog
         */
        openDeleteDialoge: function () {
            var selected = this.$Grid.getSelectedData().map(function (entry) {
                return '<li>' + entry.value + ' (' + entry.directive + ') </li>';
            });

            if (!selected.length) {
                return;
            }

            var self = this,
                list = '<ul>' + selected.join('') + '</ul>';

            new QUIConfirm({
                title    : QUILocale.get(lg, 'quiqqer.settings.security_headers.csp.delete.title'),
                icon     : 'fa fa-trash',
                maxHeight: 400,
                maxWidth : 600,

                events: {
                    onOpen: function (Win) {
                        Win.getContent().set(
                            'html',
                            QUILocale.get(lg, 'quiqqer.settings.security_headers.csp.delete.text', {
                                list: list
                            })
                        );
                    },

                    onSubmit: function (Win) {
                        Win.Loader.show();

                        self.$Grid.deleteRows(
                            self.$Grid.getSelectedIndices()
                        );

                        self.save();
                    }
                }
            }).open();
        }
    });
});