/**
 * @module controls/packages/Package
 * @author www.pcsg.de (Henning Leutz)
 *
 * @requires qui/QUI
 * @requires qui/controls/Control
 * @requires qui/controls/buttons/Button
 * @requires Packages
 * @requires Mustache
 * @requires Ajax
 * @requires css!controls/packages/Server.css
 *
 * @event onLoad
 */
define('controls/packages/Server', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'qui/controls/windows/Confirm',
    'Packages',
    'Mustache',
    'Ajax',
    'Locale',

    'css!controls/packages/Server.css'

], function (QUI, QUIControl, QUIButton, QUIConfirm, Packages, Mustache, QUIAjax, QUILocale) {
    "use strict";

    var lg = 'quiqqer/quiqqer';

    return new Class({

        Extends: QUIControl,
        Type   : 'controls/packages/Installed',

        Binds: [
            '$onInject',
            'viewTile',
            'viewList',
            '$onToggleStatusClick',
            '$onServerClick',
            '$onDeleterClick',
            '$refreshFilter'
        ],

        initialize: function (options) {
            this.parent(options);

            this.$servers = [];
            this.$view    = options && options.view || 'tile';
            this.$filter  = '';
            this.$delay   = false;

            this.$List        = null;
            this.$Search      = null;
            this.$SearchInput = null;
            this.$Result      = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Return the panel buttons
         * @returns {Object}
         */
        getButtons: function () {
            return [{
                name     : 'addServer',
                textimage: 'fa fa-plus',
                text     : 'Neuen Server hinzufügen',
                events   : {
                    onClick: function () {
                        this.openAddServerDialog();
                    }.bind(this)
                }
            }];
        },

        /**
         * Create the domnode element
         *
         * @returns {HTMLDivElement}
         */
        create: function () {
            this.$Elm = new Element('div', {
                'class': 'qui-control-packages-server',
                html   : '<form class="qui-control-packages-server-search">' +
                         '  <fieldset>' +
                         '      <label>' +
                         '          <input type="search" name="search" placeholder="Filter..."/>' +
                         '      </label>' +
                         '      <input type="submit" value="Los"/>' +
                         '  </fieldset>' +
                         '</form>' +
                         '<div class="qui-control-packages-server-result"></div>'
            });

            this.$List = {
                viewTile: this.viewTile,
                viewList: this.viewList
            };

            this.$SearchInput = this.$Elm.getElement('[type="search"]');

            this.$SearchInput.addEvents({
                change : this.$refreshFilter,
                keyup  : this.$refreshFilter,
                mouseup: this.$refreshFilter,
                cancel : this.$refreshFilter,
                blur   : this.$refreshFilter
            });

            this.$Elm.getElement('form').addEvent('submit', function (event) {
                event.stop();
                this.$refreshFilter();
            }.bind(this));

            this.$Search = this.$Elm.getElement('.qui-control-packages-server-search');
            this.$Result = this.$Elm.getElement('.qui-control-packages-server-result');

            return this.$Elm;
        },

        /**
         * Refresh the display
         */
        refresh: function () {
            switch (this.$view) {
                case 'list':
                    this.viewList();
                    break;

                default:
                case 'tile':
                    this.viewTile();
                    break;
            }
        },

        /**
         * internal filter refreshing with delay
         */
        $refreshFilter: function () {
            this.$filter = this.$SearchInput.value;

            if (this.$delay) {
                clearTimeout(this.$delay);
            }

            this.$delay = (function () {
                this.refresh();
            }).delay(200, this);
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            this.$reload().then(function () {
                this.refresh();
                this.fireEvent('load', [this]);
            }.bind(this));
        },

        /**
         * Reload / refresh the package list
         *
         * @returns {Promise}
         */
        $reload: function () {
            return Packages.getServerList().then(function (result) {
                this.$servers = result;
            }.bind(this));
        },

        /**
         * Return the list
         *
         * @returns {Object} PackageList
         */
        getList: function () {
            return this.$List;
        },

        /**
         * Tile view
         */
        viewTile: function () {
            this.$view = 'tile';
            this.$Result.set('html', '');

            var i, len, server, Server, Buttons;

            var Active = new Element('span', {
                'class': 'fa fa-check button'
            });

            var Deactive = new Element('span', {
                'class': 'fa fa-remove button'
            });

            var Delete = new Element('span', {
                'class': 'fa fa-trash button'
            });

            for (i = 0, len = this.$servers.length; i < len; i++) {
                server = this.$servers[i];

                if (this.$viewable(server) === false) {
                    continue;
                }

                Server = new Element('div', {
                    'class': 'packages-server qui-control-packages-server-tile-entry',
                    title  : server.server,
                    'html' : '<div class="qui-control-packages-server-tile-entry-image">' +
                             Packages.getServerTypeIcon(server.type) +
                             '</div>' +
                             '<div class="qui-control-packages-server-tile-entry-text">' +
                             server.server +
                             '</div>' +
                             '<div class="qui-control-packages-server-tile-entry-buttons">' +
                             '' +
                             '</div>',
                    events : {
                        click: this.$onServerClick
                    }
                }).inject(this.$Result);

                if (server.server == 'packagist') {
                    Server.setStyle('cursor', 'default');
                }

                Buttons = Server.getElement(
                    '.qui-control-packages-server-tile-entry-buttons'
                );

                if (server.active) {
                    Active.clone()
                        .addEvent('click', this.$onToggleStatusClick)
                        .inject(Buttons);
                } else {
                    Deactive.clone()
                        .addEvent('click', this.$onToggleStatusClick)
                        .inject(Buttons);
                }

                if (server.server != 'packagist') {
                    Delete.clone()
                        .addEvent('click', this.$onDeleterClick)
                        .inject(Buttons);
                }
            }
        },

        /**
         * List view
         */
        viewList: function () {
            this.$view = 'list';
            this.$Result.set('html', '');

            var i, len, server, Server, Buttons;

            var Active = new Element('span', {
                'class': 'fa fa-check button'
            });

            var Deactive = new Element('span', {
                'class': 'fa fa-remove button'
            });

            var Delete = new Element('span', {
                'class': 'fa fa-trash button'
            });

            for (i = 0, len = this.$servers.length; i < len; i++) {
                server = this.$servers[i];

                if (this.$viewable(server) === false) {
                    continue;
                }

                Server = new Element('div', {
                    'class': 'packages-server qui-control-packages-server-list-entry',
                    title  : server.server,
                    'html' : '<div class="qui-control-packages-server-list-entry-image">' +
                             Packages.getServerTypeIcon(server.type) +
                             '</div>' +
                             '<div class="qui-control-packages-server-list-entry-text">' +
                             server.server +
                             '</div>' +
                             '<div class="qui-control-packages-server-list-entry-buttons">' +
                             '' +
                             '</div>',
                    events : {
                        click: this.$onServerClick
                    }
                }).inject(this.$Result);

                if (server.server == 'packagist') {
                    Server.setStyle('cursor', 'default');
                }

                Buttons = Server.getElement(
                    '.qui-control-packages-server-list-entry-buttons'
                );

                if (server.active) {
                    Active.clone()
                        .addEvent('click', this.$onToggleStatusClick)
                        .inject(Buttons);
                } else {
                    Deactive.clone()
                        .addEvent('click', this.$onToggleStatusClick)
                        .inject(Buttons);
                }

                if (server.server != 'packagist') {
                    Delete.clone()
                        .addEvent('click', this.$onDeleterClick)
                        .inject(Buttons);
                }
            }
        },

        /**
         * internal filter method, for rendering server
         * returns the view status of the server
         *
         * @param serverData
         * @returns {boolean}
         */
        $viewable: function (serverData) {
            if (this.$filter === '') {
                return true;
            }

            if (serverData.server.contains(this.$filter)) {
                return true;
            }

            return serverData.server.contains(this.$filter);
        },

        /**
         *
         * @param {Event} event
         */
        $onToggleStatusClick: function (event) {
            event.stop();

            var Target    = event.target;
            var newStatus = !Target.hasClass('fa-check');
            var server    = Target.getParent('.packages-server').get('title');

            Target.removeClass('fa-check');
            Target.removeClass('fa-remove');
            Target.addClass('fa-spinner');
            Target.addClass('fa-spin');

            Packages.setServerStatus(server, newStatus).then(function () {
                Target.removeClass('fa-spinner');
                Target.removeClass('fa-spin');

                switch (newStatus) {
                    case true:
                        Target.addClass('fa-check');
                        break;

                    case false:
                        Target.addClass('fa-remove');
                        break;
                }
            });
        },

        /**
         * event: on click at a server DOMNode
         *
         * @param {Event} event
         */
        $onServerClick: function (event) {
            var Target = event.target;

            if (!Target.hasClass('packages-server')) {
                Target = Target.getParent('.packages-server');
            }

            if (Target.get('title') == 'packagist') {
                return;
            }

            this.openEditServerDialog(Target.get('title'));
        },

        /**
         * event: on click at a server delete button
         *
         * @param {Event} event
         */
        $onDeleterClick: function (event) {
            event.stop();

            var Target = event.target;

            if (!Target.hasClass('packages-server')) {
                Target = Target.getParent('.packages-server');
            }

            this.openDeleteServerDialog(Target.get('title'));
        },

        /**
         * Opens the add server dialog
         */
        openAddServerDialog: function () {
            var self = this;

            require([
                'controls/packages/server/AddServerWindow'
            ], function (AddServerWindow) {
                new AddServerWindow({
                    events: {
                        onSubmit: function () {
                            self.$reload().then(self.refresh.bind(self));
                        }
                    }
                }).open();
            });
        },

        /**
         * Opens the add server dialog
         *
         * @param {String} server
         */
        openEditServerDialog: function (server) {
            var self = this;

            require([
                'controls/packages/server/EditServerWindow'
            ], function (EditServerWindow) {
                new EditServerWindow({
                    server: server,
                    events: {
                        onSubmit: function () {
                            self.$reload().then(self.refresh.bind(self));
                        }
                    }
                }).open();
            });
        },

        /**
         * Opens the delete server dialog
         *
         * @param {String} server
         */
        openDeleteServerDialog: function (server) {
            var self = this;

            require([
                'controls/packages/server/DeleteServerWindow'
            ], function (DeleteServerWindow) {
                new DeleteServerWindow({
                    server: server,
                    events: {
                        onSubmit: function () {
                            self.$reload().then(self.refresh.bind(self));
                        }
                    }
                }).open();
            });
        }
    });
});
