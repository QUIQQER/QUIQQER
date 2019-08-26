/**
 * Permissions control - parent class
 *
 * @module controls/permissions/Permission
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onLoad
 * @event onLoadError
 * @event onCreate
 * @event onClose
 * @event onOpen
 */
define('controls/permissions/Permission', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'qui/utils/Object',
    'controls/permissions/Sitemap',
    'utils/Controls',
    'utils/permissions/Utils',
    'Locale',

    'css!controls/permissions/Permission.css'

], function (QUI, QUIControl, QUIButton, QUIConfirm, QUIObjectUtils, PermissionMap, ControlUtils, PermissionUtils, QUILocale) {
    "use strict";

    var lg = 'quiqqer/system';


    return new Class({

        Extends: QUIControl,
        Type   : 'controls/permissions/Permission',

        Binds: [
            '$onInject',
            '$onSitemapItemClick',
            '$clickPermissionDeletion',
            '$onFormElementChange',
            'save'
        ],

        initialize: function (Bind, options) {
            this.$Bind = null;

            this.parent(options);

            this.$Map              = null;
            this.$MapContainer     = null;
            this.$ContentContainer = null;
            this.$tableCache       = {};

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Create the DOMNode ELement
         *
         * @return {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class': 'controls-permissions-permission',
                html   : '<div class="controls-permissions-permission-buttons"></div>' +
                '<div class="controls-permissions-permission-map"></div>' +
                '<div class="controls-permissions-permission-content">' +
                '<div class="controls-permissions-permission-content-sheet"></div>' +
                '</div>'
            });

            this.$MapContainer     = this.$Elm.getElement('.controls-permissions-permission-map');
            this.$ContentContainer = this.$Elm.getElement('.controls-permissions-permission-content');
            this.$ContentSheet     = this.$Elm.getElement('.controls-permissions-permission-content-sheet');
            this.$Buttons          = this.$Elm.getElement('.controls-permissions-permission-buttons');

            this.$Buttons.setStyle('opacity', 0);

            this.$Status = new Element('div', {
                'class': 'controls-permissions-permission-buttons-status'
            }).inject(this.$Buttons);

            this.fireEvent('create');

            return this.$Elm;
        },

        /**
         * Saves the permissions
         *
         * @return {Promise}
         */
        save: function () {
            if (!this.$Bind) {
                return Promise.resolve();
            }

            return PermissionUtils.Permissions.savePermission(this.$Bind);
        },

        /**
         * Close the permission control
         *
         * @returns {Promise}
         */
        close: function () {
            var self = this;

            return new Promise(function (response) {
                var duration    = 250;
                var SelectSheet = self.$Elm.getElement('.controls-permissions-select');

                if (SelectSheet) {
                    duration = 10;
                }

                self.fireEvent('close');

                moofx(self.$Buttons).animate({
                    opacity: 1
                }, {
                    duration: duration,
                    callback: function () {
                        self.$Buttons.set('html', '');

                        moofx(self.$ContentContainer).style({
                            overflow: 'hidden'
                        }).animate({
                            opacity: 0
                        }, {
                            duration: duration,
                            equation: 'cubic-bezier(.42,.4,.46,1.29)',
                            callback: function () {
                                moofx(self.$MapContainer).animate({
                                    opacity: 0,
                                    left   : '-100%'
                                }, {
                                    duration: duration,
                                    equation: 'cubic-bezier(.42,.4,.46,1.29)',
                                    callback: function () {

                                        if (!SelectSheet) {
                                            response();
                                            return;
                                        }

                                        moofx(SelectSheet).animate({
                                            opacity: 0,
                                            left   : '-100%'
                                        }, {
                                            duration: 250,
                                            equation: 'ease-in-out',
                                            callback: function () {
                                                SelectSheet.destroy();
                                                response();
                                            }
                                        });
                                    }
                                });
                            }
                        });

                    }
                });

            });
        },

        /**
         * Opens the permission control
         *
         * @returns {Promise}
         */
        open: function () {
            var self = this;

            return new Promise(function (response, reject) {

                if (!self.$Bind) {
                    self.$openBindSelect().then(function () {
                        return self.open();

                    }.bind(self)).catch(function () {
                        reject();
                    });

                    return;
                }

                if (!self.$Map) {
                    self.$Map = new PermissionMap(self.$Bind, {
                        events: {
                            onItemClick: self.$onSitemapItemClick
                        }
                    }).inject(self.$MapContainer);
                } else {
                    self.$Map.refresh();
                }

                self.$MapContainer.setStyles({
                    opacity: 0,
                    width  : 0
                });

                moofx(self.$MapContainer).animate({
                    left   : 0,
                    opacity: 1,
                    width  : 240
                }, {
                    duration: 250,
                    equation: 'cubic-bezier(.42,.4,.46,1.29)',
                    callback: function () {
                        moofx(self.$ContentContainer).animate({
                            left   : 0,
                            opacity: 1
                        }, {
                            duration: 250,
                            equation: 'cubic-bezier(.42,.4,.46,1.29)',
                            callback: function () {
                                moofx(self.$Buttons).animate({
                                    opacity: 1
                                }, {
                                    duration: 250
                                });

                                self.fireEvent('open');

                                response();
                            }
                        });

                    }
                });

            });
        },

        /**
         * can be overwritten
         */
        $openBindSelect: function () {

        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self = this;

            self.open().then(function () {
                self.fireEvent('load');
            }).catch(function () {
                self.fireEvent('loadError');
            });
        },

        /**
         * event : on sitemap item click
         *
         * @param {Object} Item - qui/controls/sitemap/Item
         */
        $onSitemapItemClick: function (Item) {
            moofx(this.$ContentSheet).animate({
                left: '-100%'
            }, {
                duration: 250,
                equation: 'cubic-bezier(.42,.4,.46,1.29)',
                callback: function () {
                    if (Item.getAttribute('value') === '') {
                        return;
                    }

                    var Permissions = PermissionUtils.Permissions;

                    Promise.all([
                        Permissions.getPermissionsByObject(this.$Bind),
                        Permissions.getList()
                    ]).then(function (result) {
                        var permissions    = result[0];
                        var permissionData = result[1];

                        var i, len, perm, list, header, right, title, Elm;

                        var val = Item.getAttribute('value') + '.';

                        this.$tableCache = [];
                        this.$ContentSheet.set('html', '');

                        // sort permissions
                        var permHeaders  = [];
                        var _permHeaders = {};

                        for (var permission in permissions) {
                            if (!permissions.hasOwnProperty(permission)) {
                                continue;
                            }

                            if (!(permission in permissionData)) {
                                continue;
                            }

                            if (!permission.match(val)) {
                                continue;
                            }

                            header = permission.split('.');

                            header.pop(); // drop the last element
                            header = header.join('.');

                            if (header in _permHeaders) {
                                continue;
                            }

                            title = permissionData[permission].title.split(' ');

                            _permHeaders[header] = true;

                            permHeaders.push({
                                permission : header,
                                translation: QUILocale.get(title[0], 'permission.' + header + '._header')
                            });
                        }

                        var itemValue = Item.getAttribute('value');

                        permHeaders.sort(function (a, b) {
                            if (a.permission === itemValue) {
                                return -1;
                            }

                            if (b.permission === itemValue) {
                                return 1;
                            }

                            if (a.translation > b.translation) {
                                return 1;
                            }

                            if (a.translation < b.translation) {
                                return -1;
                            }

                            return 0;
                        });


                        // create permission tables
                        for (i = 0, len = permHeaders.length; i < len; i++) {
                            perm = permHeaders[i];

                            this.$tableCache[perm.permission] = new Element('table', {
                                'class': 'data-table',
                                html   : '<thead>' +
                                '<tr><th>' + perm.translation + '</th></tr>' +
                                '</thead>' +
                                '<tbody></tbody>'
                            }).inject(this.$ContentSheet);
                        }


                        // create permissions
                        for (right in permissions) {
                            if (!permissions.hasOwnProperty(right)) {
                                continue;
                            }

                            if (!(right in permissionData)) {
                                continue;
                            }

                            if (val === '.' && right.match(/\./)) {
                                continue;
                            }

                            if (val === '.' && !right.match(/\./)) {
                                this.$createPermissionRow(
                                    permissionData[right]
                                );
                            }

                            if (!right.match(val)) {
                                continue;
                            }

                            this.$createPermissionRow(
                                permissionData[right]
                            );
                        }

                        this.$ContentSheet.getElements('input').addEvent(
                            'change',
                            this.$onFormElementChange
                        );

                        // set values
                        // set form values
                        if (typeof permissions !== 'undefined' && permissions) {
                            list = this.$ContentSheet.getElements('input');

                            for (i = 0, len = list.length; i < len; i++) {

                                Elm = list[i];

                                if (!(Elm.name in permissions)) {
                                    continue;
                                }

                                if (Elm.type === 'checkbox') {
                                    if (permissions[Elm.name] == 1) {
                                        Elm.checked = true;
                                    }

                                    continue;
                                }

                                if (typeOf(permissions[Elm.name]) === 'boolean') {
                                    continue;
                                }

                                Elm.value = permissions[Elm.name];
                            }
                        }


                        // parse controls
                        if (this.$Bind && typeOf(this.$Bind) !== 'qui/classes/DOM') {
                            ControlUtils.parse(this.$ContentSheet);
                        } else {
                            // if no bind exist, we would only edit the permissions
                            this.$ContentSheet.getElements('input,textarea').setStyles({
                                display: 'none'
                            });
                        }


                        moofx(this.$ContentSheet).animate({
                            left: 0
                        }, {
                            duration: 250,
                            equation: 'cubic-bezier(.42,.4,.46,1.29)'
                        });

                    }.bind(this)).catch(function (Err) {
                        console.error(Err);
                    });

                }.bind(this)
            });
        },

        /**
         * Create the controls in the rows of the permission tables
         *
         * @param {String} right - right name
         */
        $createPermissionRow: function (right) {
            // table
            var tableRightId = right.name.split('.');

            tableRightId.pop(); // drop the last element
            tableRightId = tableRightId.join('.');

            if (!(tableRightId in this.$tableCache)) {
                var title  = right.title.split(' '),
                    header = QUILocale.get(title[0], 'permission.' + tableRightId + '._header');

                this.$tableCache[tableRightId] = new Element('table', {
                    'class': 'data-table',
                    html   : '<thead><tr><th>' + header + '</th></tr></thead>' +
                    '<tbody></tbody>'
                }).inject(this.$ContentSheet);
            }


            var Table = this.$tableCache[tableRightId];

            var Node, Row;

            Row = new Element('tr', {
                'class': Table.rows.length % 2 ? 'odd' : 'even',
                html   : '<td></td>'
            });

            Node = PermissionUtils.parse(right);

            // first we disable all nodes if the node have a specific area type
            if (!Node.getElements('input[data-area=""]')) {
                Node.addClass('disabled');
            }

            // than, we enable only for the binded area
            if (this.$Bind) {
                switch (this.$Bind.getType()) {
                    case 'classes/projects/project/Site':
                        Node.getElements('input[data-area="site"]')
                            .getParent()
                            .removeClass('disabled');
                        break;

                    case 'classes/projects/Project':
                        Node.getElements('input[data-area="project"]')
                            .getParent()
                            .removeClass('disabled');
                        break;
                }
            }

            // edit modus
            if (!this.$Bind || typeOf(this.$Bind) === 'qui/classes/DOM') {
                // only user rights can be deleted
                if (right.src === 'user') {
                    new QUIButton({
                        icon  : 'fa fa-remove',
                        title : Locale.get(lg, 'permissions.panel.btn.delete.right.alt', {
                            right: right.name
                        }),
                        alt   : Locale.get(lg, 'permissions.panel.btn.delete.right.title', {
                            right: right.name
                        }),
                        value : right.name,
                        events: {
                            onClick: this.$clickPermissionDeletion
                        }
                    }).inject(Node, 'top');
                }
            }

            Node.inject(Row.getElement('td'));
            Row.inject(Table.getElement('tbody'));
        },

        /**
         * event : delete permission
         */
        $clickPermissionDeletion: function (Button) {
            var self       = this,
                permission = Button.getAttribute('value');

            new QUIConfirm({
                maxWidth   : 450,
                maxHeight  : 300,
                title      : QUILocale.get('quiqqer/system', 'permissions.panel.window.delete.title'),
                text       : QUILocale.get('quiqqer/system', 'permissions.panel.window.delete.text', {
                    right: permission
                }),
                information: QUILocale.get('quiqqer/system', 'permissions.panel.window.delete.information', {
                    right: permission
                }),
                autoclose  : false,
                events     : {
                    onSubmit: function (Win) {

                        Win.Loader.show();

                        PermissionUtils.Permissions
                                       .deletePermission(permission)
                                       .then(function () {
                                           Win.close();

                                           self.close().then(function () {
                                               self.open();
                                           });
                                       });
                    }
                }
            }).open();
        },

        /**
         * event : form element change
         */
        $onFormElementChange: function (event) {
            var Target = event.target;

            if (Target.type === 'checkbox') {
                PermissionUtils.Permissions.setPermission(
                    this.$Bind,
                    Target.name,
                    Target.checked ? 1 : 0
                );
            } else {
                PermissionUtils.Permissions.setPermission(
                    this.$Bind,
                    Target.name,
                    Target.value
                );
            }
        }
    });
});
